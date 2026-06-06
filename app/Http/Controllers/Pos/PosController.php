<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\AccountLedger;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductColor;
use App\Models\OrderItem;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\SerialNumber;
use App\Models\Setting;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function index()
    {
        $user    = Auth::user();
        $session = PosSession::where('user_id', $user->id)->whereNull('closed_at')->latest()->first();

        // Section-based filtering: admins see everything; salesmen see only their sections
        $allowedCategoryIds = $user->allowedCategoryIds();

        $catQuery  = \App\Models\Category::active()->with('products');
        $prodQuery = Product::active()->inStock()->with('images');

        if ($allowedCategoryIds !== null) {
            $catQuery->whereIn('id', $allowedCategoryIds);
            $prodQuery->whereIn('category_id', $allowedCategoryIds);
        }

        $categories       = $catQuery->get();
        $featuredProducts = $prodQuery->limit(20)->get();

        $isAdmin = $user->hasRole('admin');

        // Daily sales orders with items (salesman sees only their own)
        $todaySalesOrders = Order::where('source', 'pos')
            ->whereDate('created_at', today())
            ->where('status', 'delivered')
            ->when(!$isAdmin, fn($q) => $q->where('served_by', $user->id))
            ->with(['items', 'customer'])
            ->latest()
            ->get();

        // Daily returns with items (salesman sees only returns they processed)
        $todayReturnsList = \App\Models\ReturnOrder::whereDate('created_at', today())
            ->when(!$isAdmin, fn($q) => $q->where('processed_by', $user->id))
            ->with(['items', 'order'])
            ->latest()
            ->get();

        // Daily customer payments received (khata credit entries — salesman sees their own)
        $todayPaymentsList = \App\Models\AccountLedger::whereDate('created_at', today())
            ->where('type', 'credit')
            ->when(!$isAdmin, fn($q) => $q->where('user_id', $user->id))
            ->with(['customer', 'user'])
            ->latest()
            ->get();

        // Aggregated totals for the summary bar
        $cashTotal = $todaySalesOrders->sum(function ($o) {
            return match ($o->payment_method) {
                'cash'    => $o->total,
                'split'   => $o->cash_amount ?? 0,
                'partial' => $o->amount_paid ?? 0,
                default   => 0,
            };
        });
        $bankTotal = $todaySalesOrders->sum(function ($o) {
            return match ($o->payment_method) {
                'bank_transfer' => $o->total,
                'split'         => $o->bank_amount ?? 0,
                default         => 0,
            };
        });

        $todaySales = (object)[
            'order_count'    => $todaySalesOrders->count(),
            'total_revenue'  => $todaySalesOrders->sum('total'),
            'cash_total'     => $cashTotal,
            'bank_total'     => $bankTotal,
        ];
        $todayReturns = (object)[
            'return_count'   => $todayReturnsList->count(),
            'total_refunded' => $todayReturnsList->sum('refund_amount'),
        ];
        $todayPayments = (object)[
            'payment_count'   => $todayPaymentsList->count(),
            'total_collected' => $todayPaymentsList->sum('amount'),
        ];

        $bankAccounts = BankAccount::active()->orderBy('sort_order')->orderBy('id')->get();

        return view('pos.index', compact(
            'session', 'categories', 'featuredProducts',
            'todaySales', 'todayReturns', 'todayPayments',
            'todaySalesOrders', 'todayReturnsList', 'todayPaymentsList',
            'bankAccounts'
        ));
    }

    public function stats(): \Illuminate\Http\JsonResponse
    {
        $user    = Auth::user();
        $isAdmin = $user->hasRole('admin');

        $todaySalesOrders = Order::where('source', 'pos')
            ->whereDate('created_at', today())
            ->where('status', 'delivered')
            ->when(!$isAdmin, fn($q) => $q->where('served_by', $user->id))
            ->get(['payment_method', 'total', 'cash_amount', 'bank_amount', 'amount_paid']);

        $cashTotal = $todaySalesOrders->sum(function ($o) {
            return match ($o->payment_method) {
                'cash'    => $o->total,
                'split'   => $o->cash_amount ?? 0,
                'partial' => $o->amount_paid ?? 0,
                default   => 0,
            };
        });
        $bankTotal = $todaySalesOrders->sum(function ($o) {
            return match ($o->payment_method) {
                'bank_transfer' => $o->total,
                'split'         => $o->bank_amount ?? 0,
                default         => 0,
            };
        });

        $todayReturnsList = \App\Models\ReturnOrder::whereDate('created_at', today())
            ->when(!$isAdmin, fn($q) => $q->where('processed_by', $user->id))
            ->get(['refund_amount']);

        $todayPaymentsList = \App\Models\AccountLedger::whereDate('created_at', today())
            ->where('type', 'credit')
            ->when(!$isAdmin, fn($q) => $q->where('user_id', $user->id))
            ->get(['amount']);

        return response()->json([
            'order_count'     => $todaySalesOrders->count(),
            'total_revenue'   => (float) $todaySalesOrders->sum('total'),
            'cash_total'      => (float) $cashTotal,
            'bank_total'      => (float) $bankTotal,
            'return_count'    => $todayReturnsList->count(),
            'total_refunded'  => (float) $todayReturnsList->sum('refund_amount'),
            'payment_count'   => $todayPaymentsList->count(),
            'total_collected' => (float) $todayPaymentsList->sum('amount'),
        ]);
    }

    public function todayActivity(): \Illuminate\Http\JsonResponse
    {
        $user    = Auth::user();
        $isAdmin = $user->hasRole('admin');

        $orders = Order::where('source', 'pos')
            ->whereDate('created_at', today())
            ->where('status', 'delivered')
            ->when(!$isAdmin, fn($q) => $q->where('served_by', $user->id))
            ->latest()
            ->with(['items'])
            ->get()
            ->map(fn($o) => [
                'id'              => $o->id,
                'time'            => $o->created_at->format('H:i'),
                'order_number'    => $o->order_number,
                'customer_name'   => $o->customer_name,
                'customer_phone'  => ($o->customer_phone && $o->customer_phone !== '-') ? $o->customer_phone : null,
                'items'           => $o->items->map(fn($i) => [
                    'quantity'     => (int) $i->quantity,
                    'product_name' => $i->product_name,
                    'unit_price'   => (float) $i->unit_price,
                ])->values(),
                'payment_method'  => $o->payment_method,
                'cash_amount'     => (float) ($o->cash_amount ?? 0),
                'bank_amount'     => (float) ($o->bank_amount ?? 0),
                'total'           => (float) $o->total,
                'discount_amount' => (float) ($o->discount_amount ?? 0),
            ])->values();

        $returns = \App\Models\ReturnOrder::whereDate('created_at', today())
            ->when(!$isAdmin, fn($q) => $q->where('processed_by', $user->id))
            ->latest()
            ->with(['order', 'items'])
            ->get()
            ->map(fn($r) => [
                'id'            => $r->id,
                'time'          => $r->created_at->format('H:i'),
                'return_number' => $r->return_number,
                'order_number'  => $r->order?->order_number ?? '—',
                'items'         => $r->items->map(fn($i) => [
                    'quantity'     => (int) $i->quantity,
                    'product_name' => $i->product_name,
                    'unit_price'   => (float) $i->unit_price,
                ])->values(),
                'reason'        => $r->reason ?? '—',
                'refund_amount' => (float) $r->refund_amount,
                'refund_method' => $r->refund_method ?? '',
            ])->values();

        $payments = \App\Models\AccountLedger::whereDate('created_at', today())
            ->where('type', 'credit')
            ->when(!$isAdmin, fn($q) => $q->where('user_id', $user->id))
            ->latest()
            ->with(['customer', 'user'])
            ->get()
            ->map(fn($p) => [
                'time'           => $p->created_at->format('H:i'),
                'customer_name'  => $p->customer?->name ?? '—',
                'customer_phone' => $p->customer?->phone ?? null,
                'description'    => $p->description ?? null,
                'reference'      => $p->reference ?? null,
                'user_name'      => $p->user?->name ?? '—',
                'amount'         => (float) $p->amount,
                'balance_after'  => (float) ($p->balance_after ?? 0),
            ])->values();

        return response()->json(compact('orders', 'returns', 'payments'));
    }

    public function openSession(Request $request)
    {
        $data = $request->validate(['opening_cash' => 'required|numeric|min:0']);

        PosSession::create([
            'user_id'      => Auth::id(),
            'opening_cash' => $data['opening_cash'],
            'opened_at'    => now(),
        ]);

        return back()->with('success', 'POS session opened.');
    }

    public function closeSession(Request $request)
    {
        $session = PosSession::where('user_id', Auth::id())->whereNull('closed_at')->latest()->first();

        if (!$session) {
            return back()->withErrors(['error' => 'No open session found.']);
        }

        $request->validate([
            'closing_cash' => 'required|numeric|min:0',
            'notes'        => 'nullable|string',
        ]);

        $session->update([
            'closing_cash' => $request->closing_cash,
            'closed_at'    => now(),
            'notes'        => $request->notes,
        ]);

        return back()->with('success', 'Session closed.');
    }

    public function searchProduct(Request $request)
    {
        $q                  = $request->input('q', '');
        $categoryId         = $request->input('category');
        $allowedCategoryIds = Auth::user()->allowedCategoryIds();

        $products = Product::active()->inStock()
            ->when($allowedCategoryIds !== null, fn($query) => $query->whereIn('category_id', $allowedCategoryIds))
            ->when($categoryId, fn($query) => $query->where('category_id', $categoryId))
            ->where(fn($query) => $query
                ->where('name', 'like', "%{$q}%")
                ->orWhere('barcode', 'like', "%{$q}%")
                ->orWhere('sku', 'like', "%{$q}%")
                // Search purchase notes (IMEI numbers stored there)
                ->orWhereIn('id', fn($sub) => $sub
                    ->select('purchase_items.product_id')
                    ->from('purchase_items')
                    ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                    ->where('purchases.notes', 'like', "%{$q}%")
                )
            )
            ->with(['images', 'colors', 'category.section'])
            ->limit($q ? 15 : 60)
            ->get()
            ->map(fn($p) => [
                'id'               => $p->id,
                'name'             => $p->name,
                'barcode'          => $p->barcode,
                'price'            => $p->getDiscountedPrice(),
                'cost_price'       => $p->cost_price,
                'stock'            => $p->colors->count() > 0
                                        ? $p->colors->sum('stock_quantity')
                                        : $p->stock_quantity,
                'image'            => $p->primary_image_url,
                'exchange_eligible' => (bool)($p->category?->section?->exchange_enabled),
                'colors'           => $p->colors->map(fn($c) => [
                    'id'             => $c->id,
                    'name'           => $c->name,
                    'hex_code'       => $c->hex_code,
                    'stock_quantity' => $c->stock_quantity,
                ])->values(),
            ]);

        return response()->json($products);
    }

    public function getByBarcode(string $barcode)
    {
        $allowedCategoryIds = Auth::user()->allowedCategoryIds();

        // ── 1. Check product barcode first ────────────────────────────────────
        $product = Product::active()
            ->when($allowedCategoryIds !== null, fn($q) => $q->whereIn('category_id', $allowedCategoryIds))
            ->where('barcode', $barcode)
            ->with(['images', 'colors', 'category.section'])
            ->first();

        if ($product) {
            return response()->json([
                'id'               => $product->id,
                'name'             => $product->name,
                'barcode'          => $product->barcode,
                'price'            => $product->getDiscountedPrice(),
                'cost_price'       => $product->cost_price,
                'stock'            => $product->stock_quantity,
                'image'            => $product->primary_image_url,
                'exchange_eligible' => (bool)($product->category?->section?->exchange_enabled),
                'is_serialized'    => false,
                'serial_number'    => null,
                'serial_id'        => null,
                'colors'           => $product->colors->map(fn($c) => [
                    'id'             => $c->id,
                    'name'           => $c->name,
                    'hex_code'       => $c->hex_code,
                    'stock_quantity' => $c->stock_quantity,
                ])->values(),
            ]);
        }

        // ── 2. Check serial number (IMEI) ─────────────────────────────────────
        $serial = SerialNumber::where('serial_number', $barcode)
            ->where('status', 'in_stock')
            ->with(['product.images', 'product.category.section'])
            ->first();

        if ($serial) {
            $p = $serial->product;

            if (!$p || !$p->is_active) {
                return response()->json(['error' => 'Product is not available.'], 404);
            }

            if ($allowedCategoryIds !== null && !in_array($p->category_id, $allowedCategoryIds)) {
                return response()->json(['error' => 'Product not in your assigned sections.'], 404);
            }

            return response()->json([
                'id'               => $p->id,
                'name'             => $p->name,
                'barcode'          => $p->barcode,
                'price'            => $p->getDiscountedPrice(),
                'cost_price'       => $p->cost_price,
                'stock'            => 1,
                'image'            => $p->primary_image_url,
                'exchange_eligible' => (bool)($p->category?->section?->exchange_enabled),
                'is_serialized'    => true,
                'serial_number'    => $serial->serial_number,
                'serial_id'        => $serial->id,
                'colors'           => [],
            ]);
        }

        return response()->json([
            'error' => 'Not found. If scanning an IMEI, ensure it is registered in a purchase first.',
        ], 404);
    }

    public function searchCustomer(Request $request)
    {
        $q = $request->input('q', '');
        $customers = Customer::where('name', 'like', "%{$q}%")
                              ->orWhere('phone', 'like', "%{$q}%")
                              ->limit(10)
                              ->get(['id', 'name', 'phone', 'credit_balance']);

        return response()->json($customers);
    }

    public function createCustomer(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'phone' => 'required|string|max:20|unique:customers',
        ]);

        $customer = Customer::create(array_merge($data, [
            'source'        => 'pos',
            'khata_enabled' => true,
            'created_by'    => Auth::id(),
        ]));
        return response()->json($customer);
    }

    public function createOrder(Request $request)
    {
        $request->validate([
            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|exists:products,id',
            'items.*.quantity'      => 'required|integer|min:1',
            'items.*.unit_price'    => 'required|numeric|min:0',
            'items.*.color_id'      => 'nullable|exists:product_colors,id',
            'items.*.serial_number' => 'nullable|string|max:100',
            'payment_method'        => 'required|in:cash,bank_transfer,khata,partial,split',
            'amount_paid'         => 'nullable|numeric|min:0',
            'cash_amount'         => 'nullable|numeric|min:0',
            'bank_amount'         => 'nullable|numeric|min:0',
            'bank_account_id'     => 'nullable|exists:bank_accounts,id',
            'customer_id'         => 'nullable|exists:customers,id',
            'discount'            => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string|max:500',
            'exchange_item_name'  => 'nullable|string|max:200',
            'exchange_value'      => 'nullable|numeric|min:0',
            'promise_date'        => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            $customer = $request->customer_id ? Customer::find($request->customer_id) : null;

            $subtotal = 0;
            $orderItems = [];

            // Track serials being used in this order (prevent duplicates within one order)
            $usedSerials = [];

            foreach ($request->items as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);

                // ── Serial number validation for serialized products ──────────
                $serial = null;
                if ($product->is_serialized) {
                    $sn = trim($item['serial_number'] ?? '');
                    if ($sn === '') {
                        DB::rollBack();
                        return response()->json([
                            'error' => "Serial number required for serialized product: {$product->name}. Please scan the IMEI/serial number.",
                        ], 422);
                    }
                    if (isset($usedSerials[$sn])) {
                        DB::rollBack();
                        return response()->json([
                            'error' => "Duplicate serial number in this order: {$sn}",
                        ], 422);
                    }
                    $serial = SerialNumber::where('serial_number', $sn)
                        ->where('product_id', $product->id)
                        ->where('status', 'in_stock')
                        ->lockForUpdate()
                        ->first();
                    if (!$serial) {
                        DB::rollBack();
                        return response()->json([
                            'error' => "Serial {$sn} is not in stock for product: {$product->name}. It may have already been sold or not been registered.",
                        ], 422);
                    }
                    $usedSerials[$sn] = true;
                }

                // Resolve color (if specified)
                $color     = null;
                $colorName = null;
                if (!empty($item['color_id'])) {
                    $color = ProductColor::lockForUpdate()->find($item['color_id']);
                    if ($color) {
                        $colorName = $color->name;
                        if ($color->stock_quantity < $item['quantity']) {
                            DB::rollBack();
                            return response()->json(['error' => "Insufficient stock for: {$product->name} ({$color->name})"], 422);
                        }
                    }
                } elseif ($product->track_inventory && $product->stock_quantity < $item['quantity']) {
                    DB::rollBack();
                    return response()->json(['error' => "Insufficient stock for: {$product->name}"], 422);
                }

                $lineTotal = $item['unit_price'] * $item['quantity'];
                $subtotal += $lineTotal;

                $orderItems[] = [
                    'product'    => $product,
                    'color'      => $color,
                    'color_name' => $colorName,
                    'qty'        => $item['quantity'],
                    'price'      => $item['unit_price'],
                    'line_total' => $lineTotal,
                    'serial'     => $serial,
                ];
            }

            $discount      = (float)($request->discount ?? 0);
            $exchangeValue = (float)($request->exchange_value ?? 0);
            $total         = max(0, $subtotal - $discount - $exchangeValue);

            // Resolve payment details
            $payMethod   = $request->payment_method;
            $amountPaid  = null;
            $cashAmount  = null;
            $bankAmount  = null;
            $payStatus   = 'paid';

            if ($payMethod === 'khata') {
                $amountPaid = 0;
                $payStatus  = 'pending';
                if (!$customer) {
                    DB::rollBack();
                    return response()->json(['error' => 'A customer must be selected for Khata payment.'], 422);
                }
            } elseif ($payMethod === 'partial') {
                $amountPaid = min((float)($request->amount_paid ?? 0), $total);
                $payStatus  = $amountPaid >= $total ? 'paid' : 'partial';
                if (!$customer && $amountPaid < $total) {
                    DB::rollBack();
                    return response()->json(['error' => 'A customer must be selected for partial payment.'], 422);
                }
            } elseif ($payMethod === 'split') {
                $cashAmount = max(0, (float)($request->cash_amount ?? 0));
                $bankAmount = max(0, (float)($request->bank_amount ?? 0));
                $amountPaid = $cashAmount + $bankAmount;
                $payStatus  = 'paid';
            }

            $order = Order::create([
                'source'          => 'pos',
                'customer_id'     => $customer?->id,
                'customer_name'   => $customer?->name ?? 'Walk-in Customer',
                'customer_phone'  => $customer?->phone ?? '-',
                'subtotal'        => $subtotal,
                'discount_amount' => $discount,
                'total'           => $total,
                'amount_paid'     => $amountPaid,
                'cash_amount'     => $cashAmount,
                'bank_amount'     => $bankAmount,
                'payment_method'   => $payMethod,
                'payment_status'   => $payStatus,
                'bank_account_id'  => in_array($payMethod, ['bank_transfer', 'split'])
                                        ? $request->bank_account_id
                                        : null,
                'notes'              => $request->notes,
                'exchange_item_name' => $request->exchange_item_name ?: null,
                'exchange_value'     => $exchangeValue > 0 ? $exchangeValue : null,
                'status'             => 'delivered',
                'served_by'       => Auth::id(),
            ]);

            foreach ($orderItems as $item) {
                $orderItem = OrderItem::create([
                    'order_id'         => $order->id,
                    'product_id'       => $item['product']->id,
                    'product_name'     => $item['product']->name,
                    'color_name'       => $item['color_name'],
                    'color_id'         => $item['color']?->id,
                    'product_barcode'  => $item['product']->barcode,
                    'unit_price'       => $item['price'],
                    'cost_price'       => $item['product']->cost_price,
                    'quantity'         => $item['qty'],
                    'line_total'       => $item['line_total'],
                    'serial_number_id' => $item['serial']?->id,
                ]);

                // Mark serial number as sold
                if ($item['serial']) {
                    $item['serial']->update([
                        'status'        => 'sold',
                        'order_id'      => $order->id,
                        'order_item_id' => $orderItem->id,
                    ]);
                }

                // Decrement color stock (if applicable) and product total stock
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
                    'reference'       => $order->order_number,
                    'user_id'         => Auth::id(),
                ]);
            }

            // Build items summary for ledger description
            $itemsList = collect($orderItems)
                ->map(fn($i) => "{$i['product']->name} x{$i['qty']}")
                ->join(', ');

            // Khata ledger: full khata OR partial (remaining amount)
            $khataDue = 0;
            if ($payMethod === 'khata') {
                $khataDue = $total;
            } elseif ($payMethod === 'partial' && $payStatus === 'partial') {
                $khataDue = $total - $amountPaid;
            }

            if ($khataDue > 0 && $customer) {
                $newBal = $customer->credit_balance - $khataDue;
                $description = $payMethod === 'partial'
                    ? "Partial Payment — {$order->order_number} | Total: Rs.{$total} | Paid: Rs.{$amountPaid}, Khata: Rs.{$khataDue} | Items: {$itemsList}"
                    : "POS Sale — {$order->order_number} | Total: Rs.{$total} | Items: {$itemsList}";
                AccountLedger::create([
                    'customer_id'   => $customer->id,
                    'type'          => 'debit',
                    'amount'        => $khataDue,
                    'balance_after' => $newBal,
                    'description'   => $description,
                    'promise_date'  => $request->promise_date ?: null,
                    'reference'     => $order->order_number,
                    'user_id'       => Auth::id(),
                ]);
                $customer->update(['credit_balance' => $newBal]);
            }

            // Update POS session totals
            PosSession::where('user_id', Auth::id())->whereNull('closed_at')
                      ->increment('total_sales', $total);
            PosSession::where('user_id', Auth::id())->whereNull('closed_at')
                      ->increment('total_orders');

            DB::commit();

            return response()->json(['success' => true, 'order_id' => $order->id, 'order_number' => $order->order_number]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('POS store() failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['error' => 'Order failed: ' . $e->getMessage()], 500);
        }
    }

    public function receipt(Order $order)
    {
        $order->load(['items.product', 'servedBy', 'bankAccount']);
        $settings = [
            'shop_name'    => Setting::get('shop_name', 'MobileHub'),
            'shop_phone'   => Setting::get('shop_phone'),
            'shop_address' => Setting::get('shop_address'),
            'header'       => Setting::get('receipt_header'),
            'footer'       => Setting::get('receipt_footer'),
        ];
        return view('pos.receipt', compact('order', 'settings'));
    }

    // ── Delete Sale ───────────────────────────────────────────────────────

    public function deleteOrder(Order $order)
    {
        abort_if($order->source !== 'pos', 404);
        abort_if(in_array($order->status, ['returned', 'cancelled']), 403, 'This order cannot be deleted.');

        // Block deletion if returns are linked to this order
        if ($order->returns()->exists()) {
            return response()->json([
                'error' => 'This order has returns linked to it and cannot be deleted.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $oldTotal    = (float) $order->total;
            $oldMethod   = $order->payment_method;
            $oldCustomer = $order->customer_id
                ? \App\Models\Customer::find($order->customer_id)
                : null;

            // ── 1. Restore stock ──────────────────────────────────────────
            $items = $order->items()->get();

            foreach ($items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);
                if (! $product) continue;

                if ($item->color_id) {
                    $color = ProductColor::lockForUpdate()->find($item->color_id);
                    if ($color) {
                        $color->increment('stock_quantity', $item->quantity);
                    }
                }

                $before = $product->stock_quantity;
                $product->increment('stock_quantity', $item->quantity);

                StockMovement::create([
                    'product_id'      => $product->id,
                    'type'            => 'adjustment',
                    'quantity'        => $item->quantity,
                    'before_quantity' => $before,
                    'after_quantity'  => $before + $item->quantity,
                    'reference'       => $order->order_number,
                    'note'            => 'Sale deleted — stock restored',
                    'user_id'         => Auth::id(),
                ]);
            }

            // ── 2. Reverse khata ledger entries ───────────────────────────
            if ($oldCustomer && in_array($oldMethod, ['khata', 'partial'])) {
                $ledgers     = AccountLedger::where('reference', $order->order_number)
                    ->where('type', 'debit')
                    ->where('customer_id', $oldCustomer->id)
                    ->get();
                $ledgerTotal = $ledgers->sum('amount');
                if ($ledgerTotal > 0) {
                    $ledgers->each->delete();
                    $oldCustomer->increment('credit_balance', $ledgerTotal);
                }
            }

            // ── 3. Soft-delete the order (items kept for audit trail) ─────
            $order->deleted_by = Auth::id();
            $order->save();
            $order->delete(); // sets deleted_at via SoftDeletes

            // ── 4. Adjust open POS session ────────────────────────────────
            PosSession::where('user_id', Auth::id())
                ->whereNull('closed_at')
                ->decrement('total_sales', $oldTotal);

            PosSession::where('user_id', Auth::id())
                ->whereNull('closed_at')
                ->decrement('total_orders');

            DB::commit();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Delete failed: ' . $e->getMessage()], 500);
        }
    }

    // ── Edit Sale ─────────────────────────────────────────────────────────

    public function editOrder(Order $order)
    {
        abort_if($order->source !== 'pos', 404);
        abort_if(in_array($order->status, ['returned', 'cancelled']), 403, 'This order cannot be edited.');

        $order->load(['items.product', 'customer', 'bankAccount', 'servedBy']);
        $bankAccounts = BankAccount::active()->orderBy('sort_order')->orderBy('id')->get();

        $orderData = [
            'id'             => $order->id,
            'order_number'   => $order->order_number,
            'exchange_value' => (float) ($order->exchange_value ?? 0),
            'customer'       => $order->customer ? [
                'id'             => $order->customer->id,
                'name'           => $order->customer->name,
                'phone'          => $order->customer->phone,
                'credit_balance' => (float) $order->customer->credit_balance,
            ] : null,
            'payment_method'  => $order->payment_method,
            'discount_amount' => (float) ($order->discount_amount ?? 0),
            'amount_paid'     => (float) ($order->amount_paid ?? 0),
            'cash_amount'     => (float) ($order->cash_amount ?? 0),
            'bank_amount'     => (float) ($order->bank_amount ?? 0),
            'bank_account_id' => $order->bank_account_id,
            'notes'           => $order->notes ?? '',
            'items'           => $order->items->map(fn($item) => [
                'product_id'   => $item->product_id,
                'product_name' => $item->product_name,
                'color_id'     => $item->color_id,
                'color_name'   => $item->color_name,
                'qty'          => (int) $item->quantity,
                'unit_price'   => (float) $item->unit_price,
                'cost_price'   => (float) $item->cost_price,
            ])->values(),
        ];

        return view('pos.edit-order', compact('order', 'bankAccounts', 'orderData'));
    }

    public function updateOrder(Request $request, Order $order)
    {
        abort_if($order->source !== 'pos', 404);
        abort_if(in_array($order->status, ['returned', 'cancelled']), 403);

        $request->validate([
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'required|exists:products,id',
            'items.*.quantity'    => 'required|integer|min:1',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'items.*.color_id'    => 'nullable|exists:product_colors,id',
            'payment_method'      => 'required|in:cash,bank_transfer,khata,partial,split',
            'amount_paid'         => 'nullable|numeric|min:0',
            'cash_amount'         => 'nullable|numeric|min:0',
            'bank_amount'         => 'nullable|numeric|min:0',
            'bank_account_id'     => 'nullable|exists:bank_accounts,id',
            'customer_id'         => 'nullable|exists:customers,id',
            'discount'            => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string|max:500',
            'promise_date'        => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            $oldTotal    = (float) $order->total;
            $oldMethod   = $order->payment_method;
            $oldCustomer = $order->customer_id
                ? \App\Models\Customer::find($order->customer_id)
                : null;

            // ── 1. Restore stock from old items ───────────────────────────
            $oldItems = $order->items()->get();

            foreach ($oldItems as $oldItem) {
                $product = Product::lockForUpdate()->find($oldItem->product_id);
                if (! $product) continue;

                if ($oldItem->color_id) {
                    $color = ProductColor::lockForUpdate()->find($oldItem->color_id);
                    if ($color) {
                        $color->increment('stock_quantity', $oldItem->quantity);
                    }
                }

                $before = $product->stock_quantity;
                $product->increment('stock_quantity', $oldItem->quantity);

                StockMovement::create([
                    'product_id'      => $product->id,
                    'type'            => 'adjustment',
                    'quantity'        => $oldItem->quantity,
                    'before_quantity' => $before,
                    'after_quantity'  => $before + $oldItem->quantity,
                    'reference'       => $order->order_number,
                    'note'            => 'Sale edit — stock restored',
                    'user_id'         => Auth::id(),
                ]);
            }

            // ── 2. Reverse khata ledger entries ───────────────────────────
            if ($oldCustomer && in_array($oldMethod, ['khata', 'partial'])) {
                $oldLedgers  = AccountLedger::where('reference', $order->order_number)
                    ->where('type', 'debit')
                    ->where('customer_id', $oldCustomer->id)
                    ->get();
                $ledgerTotal = $oldLedgers->sum('amount');
                if ($ledgerTotal > 0) {
                    $oldLedgers->each->delete();
                    $oldCustomer->increment('credit_balance', $ledgerTotal);
                    $oldCustomer->refresh();
                }
            }

            // ── 3. Delete old order items ─────────────────────────────────
            $order->items()->delete();

            // ── 4. Build and validate new items ──────────────────────────
            $customer = $request->customer_id
                ? \App\Models\Customer::find($request->customer_id)
                : null;

            $subtotal = 0;
            $newItems = [];

            foreach ($request->items as $item) {
                $product   = Product::lockForUpdate()->find($item['product_id']);
                $color     = null;
                $colorName = null;

                if (! empty($item['color_id'])) {
                    $color = ProductColor::lockForUpdate()->find($item['color_id']);
                    if ($color) {
                        $colorName = $color->name;
                        if ($color->stock_quantity < $item['quantity']) {
                            DB::rollBack();
                            return response()->json([
                                'error' => "Insufficient stock for: {$product->name} ({$color->name})",
                            ], 422);
                        }
                    }
                } elseif ($product->track_inventory && $product->stock_quantity < $item['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'error' => "Insufficient stock for: {$product->name}",
                    ], 422);
                }

                $lineTotal  = $item['unit_price'] * $item['quantity'];
                $subtotal  += $lineTotal;

                $newItems[] = [
                    'product'    => $product,
                    'color'      => $color,
                    'color_name' => $colorName,
                    'qty'        => $item['quantity'],
                    'price'      => $item['unit_price'],
                    'line_total' => $lineTotal,
                ];
            }

            $discount      = (float) ($request->discount ?? 0);
            $exchangeValue = (float) ($order->exchange_value ?? 0); // preserved as-is
            $newTotal      = max(0, $subtotal - $discount - $exchangeValue);

            // Resolve payment details
            $payMethod  = $request->payment_method;
            $amountPaid = null;
            $cashAmount = null;
            $bankAmount = null;
            $payStatus  = 'paid';

            if ($payMethod === 'khata') {
                $amountPaid = 0;
                $payStatus  = 'pending';
                if (! $customer) {
                    DB::rollBack();
                    return response()->json(['error' => 'A customer is required for Khata payment.'], 422);
                }
            } elseif ($payMethod === 'partial') {
                $amountPaid = min((float) ($request->amount_paid ?? 0), $newTotal);
                $payStatus  = $amountPaid >= $newTotal ? 'paid' : 'partial';
                if (! $customer && $amountPaid < $newTotal) {
                    DB::rollBack();
                    return response()->json(['error' => 'A customer is required for partial payment.'], 422);
                }
            } elseif ($payMethod === 'split') {
                $cashAmount = max(0, (float) ($request->cash_amount ?? 0));
                $bankAmount = max(0, (float) ($request->bank_amount ?? 0));
                $amountPaid = $cashAmount + $bankAmount;
                $payStatus  = 'paid';
            }

            // ── 5. Save new items and deduct stock ────────────────────────
            foreach ($newItems as $item) {
                OrderItem::create([
                    'order_id'        => $order->id,
                    'product_id'      => $item['product']->id,
                    'product_name'    => $item['product']->name,
                    'color_name'      => $item['color_name'],
                    'color_id'        => $item['color']?->id,
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
                    'reference'       => $order->order_number,
                    'note'            => 'Sale edit',
                    'user_id'         => Auth::id(),
                ]);
            }

            // ── 6. New khata ledger entry ─────────────────────────────────
            $khataDue = 0;
            if ($payMethod === 'khata') {
                $khataDue = $newTotal;
            } elseif ($payMethod === 'partial' && $payStatus === 'partial') {
                $khataDue = $newTotal - $amountPaid;
            }

            if ($khataDue > 0 && $customer) {
                $customer->refresh();
                $itemsList = collect($newItems)
                    ->map(fn($i) => "{$i['product']->name} x{$i['qty']}")
                    ->join(', ');
                $newBal = $customer->credit_balance - $khataDue;

                AccountLedger::create([
                    'customer_id'   => $customer->id,
                    'type'          => 'debit',
                    'amount'        => $khataDue,
                    'balance_after' => $newBal,
                    'description'   => "Edited Sale — {$order->order_number} | Total: Rs.{$newTotal} | Items: {$itemsList}",
                    'promise_date'  => $request->promise_date ?: null,
                    'reference'     => $order->order_number,
                    'user_id'       => Auth::id(),
                ]);
                $customer->update(['credit_balance' => $newBal]);
            }

            // ── 7. Update order record ────────────────────────────────────
            $order->update([
                'customer_id'     => $customer?->id,
                'customer_name'   => $customer?->name ?? 'Walk-in Customer',
                'customer_phone'  => $customer?->phone ?? '-',
                'subtotal'        => $subtotal,
                'discount_amount' => $discount,
                'total'           => $newTotal,
                'amount_paid'     => $amountPaid,
                'cash_amount'     => $cashAmount,
                'bank_amount'     => $bankAmount,
                'payment_method'  => $payMethod,
                'payment_status'  => $payStatus,
                'bank_account_id' => in_array($payMethod, ['bank_transfer', 'split'])
                                        ? $request->bank_account_id : null,
                'notes'           => $request->notes,
            ]);

            // ── 8. Adjust open POS session total ─────────────────────────
            $totalDiff = $newTotal - $oldTotal;
            if ($totalDiff != 0) {
                PosSession::where('user_id', Auth::id())
                    ->whereNull('closed_at')
                    ->increment('total_sales', $totalDiff);
            }

            DB::commit();

            return response()->json([
                'success'      => true,
                'order_id'     => $order->id,
                'order_number' => $order->order_number,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }
}
