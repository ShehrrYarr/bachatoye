<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\StockMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'servedBy', 'items'])->latest();

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
        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['items.product', 'customer', 'servedBy', 'deal', 'returns.items']);
        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status'          => 'required|in:pending,processing,shipped,delivered,cancelled,returned',
            'payment_status'  => 'nullable|in:pending,paid,partial',
            'tracking_number' => 'nullable|string|max:100',
        ]);

        $previousStatus = $order->status;

        $order->update(array_filter([
            'status'          => $data['status'],
            'payment_status'  => $data['payment_status'] ?? null,
            'tracking_number' => $data['tracking_number'] ?? null,
        ], fn($v) => $v !== null));

        // Deduct stock when an ecommerce order is marked as delivered (only once)
        if ($data['status'] === 'delivered' && $previousStatus !== 'delivered' && $order->source === 'ecommerce') {
            $order->load('items.product');
            foreach ($order->items as $item) {
                if ($item->product && $item->product->track_inventory) {
                    $before = $item->product->stock_quantity;
                    $item->product->decrement('stock_quantity', $item->quantity);
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
}
