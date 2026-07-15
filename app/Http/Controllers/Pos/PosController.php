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
use App\Models\Vendor;
use App\Models\Category;
use App\Models\HeldOrder;
use App\Models\VendorLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function index()
    {
        $user    = Auth::user();
        $shopId  = $user->shopId();
        $session = PosSession::where('user_id', $user->id)->whereNull('closed_at')->latest()->first();

        // Section-based filtering: admins see everything; salesmen see only their sections
        $allowedCategoryIds = $user->allowedCategoryIds();

        $prodQuery = Product::active()->inStockForShop($shopId)->with('images');

        $catQuery = \App\Models\Category::active()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->with(['children' => function ($q) use ($allowedCategoryIds) {
                $q->active()->orderBy('sort_order')->orderBy('name');
                if ($allowedCategoryIds !== null) {
                    $q->whereIn('id', $allowedCategoryIds);
                }
            }]);

        if ($allowedCategoryIds !== null) {
            // Show parent categories that are in the allowed list OR have allowed children
            $catQuery->where(function ($q) use ($allowedCategoryIds) {
                $q->whereIn('id', $allowedCategoryIds)
                  ->orWhereHas('children', fn ($c) => $c->whereIn('id', $allowedCategoryIds));
            });
            $prodQuery->whereIn('category_id', $allowedCategoryIds);
        }

        $categories       = $catQuery->get();
        $featuredProducts = $prodQuery->limit(20)->get();

        $isAdmin = $user->hasRole('admin');

        // Daily sales orders with items (location-scoped; salesman sees only their own)
        $todaySalesOrders = Order::where('source', 'pos')
            ->forShop($shopId)
            ->whereDate('created_at', today())
            ->where('status', 'delivered')
            ->when(!$isAdmin && !$shopId, fn($q) => $q->where('served_by', $user->id))
            ->with(['items', 'customer'])
            ->latest()
            ->get();

        // Daily returns with items (salesman sees only returns they processed)
        $todayReturnsList = \App\Models\ReturnOrder::whereDate('created_at', today())
            ->whereHas('order', fn($q) => $q->forShop($shopId))
            ->when(!$isAdmin && !$shopId, fn($q) => $q->where('processed_by', $user->id))
            ->with(['items', 'order'])
            ->latest()
            ->get();

        // Daily customer payments received (khata credit entries — salesman sees their own; excludes return credits)
        $todayPaymentsList = \App\Models\AccountLedger::whereDate('created_at', today())
            ->where('type', 'credit')
            ->whereNull('return_id')
            ->whereHas('customer', fn($q) => $q->forShop($shopId))
            ->when(!$isAdmin && !$shopId, fn($q) => $q->where('user_id', $user->id))
            ->with(['customer', 'user'])
            ->latest()
            ->get();

        // Vendor payments paid out today (admin only — manual debit entries on vendor ledger)
        $todayVendorPayList = $isAdmin
            ? VendorLedger::whereDate('created_at', today())
                ->where('type', 'debit')
                ->whereNull('purchase_id')
                ->whereNotNull('payment_method')
                ->with(['vendor', 'bankAccount'])
                ->latest()
                ->get()
            : collect();

        // Aggregated totals for the summary bar
        $cashTotal = $todaySalesOrders->sum(function ($o) {
            return match ($o->payment_method) {
                'cash'    => $o->total,
                'split'   => $o->cash_amount ?? 0,
                'partial' => $o->bank_account_id ? 0 : ($o->amount_paid ?? 0),
                default   => 0,
            };
        });
        $bankTotal = $todaySalesOrders->sum(function ($o) {
            return match ($o->payment_method) {
                'bank_transfer' => $o->total,
                'split'         => $o->bank_amount ?? 0,
                'partial'       => $o->bank_account_id ? ($o->amount_paid ?? 0) : 0,
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
        $todayVendorPayments = (object)[
            'vendor_pay_count' => $todayVendorPayList->count(),
            'vendor_pay_total' => (float) $todayVendorPayList->sum('amount'),
            'vendor_pay_cash'  => (float) $todayVendorPayList->where('payment_method', 'cash')->sum('amount'),
            'vendor_pay_bank'  => (float) $todayVendorPayList->where('payment_method', 'bank_transfer')->sum('amount'),
        ];

        $bankAccounts = BankAccount::active()->forShop($shopId)->orderBy('sort_order')->orderBy('id')->get();
        $canViewCost  = $user->can('products.view_cost');
        $currentShop  = $shopId ? $user->shop : null;

        return view('pos.index', compact(
            'session', 'categories', 'featuredProducts',
            'todaySales', 'todayReturns', 'todayPayments', 'todayVendorPayments',
            'todaySalesOrders', 'todayReturnsList', 'todayPaymentsList', 'todayVendorPayList',
            'bankAccounts', 'canViewCost', 'currentShop'
        ));
    }

    public function stats(): \Illuminate\Http\JsonResponse
    {
        $user    = Auth::user();
        $isAdmin = $user->hasRole('admin');
        $shopId  = $user->shopId();

        $todaySalesOrders = Order::where('source', 'pos')
            ->forShop($shopId)
            ->whereDate('created_at', today())
            ->where('status', 'delivered')
            ->when(!$isAdmin && !$shopId, fn($q) => $q->where('served_by', $user->id))
            ->get(['payment_method', 'total', 'cash_amount', 'bank_amount', 'amount_paid', 'bank_account_id']);

        $cashTotal = $todaySalesOrders->sum(function ($o) {
            return match ($o->payment_method) {
                'cash'    => $o->total,
                'split'   => $o->cash_amount ?? 0,
                'partial' => $o->bank_account_id ? 0 : ($o->amount_paid ?? 0),
                default   => 0,
            };
        });
        $bankTotal = $todaySalesOrders->sum(function ($o) {
            return match ($o->payment_method) {
                'bank_transfer' => $o->total,
                'split'         => $o->bank_amount ?? 0,
                'partial'       => $o->bank_account_id ? ($o->amount_paid ?? 0) : 0,
                default         => 0,
            };
        });

        $todayReturnsList = \App\Models\ReturnOrder::whereDate('created_at', today())
            ->whereHas('order', fn($q) => $q->forShop($shopId))
            ->when(!$isAdmin && !$shopId, fn($q) => $q->where('processed_by', $user->id))
            ->get(['refund_amount']);

        $todayPaymentsList = \App\Models\AccountLedger::whereDate('created_at', today())
            ->where('type', 'credit')
            ->whereNull('return_id')
            ->whereHas('customer', fn($q) => $q->forShop($shopId))
            ->when(!$isAdmin && !$shopId, fn($q) => $q->where('user_id', $user->id))
            ->get(['amount']);

        $vendorPayData = $isAdmin
            ? VendorLedger::whereDate('created_at', today())
                ->where('type', 'debit')->whereNull('purchase_id')->whereNotNull('payment_method')
                ->get(['payment_method', 'amount'])
            : collect();

        return response()->json([
            'order_count'      => $todaySalesOrders->count(),
            'total_revenue'    => (float) $todaySalesOrders->sum('total'),
            'cash_total'       => (float) $cashTotal,
            'bank_total'       => (float) $bankTotal,
            'return_count'     => $todayReturnsList->count(),
            'total_refunded'   => (float) $todayReturnsList->sum('refund_amount'),
            'payment_count'    => $todayPaymentsList->count(),
            'total_collected'  => (float) $todayPaymentsList->sum('amount'),
            'vendor_pay_count' => $vendorPayData->count(),
            'vendor_pay_total' => (float) $vendorPayData->sum('amount'),
            'vendor_pay_cash'  => (float) $vendorPayData->where('payment_method', 'cash')->sum('amount'),
            'vendor_pay_bank'  => (float) $vendorPayData->where('payment_method', 'bank_transfer')->sum('amount'),
        ]);
    }

    public function todayActivity(): \Illuminate\Http\JsonResponse
    {
        $user    = Auth::user();
        $isAdmin = $user->hasRole('admin');
        $shopId  = $user->shopId();

        $orders = Order::where('source', 'pos')
            ->forShop($shopId)
            ->whereDate('created_at', today())
            ->where('status', 'delivered')
            ->when(!$isAdmin && !$shopId, fn($q) => $q->where('served_by', $user->id))
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
            ->whereHas('order', fn($q) => $q->forShop($shopId))
            ->when(!$isAdmin && !$shopId, fn($q) => $q->where('processed_by', $user->id))
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
            ->whereNull('return_id')
            ->whereHas('customer', fn($q) => $q->forShop($shopId))
            ->when(!$isAdmin && !$shopId, fn($q) => $q->where('user_id', $user->id))
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

        $vendor_payments = $isAdmin
            ? VendorLedger::whereDate('created_at', today())
                ->where('type', 'debit')->whereNull('purchase_id')->whereNotNull('payment_method')
                ->latest()->with(['vendor', 'bankAccount'])
                ->get()
                ->map(fn($v) => [
                    'time'            => $v->created_at->format('H:i'),
                    'vendor_name'     => $v->vendor?->name ?? '—',
                    'description'     => $v->description ?? null,
                    'payment_method'  => $v->payment_method,
                    'bank_label'      => $v->bankAccount?->label ?? null,
                    'amount'          => (float) $v->amount,
                    'balance_after'   => (float) $v->balance_after,
                ])->values()
            : collect()->values();

        return response()->json(compact('orders', 'returns', 'payments', 'vendor_payments'));
    }

    public function openSession(Request $request)
    {
        $data = $request->validate(['opening_cash' => 'required|numeric|min:0']);

        PosSession::create([
            'user_id'      => Auth::id(),
            'shop_id'      => Auth::user()->shopId(),
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
        $shopId             = Auth::user()->shopId();
        $allowedCategoryIds = Auth::user()->allowedCategoryIds();

        // Resolve the selected category: a subcategory filters by subcategory_id,
        // a top-level (or "All [Parent]") category filters by category_id.
        $selectedCat = $categoryId ? \App\Models\Category::find($categoryId) : null;

        $products = Product::active()->inStockForShop($shopId)
            ->when($allowedCategoryIds !== null, fn($query) => $query->whereIn('category_id', $allowedCategoryIds))
            ->when($selectedCat, function ($query) use ($selectedCat) {
                if ($selectedCat->parent_id) {
                    $query->where('subcategory_id', $selectedCat->id);
                } else {
                    $query->where('category_id', $selectedCat->id);
                }
            })
            ->where(fn($query) => $query
                ->where('name', 'like', "%{$q}%")
                ->orWhere('barcode', 'like', "%{$q}%")
                ->orWhere('sku', 'like', "%{$q}%")
            )
            ->with([
                'images', 'colors', 'category.section',
                'serialNumbers' => fn($s) => $s->where('status', 'in_stock')->forShop($shopId)
                    ->select('id', 'product_id', 'selling_price'),
            ])
            ->when($shopId, fn($query) => $query->with(['shopStocks' => fn($s) => $s->where('shop_id', $shopId)]))
            ->limit($q ? 15 : 60)
            ->get()
            ->map(fn($p) => [
                '_key'             => 'p_' . $p->id,
                'id'               => $p->id,
                'name'             => $p->name,
                'barcode'          => $p->barcode,
                'price'            => $p->getDiscountedPrice(),
                'price_from'       => self::serialPriceFrom($p),
                'cost_price'       => $p->cost_price,
                'stock'            => $p->is_serialized
                                        ? $p->serialNumbers->count()
                                        : ($shopId
                                            ? (int) $p->shopStocks->sum('quantity')
                                            : ($p->colors->count() > 0
                                                ? $p->colors->sum('stock_quantity')
                                                : $p->stock_quantity)),
                'image'            => $p->primary_image_url,
                'exchange_eligible' => (bool)($p->category?->section?->exchange_enabled),
                'is_serialized'    => (bool) $p->is_serialized,
                'track_inventory'  => (bool) $p->track_inventory,
                'serial_number'    => null,
                'serial_id'        => null,
                'colors'           => $p->colors->map(fn($c) => [
                    'id'             => $c->id,
                    'name'           => $c->name,
                    'hex_code'       => $c->hex_code,
                    'stock_quantity' => $shopId
                        ? (int) ($p->shopStocks->firstWhere('product_color_id', $c->id)?->quantity ?? 0)
                        : $c->stock_quantity,
                ])->values(),
            ]);

        // ── Serial (IMEI) search — appended when a search query is present ──
        $serialResults = collect();
        if ($q) {
            $serialResults = SerialNumber::where('serial_number', 'like', "%{$q}%")
                ->where('status', 'in_stock')
                ->forShop($shopId)
                ->with(['product.images', 'product.category.section'])
                ->limit(10)
                ->get()
                ->filter(fn($sn) => $sn->product && $sn->product->is_active)
                ->when(
                    $allowedCategoryIds !== null,
                    fn($c) => $c->filter(fn($sn) => in_array($sn->product->category_id, $allowedCategoryIds))
                )
                ->map(function ($sn) {
                    $p = $sn->product;
                    $sellingPrice = $sn->selling_price ? (float) $sn->selling_price : $p->getDiscountedPrice();
                    $costPrice    = $sn->cost_price    ? (float) $sn->cost_price    : (float) $p->cost_price;
                    return [
                        '_key'             => 's_' . $sn->id,
                        'id'               => $p->id,
                        'name'             => $p->name,
                        'barcode'          => $p->barcode,
                        'price'            => $sellingPrice,
                        'cost_price'       => $costPrice,
                        'stock'            => 1,
                        'image'            => $p->primary_image_url,
                        'exchange_eligible' => (bool)($p->category?->section?->exchange_enabled),
                        'is_serialized'    => true,
                        'serial_number'    => $sn->serial_number,
                        'serial_id'        => $sn->id,
                        'attributes'       => $sn->attributes ?? [],
                        'colors'           => [],
                    ];
                });
        }

        return response()->json($products->concat($serialResults)->values());
    }

    /**
     * Lowest selling price among the product's loaded in-stock serials
     * (serials without their own price fall back to the product price).
     * Null for non-serialized products or when no serials are in stock —
     * the POS tile then shows "Out of stock" instead of a price.
     */
    private static function serialPriceFrom(Product $p): ?float
    {
        if (! $p->is_serialized) {
            return null;
        }

        return $p->serialNumbers
            ->map(fn($s) => $s->selling_price ? (float) $s->selling_price : (float) $p->getDiscountedPrice())
            ->min();
    }

    /**
     * Full product catalog for offline POS use. The salesman's browser caches
     * this while online so products (and serialized units) remain browsable
     * after the connection drops. Mirrors searchProduct()'s item shape, plus
     * category ids and embedded in-stock serials for client-side filtering.
     */
    public function catalog()
    {
        $shopId             = Auth::user()->shopId();
        $allowedCategoryIds = Auth::user()->allowedCategoryIds();

        $products = Product::active()->inStockForShop($shopId)
            ->when($allowedCategoryIds !== null, fn($q) => $q->whereIn('category_id', $allowedCategoryIds))
            ->with([
                'images',
                'colors',
                'category.section',
                'serialNumbers' => fn($q) => $q->where('status', 'in_stock')->forShop($shopId)->orderBy('serial_number'),
            ])
            ->when($shopId, fn($q) => $q->with(['shopStocks' => fn($s) => $s->where('shop_id', $shopId)]))
            ->get()
            ->map(fn($p) => [
                '_key'              => 'p_' . $p->id,
                'id'                => $p->id,
                'name'              => $p->name,
                'sku'               => $p->sku,
                'barcode'           => $p->barcode,
                'price'             => $p->getDiscountedPrice(),
                'price_from'        => self::serialPriceFrom($p),
                'cost_price'        => $p->cost_price,
                'stock'             => $p->is_serialized
                                        ? $p->serialNumbers->count()
                                        : ($shopId
                                            ? (int) $p->shopStocks->sum('quantity')
                                            : ($p->colors->count() > 0
                                                ? $p->colors->sum('stock_quantity')
                                                : $p->stock_quantity)),
                'image'             => $p->primary_image_url,
                'exchange_eligible' => (bool)($p->category?->section?->exchange_enabled),
                'is_serialized'     => (bool) $p->is_serialized,
                'track_inventory'   => (bool) $p->track_inventory,
                'serial_number'     => null,
                'serial_id'         => null,
                'category_id'       => $p->category_id,
                'subcategory_id'    => $p->subcategory_id,
                'colors'            => $p->colors->map(fn($c) => [
                    'id'             => $c->id,
                    'name'           => $c->name,
                    'hex_code'       => $c->hex_code,
                    'stock_quantity' => $shopId
                        ? (int) ($p->shopStocks->firstWhere('product_color_id', $c->id)?->quantity ?? 0)
                        : $c->stock_quantity,
                ])->values(),
                'serials'           => $p->is_serialized
                    ? $p->serialNumbers->map(fn($s) => [
                        'id'            => $s->id,
                        'serial_number' => $s->serial_number,
                        'selling_price' => $s->selling_price ? (float) $s->selling_price : (float) $p->getDiscountedPrice(),
                        'cost_price'    => $s->cost_price    ? (float) $s->cost_price    : (float) $p->cost_price,
                        'attributes'    => $s->attributes ?? [],
                    ])->values()
                    : [],
            ]);

        return response()->json($products);
    }

    /**
     * Customers + vendors for offline POS use. Cached in the browser so the
     * salesman can still pick a customer / vendor for khata sales offline.
     */
    public function offlineCustomers()
    {
        $shopId = Auth::user()->shopId();

        $customers = Customer::forShop($shopId)->orderBy('name')
            ->get(['id', 'name', 'phone', 'credit_balance'])
            ->map(fn($c) => [
                'id'             => $c->id,
                'name'           => $c->name,
                'phone'          => $c->phone ?? '',
                'credit_balance' => (float) $c->credit_balance,
                'type'           => 'customer',
            ]);

        // Vendors are a main-shop concern — sub shops never sell to vendors
        $vendors = $shopId
            ? collect()
            : Vendor::orderBy('name')
                ->get(['id', 'name', 'phone', 'balance'])
                ->map(fn($v) => [
                    'id'             => $v->id,
                    'name'           => $v->name,
                    'phone'          => $v->phone ?? '',
                    'credit_balance' => (float) ($v->balance * -1),
                    'type'           => 'vendor',
                ]);

        return response()->json($customers->concat($vendors)->values());
    }

    public function getByBarcode(string $barcode)
    {
        $shopId             = Auth::user()->shopId();
        $allowedCategoryIds = Auth::user()->allowedCategoryIds();

        // ── 1. Check product barcode first ────────────────────────────────────
        $product = Product::active()
            ->when($allowedCategoryIds !== null, fn($q) => $q->whereIn('category_id', $allowedCategoryIds))
            ->where('barcode', $barcode)
            ->with([
                'images', 'colors', 'category.section',
                'serialNumbers' => fn($s) => $s->where('status', 'in_stock')->forShop($shopId)
                    ->select('id', 'product_id', 'selling_price'),
            ])
            ->when($shopId, fn($q) => $q->with(['shopStocks' => fn($s) => $s->where('shop_id', $shopId)]))
            ->first();

        if ($product) {
            return response()->json([
                'id'               => $product->id,
                'name'             => $product->name,
                'barcode'          => $product->barcode,
                'price'            => $product->getDiscountedPrice(),
                'price_from'       => self::serialPriceFrom($product),
                'cost_price'       => $product->cost_price,
                'stock'            => $product->is_serialized
                                        ? $product->serialNumbers->count()
                                        : ($shopId
                                            ? (int) $product->shopStocks->sum('quantity')
                                            : $product->stock_quantity),
                'image'            => $product->primary_image_url,
                'exchange_eligible' => (bool)($product->category?->section?->exchange_enabled),
                'is_serialized'    => (bool) $product->is_serialized,
                'serial_number'    => null,
                'serial_id'        => null,
                'colors'           => $product->colors->map(fn($c) => [
                    'id'             => $c->id,
                    'name'           => $c->name,
                    'hex_code'       => $c->hex_code,
                    'stock_quantity' => $shopId
                        ? (int) ($product->shopStocks->firstWhere('product_color_id', $c->id)?->quantity ?? 0)
                        : $c->stock_quantity,
                ])->values(),
            ]);
        }

        // ── 2. Check serial number (IMEI) ─────────────────────────────────────
        $serial = SerialNumber::where('serial_number', $barcode)
            ->where('status', 'in_stock')
            ->forShop($shopId)
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

            // Use serial's own selling price if set, otherwise fall back to product price
            $sellingPrice = $serial->selling_price ? (float) $serial->selling_price : $p->getDiscountedPrice();
            $costPrice    = $serial->cost_price    ? (float) $serial->cost_price    : (float) $p->cost_price;

            return response()->json([
                'id'               => $p->id,
                'name'             => $p->name,
                'barcode'          => $p->barcode,
                'price'            => $sellingPrice,
                'cost_price'       => $costPrice,
                'stock'            => 1,
                'image'            => $p->primary_image_url,
                'exchange_eligible' => (bool)($p->category?->section?->exchange_enabled),
                'is_serialized'    => true,
                'serial_number'    => $serial->serial_number,
                'serial_id'        => $serial->id,
                'selling_price'    => $sellingPrice,
                'attributes'       => $serial->attributes ?? [],
                'colors'           => [],
            ]);
        }

        return response()->json([
            'error' => 'Not found. If scanning an IMEI, ensure it is registered in a purchase first.',
        ], 404);
    }

    public function getProductSerials(int $productId)
    {
        $allowedCategoryIds = Auth::user()->allowedCategoryIds();

        $product = Product::active()
            ->when($allowedCategoryIds !== null, fn($q) => $q->whereIn('category_id', $allowedCategoryIds))
            ->where('id', $productId)
            ->where('is_serialized', true)
            ->firstOrFail();

        $serials = SerialNumber::where('product_id', $product->id)
            ->where('status', 'in_stock')
            ->forShop(Auth::user()->shopId())
            ->orderBy('serial_number')
            ->get()
            ->map(fn($s) => [
                'id'            => $s->id,
                'serial_number' => $s->serial_number,
                'selling_price' => $s->selling_price ? (float) $s->selling_price : (float) $product->getDiscountedPrice(),
                'cost_price'    => $s->cost_price    ? (float) $s->cost_price    : (float) $product->cost_price,
                'attributes'    => $s->attributes ?? [],
            ]);

        return response()->json([
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'image'        => $product->primary_image_url,
            'exchange_eligible' => (bool)($product->category?->section?->exchange_enabled ?? false),
            'serials'      => $serials,
        ]);
    }

    public function searchCustomer(Request $request)
    {
        $q      = $request->input('q', '');
        $shopId = Auth::user()->shopId();

        $customers = Customer::where('source', '!=', 'online')
            ->forShop($shopId)
            ->where(fn($q2) => $q2->where('name', 'like', "%{$q}%")
                                  ->orWhere('phone', 'like', "%{$q}%"))
            ->limit(8)
            ->get(['id', 'name', 'phone', 'credit_balance', 'khata_enabled'])
            ->map(fn($c) => [
                'id'             => $c->id,
                'name'           => $c->name,
                'phone'          => $c->phone,
                'credit_balance' => (float) $c->credit_balance,
                'khata_enabled'  => (bool) $c->khata_enabled,
                'type'           => 'customer',
            ]);

        // Vendors are a main-shop concern — sub shops never sell to vendors
        $vendors = $shopId
            ? collect()
            : Vendor::where(fn($q2) => $q2->where('name', 'like', "%{$q}%")
                                          ->orWhere('phone', 'like', "%{$q}%"))
                ->limit(5)
                ->get(['id', 'name', 'phone', 'balance', 'khata_enabled'])
                ->map(fn($v) => [
                    'id'             => $v->id,
                    'name'           => $v->name,
                    'phone'          => $v->phone ?? '',
                    // Negative vendor balance = vendor owes us; matches customer credit_balance semantics
                    'credit_balance' => (float) ($v->balance * -1),
                    'khata_enabled'  => (bool) $v->khata_enabled,
                    'type'           => 'vendor',
                ]);

        return response()->json($customers->concat($vendors)->values());
    }

    public function createCustomer(Request $request)
    {
        $shopId = Auth::user()->shopId();

        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'phone' => [
                'required', 'string', 'max:20',
                // Phone is unique per shop (NULL shop_id = main shop)
                \Illuminate\Validation\Rule::unique('customers', 'phone')->where(
                    fn($q) => $shopId ? $q->where('shop_id', $shopId) : $q->whereNull('shop_id')
                ),
            ],
        ]);

        $khataEnabled = $request->boolean('khata_enabled');
        $customer = Customer::create(array_merge($data, [
            'shop_id'       => $shopId,
            'source'        => 'pos',
            'khata_enabled' => $khataEnabled,
            'created_by'    => Auth::id(),
        ]));

        return response()->json([
            'id'            => $customer->id,
            'name'          => $customer->name,
            'phone'         => $customer->phone,
            'credit_balance'=> 0,
            'khata_enabled' => $khataEnabled,
            'type'          => 'customer',
        ]);
    }

    public function createOrder(Request $request)
    {
        $shopId = Auth::user()->shopId();

        $request->validate([
            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|exists:products,id',
            'items.*.quantity'      => 'required|integer|min:1',
            'items.*.unit_price'    => 'required|numeric|min:0',
            'items.*.color_id'      => 'nullable|exists:product_colors,id',
            'items.*.serial_number' => 'nullable|string|max:100',
            'payment_method'           => 'required|in:cash,bank_transfer,khata,partial,split',
            'amount_paid'              => 'nullable|numeric|min:0',
            'partial_pay_via'          => 'nullable|in:cash,bank',
            'partial_bank_account_id'  => 'nullable|exists:bank_accounts,id',
            'cash_amount'              => 'nullable|numeric|min:0',
            'bank_amount'              => 'nullable|numeric|min:0',
            'bank_account_id'          => 'nullable|exists:bank_accounts,id',
            'customer_id'         => 'nullable|exists:customers,id',
            'vendor_id'           => 'nullable|exists:vendors,id',
            'discount'            => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string|max:500',
            'exchange_item_name'  => 'nullable|string|max:200',
            'exchange_value'      => 'nullable|numeric|min:0',
            'promise_date'        => 'nullable|date',
            'offline_ref'         => 'nullable|string|max:50',
            'offline_created_at'  => 'nullable|date',
        ]);

        // Idempotency: if this offline sale was already synced, return the
        // existing order instead of creating a duplicate (handles lost responses).
        if ($request->filled('offline_ref')) {
            $existing = Order::where('offline_ref', $request->offline_ref)->first();
            if ($existing) {
                return response()->json([
                    'success'      => true,
                    'order_id'     => $existing->id,
                    'order_number' => $existing->order_number,
                    'already_synced' => true,
                ]);
            }
        }

        // Sub shop guards: no vendor sales; customers and banks must belong to this shop
        if ($shopId) {
            if ($request->filled('vendor_id')) {
                return response()->json(['error' => 'Vendor sales are only available at the main shop.'], 422);
            }
        }
        if ($request->filled('customer_id')) {
            $custShop = Customer::find($request->customer_id)?->shop_id;
            if ($custShop !== $shopId) {
                return response()->json(['error' => 'This customer belongs to a different shop.'], 422);
            }
        }
        foreach (['bank_account_id', 'partial_bank_account_id'] as $bankField) {
            if ($request->filled($bankField)
                && !BankAccount::forShop($shopId)->where('id', $request->$bankField)->exists()) {
                return response()->json(['error' => 'The selected bank account belongs to a different shop.'], 422);
            }
        }

        DB::beginTransaction();
        try {
            $customer = $request->customer_id ? Customer::find($request->customer_id) : null;
            $vendor   = $request->vendor_id   ? Vendor::find($request->vendor_id)     : null;

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
                        ->forShop($shopId)
                        ->lockForUpdate()
                        ->first();
                    if (!$serial) {
                        DB::rollBack();
                        return response()->json([
                            'error' => "Serial {$sn} is not in stock at this shop for product: {$product->name}. It may have already been sold, transferred, or not been registered.",
                        ], 422);
                    }
                    $usedSerials[$sn] = true;
                }

                // Resolve color (if specified) and check stock at this location
                $color     = null;
                $colorName = null;
                if (!empty($item['color_id'])) {
                    $color = ProductColor::lockForUpdate()->find($item['color_id']);
                    if ($color) {
                        $colorName = $color->name;
                        $colorStock = $shopId
                            ? $product->stockForShop($shopId, $color->id)
                            : $color->stock_quantity;
                        if ($colorStock < $item['quantity']) {
                            DB::rollBack();
                            return response()->json(['error' => "Insufficient stock for: {$product->name} ({$color->name})"], 422);
                        }
                    }
                } elseif ($product->track_inventory
                    && ($shopId ? $product->stockForShop($shopId) : $product->stock_quantity) < $item['quantity']) {
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
                if (!$customer && !$vendor) {
                    DB::rollBack();
                    return response()->json(['error' => 'A customer or vendor must be selected for Khata payment.'], 422);
                }
            } elseif ($payMethod === 'partial') {
                $amountPaid = min((float)($request->amount_paid ?? 0), $total);
                $payStatus  = $amountPaid >= $total ? 'paid' : 'partial';
                if (!$customer && !$vendor && $amountPaid < $total) {
                    DB::rollBack();
                    return response()->json(['error' => 'A customer or vendor must be selected for partial payment.'], 422);
                }
            } elseif ($payMethod === 'split') {
                $cashAmount = max(0, (float)($request->cash_amount ?? 0));
                $bankAmount = max(0, (float)($request->bank_amount ?? 0));
                $amountPaid = $cashAmount + $bankAmount;
                $payStatus  = 'paid';
            }

            $order = Order::create([
                'source'          => 'pos',
                'shop_id'         => $shopId,
                'offline_ref'     => $request->offline_ref ?: null,
                'customer_id'     => $customer?->id,
                'vendor_id'       => $vendor?->id,
                'customer_name'   => $customer?->name ?? $vendor?->name ?? 'Walk-in Customer',
                'customer_phone'  => $customer?->phone ?? $vendor?->phone ?? '-',
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
                                        : ($payMethod === 'partial' && $request->partial_pay_via === 'bank'
                                            ? $request->partial_bank_account_id
                                            : null),
                'notes'              => $request->notes,
                'exchange_item_name' => $request->exchange_item_name ?: null,
                'exchange_value'     => $exchangeValue > 0 ? $exchangeValue : null,
                'status'             => 'delivered',
                'served_by'       => Auth::id(),
            ]);

            // Preserve the original sale time for offline sales synced later,
            // so the order lands in the correct day's books.
            if ($request->filled('offline_created_at')) {
                $order->forceFill([
                    'created_at' => \Carbon\Carbon::parse($request->offline_created_at),
                ])->save();
            }

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

                // Decrement stock at this location (main columns or shop_stocks)
                app(\App\Services\ShopStockService::class)->adjust(
                    $shopId, $item['product'], $item['color']?->id, -$item['qty'],
                    'sale', $order->order_number
                );
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

            if ($khataDue > 0 && $vendor) {
                // Vendor buying on credit: reduces our payable to them (debit entry)
                // If vendor.balance goes negative, it means vendor owes us money
                $newBal = $vendor->balance - $khataDue;
                $partialVia = $request->partial_pay_via === 'bank' ? 'Bank' : 'Cash';
                $description = $payMethod === 'partial'
                    ? "Partial Sale — {$order->order_number} | Total: Rs.{$total} | Paid via {$partialVia}: Rs.{$amountPaid}, Pending: Rs.{$khataDue} | Items: {$itemsList}"
                    : "POS Sale — {$order->order_number} | Total: Rs.{$total} | Items: {$itemsList}";
                VendorLedger::create([
                    'vendor_id'    => $vendor->id,
                    'order_id'     => $order->id,
                    'type'         => 'debit',
                    'amount'       => $khataDue,
                    'balance_after' => $newBal,
                    'description'  => $description,
                    'reference'    => $order->order_number,
                    'created_by'   => Auth::id(),
                ]);
                $vendor->update(['balance' => $newBal]);
            } elseif ($khataDue > 0 && $customer) {
                $newBal = $customer->credit_balance - $khataDue;
                $partialVia = $request->partial_pay_via === 'bank' ? 'Bank' : 'Cash';
                $description = $payMethod === 'partial'
                    ? "Partial Payment — {$order->order_number} | Total: Rs.{$total} | Paid via {$partialVia}: Rs.{$amountPaid}, Khata: Rs.{$khataDue} | Items: {$itemsList}"
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
            } elseif ($khataDue == 0 && $customer) {
                // Full cash/bank payment — record sale + payment in ledger without changing balance
                $bal = $customer->credit_balance;
                $payLabel = match($payMethod) {
                    'bank_transfer' => 'Bank Transfer',
                    'split'         => "Cash: Rs.{$cashAmount} + Bank: Rs.{$bankAmount}",
                    default         => 'Cash',
                };
                $bankAccId = in_array($payMethod, ['bank_transfer', 'split']) ? $request->bank_account_id : null;

                AccountLedger::create([
                    'customer_id'    => $customer->id,
                    'type'           => 'debit',
                    'amount'         => $total,
                    'balance_after'  => $bal - $total,
                    'description'    => "Sale — {$order->order_number} | Rs.{$total} | Items: {$itemsList}",
                    'reference'      => $order->order_number,
                    'user_id'        => Auth::id(),
                ]);
                AccountLedger::create([
                    'customer_id'    => $customer->id,
                    'type'           => 'credit',
                    'payment_method' => in_array($payMethod, ['cash', 'split']) ? 'cash' : 'bank_transfer',
                    'bank_account_id' => $bankAccId,
                    'amount'         => $total,
                    'balance_after'  => $bal,
                    'description'    => "Payment Received — {$order->order_number} | Rs.{$total} via {$payLabel}",
                    'reference'      => $order->order_number,
                    'user_id'        => Auth::id(),
                ]);
            }

            // Update POS session totals
            PosSession::where('user_id', Auth::id())->whereNull('closed_at')
                      ->increment('total_sales', $total);
            PosSession::where('user_id', Auth::id())->whereNull('closed_at')
                      ->increment('total_orders');

            DB::commit();

            return response()->json(['success' => true, 'order_id' => $order->id, 'order_number' => $order->order_number]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 422);
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
        $user = Auth::user();
        abort_if($user->isSubshop() && $order->shop_id !== $user->shopId(), 404);

        $order->load(['items.product', 'servedBy', 'bankAccount', 'shop']);

        // Sub shop sales print the sub shop's identity (fall back to global settings)
        $shop     = $order->shop;
        $settings = [
            'shop_name'    => $shop?->name ?? Setting::get('shop_name', 'MobileHub'),
            'shop_phone'   => $shop?->phone ?? Setting::get('shop_phone'),
            'shop_address' => $shop?->address ?? Setting::get('shop_address'),
            'header'       => $shop?->receipt_header ?? Setting::get('receipt_header'),
            'footer'       => $shop?->receipt_footer ?? Setting::get('receipt_footer'),
        ];
        return view('pos.receipt', compact('order', 'settings'));
    }

    // ── Delete Sale ───────────────────────────────────────────────────────

    public function deleteOrder(Order $order)
    {
        abort_if($order->source !== 'pos', 404);
        abort_if(Auth::user()->isSubshop() && $order->shop_id !== Auth::user()->shopId(), 404);
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
            $oldVendor = $order->vendor_id
                ? Vendor::find($order->vendor_id)
                : null;

            // ── 1. Restore stock at the order's own shop ───────────────────
            $items = $order->items()->get();

            foreach ($items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);
                if (! $product) continue;

                app(\App\Services\ShopStockService::class)->adjust(
                    $order->shop_id, $product, $item->color_id, $item->quantity,
                    'adjustment', $order->order_number, 'Sale deleted — stock restored'
                );
            }

            // ── 2. Reverse khata ledger entries ───────────────────────────
            if ($oldVendor && in_array($oldMethod, ['khata', 'partial'])) {
                $ledgers     = VendorLedger::where('reference', $order->order_number)
                    ->where('type', 'debit')
                    ->where('vendor_id', $oldVendor->id)
                    ->get();
                $ledgerTotal = $ledgers->sum('amount');
                if ($ledgerTotal > 0) {
                    $ledgers->each->delete();
                    $oldVendor->increment('balance', $ledgerTotal);
                }
            } elseif ($oldCustomer && in_array($oldMethod, ['khata', 'partial'])) {
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
        abort_if(Auth::user()->isSubshop() && $order->shop_id !== Auth::user()->shopId(), 404);
        abort_if(in_array($order->status, ['returned', 'cancelled']), 403, 'This order cannot be edited.');

        $order->load(['items.product', 'customer', 'bankAccount', 'servedBy']);
        $bankAccounts = BankAccount::active()->forShop($order->shop_id)->orderBy('sort_order')->orderBy('id')->get();

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
        abort_if(Auth::user()->isSubshop() && $order->shop_id !== Auth::user()->shopId(), 404);
        abort_if(in_array($order->status, ['returned', 'cancelled']), 403);

        $orderShopId = $order->shop_id;

        $request->validate([
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'required|exists:products,id',
            'items.*.quantity'    => 'required|integer|min:1',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'items.*.color_id'    => 'nullable|exists:product_colors,id',
            'payment_method'           => 'required|in:cash,bank_transfer,khata,partial,split',
            'amount_paid'              => 'nullable|numeric|min:0',
            'partial_pay_via'          => 'nullable|in:cash,bank',
            'partial_bank_account_id'  => 'nullable|exists:bank_accounts,id',
            'cash_amount'              => 'nullable|numeric|min:0',
            'bank_amount'              => 'nullable|numeric|min:0',
            'bank_account_id'          => 'nullable|exists:bank_accounts,id',
            'customer_id'              => 'nullable|exists:customers,id',
            'vendor_id'                => 'nullable|exists:vendors,id',
            'discount'                 => 'nullable|numeric|min:0',
            'notes'                    => 'nullable|string|max:500',
            'promise_date'        => 'nullable|date',
        ]);

        // Shop guards: no vendors on sub shop orders; customer/banks must match the order's shop
        if ($orderShopId && $request->filled('vendor_id')) {
            return response()->json(['error' => 'Vendor sales are only available at the main shop.'], 422);
        }
        if ($request->filled('customer_id')
            && Customer::find($request->customer_id)?->shop_id !== $orderShopId) {
            return response()->json(['error' => 'This customer belongs to a different shop.'], 422);
        }
        foreach (['bank_account_id', 'partial_bank_account_id'] as $bankField) {
            if ($request->filled($bankField)
                && !BankAccount::forShop($orderShopId)->where('id', $request->$bankField)->exists()) {
                return response()->json(['error' => 'The selected bank account belongs to a different shop.'], 422);
            }
        }

        DB::beginTransaction();
        try {
            $oldTotal    = (float) $order->total;
            $oldMethod   = $order->payment_method;
            $oldCustomer = $order->customer_id
                ? \App\Models\Customer::find($order->customer_id)
                : null;
            $oldVendor = $order->vendor_id
                ? Vendor::find($order->vendor_id)
                : null;

            // ── 1. Restore stock from old items (at the order's own shop) ──
            $oldItems = $order->items()->get();

            foreach ($oldItems as $oldItem) {
                $product = Product::lockForUpdate()->find($oldItem->product_id);
                if (! $product) continue;

                app(\App\Services\ShopStockService::class)->adjust(
                    $orderShopId, $product, $oldItem->color_id, $oldItem->quantity,
                    'adjustment', $order->order_number, 'Sale edit — stock restored'
                );
            }

            // ── 2. Reverse khata ledger entries ───────────────────────────
            if ($oldVendor && in_array($oldMethod, ['khata', 'partial'])) {
                $oldLedgers  = VendorLedger::where('reference', $order->order_number)
                    ->where('type', 'debit')
                    ->where('vendor_id', $oldVendor->id)
                    ->get();
                $ledgerTotal = $oldLedgers->sum('amount');
                if ($ledgerTotal > 0) {
                    $oldLedgers->each->delete();
                    $oldVendor->increment('balance', $ledgerTotal);
                    $oldVendor->refresh();
                }
            } elseif ($oldCustomer && in_array($oldMethod, ['khata', 'partial'])) {
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
            $vendor = $request->vendor_id
                ? Vendor::find($request->vendor_id)
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
                        $colorStock = $orderShopId
                            ? $product->stockForShop($orderShopId, $color->id)
                            : $color->stock_quantity;
                        if ($colorStock < $item['quantity']) {
                            DB::rollBack();
                            return response()->json([
                                'error' => "Insufficient stock for: {$product->name} ({$color->name})",
                            ], 422);
                        }
                    }
                } elseif ($product->track_inventory
                    && ($orderShopId ? $product->stockForShop($orderShopId) : $product->stock_quantity) < $item['quantity']) {
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
                if (! $customer && ! $vendor) {
                    DB::rollBack();
                    return response()->json(['error' => 'A customer or vendor is required for Khata payment.'], 422);
                }
            } elseif ($payMethod === 'partial') {
                $amountPaid = min((float) ($request->amount_paid ?? 0), $newTotal);
                $payStatus  = $amountPaid >= $newTotal ? 'paid' : 'partial';
                if (! $customer && ! $vendor && $amountPaid < $newTotal) {
                    DB::rollBack();
                    return response()->json(['error' => 'A customer or vendor is required for partial payment.'], 422);
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

                app(\App\Services\ShopStockService::class)->adjust(
                    $orderShopId, $item['product'], $item['color']?->id, -$item['qty'],
                    'sale', $order->order_number, 'Sale edit'
                );
            }

            // ── 6. New khata ledger entry ─────────────────────────────────
            $khataDue = 0;
            if ($payMethod === 'khata') {
                $khataDue = $newTotal;
            } elseif ($payMethod === 'partial' && $payStatus === 'partial') {
                $khataDue = $newTotal - $amountPaid;
            }

            $itemsList = collect($newItems)
                ->map(fn($i) => "{$i['product']->name} x{$i['qty']}")
                ->join(', ');

            if ($khataDue > 0 && $vendor) {
                $vendor->refresh();
                $newBal = $vendor->balance - $khataDue;
                VendorLedger::create([
                    'vendor_id'    => $vendor->id,
                    'order_id'     => $order->id,
                    'type'         => 'debit',
                    'amount'       => $khataDue,
                    'balance_after' => $newBal,
                    'description'  => "Edited Sale — {$order->order_number} | Total: Rs.{$newTotal} | Items: {$itemsList}",
                    'reference'    => $order->order_number,
                    'created_by'   => Auth::id(),
                ]);
                $vendor->update(['balance' => $newBal]);
            } elseif ($khataDue > 0 && $customer) {
                $customer->refresh();
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
            } elseif ($khataDue == 0 && $customer) {
                // Full cash/bank payment on edit — record without changing balance
                $customer->refresh();
                $bal = $customer->credit_balance;
                $payLabel = match($payMethod) {
                    'bank_transfer' => 'Bank Transfer',
                    'split'         => "Cash: Rs.{$cashAmount} + Bank: Rs.{$bankAmount}",
                    default         => 'Cash',
                };
                $bankAccId = in_array($payMethod, ['bank_transfer', 'split']) ? $request->bank_account_id : null;

                AccountLedger::create([
                    'customer_id'    => $customer->id,
                    'type'           => 'debit',
                    'amount'         => $newTotal,
                    'balance_after'  => $bal - $newTotal,
                    'description'    => "Edited Sale — {$order->order_number} | Rs.{$newTotal} | Items: {$itemsList}",
                    'reference'      => $order->order_number,
                    'user_id'        => Auth::id(),
                ]);
                AccountLedger::create([
                    'customer_id'    => $customer->id,
                    'type'           => 'credit',
                    'payment_method' => in_array($payMethod, ['cash', 'split']) ? 'cash' : 'bank_transfer',
                    'bank_account_id' => $bankAccId,
                    'amount'         => $newTotal,
                    'balance_after'  => $bal,
                    'description'    => "Payment Received — {$order->order_number} | Rs.{$newTotal} via {$payLabel}",
                    'reference'      => $order->order_number,
                    'user_id'        => Auth::id(),
                ]);
            }

            // ── 7. Update order record ────────────────────────────────────
            $order->update([
                'customer_id'     => $customer?->id,
                'vendor_id'       => $vendor?->id,
                'customer_name'   => $customer?->name ?? $vendor?->name ?? 'Walk-in Customer',
                'customer_phone'  => $customer?->phone ?? $vendor?->phone ?? '-',
                'subtotal'        => $subtotal,
                'discount_amount' => $discount,
                'total'           => $newTotal,
                'amount_paid'     => $amountPaid,
                'cash_amount'     => $cashAmount,
                'bank_amount'     => $bankAmount,
                'payment_method'  => $payMethod,
                'payment_status'  => $payStatus,
                'bank_account_id' => in_array($payMethod, ['bank_transfer', 'split'])
                                        ? $request->bank_account_id
                                        : ($payMethod === 'partial' && $request->partial_pay_via === 'bank'
                                            ? $request->partial_bank_account_id
                                            : null),
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

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    // ── Held Orders ──────────────────────────────────────────────────────────

    public function getHolds(): \Illuminate\Http\JsonResponse
    {
        $user    = Auth::user();
        $isAdmin = $user->hasRole('admin');
        $shopId  = $user->shopId();

        // Holds are location-scoped: main POS never sees a sub shop's holds and vice versa
        $query = HeldOrder::forShop($shopId)->with('creator:id,name')->latest();

        if (!$isAdmin && !$shopId) {
            $sectionIds = $user->sections->pluck('id')->map(fn($id) => (int) $id)->toArray();
            $query->where(function ($q) use ($user, $sectionIds) {
                $q->where('created_by', $user->id);
                foreach ($sectionIds as $sid) {
                    $q->orWhereJsonContains('section_ids', $sid);
                }
            });
        }

        $holds = $query->get()->map(fn($h) => [
            'id'           => $h->id,
            'label'        => $h->label,
            'cart'         => $h->cart_data,
            'customer'     => $h->customer_data,
            'discountType' => $h->discount_type,
            'discountValue'=> (float) $h->discount_value,
            'paymentMethod'=> $h->payment_method,
            'orderNotes'   => $h->order_notes,
            'total'        => (float) $h->total,
            'itemCount'    => collect($h->cart_data)->sum('quantity'),
            'heldAt'       => $h->created_at->format('h:i A'),
            'createdBy'    => $h->creator->name,
            'isOwn'        => $h->created_by === $user->id,
        ]);

        return response()->json($holds);
    }

    public function storeHold(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'label'         => 'required|string|max:100',
            'cart'          => 'required|array|min:1',
            'total'         => 'required|numeric|min:0',
            'discount_type' => 'nullable|string',
            'discount_value'=> 'nullable|numeric|min:0',
            'payment_method'=> 'nullable|string',
            'order_notes'   => 'nullable|string|max:500',
            'customer'      => 'nullable|array',
        ]);

        $productIds  = collect($request->cart)->pluck('product_id')->filter()->unique();
        $categoryIds = Product::whereIn('id', $productIds)->pluck('category_id')->filter()->unique();
        $sectionIds  = Category::whereIn('id', $categoryIds)
            ->whereNotNull('section_id')
            ->pluck('section_id')
            ->unique()
            ->values()
            ->map(fn($id) => (int) $id)
            ->toArray();

        $hold = HeldOrder::create([
            'label'          => $request->label,
            'shop_id'        => Auth::user()->shopId(),
            'created_by'     => Auth::id(),
            'cart_data'      => $request->cart,
            'customer_data'  => $request->customer,
            'discount_type'  => $request->discount_type  ?? 'flat',
            'discount_value' => $request->discount_value ?? 0,
            'payment_method' => $request->payment_method ?? 'cash',
            'order_notes'    => $request->order_notes,
            'total'          => $request->total,
            'section_ids'    => $sectionIds,
        ]);

        $hold->load('creator:id,name');

        return response()->json([
            'id'           => $hold->id,
            'label'        => $hold->label,
            'cart'         => $hold->cart_data,
            'customer'     => $hold->customer_data,
            'discountType' => $hold->discount_type,
            'discountValue'=> (float) $hold->discount_value,
            'paymentMethod'=> $hold->payment_method,
            'orderNotes'   => $hold->order_notes,
            'total'        => (float) $hold->total,
            'itemCount'    => collect($hold->cart_data)->sum('quantity'),
            'heldAt'       => $hold->created_at->format('h:i A'),
            'createdBy'    => $hold->creator->name,
            'isOwn'        => true,
        ]);
    }

    public function deleteHold(HeldOrder $hold): \Illuminate\Http\JsonResponse
    {
        $user    = Auth::user();
        $isAdmin = $user->hasRole('admin');

        if ($user->isSubshop()) {
            abort_unless($hold->shop_id === $user->shopId(), 403, 'You do not have permission to delete this held order.');
        } elseif (!$isAdmin) {
            $canDelete = (int) $hold->created_by === (int) $user->id;
            if (!$canDelete) {
                $sectionIds  = $user->sections->pluck('id')->toArray();
                $holdSections = $hold->section_ids ?? [];
                $canDelete   = !empty(array_intersect($sectionIds, $holdSections));
            }
            abort_unless($canDelete, 403, 'You do not have permission to delete this held order.');
        }

        $hold->delete();
        return response()->json(['success' => true]);
    }
}
