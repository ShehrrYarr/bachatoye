<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPlatform;
use App\Models\Order;
use App\Models\StockMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'servedBy', 'items', 'shop'])
            ->forShopFilter($request->input('shop', ''))
            ->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('order_number', 'like', "%{$s}%")
                                      ->orWhere('customer_name', 'like', "%{$s}%")
                                      ->orWhere('customer_phone', 'like', "%{$s}%"));
        }

        foreach (['status', 'payment_status', 'payment_method', 'source'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->$filter);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->paginate(25)->withQueryString();
        $shops  = \App\Models\Shop::orderBy('name')->get();
        return view('admin.orders.index', compact('orders', 'shops'));
    }

    public function show(Order $order)
    {
        $order->load(['items.product', 'customer', 'servedBy', 'deal', 'returns.items', 'deliveryPlatform']);
        $deliveryPlatforms = DeliveryPlatform::active()->orderBy('name')->get();
        return view('admin.orders.show', compact('order', 'deliveryPlatforms'));
    }

    public function dispatch(Request $request, Order $order)
    {
        $data = $request->validate([
            'delivery_platform_id' => 'required|exists:delivery_platforms,id',
            'tracking_number'      => 'nullable|string|max:100',
            'is_cod'               => 'nullable|boolean',
        ]);

        $isCod = (bool) ($data['is_cod'] ?? false);

        $order->update([
            'delivery_platform_id' => $data['delivery_platform_id'],
            'tracking_number'      => $data['tracking_number'] ?? $order->tracking_number,
            'cod_status'           => $isCod ? 'pending' : null,
            'status'               => in_array($order->status, ['pending', 'processing']) ? 'shipped' : $order->status,
        ]);

        return back()->with('success', 'Order dispatched' . ($isCod ? ' — COD tracking enabled.' : '.'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status'          => 'required|in:pending,processing,shipped,delivered,cancelled,returned',
            'payment_status'  => 'nullable|in:pending,paid,partial',
            'tracking_number' => 'nullable|string|max:100',
        ]);

        $previousStatus = $order->status;

        // ── Stock check before marking an ecommerce order as delivered ────────
        if ($data['status'] === 'delivered' && $previousStatus !== 'delivered' && $order->source === 'ecommerce') {
            $order->load('items.product');
            $outOfStock = [];

            foreach ($order->items as $item) {
                if ($item->product && $item->product->track_inventory) {
                    if ($item->product->stock_quantity < $item->quantity) {
                        $available = max(0, $item->product->stock_quantity);
                        $outOfStock[] = "{$item->product_name} (need {$item->quantity}, only {$available} in stock)";
                    }

                    // Color-specific availability when the item was ordered in a color
                    if ($item->color_id) {
                        $color = \App\Models\ProductColor::find($item->color_id);
                        if ($color && $color->stock_quantity < $item->quantity) {
                            $outOfStock[] = "{$item->product_name} ({$color->name}) — need {$item->quantity}, only " . max(0, $color->stock_quantity) . ' in stock';
                        }
                    }
                }
            }

            if (!empty($outOfStock)) {
                return back()->withErrors([
                    'status' => 'Cannot mark as delivered — insufficient stock: ' . implode(' | ', $outOfStock),
                ]);
            }
        }

        $order->update(array_filter([
            'status'          => $data['status'],
            'payment_status'  => $data['payment_status'] ?? null,
            'tracking_number' => $data['tracking_number'] ?? null,
        ], fn($v) => $v !== null));

        // ── Deduct stock when an ecommerce order is marked as delivered ───────
        if ($data['status'] === 'delivered' && $previousStatus !== 'delivered' && $order->source === 'ecommerce') {
            $order->loadMissing('items.product');
            foreach ($order->items as $item) {
                if ($item->product && $item->product->track_inventory) {
                    $before = $item->product->stock_quantity;
                    $item->product->decrement('stock_quantity', $item->quantity);

                    // Keep the color ledger in sync — this was the source of
                    // product-vs-color stock drift on delivered online orders
                    if ($item->color_id) {
                        \App\Models\ProductColor::where('id', $item->color_id)
                            ->decrement('stock_quantity', $item->quantity);
                    }

                    StockMovement::create([
                        'product_id'      => $item->product_id,
                        'type'            => 'sale',
                        'quantity'        => -$item->quantity,
                        'before_quantity' => $before,
                        'after_quantity'  => $before - $item->quantity,
                        'reference'       => $order->order_number,
                        'user_id'         => Auth::id(),
                    ]);
                }
            }
        }

        // ── Release reserved used-unit serials when an ecommerce order is cancelled ──
        if ($data['status'] === 'cancelled' && $previousStatus !== 'cancelled' && $order->source === 'ecommerce') {
            $order->loadMissing('items.serialNumber');
            foreach ($order->items as $item) {
                if ($item->serialNumber && $item->serialNumber->status === 'sold') {
                    $item->serialNumber->update([
                        'status'        => 'in_stock',
                        'order_id'      => null,
                        'order_item_id' => null,
                    ]);
                }
            }
        }

        return back()->with('success', 'Order updated.');
    }

    public function updatePaymentStatus(Request $request, Order $order)
    {
        $request->validate(['payment_status' => 'required|in:pending,paid,partial']);
        $order->update(['payment_status' => $request->payment_status]);
        return back()->with('success', 'Payment status updated.');
    }

    public function invoice(Order $order)
    {
        $order->load(['items.product', 'customer', 'servedBy']);
        return view('admin.orders.invoice', compact('order'));
    }

    public function invoicePdf(Order $order)
    {
        $order->load(['items.product', 'customer']);
        $pdf = Pdf::loadView('admin.orders.invoice-pdf', compact('order'));
        return $pdf->download("invoice-{$order->order_number}.pdf");
    }

    public function deletedSales(Request $request)
    {
        $query = Order::onlyTrashed()
            ->with(['customer', 'servedBy', 'deletedBy', 'items'])
            ->where('source', 'pos')
            ->latest('deleted_at');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('order_number', 'like', "%{$s}%")
                                      ->orWhere('customer_name', 'like', "%{$s}%")
                                      ->orWhere('customer_phone', 'like', "%{$s}%"));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('deleted_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('deleted_at', '<=', $request->date_to);
        }

        $orders     = $query->paginate(25)->withQueryString();
        $totalValue = Order::onlyTrashed()->where('source', 'pos')->sum('total');

        return view('admin.orders.deleted', compact('orders', 'totalValue'));
    }
}
