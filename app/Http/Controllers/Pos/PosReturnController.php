<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\AccountLedger;
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
        return view('pos.return');
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

        return response()->json($order->load(['items']));
    }

    public function process(Request $request)
    {
        $request->validate([
            'order_id'      => 'required|exists:orders,id',
            'items'         => 'required|array|min:1',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.quantity'      => 'required|integer|min:1',
            'reason'        => 'nullable|string|max:500',
            'refund_method' => 'required|in:cash,khata_credit,bank_transfer',
            'restock'       => 'boolean',
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
            $refund  = 0;
            $items   = [];

            foreach ($request->items as $ri) {
                $orderItem = $order->items->find($ri['order_item_id']);
                if (!$orderItem) continue;

                $qty       = min($ri['quantity'], $orderItem->quantity);
                $lineTotal = $orderItem->unit_price * $qty;
                $refund   += $lineTotal;

                $items[] = [
                    'order_item_id' => $orderItem->id,
                    'product_id'    => $orderItem->product_id,
                    'product_name'  => $orderItem->product_name,
                    'quantity'      => $qty,
                    'unit_price'    => $orderItem->unit_price,
                    'line_total'    => $lineTotal,
                ];
            }

            $returnOrder = ReturnOrder::create([
                'order_id'      => $order->id,
                'customer_id'   => $order->customer_id,
                'reason'        => $request->reason,
                'refund_amount' => $refund,
                'refund_method' => $request->refund_method,
                'status'        => 'completed',
                'restock'       => $request->boolean('restock', true),
                'processed_by'  => Auth::id(),
            ]);

            foreach ($items as $item) {
                ReturnItem::create(array_merge($item, ['return_id' => $returnOrder->id]));

                // Restock
                if ($request->boolean('restock', true) && $item['product_id']) {
                    $product = \App\Models\Product::find($item['product_id']);
                    $before  = $product->stock_quantity;
                    $product->increment('stock_quantity', $item['quantity']);

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
