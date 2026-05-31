<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountLedger;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\ReturnOrder;
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
            'today_purchases'   => Purchase::whereDate('purchase_date', today())->sum('total'),
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

        $returnOrders = ReturnOrder::whereDate('created_at', today())
            ->whereIn('status', ['approved', 'completed'])
            ->get(['refund_method', 'refund_amount']);

        $returnCash  = $returnOrders->where('refund_method', 'cash')->sum('refund_amount');
        $returnBank  = $returnOrders->where('refund_method', 'bank_transfer')->sum('refund_amount');
        $returnTotal = $returnOrders->sum('refund_amount');

        $todayPurchases     = Purchase::whereDate('purchase_date', today())->get(['payment_method', 'total', 'amount_paid']);
        $purchasesTotal     = (float) $todayPurchases->sum('total');
        $purchasesPaid      = (float) $todayPurchases->sum('amount_paid');
        $purchasesDue       = $purchasesTotal - $purchasesPaid;
        $purchasesCashPaid  = (float) $todayPurchases->whereIn('payment_method', ['cash', 'partial'])->sum('amount_paid');
        $purchasesBankPaid  = (float) $todayPurchases->where('payment_method', 'bank_transfer')->sum('amount_paid');

        $todayReport = [
            'pos_total'      => $posOrders->sum('total'),
            'pos_cash'       => $posCash,
            'pos_bank'       => $posBank,
            'expenses'       => $todayExpenses,
            'khata_total'    => $khataTotal,
            'khata_cash'     => $khataCash,
            'khata_bank'     => $khataBank,
            'khata_other'    => $khataOther,
            'return_total'   => $returnTotal,
            'return_cash'    => $returnCash,
            'return_bank'    => $returnBank,
            'total_cash'        => $posCash + $khataCash - $returnCash - $purchasesCashPaid,
            'total_bank'        => $posBank + $khataBank - $returnBank - $purchasesBankPaid,
            'grand_total'       => $posCash + $posBank + $khataTotal - $returnTotal - $purchasesPaid,
            'purchases_total'   => $purchasesTotal,
            'purchases_paid'    => $purchasesPaid,
            'purchases_due'     => $purchasesDue,
            'purchases_cash'    => $purchasesCashPaid,
            'purchases_bank'    => $purchasesBankPaid,
            'store_name'        => Setting::get('shop_name', config('app.name')),
            'date'              => today()->format('d M Y'),
        ];

        // ── Detail rows for Today's Report modals ────────────────────────
        $todayPosOrders = Order::whereDate('created_at', today())
            ->where('source', 'pos')->where('status', 'delivered')
            ->with(['customer', 'bankAccount', 'items'])->latest()->get();

        $todayKhataEntries = AccountLedger::whereDate('created_at', today())
            ->where('type', 'credit')
            ->with(['customer', 'user', 'bankAccount'])->latest()->get();

        $todayExpensesList = Expense::whereDate('expense_date', today())
            ->latest()->get();

        $todayReturnsList = ReturnOrder::whereDate('created_at', today())
            ->whereIn('status', ['approved', 'completed'])
            ->with(['order', 'items'])->latest()->get();

        $todayPurchasesList = Purchase::whereDate('purchase_date', today())
            ->with(['vendor', 'items'])->latest()->get();

        return view('admin.dashboard', compact(
            'stats', 'recentOrders', 'lowStockItems', 'posChart', 'ecomChart', 'todayReport',
            'todayPosOrders', 'todayKhataEntries', 'todayExpensesList', 'todayReturnsList',
            'todayPurchasesList'
        ));
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
            ->with('bankAccount')
            ->get(['id', 'payment_method', 'bank_account_id', 'amount']);

        $returnOrders = ReturnOrder::whereDate('created_at', today())
            ->whereIn('status', ['approved', 'completed'])
            ->get(['refund_method', 'refund_amount']);

        $returnCash  = $returnOrders->where('refund_method', 'cash')->sum('refund_amount');
        $returnBank  = $returnOrders->where('refund_method', 'bank_transfer')->sum('refund_amount');
        $returnTotal = $returnOrders->sum('refund_amount');

        $purchasesForPrint   = Purchase::whereDate('purchase_date', today())->get(['payment_method', 'total', 'amount_paid', 'payment_status']);
        $purchasesTotalPrint = (float) $purchasesForPrint->sum('total');
        $purchasesPaidPrint  = (float) $purchasesForPrint->sum('amount_paid');
        $purchasesCashPrint  = (float) $purchasesForPrint->whereIn('payment_method', ['cash', 'partial'])->sum('amount_paid');
        $purchasesBankPrint  = (float) $purchasesForPrint->where('payment_method', 'bank_transfer')->sum('amount_paid');

        $khataCash  = $khataEntries->where('payment_method', 'cash')->sum('amount');
        $khataBank  = $khataEntries->where('payment_method', 'bank_transfer')->sum('amount');
        $khataTotal = $khataEntries->sum('amount');

        // Per-bank breakdown for print
        $khataByBank = $khataEntries
            ->where('payment_method', 'bank_transfer')
            ->filter(fn($e) => $e->bankAccount)
            ->groupBy('bank_account_id')
            ->map(fn($g) => [
                'label'          => $g->first()->bankAccount->label,
                'bank_name'      => $g->first()->bankAccount->bank_name,
                'account_number' => $g->first()->bankAccount->account_number,
                'total'          => $g->sum('amount'),
                'count'          => $g->count(),
            ])
            ->values();

        $todayReport = [
            'pos_total'    => $posOrders->sum('total'),
            'pos_cash'     => $posCash,
            'pos_bank'     => $posBank,
            'expenses'     => (float) Expense::whereDate('expense_date', today())->sum('amount'),
            'khata_total'  => $khataTotal,
            'khata_cash'   => $khataCash,
            'khata_bank'   => $khataBank,
            'khata_other'  => $khataEntries->whereNotIn('payment_method', ['cash','bank_transfer'])->sum('amount'),
            'khata_by_bank'=> $khataByBank,
            'return_total'      => $returnTotal,
            'return_cash'       => $returnCash,
            'return_bank'       => $returnBank,
            'purchases_total'   => $purchasesTotalPrint,
            'purchases_paid'    => $purchasesPaidPrint,
            'purchases_due'     => $purchasesTotalPrint - $purchasesPaidPrint,
            'purchases_cash'    => $purchasesCashPrint,
            'purchases_bank'    => $purchasesBankPrint,
            'total_cash'        => $posCash + $khataCash - $returnCash - $purchasesCashPrint,
            'total_bank'        => $posBank + $khataBank - $returnBank - $purchasesBankPrint,
            'grand_total'       => $posOrders->sum('total') + $khataTotal - $returnTotal - $purchasesPaidPrint,
            'store_name'        => Setting::get('shop_name', config('app.name')),
            'store_phone'       => Setting::get('shop_phone', ''),
            'date'              => today()->format('d M Y'),
        ];

        return view('admin.dashboard.today-report-print', compact('todayReport'));
    }

    public function salesmanTodayReportPrint()
    {
        $user = Auth::user();

        $posOrders = Order::where('served_by', $user->id)
            ->whereDate('created_at', today())
            ->where('source', 'pos')->where('status', 'delivered')
            ->get(['payment_method', 'total', 'cash_amount', 'bank_amount']);

        $posCash = $posOrders->sum(fn($o) => match($o->payment_method) {
            'cash'  => (float) $o->total, 'split' => (float) $o->cash_amount, default => 0,
        });
        $posBank = $posOrders->sum(fn($o) => match($o->payment_method) {
            'bank_transfer' => (float) $o->total, 'split' => (float) $o->bank_amount, default => 0,
        });

        $returnOrders = ReturnOrder::where('processed_by', $user->id)
            ->whereDate('created_at', today())->whereIn('status', ['approved', 'completed'])
            ->get(['refund_method', 'refund_amount']);
        $returnTotal = (float) $returnOrders->sum('refund_amount');
        $returnCash  = (float) $returnOrders->where('refund_method', 'cash')->sum('refund_amount');
        $returnBank  = (float) $returnOrders->where('refund_method', 'bank_transfer')->sum('refund_amount');

        $khataEntries = AccountLedger::where('user_id', $user->id)
            ->whereDate('created_at', today())->where('type', 'credit')
            ->get(['payment_method', 'amount']);
        $khataTotal = (float) $khataEntries->sum('amount');
        $khataCash  = (float) $khataEntries->where('payment_method', 'cash')->sum('amount');
        $khataBank  = (float) $khataEntries->where('payment_method', 'bank_transfer')->sum('amount');

        $todayReport = [
            'pos_total'    => (float) $posOrders->sum('total'),
            'pos_cash'     => $posCash,
            'pos_bank'     => $posBank,
            'return_total' => $returnTotal,
            'return_cash'  => $returnCash,
            'return_bank'  => $returnBank,
            'khata_total'  => $khataTotal,
            'khata_cash'   => $khataCash,
            'khata_bank'   => $khataBank,
            'total_cash'   => $posCash + $khataCash - $returnCash,
            'total_bank'   => $posBank + $khataBank - $returnBank,
            'grand_total'  => (float) $posOrders->sum('total') + $khataTotal - $returnTotal,
            'purchases_total' => 0,
            'purchases_paid'  => 0,
            'purchases_due'   => 0,
            'purchases_cash'  => 0,
            'purchases_bank'  => 0,
            'store_name'   => Setting::get('shop_name', config('app.name')),
            'store_phone'  => Setting::get('shop_phone', ''),
            'salesman_name'=> $user->name,
            'date'         => today()->format('d M Y'),
        ];

        if ($user->can('purchases.view')) {
            $printPurchases = Purchase::whereDate('purchase_date', today())->get(['payment_method', 'total', 'amount_paid']);
            $todayReport['purchases_total'] = (float) $printPurchases->sum('total');
            $todayReport['purchases_paid']  = (float) $printPurchases->sum('amount_paid');
            $todayReport['purchases_due']   = $todayReport['purchases_total'] - $todayReport['purchases_paid'];
            $todayReport['purchases_cash']  = (float) $printPurchases->whereIn('payment_method', ['cash', 'partial'])->sum('amount_paid');
            $todayReport['purchases_bank']  = (float) $printPurchases->where('payment_method', 'bank_transfer')->sum('amount_paid');
            $todayReport['total_cash']     -= $todayReport['purchases_cash'];
            $todayReport['total_bank']     -= $todayReport['purchases_bank'];
            $todayReport['grand_total']    -= $todayReport['purchases_paid'];
        }

        return view('salesman.today-report-print', compact('todayReport'));
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

        $recentOrders  = Order::where('served_by', $user->id)->with('customer')->latest()->take(8)->get();
        $lowStockItems = Product::active()->where('track_inventory', true)
                                 ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                                 ->with('category')->take(6)->get();
        $pendingOrders = Order::where('status', 'pending')->latest()->take(8)->get();

        // ── Today's Report ────────────────────────────────────────────────────
        $posOrders = Order::where('served_by', $user->id)
            ->whereDate('created_at', today())
            ->where('source', 'pos')
            ->where('status', 'delivered')
            ->get(['payment_method', 'total', 'cash_amount', 'bank_amount']);

        $posCash = $posOrders->sum(fn($o) => match($o->payment_method) {
            'cash'  => (float) $o->total,
            'split' => (float) $o->cash_amount,
            default => 0,
        });
        $posBank = $posOrders->sum(fn($o) => match($o->payment_method) {
            'bank_transfer' => (float) $o->total,
            'split'         => (float) $o->bank_amount,
            default         => 0,
        });

        $returnOrders = ReturnOrder::where('processed_by', $user->id)
            ->whereDate('created_at', today())
            ->whereIn('status', ['approved', 'completed'])
            ->get(['refund_method', 'refund_amount']);
        $returnTotal = (float) $returnOrders->sum('refund_amount');
        $returnCash  = (float) $returnOrders->where('refund_method', 'cash')->sum('refund_amount');
        $returnBank  = (float) $returnOrders->where('refund_method', 'bank_transfer')->sum('refund_amount');

        $khataEntries = AccountLedger::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->where('type', 'credit')
            ->get(['payment_method', 'amount']);
        $khataTotal = (float) $khataEntries->sum('amount');
        $khataCash  = (float) $khataEntries->where('payment_method', 'cash')->sum('amount');
        $khataBank  = (float) $khataEntries->where('payment_method', 'bank_transfer')->sum('amount');

        $todayReport = [
            'pos_total'    => (float) $posOrders->sum('total'),
            'pos_cash'     => $posCash,
            'pos_bank'     => $posBank,
            'return_total' => $returnTotal,
            'return_cash'  => $returnCash,
            'return_bank'  => $returnBank,
            'khata_total'  => $khataTotal,
            'khata_cash'   => $khataCash,
            'khata_bank'   => $khataBank,
            'total_cash'   => $posCash + $khataCash - $returnCash,
            'total_bank'   => $posBank + $khataBank - $returnBank,
            'grand_total'  => (float) $posOrders->sum('total') + $khataTotal - $returnTotal,
            'date'         => today()->format('d M Y'),
            // purchases keys initialised; adjusted after permission check below
            'purchases_total' => 0, 'purchases_paid' => 0, 'purchases_due' => 0,
            'purchases_cash'  => 0, 'purchases_bank' => 0,
        ];

        // Purchases (only if permission granted)
        if ($user->can('purchases.view')) {
            $salePurchases = Purchase::whereDate('purchase_date', today())->get(['payment_method', 'total', 'amount_paid']);
            $todayReport['purchases_total'] = (float) $salePurchases->sum('total');
            $todayReport['purchases_paid']  = (float) $salePurchases->sum('amount_paid');
            $todayReport['purchases_due']   = $todayReport['purchases_total'] - $todayReport['purchases_paid'];
            $todayReport['purchases_cash']  = (float) $salePurchases->whereIn('payment_method', ['cash', 'partial'])->sum('amount_paid');
            $todayReport['purchases_bank']  = (float) $salePurchases->where('payment_method', 'bank_transfer')->sum('amount_paid');
            // Adjust totals to account for purchases outflow
            $todayReport['total_cash']  -= $todayReport['purchases_cash'];
            $todayReport['total_bank']  -= $todayReport['purchases_bank'];
            $todayReport['grand_total'] -= $todayReport['purchases_paid'];
        }

        // Detail lists for modals
        $todayPosOrders = Order::where('served_by', $user->id)
            ->whereDate('created_at', today())
            ->where('source', 'pos')->where('status', 'delivered')
            ->with(['customer', 'items'])->latest()->get();

        $todayReturnsList = ReturnOrder::where('processed_by', $user->id)
            ->whereDate('created_at', today())
            ->whereIn('status', ['approved', 'completed'])
            ->with(['order', 'items'])->latest()->get();

        $todayKhataEntries = AccountLedger::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->where('type', 'credit')
            ->with(['customer', 'bankAccount'])->latest()->get();

        $todayPurchasesList = $user->can('purchases.view')
            ? Purchase::whereDate('purchase_date', today())->with(['vendor', 'items'])->latest()->get()
            : collect();

        return view('salesman.dashboard', compact(
            'todaySales', 'todayOrders', 'monthSales', 'lowStockCount',
            'recentOrders', 'lowStockItems', 'pendingOrders',
            'todayReport', 'todayPosOrders', 'todayReturnsList',
            'todayKhataEntries', 'todayPurchasesList'
        ));
    }
}
