<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SerialNumber;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\StockTransfer;
use App\Services\ShopStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        $user   = Auth::user();
        $shopId = $user->shopId();

        $transfers = StockTransfer::with(['fromShop', 'toShop', 'creator'])
            // Sub shop login sees only its own transfers
            ->when($shopId, fn($q) => $q->where(fn($w) => $w
                ->where('from_shop_id', $shopId)->orWhere('to_shop_id', $shopId)))
            // Admin filter by shop (either direction)
            ->when(!$shopId && $request->filled('shop'), fn($q) => $q->where(fn($w) => $w
                ->where('from_shop_id', $request->shop)->orWhere('to_shop_id', $request->shop)))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $shops = $shopId ? collect() : Shop::orderBy('name')->get();

        return view('admin.transfers.index', compact('transfers', 'shops'));
    }

    public function create()
    {
        $shops = Shop::active()->orderBy('name')->get();

        if ($shops->isEmpty()) {
            return redirect()->route('admin.shops.index')
                ->with('error', 'Create a sub shop first — transfers move stock between the main shop and sub shops.');
        }

        return view('admin.transfers.create', compact('shops'));
    }

    public function store(Request $request, ShopStockService $stockService)
    {
        $data = $request->validate([
            'direction'          => 'required|in:to_shop,from_shop',
            'shop_id'            => 'required|exists:shops,id',
            'note'               => 'nullable|string|max:1000',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.color_id'   => 'nullable|exists:product_colors,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.serial_ids' => 'nullable|array',
        ]);

        // Instant transfer: main ↔ sub. NULL side = main shop.
        $fromShopId = $data['direction'] === 'to_shop' ? null : (int) $data['shop_id'];
        $toShopId   = $data['direction'] === 'to_shop' ? (int) $data['shop_id'] : null;

        try {
            $transfer = DB::transaction(function () use ($data, $fromShopId, $toShopId, $stockService) {
                $transfer = StockTransfer::create([
                    'from_shop_id' => $fromShopId,
                    'to_shop_id'   => $toShopId,
                    'note'         => $data['note'] ?? null,
                    'created_by'   => Auth::id(),
                ]);

                $totalQty = 0;

                foreach ($data['items'] as $row) {
                    $product = Product::findOrFail($row['product_id']);
                    $colorId = $row['color_id'] ?? null;

                    if ($product->is_serialized) {
                        $serialIds = array_filter($row['serial_ids'] ?? []);
                        if (empty($serialIds)) {
                            throw ValidationException::withMessages([
                                'items' => "{$product->name} is serialized — pick the serial numbers to transfer.",
                            ]);
                        }

                        $serials = SerialNumber::lockForUpdate()
                            ->whereIn('id', $serialIds)
                            ->where('product_id', $product->id)
                            ->get();

                        foreach ($serialIds as $sid) {
                            $serial = $serials->firstWhere('id', (int) $sid);
                            if (!$serial || $serial->status !== 'in_stock' || $serial->shop_id !== $fromShopId) {
                                throw ValidationException::withMessages([
                                    'items' => "A selected serial of {$product->name} is no longer in stock at the source shop.",
                                ]);
                            }

                            $serial->update(['shop_id' => $toShopId]);

                            $transfer->items()->create([
                                'product_id'       => $product->id,
                                'product_name'     => $product->name,
                                'serial_number_id' => $serial->id,
                                'serial_code'      => $serial->serial_number,
                                'quantity'         => 1,
                            ]);
                        }

                        $qty = count($serialIds);
                        $stockService->adjust($fromShopId, $product, null, -$qty, 'transfer_out', $transfer->transfer_number);
                        $stockService->adjust($toShopId, $product, null, $qty, 'transfer_in', $transfer->transfer_number);
                        $totalQty += $qty;
                        continue;
                    }

                    $qty       = (int) $row['quantity'];
                    $colorName = null;
                    if ($colorId) {
                        $color = $product->colors->firstWhere('id', (int) $colorId);
                        if (!$color) {
                            throw ValidationException::withMessages([
                                'items' => "Invalid color selected for {$product->name}.",
                            ]);
                        }
                        $colorName = $color->name;
                    }

                    $stockService->adjust($fromShopId, $product, $colorId ? (int) $colorId : null, -$qty, 'transfer_out', $transfer->transfer_number);
                    $stockService->adjust($toShopId, $product, $colorId ? (int) $colorId : null, $qty, 'transfer_in', $transfer->transfer_number);

                    $transfer->items()->create([
                        'product_id'       => $product->id,
                        'product_name'     => $product->name,
                        'product_color_id' => $colorId ?: null,
                        'color_name'       => $colorName,
                        'quantity'         => $qty,
                    ]);
                    $totalQty += $qty;
                }

                $transfer->update([
                    'total_items' => count($data['items']),
                    'total_qty'   => $totalQty,
                ]);

                return $transfer;
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('admin.transfers.show', $transfer)
            ->with('success', "Transfer {$transfer->transfer_number} completed — stock moved instantly.");
    }

    public function show(StockTransfer $transfer)
    {
        $this->authorizeView($transfer);
        $transfer->load(['items.product', 'fromShop', 'toShop', 'creator']);

        return view('admin.transfers.show', compact('transfer'));
    }

    public function slip(StockTransfer $transfer)
    {
        $this->authorizeView($transfer);
        $transfer->load(['items', 'fromShop', 'toShop', 'creator']);

        return view('admin.transfers.slip', compact('transfer'));
    }

    /** Product search with availability at the source location. */
    public function searchProducts(Request $request)
    {
        $q          = trim((string) $request->get('q', ''));
        $fromShopId = $request->filled('from_shop_id') ? (int) $request->get('from_shop_id') : null;

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $products = Product::active()
            ->where(fn($w) => $w->where('name', 'like', "%{$q}%")
                ->orWhere('sku', 'like', "%{$q}%")
                ->orWhere('barcode', 'like', "%{$q}%"))
            ->with('colors')
            ->take(15)
            ->get();

        return response()->json($products->map(function (Product $p) use ($fromShopId) {
            if ($fromShopId === null) {
                $stock  = (int) $p->stock_quantity;
                $colors = $p->colors->map(fn($c) => [
                    'id' => $c->id, 'name' => $c->name, 'stock' => (int) $c->stock_quantity,
                ]);
            } else {
                $rows   = ShopStock::where('shop_id', $fromShopId)->where('product_id', $p->id)->get();
                $stock  = (int) $rows->sum('quantity');
                $colors = $p->colors->map(fn($c) => [
                    'id'    => $c->id,
                    'name'  => $c->name,
                    'stock' => (int) ($rows->firstWhere('product_color_id', $c->id)?->quantity ?? 0),
                ]);
            }

            if ($p->is_serialized) {
                $stock = SerialNumber::where('product_id', $p->id)
                    ->where('status', 'in_stock')
                    ->forShop($fromShopId)
                    ->count();
            }

            return [
                'id'            => $p->id,
                'name'          => $p->name,
                'sku'           => $p->sku,
                'is_serialized' => (bool) $p->is_serialized,
                'stock'         => $stock,
                'colors'        => $p->colors->count() ? $colors : [],
            ];
        })->filter(fn($p) => $p['stock'] > 0)->values());
    }

    /** In-stock serials of a product at the source location, for the picker. */
    public function serials(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);
        $fromShopId = $request->filled('from_shop_id') ? (int) $request->get('from_shop_id') : null;

        $serials = SerialNumber::where('product_id', $request->product_id)
            ->where('status', 'in_stock')
            ->forShop($fromShopId)
            ->orderBy('serial_number')
            ->get(['id', 'serial_number', 'attributes', 'selling_price']);

        return response()->json($serials->map(fn($s) => [
            'id'            => $s->id,
            'serial_number' => $s->serial_number,
            'attributes'    => array_filter($s->attributes ?? []),
            'selling_price' => $s->selling_price,
        ]));
    }

    private function authorizeView(StockTransfer $transfer): void
    {
        $shopId = Auth::user()->shopId();
        if ($shopId !== null && $transfer->from_shop_id !== $shopId && $transfer->to_shop_id !== $shopId) {
            abort(404);
        }
    }
}
