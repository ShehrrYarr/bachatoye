<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\SerialAttributeDefinition;
use App\Models\SerialNumber;
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
            'serials'                           => 'required|array',
            'serials.*'                         => 'required|array',
            'serials.*.*.serial'                => 'nullable|string|max:100',
            'serials.*.*.cost_price'            => 'nullable|numeric|min:0',
            'serials.*.*.selling_price'         => 'nullable|numeric|min:0',
            'serials.*.*.attributes'            => 'nullable|array',
            'serials.*.*.attributes.*'          => 'nullable|string|max:200',
        ]);

        $purchase->load(['items.product']);

        $serializedItems = $purchase->items->filter(fn($i) => $i->product?->is_serialized);

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

        DB::transaction(function () use ($purchase, $toInsert) {
            // Remove existing in_stock serials for this purchase (replace strategy)
            SerialNumber::where('purchase_id', $purchase->id)
                ->where('status', 'in_stock')
                ->delete();

            foreach ($toInsert as $row) {
                SerialNumber::create([
                    'product_id'       => $row['product_id'],
                    'serial_number'    => $row['serial_number'],
                    'cost_price'       => $row['cost_price'],
                    'selling_price'    => $row['selling_price'],
                    'attributes'       => $row['attributes'],
                    'status'           => 'in_stock',
                    'purchase_id'      => $row['purchase_id'],
                    'purchase_item_id' => $row['item_id'],
                ]);
            }
        });

        $rPrefix = auth()->user()->hasRole('admin') ? 'admin' : 'salesman';
        $count   = count($toInsert);

        return redirect()->route("{$rPrefix}.purchases.show", $purchase)
            ->with('success', "{$count} serial number(s) registered successfully.");
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
