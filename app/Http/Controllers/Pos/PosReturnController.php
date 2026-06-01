<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\AccountLedger;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ReturnItem;
use App\Models\ReturnOrder;
use App\Models\Setting;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosReturnController extends Controller
{
    public function index()
    {
        $bankAccounts = BankAccount::active()->orderBy('sort_order')->get();
        return view('pos.return', compact('bankAccounts'));
    }

    public function searchBySku(Request $request)
    {
        $q = trim($request->get('q', ''));
        if (!$q) {
            return response()->json(['error' => 'Please enter a SKU or barcode.'], 422);
        }

        $orders = Order::whereHas('items', function ($query) use ($q) {
                $query->where('product_barcode', $q)
                      ->orWhereHas('product', fn($pq) => $pq->where('sku', $q)->orWhere('barcode', $q));
            })
            ->with(['items' => function ($query) use ($q) {
                $query->where('product_barcode', $q)
                      ->orWhereHas('product', fn($pq) => $pq->where('sku', $q)->orWhere('barcode', $q));
            }])
            ->latest()
            ->get();

        if ($orders->isEmpty()) {
            return response()->json(['error' => 'No orders found with that SKU or barcode.'], 404);
        }

        $result = $orders->map(function ($order) {
            $item = $order->items->first();
            return [
                'id'            => $order->id,
                'order_number'  => $order->order_number,
                'customer_name' => $order->customer_name ?: 'Walk-in',
                'customer_phone'=> $order->customer_phone,
                'total'         => $order->total,
                'status'        => $order->status,
                'payment_method'=> $order->payment_method,
                'date'          => $order->created_at->format('d M Y'),
                'time'          => $order->created_at->format('H:i'),
                'product_name'  => $item?->product_name ?? '—',
                'quantity'      => $item?->quantity ?? 0,
            ];
        })->values();

        return response()->json(['orders' => $result]);
    }

    public function findOrder(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
                      ->with(['items.product'])
                      ->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found.'], 404);
        }

        if ($order->source === 'ecommerce' && $order->status !== 'delivered') {
            $label = match($order->status) {
                'pending'    => 'Pending',
                'processing' => 'Processing',
                'shipped'    => 'Shipped',
                'cancelled'  => 'Cancelled',
                default      => ucfirst($order->status),
            };
            return response()->json([
                'error' => "This online order cannot be returned yet. Current status: {$label}. Returns are only allowed after the order has been delivered.",
            ], 422);
        }

        // Calculate already-returned quantities per order item
        $returnedQtys = ReturnItem::whereIn('order_item_id', $order->items->pluck('id'))
            ->whereHas('returnOrder', fn($q) => $q->where('order_id', $order->id)
                ->whereIn('status', ['approved', 'completed']))
            ->selectRaw('order_item_id, SUM(quantity) as total_returned')
            ->groupBy('order_item_id')
            ->pluck('total_returned', 'order_item_id');

        // Annotate each item with returnable qty
        $order->items->each(function ($item) use ($returnedQtys) {
            $item->already_returned = (int) ($returnedQtys[$item->id] ?? 0);
            $item->returnable_qty   = max(0, $item->quantity - $item->already_returned);
        });

        // Block if every item has been fully returned
        if ($order->items->every(fn($i) => $i->returnable_qty === 0)) {
            return response()->json([
                'error' => 'This order has already been fully returned. No items are available for return.',
            ], 422);
        }

        return response()->json($order);
    }

    public function process(Request $request)
    {
        $request->validate([
            'order_id'       => 'required|exists:orders,id',
            'items'          => 'required|array|min:1',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.quantity'      => 'required|integer|min:1',
            'reason'               => 'nullable|string|max:500',
            'refund_method'        => 'required|in:cash,khata_credit,bank_transfer',
            'bank_account_id'      => 'nullable|exists:bank_accounts,id',
            'custom_refund_amount' => 'nullable|numeric|min:0',
            'restock'              => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::with('items')->find($request->order_id);

            if ($order->source === 'ecommerce' && $order->status !== 'delivered') {
                DB::rollBack();
                return response()->json([
                    'error' => 'Cannot process return: this online order has not been delivered yet.',
                ], 422);
            }

            // Build already-returned qty map for this order
            $returnedQtys = ReturnItem::whereIn('order_item_id', $order->items->pluck('id'))
                ->whereHas('returnOrder', fn($q) => $q->where('order_id', $order->id)
                    ->whereIn('status', ['approved', 'completed']))
                ->selectRaw('order_item_id, SUM(quantity) as total_returned')
                ->groupBy('order_item_id')
                ->pluck('total_returned', 'order_item_id');

            // Validate each requested return quantity against what's still returnable
            foreach ($request->items as $ri) {
                $orderItem      = $order->items->find($ri['order_item_id']);
                if (!$orderItem) continue;
                $alreadyReturned = (int) ($returnedQtys[$orderItem->id] ?? 0);
                $returnableQty   = $orderItem->quantity - $alreadyReturned;

                if ($returnableQty <= 0) {
                    DB::rollBack();
                    return response()->json([
                        'error' => "\"{$orderItem->product_name}\" has already been fully returned.",
                    ], 422);
                }

                if ($ri['quantity'] > $returnableQty) {
                    DB::rollBack();
                    return response()->json([
                        'error' => "Cannot return {$ri['quantity']} of \"{$orderItem->product_name}\". "
                                 . "Only {$returnableQty} unit(s) remaining (already returned: {$alreadyReturned}).",
                    ], 422);
                }
            }
            $refund  = 0;
            $items   = [];

            foreach ($request->items as $ri) {
                $orderItem = $order->items->find($ri['order_item_id']);
                if (!$orderItem) continue;

                $alreadyRet = (int) ($returnedQtys[$orderItem->id] ?? 0);
                $qty        = min($ri['quantity'], $orderItem->quantity - $alreadyRet);
                $lineTotal = $orderItem->unit_price * $qty;
                $refund   += $lineTotal;

                $items[] = [
                    'order_item_id' => $orderItem->id,
                    'product_id'    => $orderItem->product_id,
                    'product_name'  => $orderItem->product_name,
                    'color_id'      => $orderItem->color_id,
                    'quantity'      => $qty,
                    'unit_price'    => $orderItem->unit_price,
                    'line_total'    => $lineTotal,
                ];
            }

            $bankAccountId = ($request->refund_method === 'bank_transfer')
                ? ($request->bank_account_id ?: null)
                : null;

            // Allow operator to override the refund amount
            if ($request->filled('custom_refund_amount') && (float) $request->custom_refund_amount >= 0) {
                $refund = (float) $request->custom_refund_amount;
            }

            $returnOrder = ReturnOrder::create([
                'order_id'        => $order->id,
                'customer_id'     => $order->customer_id,
                'reason'          => $request->reason,
                'refund_amount'   => $refund,
                'refund_method'   => $request->refund_method,
                'bank_account_id' => $bankAccountId,
                'status'          => 'completed',
                'restock'         => $request->boolean('restock', true),
                'processed_by'    => Auth::id(),
            ]);

            foreach ($items as $item) {
                ReturnItem::create(array_merge($item, ['return_id' => $returnOrder->id]));

                // Restock
                if ($request->boolean('restock', true) && $item['product_id']) {
                    $product = \App\Models\Product::find($item['product_id']);
                    $before  = $product->stock_quantity;
                    $product->increment('stock_quantity', $item['quantity']);

                    // Restore color stock if the order item was for a specific color
                    if (!empty($item['color_id'])) {
                        $color = \App\Models\ProductColor::find($item['color_id']);
                        if ($color) {
                            $color->increment('stock_quantity', $item['quantity']);
                        }
                    }

                    StockMovement::create([
                        'product_id'      => $product->id,
                        'type'            => 'return',
                        'quantity'        => $item['quantity'],
                        'before_quantity' => $before,
                        'after_quantity'  => $before + $item['quantity'],
                        'reference'       => $returnOrder->return_number,
                        'user_id'         => Auth::id(),
                    ]);
                }
            }

            // Khata credit
            if ($request->refund_method === 'khata_credit' && $order->customer_id) {
                $customer = Customer::find($order->customer_id);
                $newBal   = $customer->credit_balance + $refund;
                AccountLedger::create([
                    'customer_id'   => $customer->id,
                    'type'          => 'credit',
                    'amount'        => $refund,
                    'balance_after' => $newBal,
                    'description'   => "Return Credit — {$returnOrder->return_number}",
                    'reference'     => $returnOrder->return_number,
                    'user_id'       => Auth::id(),
                ]);
                $customer->update(['credit_balance' => $newBal]);
            }

            DB::commit();
            return response()->json(['success' => true, 'return_id' => $returnOrder->id, 'return_number' => $returnOrder->return_number]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function receipt(ReturnOrder $return)
    {
        $return->load(['items', 'order', 'processedBy']);
        $settings = [
            'shop_name'    => Setting::get('shop_name', 'MobileHub'),
            'shop_phone'   => Setting::get('shop_phone'),
            'shop_address' => Setting::get('shop_address'),
            'footer'       => Setting::get('receipt_footer'),
        ];
        return view('pos.return-receipt', compact('return', 'settings'));
    }
}
