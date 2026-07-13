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
        $bankAccounts = BankAccount::active()->forShop(Auth::user()->shopId())->orderBy('sort_order')->get();
        return view('pos.exchange', compact('bankAccounts'));
    }

    public function findOrder(string $orderNumber)
    {
        $shopId = Auth::user()->shopId();

        $order = Order::where('order_number', $orderNumber)
                      ->where('source', 'pos')
                      ->when($shopId, fn($q) => $q->forShop($shopId))
                      ->where('status', '!=', 'cancelled')
                      ->with(['items.product', 'items.returnItems'])
                      ->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found.'], 404);
        }

        $exchangeableItems = $order->items
            ->map(function ($item) {
                $item->quantity = $item->quantity - $item->returnItems->sum('quantity');
                return $item;
            })
            ->filter(fn($item) => $item->quantity > 0)
            ->values();

        $order->setRelation('items', $exchangeableItems);

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

            // Sub shop can only exchange its own shop's orders; the new order
            // is created at the same shop the original was sold from.
            $userShopId = Auth::user()->shopId();
            if ($userShopId && $originalOrder->shop_id !== $userShopId) {
                DB::rollBack();
                return response()->json(['error' => 'Order not found.'], 404);
            }
            $orderShopId = $originalOrder->shop_id;

            if ($request->filled('bank_account_id')
                && !BankAccount::forShop($orderShopId)->where('id', $request->bank_account_id)->exists()) {
                DB::rollBack();
                return response()->json(['error' => 'The selected bank account belongs to a different shop.'], 422);
            }

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

            // Restock the returned product at the order's own shop
            $returnProduct = Product::lockForUpdate()->find($returnOrderItem->product_id);
            if ($returnProduct) {
                app(\App\Services\ShopStockService::class)->adjust(
                    $orderShopId, $returnProduct, $returnOrderItem->color_id ?? null, $returnQty,
                    'return', $returnOrder->return_number
                );
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
                        $colorStock = $orderShopId
                            ? $product->stockForShop($orderShopId, $color->id)
                            : $color->stock_quantity;
                        if ($colorStock < $item['quantity']) {
                            DB::rollBack();
                            return response()->json(['error' => "Insufficient stock for {$product->name} ({$color->name})."], 422);
                        }
                    }
                } elseif ($product->track_inventory
                    && ($orderShopId ? $product->stockForShop($orderShopId) : $product->stock_quantity) < $item['quantity']) {
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
                'shop_id'            => $orderShopId,
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

                app(\App\Services\ShopStockService::class)->adjust(
                    $orderShopId, $item['product'], $item['color']?->id, -$item['qty'],
                    'sale', $newOrder->order_number
                );
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

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Exchange failed: ' . $e->getMessage()], 500);
        }
    }
}
