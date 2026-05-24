<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'brand'])->active();

        if ($request->filled('q')) {
            $s = $request->q;
            $query->where(fn($q) => $q->where('name', 'like', "%{$s}%")
                                      ->orWhere('barcode', 'like', "%{$s}%")
                                      ->orWhere('sku', 'like', "%{$s}%"));
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('stock_status')) {
            match ($request->stock_status) {
                'in_stock'    => $query->where('stock_quantity', '>', 0),
                'out_of_stock' => $query->where('stock_quantity', '<=', 0),
                'low_stock'   => $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold'),
                default       => null,
            };
        }

        $products   = $query->orderBy('stock_quantity')->paginate(25)->withQueryString();
        $categories = Category::active()->orderBy('name')->get();
        $totalValue = Product::active()->sum(DB::raw('stock_quantity * cost_price'));

        return view('admin.inventory.index', compact('products', 'categories', 'totalValue'));
    }

    public function adjustForm(Product $product)
    {
        $movements = $product->stockMovements()->with('user')->latest()->paginate(20);
        return view('admin.inventory.adjust', compact('product', 'movements'));
    }

    public function adjust(Request $request, Product $product)
    {
        $data = $request->validate([
            'type'     => 'required|in:purchase,adjustment,damage',
            'quantity' => 'required|integer|not_in:0',
            'note'     => 'nullable|string|max:255',
        ]);

        $before = $product->stock_quantity;
        $after  = $before + $data['quantity'];

        if ($after < 0) {
            return back()->withErrors(['quantity' => 'Stock cannot go below zero.']);
        }

        DB::transaction(function () use ($product, $data, $before, $after) {
            $updates = ['stock_quantity' => $after];

            // Auto-clear the low stock dismissal when restocked above threshold
            if ($after > $product->low_stock_threshold && $product->low_stock_dismissed) {
                $updates['low_stock_dismissed'] = false;
            }

            $product->update($updates);

            StockMovement::create([
                'product_id'       => $product->id,
                'type'             => $data['type'],
                'quantity'         => $data['quantity'],
                'before_quantity'  => $before,
                'after_quantity'   => $after,
                'note'             => $data['note'],
                'user_id'          => Auth::id(),
            ]);
        });

        return back()->with('success', "Stock updated. {$product->name}: {$before} → {$after}");
    }

    public function history(Product $product)
    {
        $movements = $product->stockMovements()->with('user')->latest()->paginate(30);
        return view('admin.inventory.history', compact('product', 'movements'));
    }

    public function barcodeScan(Request $request)
    {
        $product = Product::where('barcode', $request->code)
                          ->orWhere('sku', $request->code)
                          ->first();

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json([
            'id'             => $product->id,
            'name'           => $product->name,
            'price'          => $product->price,
            'stock_quantity' => $product->stock_quantity,
            'barcode'        => $product->barcode,
            'sku'            => $product->sku,
        ]);
    }

    public function assignBarcode(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'barcode'    => 'required|string|max:50|unique:products,barcode,' . $request->product_id,
        ]);

        Product::find($data['product_id'])->update(['barcode' => $data['barcode']]);

        return back()->with('success', 'Barcode assigned successfully.');
    }

    public function printLabels(Request $request)
    {
        $query = Product::active()->whereNotNull('barcode');

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $products   = $query->orderBy('name')->get();
        $categories = Category::active()->orderBy('name')->get();

        return view('admin.inventory.print-labels', compact('products', 'categories'));
    }

    public function lowStock()
    {
        $products = Product::active()
                            ->where('track_inventory', true)
                            ->where('low_stock_dismissed', false)
                            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                            ->with(['category', 'brand'])
                            ->orderBy('stock_quantity')
                            ->paginate(30);

        return view('admin.inventory.low-stock', compact('products'));
    }

    public function dismissLowStock(Product $product)
    {
        $product->update(['low_stock_dismissed' => true]);
        return back()->with('success', "{$product->name} dismissed from low stock alerts.");
    }
}
