<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPlatform;
use App\Models\Order;
use App\Models\SerialNumber;
use App\Services\ShopStockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

    public function updateStatus(Request $request, Order $order, ShopStockService $stock)
    {
        $data = $request->validate([
            'status'          => 'required|in:pending,processing,shipped,delivered,cancelled,returned',
            'payment_status'  => 'nullable|in:pending,paid,partial',
            'tracking_number' => 'nullable|string|max:100',
        ]);

        try {
            DB::transaction(function () use ($data, $order, $stock) {
                // Locked so two quick status changes can't both deduct or restore
                $order = Order::lockForUpdate()->findOrFail($order->id);

                if ($order->source === 'ecommerce') {
                    $this->syncEcomStock($order, $data['status'], $stock);
                }

                $order->update(array_filter([
                    'status'          => $data['status'],
                    'payment_status'  => $data['payment_status'] ?? null,
                    'tracking_number' => $data['tracking_number'] ?? null,
                ], fn($v) => $v !== null));
            });
        } catch (ValidationException $e) {
            return back()->withErrors([
                'status' => 'Cannot change status — ' . collect($e->errors())->flatten()->first(),
            ]);
        }

        return back()->with('success', 'Order updated.');
    }

    /**
     * Online orders take stock off when first marked delivered and put it back
     * when cancelled or returned. `stock_deducted` records which side of that
     * the order is on, so delivered → processing → delivered deducts once and
     * a restore can never happen without a matching deduction.
     */
    private function syncEcomStock(Order $order, string $newStatus, ShopStockService $stock): void
    {
        $order->load('items.product', 'items.serialNumber', 'items.returnItems.returnOrder');

        if ($newStatus === 'delivered') {
            foreach ($order->items as $item) {
                $this->reserveSerial($order, $item);
            }

            if (!$order->stock_deducted) {
                foreach ($order->items as $item) {
                    if ($item->product?->track_inventory) {
                        $stock->adjust(
                            $order->shop_id, $item->product, $item->color_id, -$item->quantity,
                            'sale', $order->order_number
                        );
                    }
                }
                $order->stock_deducted = true;
            }
            return;
        }

        if (!in_array($newStatus, ['cancelled', 'returned'], true)) {
            return;
        }

        if ($order->stock_deducted) {
            foreach ($order->items as $item) {
                // Units already restocked through the POS Return screen are back already
                $restocked = (int) $item->returnItems
                    ->filter(fn($ri) => in_array($ri->returnOrder?->status, ['approved', 'completed'], true)
                        && $ri->returnOrder->restock)
                    ->sum('quantity');
                $qty = $item->quantity - $restocked;

                if ($qty > 0 && $item->product?->track_inventory) {
                    $stock->adjust(
                        $order->shop_id, $item->product, $item->color_id, $qty,
                        $newStatus === 'returned' ? 'return' : 'adjustment', $order->order_number,
                        'Online order ' . $newStatus . ' — stock restored'
                    );
                }
            }
            $order->stock_deducted = false;
        }

        // Release reserved/sold used units still tied to this order
        foreach ($order->items as $item) {
            $serial = $item->serialNumber;
            if ($serial && $serial->status === 'sold' && (int) $serial->order_id === (int) $order->id) {
                $serial->update(['status' => 'in_stock', 'order_id' => null, 'order_item_id' => null]);
            }
        }
    }

    /**
     * A used unit is reserved at checkout but released if the order is
     * cancelled; delivering the order again must re-reserve it, and must not
     * if someone else has bought it since.
     */
    private function reserveSerial(Order $order, $item): void
    {
        if (!$item->serial_number_id) {
            return;
        }

        $serial = SerialNumber::lockForUpdate()->find($item->serial_number_id);
        if (!$serial || ((int) $serial->order_id === (int) $order->id && $serial->status === 'sold')) {
            return;
        }

        if ($serial->status !== 'in_stock') {
            throw ValidationException::withMessages([
                'stock' => "used unit {$serial->serial_number} of {$item->product_name} is no longer available ({$serial->status}).",
            ]);
        }

        $serial->update(['status' => 'sold', 'order_id' => $order->id, 'order_item_id' => $item->id]);
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
