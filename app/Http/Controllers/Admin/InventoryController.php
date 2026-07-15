<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        // Sub shop login gets its own location-scoped stock view
        if (Auth::user()->isSubshop()) {
            return $this->subshopStock($request, false);
        }

        $groupBy = $request->input('group_by'); // 'category' | 'brand' | null
        $groupId = $request->input('group_id'); // integer | null

        // ── Stage 1: Picker ───────────────────────────────────────────────
        if (! $groupBy) {
            return view('admin.inventory.index', ['stage' => 'picker']);
        }

        $isCategory = $groupBy === 'category';
        $table      = $isCategory ? 'categories' : 'brands';
        $fk         = $isCategory ? 'category_id' : 'brand_id';

        // ── Stage 2: Group cards (categories or brands with counts + value) ─
        if (! $groupId) {
            $groups = DB::table($table)
                ->join('products', "products.{$fk}", '=', "{$table}.id")
                ->where('products.is_active', true)
                ->where("{$table}.is_active", true)
                ->select("{$table}.id", "{$table}.name")
                ->selectRaw('COUNT(products.id) as item_count')
                ->selectRaw('COALESCE(SUM(products.stock_quantity * products.cost_price), 0) as total_cost')
                ->selectRaw('COALESCE(SUM(products.stock_quantity), 0) as total_stock')
                ->groupBy("{$table}.id", "{$table}.name")
                ->orderBy("{$table}.name")
                ->get();

            $grandTotal = $groups->sum('total_cost');

            return view('admin.inventory.index', compact('groups', 'groupBy', 'grandTotal') + ['stage' => 'groups']);
        }

        // ── Stage 3: Products within a specific group ─────────────────────
        $group = DB::table($table)->find($groupId);
        abort_if(! $group, 404);

        $query = Product::with(['category', 'brand'])->active()->where($fk, $groupId);

        if ($request->filled('q')) {
            $s = $request->q;
            $query->where(fn($q) => $q->where('name', 'like', "%{$s}%")
                                      ->orWhere('barcode', 'like', "%{$s}%")
                                      ->orWhere('sku', 'like', "%{$s}%"));
        }

        if ($request->filled('stock_status')) {
            match ($request->stock_status) {
                'in_stock'     => $query->where('stock_quantity', '>', 0),
                'out_of_stock' => $query->where('stock_quantity', '<=', 0),
                'low_stock'    => $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold'),
                default        => null,
            };
        }

        $products   = $query->orderBy('stock_quantity')->paginate(25)->withQueryString();
        $totalValue = Product::active()->where($fk, $groupId)->sum(DB::raw('stock_quantity * cost_price'));
        $totalStock = Product::active()->where($fk, $groupId)->sum('stock_quantity');
        $totalItems = Product::active()->where($fk, $groupId)->count();

        return view('admin.inventory.index', compact(
            'products', 'groupBy', 'groupId', 'group', 'totalValue', 'totalStock', 'totalItems'
        ) + ['stage' => 'products']);
    }

    public function adjustForm(Product $product)
    {
        abort_if(
            \App\Models\Setting::get('stock_adjustment_enabled', '1') != '1',
            403,
            'Stock adjustment is currently disabled. Enable it in Admin → Settings → Inventory.'
        );

        $product->load('colors');
        $movements = $product->stockMovements()->with('user')->latest()->paginate(20);
        return view('admin.inventory.adjust', compact('product', 'movements'));
    }

    public function adjust(Request $request, Product $product)
    {
        abort_if(
            \App\Models\Setting::get('stock_adjustment_enabled', '1') != '1',
            403,
            'Stock adjustment is currently disabled. Enable it in Admin → Settings → Inventory.'
        );

        $product->load('colors');
        $hasColors = $product->colors->count() > 0;

        $data = $request->validate([
            'type'     => 'required|in:purchase,adjustment,damage',
            'quantity' => 'required|integer|not_in:0',
            'note'     => 'nullable|string|max:255',
            'color_id' => $hasColors ? 'required|exists:product_colors,id' : 'nullable',
        ]);

        // ── Color product adjustment ──────────────────────────────────────
        if ($hasColors && !empty($data['color_id'])) {
            $color = $product->colors->find($data['color_id']);

            if (!$color) {
                return back()->withErrors(['color_id' => 'Invalid color selected.']);
            }

            $colorBefore = (int) $color->stock_quantity;
            $colorAfter  = $colorBefore + $data['quantity'];

            if ($colorAfter < 0) {
                return back()->withErrors(['quantity' => "Stock for {$color->name} cannot go below zero (current: {$colorBefore})."]);
            }

            DB::transaction(function () use ($product, $color, $data, $colorBefore, $colorAfter) {
                $color->update(['stock_quantity' => $colorAfter]);

                // Sync main product stock to sum of all color stocks
                $product->refresh()->load('colors');
                $newTotal      = $product->colors->sum('stock_quantity');
                $productBefore = $product->stock_quantity;

                $updates = ['stock_quantity' => $newTotal];
                if ($newTotal > $product->low_stock_threshold && $product->low_stock_dismissed) {
                    $updates['low_stock_dismissed'] = false;
                }
                $product->update($updates);

                $noteText = "Color: {$color->name}" . ($data['note'] ? " — {$data['note']}" : '');

                StockMovement::create([
                    'product_id'      => $product->id,
                    'type'            => $data['type'],
                    'quantity'        => $data['quantity'],
                    'before_quantity' => $productBefore,
                    'after_quantity'  => $newTotal,
                    'note'            => $noteText,
                    'user_id'         => Auth::id(),
                ]);
            });

            return back()->with('success', "Stock updated for {$color->name}: {$colorBefore} → {$colorAfter}");
        }

        // ── Plain product adjustment ──────────────────────────────────────
        $before = $product->stock_quantity;
        $after  = $before + $data['quantity'];

        if ($after < 0) {
            return back()->withErrors(['quantity' => 'Stock cannot go below zero.']);
        }

        DB::transaction(function () use ($product, $data, $before, $after) {
            $updates = ['stock_quantity' => $after];
            if ($after > $product->low_stock_threshold && $product->low_stock_dismissed) {
                $updates['low_stock_dismissed'] = false;
            }
            $product->update($updates);

            StockMovement::create([
                'product_id'      => $product->id,
                'type'            => $data['type'],
                'quantity'        => $data['quantity'],
                'before_quantity' => $before,
                'after_quantity'  => $after,
                'note'            => $data['note'],
                'user_id'         => Auth::id(),
            ]);
        });

        return back()->with('success', "Stock updated. {$product->name}: {$before} → {$after}");
    }

    public function history(Product $product)
    {
        $movements = $product->stockMovements()->with('user')->latest()->paginate(30);
        return view('admin.inventory.history', compact('product', 'movements'));
    }

    public function search(Request $request)
    {
        $q = trim($request->input('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $prefix   = Auth::user()->panelPrefix();
        $products = Product::active()
            ->where(fn($query) =>
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('barcode', $q)
                      ->orWhere('sku', 'like', "%{$q}%")
            )
            ->orderByRaw('CASE WHEN barcode = ? THEN 0 ELSE 1 END', [$q])
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'barcode', 'sku', 'stock_quantity', 'track_inventory']);

        return response()->json($products->map(fn($p) => [
            'id'         => $p->id,
            'name'       => $p->name,
            'barcode'    => $p->barcode ?? '—',
            'sku'        => $p->sku ?? '—',
            'stock'      => $p->track_inventory ? $p->stock_quantity : null,
            'adjust_url' => route("{$prefix}.inventory.adjust.form", $p),
        ]));
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

    public function lowStock(Request $request)
    {
        if (Auth::user()->isSubshop()) {
            return $this->subshopStock($request, true);
        }

        $products = Product::active()
                            ->where('track_inventory', true)
                            ->where('low_stock_dismissed', false)
                            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                            ->with(['category', 'brand'])
                            ->orderBy('stock_quantity')
                            ->paginate(30);

        return view('admin.inventory.low-stock', compact('products'));
    }

    /** Read-only stock listing for the sub shop's own location. */
    private function subshopStock(Request $request, bool $lowOnly)
    {
        $shopId = Auth::user()->shopId();

        $query = Product::active()
            ->with([
                'category', 'brand',
                'shopStocks'    => fn($s) => $s->where('shop_id', $shopId),
                'serialNumbers' => fn($s) => $s->where('shop_id', $shopId)->where('status', 'in_stock'),
            ]);

        if ($lowOnly) {
            $query->where('track_inventory', true)
                ->whereHas('shopStocks', fn($s) => $s->where('shop_id', $shopId)
                    ->whereColumn('quantity', '<=', 'products.low_stock_threshold')
                    ->where('quantity', '>', 0));
        } else {
            $query->where(fn($q) => $q
                ->whereHas('shopStocks', fn($s) => $s->where('shop_id', $shopId)->where('quantity', '>', 0))
                ->orWhereHas('serialNumbers', fn($s) => $s->where('shop_id', $shopId)->where('status', 'in_stock')));
        }

        if ($request->filled('q')) {
            $s = $request->q;
            $query->where(fn($q) => $q->where('name', 'like', "%{$s}%")
                                      ->orWhere('barcode', 'like', "%{$s}%")
                                      ->orWhere('sku', 'like', "%{$s}%"));
        }

        $products = $query->orderBy('name')->paginate(30)->withQueryString();

        $totalUnits = \App\Models\ShopStock::where('shop_id', $shopId)->sum('quantity')
            + \App\Models\SerialNumber::where('shop_id', $shopId)->where('status', 'in_stock')->count();

        return view('shop.inventory', compact('products', 'totalUnits', 'lowOnly'));
    }

    public function dismissLowStock(Product $product)
    {
        $product->update(['low_stock_dismissed' => true]);
        return back()->with('success', "{$product->name} dismissed from low stock alerts.");
    }
}
