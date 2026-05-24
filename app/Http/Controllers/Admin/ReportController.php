<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\LoginLog;
use App\Models\Order;
use App\Models\Product;
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

        $query = Order::whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
                       ->where('status', '!=', 'cancelled');

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        $orders = $query->with('items')->latest()->get();

        $totalRevenue       = $orders->sum('total');
        $totalOrders        = $orders->count();
        $avgOrderValue      = $totalOrders ? round($totalRevenue / $totalOrders, 2) : 0;
        $itemsSold          = $orders->sum(fn($o) => $o->items->sum('quantity'));
        $totalExchangeValue = $orders->whereNotNull('exchange_value')->sum('exchange_value');

        $dailyData = $orders->groupBy(fn($o) => $o->created_at->toDateString())
                            ->map(fn($g, $date) => ['date' => $date, 'total' => $g->sum('total')])
                            ->sortKeys()
                            ->values()
                            ->toArray();

        $topProducts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$from, $to])
            ->where('orders.status', '!=', 'cancelled')
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
            'totalExchangeValue', 'dailyData', 'topProducts', 'byPayment', 'from', 'to'
        ));
    }

    public function profitLoss(Request $request)
    {
        ['from' => $from, 'to' => $to, 'label' => $periodLabel] = $this->dateRange($request);

        $ordersBase = Order::whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
                            ->where('status', '!=', 'cancelled');

        $grossRevenue   = $ordersBase->sum('total');
        $totalDiscounts = $ordersBase->sum('discount_amount');
        $deliveryIncome = $ordersBase->sum('delivery_charge');
        $netRevenue     = $grossRevenue - $totalDiscounts;

        $totalCogs = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$from, $to])
            ->where('orders.status', '!=', 'cancelled')
            ->sum(DB::raw('order_items.quantity * COALESCE(order_items.cost_price, 0)'));

        $grossProfit   = $netRevenue - $totalCogs;
        $totalExpenses = Expense::whereBetween('expense_date', [$from, $to])->sum('amount');
        $netProfit     = $grossProfit - $totalExpenses + $deliveryIncome;

        $expensesByCategory = DB::table('expenses')
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

        $monthlyData = DB::table('orders')
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->where('status', '!=', 'cancelled')
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy(DB::raw('MONTH(created_at)'))
            ->get()
            ->map(fn($r) => ['month' => $r->month, 'revenue' => $r->revenue, 'cogs' => 0])
            ->toArray();

        return view('admin.reports.profit-loss', compact(
            'grossRevenue', 'totalDiscounts', 'netRevenue', 'totalCogs', 'grossProfit',
            'totalExpenses', 'deliveryIncome', 'netProfit', 'periodLabel',
            'expensesByCategory', 'monthlyData', 'from', 'to'
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
                           ->where('status', '!=', 'cancelled');

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

        $expenses      = Expense::with(['category', 'user'])->whereBetween('expense_date', [$from, $to])->get();
        $totalExpenses = $expenses->sum('amount');
        $totalCount    = $expenses->count();
        $avgExpense    = $totalCount ? round($totalExpenses / $totalCount, 2) : 0;
        $categoryCount = $expenses->whereNotNull('expense_category_id')->groupBy('expense_category_id')->count();

        $byCategory = DB::table('expenses')
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

        $byMethod = DB::table('expenses')
            ->whereBetween('expense_date', [$from, $to])
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        return view('admin.reports.expenses', compact(
            'expenses', 'totalExpenses', 'totalCount', 'avgExpense', 'categoryCount',
            'byCategory', 'byMethod', 'from', 'to'
        ));
    }

    public function accounts(Request $request)
    {
        $customersOwing = Customer::where('credit_balance', '<', 0)
            ->with('orders')
            ->withCount('orders')
            ->orderBy('credit_balance')
            ->get();

        $customersWithCredit = Customer::where('credit_balance', '>', 0)
            ->orderByDesc('credit_balance')
            ->get();

        $totalOutstanding = abs($customersOwing->sum('credit_balance'));
        $totalCredit      = $customersWithCredit->sum('credit_balance');
        $customersWithDebt = $customersOwing->count();
        $khataOrders      = Order::where('payment_method', 'khata')->count();

        return view('admin.reports.accounts', compact(
            'customersOwing', 'customersWithCredit', 'totalOutstanding', 'totalCredit',
            'customersWithDebt', 'khataOrders'
        ));
    }

    public function export(Request $request, string $type)
    {
        return back()->with('info', 'Export feature coming soon.');
    }
}
