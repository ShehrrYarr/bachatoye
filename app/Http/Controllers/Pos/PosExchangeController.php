<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\PosSession;
use App\Models\ReturnItem;
use App\Models\ReturnOrder;
use App\Models\Setting;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosExchangeController extends Controller
{
    public function index()
    {
        $bankAccounts = BankAccount::active()->orderBy('sort_order')->get();
        return view('pos.exchange', compact('bankAccounts'));
    }

    public function findOrder(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
                      ->where('source', 'pos')
                      ->where('status', '!=', 'cancelled')
                      ->with(['items.product'])
                      ->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found.'], 404);
        }

        return response()->json($order);
    }

    public function processExchange(Request $request)
    {
        $request->validate([
            'original_order_id'      => 'required|exists:orders,id',
            'return_item_id'         => 'required|exists:order_items,id',
            'return_quantity'        => 'required|integer|min:1',
            'exchange_value'         => 'required|numeric|min:0',
            'new_items'              => 'required|array|min:1',
            'new_items.*.product_id' => 'required|exists:products,id',
            'new_items.*.quantity'   => 'required|integer|min:1',
            'new_items.*.unit_price' => 'required|numeric|min:0',
            'new_items.*.color_id'   => 'nullable|exists:product_colors,id',
            'payment_method'         => 'required|in:cash,bank_transfer,split,none',
            'cash_amount'            => 'nullable|numeric|min:0',
            'bank_amount'            => 'nullable|numeric|min:0',
            'bank_account_id'        => 'nullable|exists:bank_accounts,id',
            'notes'                  => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $originalOrder  = Order::with('items')->find($request->original_order_id);
            $returnOrderItem = OrderItem::find($request->return_item_id);

            if (!$returnOrderItem || (int)$returnOrderItem->order_id !== (int)$originalOrder->id) {
                DB::rollBack();
                return response()->json(['error' => 'Invalid order item for this order.'], 422);
            }

            $returnQty     = min((int)$request->return_quantity, $returnOrderItem->quantity);
            $exchangeValue = (float)$request->exchange_value;

            // ─── Step 1: Create the Return ─────────────────────────────────────
            $returnOrder = ReturnOrder::create([
                'order_id'      => $originalOrder->id,
                'customer_id'   => $originalOrder->customer_id,
                'reason'        => 'exchange',
                'refund_amount' => $exchangeValue,
                'refund_method' => 'exchange',
                'status'        => 'completed',
                'restock'       => true,
                'processed_by'  => Auth::id(),
            ]);

            ReturnItem::create([
                'return_id'     => $returnOrder->id,
                'order_item_id' => $returnOrderItem->id,
                'product_id'    => $returnOrderItem->product_id,
                'product_name'  => $returnOrderItem->product_name,
                'quantity'      => $returnQty,
                'unit_price'    => $returnOrderItem->unit_price,
                'line_total'    => $returnOrderItem->unit_price * $returnQty,
            ]);

            // Restock the returned product
            $returnProduct = Product::lockForUpdate()->find($returnOrderItem->product_id);
            if ($returnProduct) {
                $before = $returnProduct->stock_quantity;
                $returnProduct->increment('stock_quantity', $returnQty);

                StockMovement::create([
                    'product_id'      => $returnProduct->id,
                    'type'            => 'return',
                    'quantity'        => $returnQty,
                    'before_quantity' => $before,
                    'after_quantity'  => $before + $returnQty,
                    'reference'       => $returnOrder->return_number,
                    'user_id'         => Auth::id(),
                ]);
            }

            // ─── Step 2: Build new order items ─────────────────────────────────
            $subtotal   = 0;
            $orderItems = [];

            foreach ($request->new_items as $item) {
                $product   = Product::lockForUpdate()->find($item['product_id']);
                $color     = null;
                $colorName = null;

                if (!empty($item['color_id'])) {
                    $color = ProductColor::lockForUpdate()->find($item['color_id']);
                    if ($color) {
                        $colorName = $color->name;
                        if ($color->stock_quantity < $item['quantity']) {
                            DB::rollBack();
                            return response()->json(['error' => "Insufficient stock for {$product->name} ({$color->name})."], 422);
                        }
                    }
                } elseif ($product->track_inventory && $product->stock_quantity < $item['quantity']) {
                    DB::rollBack();
                    return response()->json(['error' => "Insufficient stock for {$product->name}."], 422);
                }

                $lineTotal  = $item['unit_price'] * $item['quantity'];
                $subtotal  += $lineTotal;

                $orderItems[] = [
                    'product'    => $product,
                    'color'      => $color,
                    'color_name' => $colorName,
                    'qty'        => $item['quantity'],
                    'price'      => $item['unit_price'],
                    'line_total' => $lineTotal,
                ];
            }

            $total = max(0, $subtotal - $exchangeValue);

            // ─── Resolve payment details ────────────────────────────────────────
            $payMethod     = $request->payment_method;
            $amountPaid    = $total;
            $cashAmount    = null;
            $bankAmount    = null;
            $bankAccountId = null;

            if ($payMethod === 'none') {
                // Exchange value fully covers new items — no extra payment
                $payMethod  = 'cash';
                $amountPaid = 0;
            } elseif ($payMethod === 'split') {
                $cashAmount    = (float)($request->cash_amount ?? 0);
                $bankAmount    = (float)($request->bank_amount ?? 0);
                $amountPaid    = $cashAmount + $bankAmount;
                $bankAccountId = $request->bank_account_id ?: null;
            } elseif ($payMethod === 'bank_transfer') {
                $bankAccountId = $request->bank_account_id ?: null;
            }

            // ─── Step 3: Create new POS Order ──────────────────────────────────
            $newOrder = Order::create([
                'source'             => 'pos',
                'customer_id'        => $originalOrder->customer_id,
                'customer_name'      => $originalOrder->customer_name,
                'customer_phone'     => $originalOrder->customer_phone,
                'subtotal'           => $subtotal,
                'discount_amount'    => 0,
                'exchange_item_name' => $returnOrderItem->product_name,
                'exchange_value'     => $exchangeValue,
                'total'              => $total,
                'amount_paid'        => $amountPaid,
                'cash_amount'        => $cashAmount,
                'bank_amount'        => $bankAmount,
                'bank_account_id'    => $bankAccountId,
                'payment_method'     => $payMethod,
                'payment_status'     => 'paid',
                'notes'              => $request->notes,
                'status'             => 'delivered',
                'served_by'          => Auth::id(),
            ]);

            foreach ($orderItems as $item) {
                OrderItem::create([
                    'order_id'        => $newOrder->id,
                    'product_id'      => $item['product']->id,
                    'product_name'    => $item['product']->name,
                    'color_name'      => $item['color_name'],
                    'product_barcode' => $item['product']->barcode,
                    'unit_price'      => $item['price'],
                    'cost_price'      => $item['product']->cost_price,
                    'quantity'        => $item['qty'],
                    'line_total'      => $item['line_total'],
                ]);

                if ($item['color']) {
                    $item['color']->decrement('stock_quantity', $item['qty']);
                }

                $before = $item['product']->stock_quantity;
                $item['product']->decrement('stock_quantity', $item['qty']);

                StockMovement::create([
                    'product_id'      => $item['product']->id,
                    'type'            => 'sale',
                    'quantity'        => -$item['qty'],
                    'before_quantity' => $before,
                    'after_quantity'  => $before - $item['qty'],
                    'reference'       => $newOrder->order_number,
                    'user_id'         => Auth::id(),
                ]);
            }

            // ─── Update POS session ─────────────────────────────────────────────
            PosSession::where('user_id', Auth::id())->whereNull('closed_at')
                      ->increment('total_sales', $total);
            PosSession::where('user_id', Auth::id())->whereNull('closed_at')
                      ->increment('total_orders');

            DB::commit();

            return response()->json([
                'success'       => true,
                'order_id'      => $newOrder->id,
                'order_number'  => $newOrder->order_number,
                'return_id'     => $returnOrder->id,
                'return_number' => $returnOrder->return_number,
                'cashback'      => max(0, $exchangeValue - $subtotal),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Exchange failed: ' . $e->getMessage()], 500);
        }
    }
}
