<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountLedger;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'today_sales'       => Order::whereDate('created_at', today())
                                        ->where('status', 'delivered')
                                        ->sum('total'),
            'today_orders'      => Order::whereDate('created_at', today())->count(),
            'pending_orders'    => Order::where('status', 'pending')->count(),
            'total_customers'   => Customer::count(),
            'outstanding_khata' => abs(Customer::where('credit_balance', '<', 0)->sum('credit_balance')),
            'month_sales'       => Order::whereMonth('created_at', now()->month)
                                        ->whereYear('created_at', now()->year)
                                        ->where('status', 'delivered')
                                        ->sum('total'),
            'today_expenses'    => Expense::whereDate('expense_date', today())->sum('amount'),
        ];

        $recentOrders  = Order::where('source', 'ecommerce')->latest()->take(10)->get();
        $lowStockItems = Product::active()->where('track_inventory', true)
                                 ->where('low_stock_dismissed', false)
                                 ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                                 ->with('category')->take(8)->get();

        $posChart = collect(range(6, 0))->map(fn($i) => [
            'date'  => now()->subDays($i)->format('M d'),
            'total' => Order::whereDate('created_at', now()->subDays($i))
                            ->where('source', 'pos')
                            ->where('status', 'delivered')
                            ->sum('total'),
        ]);

        $ecomChart = collect(range(6, 0))->map(fn($i) => [
            'date'  => now()->subDays($i)->format('M d'),
            'total' => Order::whereDate('created_at', now()->subDays($i))
                            ->where('source', 'ecommerce')
                            ->where('status', 'delivered')
                            ->sum('total'),
        ]);

        // ── Today's Total Report ──────────────────────────────────────────
        $posOrders = Order::whereDate('created_at', today())
            ->where('source', 'pos')
            ->where('status', 'delivered')
            ->get(['payment_method', 'total', 'cash_amount', 'bank_amount']);

        $posCash = $posOrders->sum(fn($o) => match($o->payment_method) {
            'cash'    => $o->total,
            'split'   => (float) $o->cash_amount,
            default   => 0,
        });
        $posBank = $posOrders->sum(fn($o) => match($o->payment_method) {
            'bank_transfer' => $o->total,
            'split'         => (float) $o->bank_amount,
            default         => 0,
        });

        $khataEntries = AccountLedger::whereDate('created_at', today())
            ->where('type', 'credit')
            ->get(['payment_method', 'amount']);

        $khataCash  = $khataEntries->where('payment_method', 'cash')->sum('amount');
        $khataBank  = $khataEntries->where('payment_method', 'bank_transfer')->sum('amount');
        $khataOther = $khataEntries->whereNotIn('payment_method', ['cash', 'bank_transfer'])->sum('amount');
        $khataTotal = $khataEntries->sum('amount');

        $todayExpenses = (float) ($stats['today_expenses'] ?? 0);

        $todayReport = [
            'pos_total'    => $posOrders->sum('total'),
            'pos_cash'     => $posCash,
            'pos_bank'     => $posBank,
            'expenses'     => $todayExpenses,
            'khata_total'  => $khataTotal,
            'khata_cash'   => $khataCash,
            'khata_bank'   => $khataBank,
            'khata_other'  => $khataOther,
            'total_cash'   => $posCash + $khataCash,
            'total_bank'   => $posBank + $khataBank,
            'grand_total'  => $posCash + $posBank + $khataTotal,
            'store_name'   => Setting::get('shop_name', config('app.name')),
            'date'         => today()->format('d M Y'),
        ];

        return view('admin.dashboard', compact('stats', 'recentOrders', 'lowStockItems', 'posChart', 'ecomChart', 'todayReport'));
    }

    public function todayReportPrint()
    {
        $posOrders = Order::whereDate('created_at', today())
            ->where('source', 'pos')->where('status', 'delivered')
            ->get(['payment_method', 'total', 'cash_amount', 'bank_amount']);

        $posCash = $posOrders->sum(fn($o) => match($o->payment_method) {
            'cash'  => $o->total, 'split' => (float)$o->cash_amount, default => 0,
        });
        $posBank = $posOrders->sum(fn($o) => match($o->payment_method) {
            'bank_transfer' => $o->total, 'split' => (float)$o->bank_amount, default => 0,
        });

        $khataEntries = AccountLedger::whereDate('created_at', today())->where('type', 'credit')
            ->get(['payment_method', 'amount']);

        $todayReport = [
            'pos_total'   => $posOrders->sum('total'),
            'pos_cash'    => $posCash,
            'pos_bank'    => $posBank,
            'expenses'    => (float) Expense::whereDate('expense_date', today())->sum('amount'),
            'khata_total' => $khataEntries->sum('amount'),
            'khata_cash'  => $khataEntries->where('payment_method', 'cash')->sum('amount'),
            'khata_bank'  => $khataEntries->where('payment_method', 'bank_transfer')->sum('amount'),
            'khata_other' => $khataEntries->whereNotIn('payment_method', ['cash','bank_transfer'])->sum('amount'),
            'total_cash'  => $posCash + $khataEntries->where('payment_method', 'cash')->sum('amount'),
            'total_bank'  => $posBank + $khataEntries->where('payment_method', 'bank_transfer')->sum('amount'),
            'grand_total' => $posOrders->sum('total') + $khataEntries->sum('amount'),
            'store_name'  => Setting::get('shop_name', config('app.name')),
            'store_phone' => Setting::get('shop_phone', ''),
            'date'        => today()->format('d M Y'),
        ];

        return view('admin.dashboard.today-report-print', compact('todayReport'));
    }

    public function salesmanDashboard()
    {
        $user = Auth::user();

        $todaySales  = Order::where('served_by', $user->id)->whereDate('created_at', today())
                            ->where('status', 'delivered')->sum('total');
        $todayOrders = Order::where('served_by', $user->id)->whereDate('created_at', today())->count();
        $monthSales  = Order::where('served_by', $user->id)
                            ->whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year)
                            ->where('status', 'delivered')
                            ->sum('total');
        $lowStockCount = Product::active()->where('track_inventory', true)
                                 ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                                 ->count();

        $recentOrders = Order::where('served_by', $user->id)->with('customer')->latest()->take(8)->get();
        $lowStockItems = Product::active()->where('track_inventory', true)
                                 ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                                 ->with('category')->take(6)->get();
        $pendingOrders = Order::where('status', 'pending')->latest()->take(8)->get();

        return view('salesman.dashboard', compact(
            'todaySales', 'todayOrders', 'monthSales', 'lowStockCount',
            'recentOrders', 'lowStockItems', 'pendingOrders'
        ));
    }
}
