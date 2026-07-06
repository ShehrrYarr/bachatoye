<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\SerialAttributeDefinition;
use App\Models\SerialNumber;
use App\Models\Vendor;
use App\Models\VendorLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SerialController extends Controller
{
    /**
     * Show the serial registration form for a purchase.
     */
    public function showForPurchase(Purchase $purchase)
    {
        $purchase->load(['items.product', 'vendor']);

        // Only show items for serialized products
        $serializedItems = $purchase->items
            ->filter(fn($item) => $item->product?->is_serialized)
            ->values();

        if ($serializedItems->isEmpty()) {
            $rPrefix = auth()->user()->hasRole('admin') ? 'admin' : 'salesman';
            return redirect()->route("{$rPrefix}.purchases.show", $purchase)
                ->with('info', 'No serialized products in this purchase.');
        }

        // Already-registered serials for this purchase (for display)
        $registeredSerials = SerialNumber::where('purchase_id', $purchase->id)
            ->get()
            ->groupBy('purchase_item_id');

        $serialAttributeDefs = SerialAttributeDefinition::activeOrdered();

        return view('admin.purchases.serials', compact('purchase', 'serializedItems', 'registeredSerials', 'serialAttributeDefs'));
    }

    /**
     * Save serial numbers for a purchase.
     */
    public function storeForPurchase(Request $request, Purchase $purchase)
    {
        $request->validate([
            'serials'                           => 'nullable|array',
            'serials.*'                         => 'nullable|array',
            'serials.*.*.id'                    => 'nullable|integer',
            'serials.*.*.serial'                => 'nullable|string|max:100',
            'serials.*.*.cost_price'            => 'nullable|numeric|min:0',
            'serials.*.*.selling_price'         => 'nullable|numeric|min:0',
            'serials.*.*.attributes'            => 'nullable|array',
            'serials.*.*.attributes.*'          => 'nullable|string|max:200',
        ]);

        $purchase->load(['items.product']);

        $serializedItems = $purchase->items->filter(fn($i) => $i->product?->is_serialized);

        // Nothing was submitted (e.g. all units are sold/locked)
        if (empty($request->input('serials'))) {
            $rPrefix = auth()->user()->hasRole('admin') ? 'admin' : 'salesman';
            return redirect()->route("{$rPrefix}.purchases.show", $purchase)
                ->with('info', 'No serial numbers to save (all units are sold or were left blank).');
        }

        $errors     = [];
        $toInsert   = [];
        $allSerials = [];

        foreach ($serializedItems as $item) {
            $inputRows = $request->input("serials.{$item->id}", []);

            foreach ($inputRows as $pos => $row) {
                $sn = trim($row['serial'] ?? '');
                if ($sn === '') continue;

                // Duplicate within this submission
                if (in_array($sn, $allSerials)) {
                    $errors[] = "Duplicate serial number: {$sn}";
                    continue;
                }

                // Already exists in DB (different purchase)
                $existing = SerialNumber::where('serial_number', $sn)
                    ->where('purchase_id', '!=', $purchase->id)
                    ->first();
                if ($existing) {
                    $errors[] = "Serial {$sn} is already registered in another purchase.";
                    continue;
                }

                $attrs = $row['attributes'] ?? [];
                $attrs = array_filter($attrs, fn($v) => $v !== null && $v !== '');

                $allSerials[] = $sn;
                $toInsert[]   = [
                    'id'             => !empty($row['id']) ? (int) $row['id'] : null,
                    'item_id'        => $item->id,
                    'product_id'     => $item->product_id,
                    'purchase_id'    => $purchase->id,
                    'serial_number'  => $sn,
                    'cost_price'     => isset($row['cost_price']) && $row['cost_price'] !== '' ? $row['cost_price'] : null,
                    'selling_price'  => isset($row['selling_price']) && $row['selling_price'] !== '' ? $row['selling_price'] : null,
                    'attributes'     => !empty($attrs) ? $attrs : null,
                ];
            }
        }

        if (!empty($errors)) {
            return back()->withErrors(['serials' => $errors])->withInput();
        }

        $updatedTotal = DB::transaction(function () use ($purchase, $toInsert, $serializedItems) {
            // Remove in_stock serials the user blanked out of the form.
            // Existing rows are updated in place so image / subcategory_id survive.
            $keptIds = collect($toInsert)->pluck('id')->filter()->all();
            SerialNumber::where('purchase_id', $purchase->id)
                ->where('status', 'in_stock')
                ->whereNotIn('id', $keptIds)
                ->delete();

            foreach ($toInsert as $row) {
                $data = [
                    'serial_number'    => $row['serial_number'],
                    'cost_price'       => $row['cost_price'],
                    'selling_price'    => $row['selling_price'],
                    'attributes'       => $row['attributes'],
                    'purchase_item_id' => $row['item_id'],
                ];

                $existing = $row['id']
                    ? SerialNumber::where('id', $row['id'])
                        ->where('purchase_id', $purchase->id)
                        ->where('status', 'in_stock')
                        ->first()
                    : null;

                if ($existing) {
                    $existing->update($data);
                } else {
                    SerialNumber::create($data + [
                        'product_id'  => $row['product_id'],
                        'status'      => 'in_stock',
                        'purchase_id' => $row['purchase_id'],
                    ]);
                }
            }

            // ── Propagate cost changes to item / purchase / payment / khata ──
            foreach ($serializedItems as $item) {
                // Skip items whose serials carry no cost data (legacy purchases) so we
                // don't zero out a line total that was entered at purchase time.
                $hasCosts = SerialNumber::where('purchase_item_id', $item->id)
                    ->whereNotNull('cost_price')->exists();
                if (!$hasCosts) continue;

                // Sold serials still count toward what this purchase cost
                $lineTotal = (float) SerialNumber::where('purchase_item_id', $item->id)->sum('cost_price');
                $unitCost  = $item->quantity > 0 ? round($lineTotal / $item->quantity, 2) : 0;
                $item->update(['unit_cost' => $unitCost, 'line_total' => $lineTotal]);
                $item->product?->update(['cost_price' => $unitCost]);
            }

            $newTotal = (float) $purchase->items()->sum('line_total');
            if (abs($newTotal - (float) $purchase->total) < 0.01) {
                return null; // money unchanged
            }

            // Auto-adjust the payment to the new total (cash/bank paid in full stay
            // paid in full; credit stays unpaid; partial keeps what was actually paid).
            $amountPaid = match ($purchase->payment_method) {
                'cash', 'bank_transfer' => $newTotal,
                'credit'                => 0,
                default                 => min((float) $purchase->amount_paid, $newTotal),
            };
            $payStatus = $amountPaid >= $newTotal ? 'paid' : ($amountPaid > 0 ? 'partial' : 'unpaid');

            $purchase->update([
                'subtotal'       => $newTotal,
                'total'          => $newTotal,
                'amount_paid'    => $amountPaid,
                'payment_status' => $payStatus,
            ]);
            // Cash & bank balances are computed from amount_paid — nothing more to write.

            // Correct the vendor khata entry for this purchase
            if ($purchase->vendor_id) {
                $owed  = max(0, $newTotal - $amountPaid);
                $entry = VendorLedger::where('purchase_id', $purchase->id)
                    ->where('type', 'credit')->first();

                if ($entry) {
                    $delta = $owed - (float) $entry->amount;
                    if (abs($delta) >= 0.01) {
                        $entry->update([
                            'amount'        => $owed,
                            'balance_after' => $entry->balance_after + $delta,
                        ]);
                        VendorLedger::where('vendor_id', $purchase->vendor_id)
                            ->where('id', '>', $entry->id)
                            ->increment('balance_after', $delta);
                        Vendor::where('id', $purchase->vendor_id)->increment('balance', $delta);
                    }
                } elseif ($owed > 0) {
                    $vendor = Vendor::find($purchase->vendor_id);
                    $newBal = $vendor->balance + $owed;
                    VendorLedger::create([
                        'vendor_id'     => $vendor->id,
                        'purchase_id'   => $purchase->id,
                        'type'          => 'credit',
                        'amount'        => $owed,
                        'balance_after' => $newBal,
                        'description'   => 'Purchase total adjusted — Rs. ' . number_format($owed) . ' on credit',
                        'created_by'    => auth()->id(),
                    ]);
                    $vendor->update(['balance' => $newBal]);
                }
            }

            return $newTotal;
        });

        $rPrefix = auth()->user()->hasRole('admin') ? 'admin' : 'salesman';
        $count   = count($toInsert);

        $message = "{$count} serial number(s) registered successfully.";
        if ($updatedTotal !== null) {
            $message .= ' Purchase total updated to Rs. ' . number_format($updatedTotal) . ' and payment records adjusted.';
        }

        return redirect()->route("{$rPrefix}.purchases.show", $purchase)
            ->with('success', $message);
    }

    /**
     * Serial number lookup page.
     */
    public function lookup(Request $request)
    {
        $query  = trim($request->get('q', ''));
        $serial = null;

        if ($query) {
            $serial = SerialNumber::where('serial_number', 'like', "%{$query}%")
                ->with([
                    'product',
                    'purchase.vendor',
                    'order.customer',
                    'order.servedBy',
                    'returnOrder',
                ])
                ->first();
        }

        return view('admin.serials.lookup', compact('query', 'serial'));
    }
}
