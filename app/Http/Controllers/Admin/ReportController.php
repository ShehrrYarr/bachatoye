<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\LoginLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private function dateRange(Request $request): array
    {
        if ($request->filled('date_from') && $request->filled('date_to')) {
            return ['from' => $request->date_from, 'to' => $request->date_to, 'label' => 'Custom Range'];
        }

        return match ($request->input('period', 'this_month')) {
            'today'      => ['from' => now()->toDateString(),                        'to' => now()->toDateString(),                         'label' => 'Today'],
            'yesterday'  => ['from' => now()->subDay()->toDateString(),              'to' => now()->subDay()->toDateString(),                'label' => 'Yesterday'],
            'this_week'  => ['from' => now()->startOfWeek()->toDateString(),         'to' => now()->toDateString(),                         'label' => 'This Week'],
            'last_week'  => ['from' => now()->subWeek()->startOfWeek()->toDateString(), 'to' => now()->subWeek()->endOfWeek()->toDateString(), 'label' => 'Last Week'],
            'this_month' => ['from' => now()->startOfMonth()->toDateString(),        'to' => now()->toDateString(),                         'label' => 'This Month'],
            'last_month' => ['from' => now()->subMonth()->startOfMonth()->toDateString(), 'to' => now()->subMonth()->endOfMonth()->toDateString(), 'label' => 'Last Month'],
            'this_year'  => ['from' => now()->startOfYear()->toDateString(),         'to' => now()->toDateString(),                         'label' => 'This Year'],
            'last_year'  => ['from' => now()->subYear()->startOfYear()->toDateString(), 'to' => now()->subYear()->endOfYear()->toDateString(), 'label' => 'Last Year'],
            default      => ['from' => now()->startOfMonth()->toDateString(),        'to' => now()->toDateString(),                         'label' => 'This Month'],
        };
    }

    public function sales(Request $request)
    {
        ['from' => $from, 'to' => $to] = $this->dateRange($request);

        $sections   = Section::orderBy('sort_order')->get();
        $sectionId  = $request->filled('section') ? (int) $request->section : null;
        $shopFilter = (string) $request->input('shop', '');
        $shops      = \App\Models\Shop::orderBy('name')->get();

        $query = Order::forShopFilter($shopFilter)
                       ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
                       ->where('status', 'delivered');

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($sectionId) {
            $query->whereExists(fn($sub) =>
                $sub->select(DB::raw(1))
                    ->from('order_items')
                    ->join('products', 'products.id', '=', 'order_items.product_id')
                    ->join('categories', 'categories.id', '=', 'products.category_id')
                    ->whereColumn('order_items.order_id', 'orders.id')
                    ->where('categories.section_id', $sectionId)
            );
        }

        $orders = $query->with(['items', 'servedBy'])->latest()->get();

        $totalRevenue       = $orders->sum('total');
        $totalOrders        = $orders->count();
        $avgOrderValue      = $totalOrders ? round($totalRevenue / $totalOrders, 2) : 0;
        $itemsSold          = $orders->sum(fn($o) => $o->items->sum('quantity'));
        $totalExchangeValue = $orders->whereNotNull('exchange_value')->sum('exchange_value');
        $totalCogs          = $orders->sum(fn($o) => $o->items->sum(fn($i) => (float) $i->cost_price * (int) $i->quantity));
        $totalProfit        = $totalRevenue - $totalCogs;
        $totalMarginPct     = $totalRevenue > 0 ? round($totalProfit / $totalRevenue * 100, 1) : 0;

        $dailyData = $orders->groupBy(fn($o) => $o->created_at->toDateString())
                            ->map(fn($g, $date) => ['date' => $date, 'total' => $g->sum('total')])
                            ->sortKeys()
                            ->values()
                            ->toArray();

        $topProductsQuery = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$from, $to])
            ->where('orders.status', 'delivered')
            ->whereNull('orders.deleted_at')
            ->when($shopFilter === 'main', fn($q) => $q->whereNull('orders.shop_id'))
            ->when($shopFilter !== '' && $shopFilter !== 'main', fn($q) => $q->where('orders.shop_id', (int) $shopFilter));

        if ($sectionId) {
            $topProductsQuery
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->join('categories', 'categories.id', '=', 'products.category_id')
                ->where('categories.section_id', $sectionId);
        }

        $topProducts = $topProductsQuery
            ->select(
                'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_revenue')
            )
            ->groupBy('order_items.product_name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        $byPayment = $orders->groupBy('payment_method')
                            ->map(fn($g) => ['total' => $g->sum('total'), 'count' => $g->count()]);

        return view('admin.reports.sales', compact(
            'orders', 'totalRevenue', 'totalOrders', 'avgOrderValue', 'itemsSold',
            'totalExchangeValue', 'totalCogs', 'totalProfit', 'totalMarginPct',
            'dailyData', 'topProducts', 'byPayment', 'from', 'to',
            'sections', 'sectionId', 'shops', 'shopFilter'
        ));
    }

    public function profitLoss(Request $request)
    {
        ['from' => $from, 'to' => $to, 'label' => $periodLabel] = $this->dateRange($request);

        $sections   = Section::orderBy('sort_order')->get();
        $categories = Category::active()
            ->with(['children' => fn($q) => $q->active()->orderBy('name')])
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        $sectionId  = $request->filled('section')  ? (int) $request->section  : null;
        $categoryId = $request->filled('category') ? (int) $request->category : null;
        $isFiltered = $sectionId || $categoryId;

        $shopFilter = (string) $request->input('shop', '');
        $shops      = \App\Models\Shop::orderBy('name')->get();
        $shopRaw    = fn($q, $col = 'orders.shop_id') => $shopFilter === '' ? $q
            : ($shopFilter === 'main' ? $q->whereNull($col) : $q->where($col, (int) $shopFilter));

        if ($isFiltered) {
            $baseItems = $shopRaw(DB::table('order_items')
                ->join('orders',    'orders.id',    '=', 'order_items.order_id')
                ->join('products',  'products.id',  '=', 'order_items.product_id')
                ->join('categories as cats', 'cats.id', '=', 'products.category_id')
                ->whereBetween(DB::raw('DATE(orders.created_at)'), [$from, $to])
                ->where('orders.status', 'delivered')
                ->whereNull('orders.deleted_at'));

            if ($sectionId)  $baseItems->where('cats.section_id', $sectionId);
            if ($categoryId) $baseItems->where(fn($q) =>
                $q->where('products.category_id', $categoryId)
                  ->orWhere('products.subcategory_id', $categoryId)
            );

            $grossRevenue   = (clone $baseItems)->sum(DB::raw('order_items.quantity * order_items.unit_price'));
            $totalDiscounts = (clone $baseItems)->sum('order_items.discount_amount');
            $totalCogs      = (clone $baseItems)->sum(DB::raw('order_items.quantity * COALESCE(order_items.cost_price, 0)'));
            $deliveryIncome = 0;

            $monthlyData = (clone $baseItems)
                ->select(
                    DB::raw('MONTH(orders.created_at) as month'),
                    DB::raw('SUM(order_items.quantity * order_items.unit_price) as revenue')
                )
                ->groupBy(DB::raw('MONTH(orders.created_at)'))
                ->orderBy(DB::raw('MONTH(orders.created_at)'))
                ->get()
                ->map(fn($r) => ['month' => $r->month, 'revenue' => $r->revenue, 'cogs' => 0])
                ->toArray();
        } else {
            $ordersBase = Order::forShopFilter($shopFilter)
                                ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
                                ->where('status', 'delivered');

            $grossRevenue   = $ordersBase->sum('total');
            $totalDiscounts = $ordersBase->sum('discount_amount');
            $deliveryIncome = $ordersBase->sum('delivery_charge');

            $totalCogs = $shopRaw(DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereBetween(DB::raw('DATE(orders.created_at)'), [$from, $to])
                ->where('orders.status', 'delivered')
                // Raw join bypasses soft deletes — without this, deleted sales
                // contribute cost but no revenue and drag the report into loss
                ->whereNull('orders.deleted_at'))
                ->sum(DB::raw('order_items.quantity * COALESCE(order_items.cost_price, 0)'));

            $monthlyData = $shopRaw(DB::table('orders')
                ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
                ->where('status', 'delivered')
                ->whereNull('deleted_at'), 'orders.shop_id')
                ->select(
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('SUM(total) as revenue')
                )
                ->groupBy(DB::raw('MONTH(created_at)'))
                ->orderBy(DB::raw('MONTH(created_at)'))
                ->get()
                ->map(fn($r) => ['month' => $r->month, 'revenue' => $r->revenue, 'cogs' => 0])
                ->toArray();
        }

        $netRevenue  = $grossRevenue - $totalDiscounts;
        $grossProfit = $netRevenue - $totalCogs;
        $totalExpenses = Expense::forShopFilter($shopFilter)->whereBetween('expense_date', [$from, $to])->sum('amount');
        $netProfit   = $grossProfit - $totalExpenses + $deliveryIncome;

        $expensesByCategory = $shopRaw(DB::table('expenses')
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->whereBetween('expenses.expense_date', [$from, $to]), 'expenses.shop_id')
            ->select(
                'expense_categories.name as category_name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(expenses.amount) as total')
            )
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->get();

        return view('admin.reports.profit-loss', compact(
            'grossRevenue', 'totalDiscounts', 'netRevenue', 'totalCogs', 'grossProfit',
            'totalExpenses', 'deliveryIncome', 'netProfit', 'periodLabel',
            'expensesByCategory', 'monthlyData', 'from', 'to',
            'sections', 'categories', 'sectionId', 'categoryId', 'isFiltered',
            'shops', 'shopFilter'
        ));
    }

    public function inventory(Request $request)
    {
        $products = Product::with(['category'])->active()
                           ->orderBy('stock_quantity')
                           ->paginate(50)
                           ->withQueryString();

        $totalProducts   = Product::active()->count();
        $totalStockUnits = Product::active()->where('track_inventory', true)->sum('stock_quantity');
        $stockValue      = Product::active()->where('track_inventory', true)
                                  ->sum(DB::raw('stock_quantity * COALESCE(cost_price, 0)'));
        $lowStockCount   = Product::active()->where('track_inventory', true)
                                  ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count();

        $outOfStock = Product::active()->where('track_inventory', true)
                             ->where('stock_quantity', '<=', 0)->orderBy('name')->get();
        $lowStock   = Product::active()->where('track_inventory', true)
                             ->where('stock_quantity', '>', 0)
                             ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                             ->orderBy('stock_quantity')->get();

        $byCategory = Category::select(
            'categories.*',
            DB::raw('(SELECT COUNT(*) FROM products WHERE products.category_id = categories.id AND products.is_active = 1) as products_count'),
            DB::raw('(SELECT COALESCE(SUM(stock_quantity), 0) FROM products WHERE products.category_id = categories.id AND products.is_active = 1) as total_stock')
        )->orderBy('name')->get();

        return view('admin.reports.inventory', compact(
            'products', 'totalProducts', 'totalStockUnits', 'stockValue', 'lowStockCount',
            'outOfStock', 'lowStock', 'byCategory'
        ));
    }

    public function salesmanPerformance(Request $request)
    {
        ['from' => $from, 'to' => $to, 'label' => $periodLabel] = $this->dateRange($request);

        $salesmen = User::role('salesman')->get()->each(function ($user) use ($from, $to) {
            $orders = Order::where('served_by', $user->id)
                           ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
                           ->where('status', 'delivered');

            $user->period_orders_count = $orders->count();
            $user->period_sales_total  = $orders->sum('total');

            $logs = LoginLog::where('user_id', $user->id)
                            ->where('logged_in_at', '>=', $from)
                            ->where('logged_in_at', '<=', $to . ' 23:59:59')
                            ->get();

            $user->login_days       = $logs->groupBy(fn($l) => $l->logged_in_at->toDateString())->count();
            $totalHours             = $logs->sum(fn($l) => $l->logged_in_at->diffInHours($l->logged_out_at ?? now()));
            $user->total_login_hours = $totalHours . 'h';
        });

        $recentLogins = LoginLog::with('user')
            ->whereHas('user', fn($q) => $q->role('salesman'))
            ->latest('logged_in_at')
            ->limit(30)
            ->get()
            ->each(function ($log) {
                $log->duration = $log->logged_out_at
                    ? $log->logged_in_at->diff($log->logged_out_at)->format('%hh %im')
                    : null;
            });

        return view('admin.reports.salesman-performance', compact(
            'salesmen', 'recentLogins', 'periodLabel', 'from', 'to'
        ));
    }

    public function expenses(Request $request)
    {
        ['from' => $from, 'to' => $to] = $this->dateRange($request);

        $shopFilter = (string) $request->input('shop', '');
        $shops      = \App\Models\Shop::orderBy('name')->get();
        $shopRaw    = fn($q, $col = 'expenses.shop_id') => $shopFilter === '' ? $q
            : ($shopFilter === 'main' ? $q->whereNull($col) : $q->where($col, (int) $shopFilter));

        $expenses      = Expense::forShopFilter($shopFilter)->with(['category', 'user'])->whereBetween('expense_date', [$from, $to])->get();
        $totalExpenses = $expenses->sum('amount');
        $totalCount    = $expenses->count();
        $avgExpense    = $totalCount ? round($totalExpenses / $totalCount, 2) : 0;
        $categoryCount = $expenses->whereNotNull('expense_category_id')->groupBy('expense_category_id')->count();

        $byCategory = $shopRaw(DB::table('expenses')
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->whereBetween('expenses.expense_date', [$from, $to]))
            ->select(
                'expense_categories.name as category_name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(expenses.amount) as total')
            )
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->get();

        $byMethod = $shopRaw(DB::table('expenses')
            ->whereBetween('expense_date', [$from, $to]), 'shop_id')
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        return view('admin.reports.expenses', compact(
            'expenses', 'totalExpenses', 'totalCount', 'avgExpense', 'categoryCount',
            'byCategory', 'byMethod', 'from', 'to', 'shops', 'shopFilter'
        ));
    }

    public function accounts(Request $request)
    {
        $shopFilter = (string) $request->input('shop', '');
        $shops      = \App\Models\Shop::orderBy('name')->get();

        $customersOwing = Customer::forShopFilter($shopFilter)
            ->where('credit_balance', '<', 0)
            ->with(['orders', 'shop'])
            ->withCount('orders')
            ->orderBy('credit_balance')
            ->get();

        $customersWithCredit = Customer::forShopFilter($shopFilter)
            ->where('credit_balance', '>', 0)
            ->with('shop')
            ->orderByDesc('credit_balance')
            ->get();

        $totalOutstanding = abs($customersOwing->sum('credit_balance'));
        $totalCredit      = $customersWithCredit->sum('credit_balance');
        $customersWithDebt = $customersOwing->count();
        $khataOrders      = Order::forShopFilter($shopFilter)->where('payment_method', 'khata')->count();

        return view('admin.reports.accounts', compact(
            'customersOwing', 'customersWithCredit', 'totalOutstanding', 'totalCredit',
            'customersWithDebt', 'khataOrders', 'shops', 'shopFilter'
        ));
    }

    public function export(Request $request, string $type)
    {
        return back()->with('info', 'Export feature coming soon.');
    }
}
