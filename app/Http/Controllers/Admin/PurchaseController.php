<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Vendor;
use App\Models\VendorLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::with('vendor')->latest('purchase_date');

        if ($request->filled('vendor')) {
            $query->where('vendor_id', $request->vendor);
        }
        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }
        if ($request->filled('from')) {
            $query->whereDate('purchase_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('purchase_date', '<=', $request->to);
        }

        $purchases = $query->paginate(25)->withQueryString();
        $vendors   = Vendor::orderBy('name')->get(['id', 'name']);

        return view('admin.purchases.index', compact('purchases', 'vendors'));
    }

    public function create()
    {
        $vendors  = Vendor::orderBy('name')->get();
        $products = Product::orderBy('name')->get(['id', 'name', 'cost_price', 'sku']);
        return view('admin.purchases.create', compact('vendors', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'purchase_date'       => 'required|date',
            'vendor_id'           => 'required|exists:vendors,id',
            'reference'           => 'nullable|string|max:100',
            'payment_method'      => 'required|in:cash,credit,partial',
            'amount_paid'         => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string|max:1000',
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'required|exists:products,id',
            'items.*.quantity'    => 'required|integer|min:1',
            'items.*.unit_cost'   => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $items   = $request->items;
            $subtotal = collect($items)->sum(fn($i) => $i['quantity'] * $i['unit_cost']);
            $total    = $subtotal;

            $payMethod = $request->payment_method;
            $amountPaid = match ($payMethod) {
                'cash'    => $total,
                'credit'  => 0,
                'partial' => min((float) ($request->amount_paid ?? 0), $total),
            };
            $payStatus = match (true) {
                $amountPaid >= $total => 'paid',
                $amountPaid > 0       => 'partial',
                default               => 'unpaid',
            };

            $purchase = Purchase::create([
                'reference'      => $request->reference,
                'vendor_id'      => $request->vendor_id,
                'purchase_date'  => $request->purchase_date,
                'subtotal'       => $subtotal,
                'total'          => $total,
                'payment_method' => $payMethod,
                'amount_paid'    => $amountPaid,
                'payment_status' => $payStatus,
                'notes'          => $request->notes,
                'created_by'     => auth()->id(),
            ]);

            foreach ($items as $row) {
                $product = Product::find($row['product_id']);
                $lineTotal = $row['quantity'] * $row['unit_cost'];

                $purchase->items()->create([
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'quantity'     => $row['quantity'],
                    'unit_cost'    => $row['unit_cost'],
                    'line_total'   => $lineTotal,
                ]);

                // Record stock movement (capture before/after)
                $before = (int) $product->stock_quantity;
                $after  = $before + $row['quantity'];

                $product->increment('stock_quantity', $row['quantity']);
                $product->update(['cost_price' => $row['unit_cost']]);

                StockMovement::create([
                    'product_id'      => $product->id,
                    'type'            => 'purchase',
                    'quantity'        => $row['quantity'],
                    'before_quantity' => $before,
                    'after_quantity'  => $after,
                    'reference'       => $purchase->reference ?? 'PUR-' . $purchase->id,
                    'note'            => 'Purchase from ' . ($purchase->vendor?->name ?? 'unknown vendor'),
                    'user_id'         => auth()->id(),
                ]);
            }

            // Update vendor ledger if payment is credit or partial
            if ($request->vendor_id && $payStatus !== 'paid') {
                $vendor    = Vendor::find($request->vendor_id);
                $owed      = $total - $amountPaid;
                $newBal    = $vendor->balance + $owed;

                VendorLedger::create([
                    'vendor_id'    => $vendor->id,
                    'purchase_id'  => $purchase->id,
                    'type'         => 'credit',
                    'amount'       => $owed,
                    'balance_after' => $newBal,
                    'description'  => $payStatus === 'partial'
                        ? "Partial payment — Rs. {$amountPaid} paid, Rs. {$owed} on credit"
                        : "Purchase on credit",
                    'created_by'   => auth()->id(),
                ]);

                $vendor->update(['balance' => $newBal]);
            }
        });

        return redirect()->route('admin.purchases.index')->with('success', 'Purchase recorded and stock updated.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['vendor', 'items.product', 'createdBy']);
        return view('admin.purchases.show', compact('purchase'));
    }

    public function report(Request $request)
    {
        $query = Purchase::with('vendor')->latest('purchase_date');

        if ($request->filled('vendor')) {
            $query->where('vendor_id', $request->vendor);
        }
        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }
        if ($request->filled('from')) {
            $query->whereDate('purchase_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('purchase_date', '<=', $request->to);
        }

        $purchases = $query->get();
        $vendors   = Vendor::orderBy('name')->get(['id', 'name']);

        $summary = [
            'total_spent'  => $purchases->sum('total'),
            'total_paid'   => $purchases->sum('amount_paid'),
            'total_unpaid' => $purchases->sum(fn($p) => $p->total - $p->amount_paid),
            'count'        => $purchases->count(),
        ];

        return view('admin.reports.purchases', compact('purchases', 'vendors', 'summary'));
    }
}
