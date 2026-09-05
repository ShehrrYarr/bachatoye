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
use App\Services\OrderProfitCalculator;
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

        $orders = $query->with(['items.returnItems.returnOrder', 'items.product.category', 'servedBy', 'bankAccount'])->latest()->get();

        // Item-level, returns-aware revenue/COGS/profit — the same calculation
        // the Profit & Loss report uses, so the two reports can never diverge.
        $profitData = OrderProfitCalculator::summarize($orders, $sectionId, null);

        if ($sectionId) {
            // Discounts/exchange/delivery apply to the whole order, not a single
            // section — not attributable per-item, same disclosed limitation as P&L.
            $totalRevenue = $profitData['netRevenue'];
        } else {
            // Same waterfall as the unfiltered Profit & Loss report: orders.total
            // already nets discount_amount/exchange_value (POS) and
            // delivery_charge/coupon_discount (ecommerce) in — subtract those (once)
            // from item-level gross revenue, then net out returns, so both reports
            // land on the exact same figure.
            $totalRevenue = $profitData['grossRevenue']
                - $orders->sum('discount_amount')
                - $orders->sum('coupon_discount')
                - $orders->sum('exchange_value')
                + $orders->sum('delivery_charge')
                - $profitData['totalRefunds'];
        }

        $totalRefunds       = $profitData['totalRefunds'];
        $totalOrders        = $orders->count();
        $avgOrderValue      = $totalOrders ? round($totalRevenue / $totalOrders, 2) : 0;
        $itemsSold          = $orders->sum(fn($o) => $o->items->sum('quantity'));
        $totalExchangeValue = $orders->whereNotNull('exchange_value')->sum('exchange_value');
        $totalCogs          = $profitData['totalCogs'];
        $totalProfit        = $totalRevenue - $totalCogs;
        $totalMarginPct     = $totalRevenue > 0 ? round($totalProfit / $totalRevenue * 100, 1) : 0;

        $dailyData = $orders->groupBy(fn($o) => $o->created_at->toDateString())
                            ->map(fn($g, $date) => ['date' => $date, 'total' => $g->sum('total')])
                            ->sortKeys()
                            ->values()
                            ->toArray();

        // All products sold, broken down by section (one card per section on
        // the report) — independent of the section filter above, which only
        // narrows the main revenue/profit figures.
        $sectionProductRows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$from, $to])
            ->where('orders.status', 'delivered')
            ->whereNull('orders.deleted_at')
            ->when($shopFilter === 'main', fn($q) => $q->whereNull('orders.shop_id'))
            ->when($shopFilter !== '' && $shopFilter !== 'main', fn($q) => $q->where('orders.shop_id', (int) $shopFilter))
            ->select(
                'categories.section_id',
                'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_revenue')
            )
            ->groupBy('categories.section_id', 'order_items.product_name')
            ->orderByDesc('total_revenue')
            ->get()
            ->groupBy(fn($row) => $row->section_id ?? 'none');

        $sectionProducts = $sections->map(fn($section) => [
                'name'     => $section->name,
                'products' => $sectionProductRows->get($section->id, collect()),
            ])
            ->filter(fn($s) => $s['products']->isNotEmpty())
            ->values();

        if ($sectionProductRows->has('none')) {
            $sectionProducts->push([
                'name'     => 'Uncategorized',
                'products' => $sectionProductRows->get('none'),
            ]);
        }

        $byPayment = $orders->groupBy('payment_method')
                            ->map(fn($g) => ['total' => $g->sum('total'), 'count' => $g->count()]);

        return view('admin.reports.sales', compact(
            'orders', 'totalRevenue', 'totalRefunds', 'totalOrders', 'avgOrderValue', 'itemsSold',
            'totalExchangeValue', 'totalCogs', 'totalProfit', 'totalMarginPct',
            'dailyData', 'sectionProducts', 'byPayment', 'from', 'to',
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

        // Same base order set either way — status/date/shop scoping never differs
        // between the filtered and unfiltered view, only which items count.
        $ordersBase = Order::forShopFilter($shopFilter)
                            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
                            ->where('status', 'delivered');

        $orders = (clone $ordersBase)
            ->with(['items.returnItems.returnOrder', 'items.product.category'])
            ->get();

        // Item-level, returns-aware revenue/COGS/profit — the same calculation
        // the Sales report uses, so the two reports can never diverge.
        $profitData = OrderProfitCalculator::summarize($orders, $sectionId, $categoryId);
        $totalCogs    = $profitData['totalCogs'];
        $totalRefunds = $profitData['totalRefunds'];

        if ($isFiltered) {
            // Discounts/delivery/exchange apply to the whole order, not a single
            // category — they aren't attributable per-item, so they're left out
            // of this view's numbers (disclosed in the UI) rather than guessed at.
            $grossRevenue   = $profitData['grossRevenue'];
            $totalDiscounts = 0;
            $exchangeValue  = 0;
            $deliveryIncome = 0;
            $netRevenue     = $profitData['netRevenue'];

            $monthlyData = $orders->groupBy(fn($o) => $o->created_at->month)
                ->map(fn($group, $month) => [
                    'month'   => $month,
                    'revenue' => OrderProfitCalculator::summarize($group, $sectionId, $categoryId)['grossRevenue'],
                    'cogs'    => 0,
                ])
                ->sortKeys()
                ->values()
                ->toArray();
        } else {
            // orders.total already nets discount_amount/exchange_value (POS) and
            // delivery_charge/coupon_discount (ecommerce) in at creation time, so
            // building the same figure back up from components (subtotal minus
            // discounts minus exchange plus delivery) must equal orders.sum('total')
            // — that's the whole waterfall, done exactly once.
            $totals = (clone $ordersBase)->selectRaw('
                    COALESCE(SUM(subtotal), 0)         as subtotal,
                    COALESCE(SUM(discount_amount), 0)  as discount_amount,
                    COALESCE(SUM(coupon_discount), 0)  as coupon_discount,
                    COALESCE(SUM(exchange_value), 0)   as exchange_value,
                    COALESCE(SUM(delivery_charge), 0)  as delivery_charge
                ')->first();

            $grossRevenue   = (float) $totals->subtotal;
            $totalDiscounts = (float) $totals->discount_amount + (float) $totals->coupon_discount;
            $exchangeValue  = (float) $totals->exchange_value;
            $deliveryIncome = (float) $totals->delivery_charge;

            $netRevenue = $grossRevenue - $totalDiscounts - $exchangeValue + $deliveryIncome - $totalRefunds;

            $monthlyData = $orders->groupBy(fn($o) => $o->created_at->month)
                ->map(fn($group, $month) => [
                    'month'   => $month,
                    'revenue' => $group->sum('total'),
                    'cogs'    => 0,
                ])
                ->sortKeys()
                ->values()
                ->toArray();
        }

        $grossProfit   = $netRevenue - $totalCogs;
        $totalExpenses = Expense::forShopFilter($shopFilter)->whereBetween('expense_date', [$from, $to])->sum('amount');
        $netProfit     = $grossProfit - $totalExpenses;

        $expensesByCategory = Expense::forShopFilter($shopFilter)
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->whereBetween('expenses.expense_date', [$from, $to])
            ->select(
                'expense_categories.name as category_name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(expenses.amount) as total')
            )
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->get();

        return view('admin.reports.profit-loss', compact(
            'grossRevenue', 'totalDiscounts', 'exchangeValue', 'totalRefunds', 'netRevenue',
            'totalCogs', 'grossProfit', 'totalExpenses', 'deliveryIncome', 'netProfit', 'periodLabel',
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
