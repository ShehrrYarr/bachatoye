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
use App\Models\SerialNumber;
use App\Models\Setting;
use App\Models\ShopStock;
use App\Models\VendorLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Shop filter: '' = all shops combined, 'main' = main shop only, id = that sub shop
        $shopFilter = (string) request('shop', '');
        $shops      = \App\Models\Shop::orderBy('name')->get();
        // Purchases & vendor payments only happen at the main shop
        $includePurchases = $shopFilter === '' || $shopFilter === 'main';
        // Ledger entries scope through their customer's shop
        $ledgerScope = fn($q) => $shopFilter === '' ? $q : $q->whereHas('customer', fn($c) => $c->forShopFilter($shopFilter));
        $returnScope = fn($q) => $shopFilter === '' ? $q : $q->whereHas('order', fn($o) => $o->forShopFilter($shopFilter));

        $stats = [
            'today_sales'       => Order::forShopFilter($shopFilter)->whereDate('created_at', today())
                                        ->where('status', 'delivered')
                                        ->sum('total'),
            'today_orders'      => Order::forShopFilter($shopFilter)->whereDate('created_at', today())->count(),
            'pending_orders'    => Order::forShopFilter($shopFilter)->where('status', 'pending')->count(),
            'total_customers'   => Customer::forShopFilter($shopFilter)->count(),
            'outstanding_khata' => abs(Customer::forShopFilter($shopFilter)->where('credit_balance', '<', 0)->sum('credit_balance')),
            'month_sales'       => Order::forShopFilter($shopFilter)->whereMonth('created_at', now()->month)
                                        ->whereYear('created_at', now()->year)
                                        ->where('status', 'delivered')
                                        ->sum('total'),
            'today_expenses'    => Expense::forShopFilter($shopFilter)->whereDate('expense_date', today())->sum('amount'),
            'today_purchases'   => $includePurchases ? Purchase::whereDate('purchase_date', today())->sum('total') : 0,
        ];

        $recentOrders  = Order::where('source', 'ecommerce')->latest()->take(10)->get();
        $lowStockItems = Product::active()->where('track_inventory', true)
                                 ->where('low_stock_dismissed', false)
                                 ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                                 ->with('category')->take(8)->get();

        $posChart = collect(range(6, 0))->map(fn($i) => [
            'date'  => now()->subDays($i)->format('M d'),
            'total' => Order::forShopFilter($shopFilter)->whereDate('created_at', now()->subDays($i))
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
        $posOrders = Order::forShopFilter($shopFilter)->whereDate('created_at', today())
            ->where('source', 'pos')
            ->where('status', 'delivered')
            ->get(['payment_method', 'total', 'cash_amount', 'bank_amount', 'amount_paid', 'bank_account_id']);

        $posCash = $posOrders->sum(fn($o) => match($o->payment_method) {
            'cash'    => $o->total,
            'split'   => (float) $o->cash_amount,
            'partial' => $o->bank_account_id ? 0 : (float) $o->amount_paid,
            default   => 0,
        });
        $posBank = $posOrders->sum(fn($o) => match($o->payment_method) {
            'bank_transfer' => $o->total,
            'split'         => (float) $o->bank_amount,
            'partial'       => $o->bank_account_id ? (float) $o->amount_paid : 0,
            default         => 0,
        });

        $khataEntries = $ledgerScope(AccountLedger::whereDate('created_at', today()))
            ->where('type', 'credit')
            ->whereNull('return_id')->whereNull('order_id')
            ->get(['payment_method', 'amount']);

        $khataCash  = $khataEntries->where('payment_method', 'cash')->sum('amount');
        $khataBank  = $khataEntries->where('payment_method', 'bank_transfer')->sum('amount');
        $khataOther = $khataEntries->whereNotIn('payment_method', ['cash', 'bank_transfer'])->sum('amount');
        $khataTotal = $khataEntries->sum('amount');

        $expensesData  = Expense::forShopFilter($shopFilter)->whereDate('expense_date', today())->get(['payment_method', 'amount']);
        $todayExpenses = (float) $expensesData->sum('amount');
        $expenseCash   = (float) $expensesData->where('payment_method', 'cash')->sum('amount');
        $expenseBank   = (float) $expensesData->where('payment_method', 'bank_transfer')->sum('amount');

        $returnOrders = $returnScope(ReturnOrder::whereDate('created_at', today()))
            ->whereIn('status', ['approved', 'completed'])
            ->get(['refund_method', 'refund_amount']);

        $returnCash  = $returnOrders->where('refund_method', 'cash')->sum('refund_amount');
        $returnBank  = $returnOrders->where('refund_method', 'bank_transfer')->sum('refund_amount');
        $returnTotal = $returnOrders->sum('refund_amount');

        $todayPurchases     = $includePurchases
            ? Purchase::whereDate('purchase_date', today())->get(['payment_method', 'total', 'amount_paid', 'bank_account_id'])
            : collect();
        $purchasesTotal     = (float) $todayPurchases->sum('total');
        $purchasesPaid      = (float) $todayPurchases->sum('amount_paid');
        $purchasesDue       = $purchasesTotal - $purchasesPaid;
        $purchasesCashPaid  = (float) $todayPurchases->filter(fn($p) => $p->payment_method === 'cash' || ($p->payment_method === 'partial' && !$p->bank_account_id))->sum('amount_paid');
        $purchasesBankPaid  = (float) $todayPurchases->filter(fn($p) => $p->payment_method === 'bank_transfer' || ($p->payment_method === 'partial' && $p->bank_account_id))->sum('amount_paid');

        // Manual vendor payments (cash/bank) recorded via vendor ledger page (not tied to a purchase)
        $vendorPayData  = $includePurchases
            ? VendorLedger::whereDate('created_at', today())
                ->where('type', 'debit')->whereNull('purchase_id')->whereNotNull('payment_method')
                ->get(['payment_method', 'amount'])
            : collect();
        $vendorPayCash  = (float) $vendorPayData->where('payment_method', 'cash')->sum('amount');
        $vendorPayBank  = (float) $vendorPayData->where('payment_method', 'bank_transfer')->sum('amount');
        $vendorPayTotal = (float) $vendorPayData->sum('amount');

        // Cash/bank received from vendors (refunds, damage returns, etc.)
        $vendorRecvData  = $includePurchases
            ? VendorLedger::whereDate('created_at', today())
                ->where('type', 'credit')->whereNotNull('payment_method')
                ->get(['payment_method', 'amount'])
            : collect();
        $vendorRecvCash  = (float) $vendorRecvData->where('payment_method', 'cash')->sum('amount');
        $vendorRecvBank  = (float) $vendorRecvData->where('payment_method', 'bank_transfer')->sum('amount');
        $vendorRecvTotal = (float) $vendorRecvData->sum('amount');

        // Cash/bank paid out to customers (manual khata debits with a payment method)
        $payoutData  = $ledgerScope(AccountLedger::whereDate('created_at', today()))
            ->where('type', 'debit')->whereNotNull('payment_method')
            ->get(['payment_method', 'amount']);
        $payoutCash  = (float) $payoutData->where('payment_method', 'cash')->sum('amount');
        $payoutBank  = (float) $payoutData->where('payment_method', 'bank_transfer')->sum('amount');
        $payoutTotal = (float) $payoutData->sum('amount');

        $todayReport = [
            'pos_total'           => $posOrders->sum('total'),
            'pos_cash'            => $posCash,
            'pos_bank'            => $posBank,
            'expenses'            => $todayExpenses,
            'expense_cash'        => $expenseCash,
            'expense_bank'        => $expenseBank,
            'khata_total'         => $khataTotal,
            'khata_cash'          => $khataCash,
            'khata_bank'          => $khataBank,
            'khata_other'         => $khataOther,
            'return_total'        => $returnTotal,
            'return_cash'         => $returnCash,
            'return_bank'         => $returnBank,
            'vendor_pay_total'    => $vendorPayTotal,
            'vendor_pay_cash'     => $vendorPayCash,
            'vendor_pay_bank'     => $vendorPayBank,
            'vendor_recv_total'   => $vendorRecvTotal,
            'vendor_recv_cash'    => $vendorRecvCash,
            'vendor_recv_bank'    => $vendorRecvBank,
            'payout_total'        => $payoutTotal,
            'payout_cash'         => $payoutCash,
            'payout_bank'         => $payoutBank,
            'total_cash'          => $posCash + $khataCash + $vendorRecvCash - $returnCash - $purchasesCashPaid - $expenseCash - $vendorPayCash - $payoutCash,
            'total_bank'          => $posBank + $khataBank + $vendorRecvBank - $returnBank - $purchasesBankPaid - $expenseBank - $vendorPayBank - $payoutBank,
            'grand_total'         => $posCash + $posBank + $khataTotal + $vendorRecvTotal - $returnTotal - $purchasesPaid - $todayExpenses - $vendorPayTotal - $payoutTotal,
            'purchases_total'     => $purchasesTotal,
            'purchases_paid'      => $purchasesPaid,
            'purchases_due'       => $purchasesDue,
            'purchases_cash'      => $purchasesCashPaid,
            'purchases_bank'      => $purchasesBankPaid,
            'store_name'          => Setting::get('shop_name', config('app.name')),
            'date'                => today()->format('d M Y'),
        ];

        // ── Detail rows for Today's Report modals ────────────────────────
        $todayPosOrders = Order::forShopFilter($shopFilter)->whereDate('created_at', today())
            ->where('source', 'pos')->where('status', 'delivered')
            ->with(['customer', 'bankAccount', 'items'])->latest()->get();

        $todayKhataEntries = $ledgerScope(AccountLedger::whereDate('created_at', today()))
            ->where('type', 'credit')
            ->whereNull('return_id')->whereNull('order_id')
            ->with(['customer', 'user', 'bankAccount'])->latest()->get();

        $todayExpensesList = Expense::forShopFilter($shopFilter)->whereDate('expense_date', today())
            ->with('category')->latest()->get();

        $todayReturnsList = $returnScope(ReturnOrder::whereDate('created_at', today()))
            ->whereIn('status', ['approved', 'completed'])
            ->with(['order', 'items'])->latest()->get();

        $todayPurchasesList = $includePurchases
            ? Purchase::whereDate('purchase_date', today())->with(['vendor', 'items'])->latest()->get()
            : collect();

        // ── Khata payment reminders (promise dates within 5 days + overdue) ─
        $khataReminders = AccountLedger::where('type', 'debit')
            ->whereNotNull('promise_date')
            ->where('promise_date', '<=', today()->addDays(5))
            ->whereHas('customer', fn($q) => $q->forShopFilter($shopFilter)->where('credit_balance', '<', 0))
            ->with('customer')
            ->orderBy('promise_date')
            ->get();

        return view('admin.dashboard', compact(
            'stats', 'recentOrders', 'lowStockItems', 'posChart', 'ecomChart', 'todayReport',
            'todayPosOrders', 'todayKhataEntries', 'todayExpensesList', 'todayReturnsList',
            'todayPurchasesList', 'khataReminders', 'shops', 'shopFilter'
        ));
    }

    public function todayReportPrint()
    {
        $posOrders = Order::whereDate('created_at', today())
            ->where('source', 'pos')->where('status', 'delivered')
            ->get(['payment_method', 'total', 'cash_amount', 'bank_amount', 'amount_paid', 'bank_account_id']);

        $posCash = $posOrders->sum(fn($o) => match($o->payment_method) {
            'cash'    => $o->total, 'split' => (float)$o->cash_amount,
            'partial' => $o->bank_account_id ? 0 : (float)$o->amount_paid, default => 0,
        });
        $posBank = $posOrders->sum(fn($o) => match($o->payment_method) {
            'bank_transfer' => $o->total, 'split' => (float)$o->bank_amount,
            'partial'       => $o->bank_account_id ? (float)$o->amount_paid : 0, default => 0,
        });

        $khataEntries = AccountLedger::whereDate('created_at', today())->where('type', 'credit')
            ->whereNull('return_id')->whereNull('order_id')
            ->with('bankAccount')
            ->get(['id', 'payment_method', 'bank_account_id', 'amount']);

        $returnOrders = ReturnOrder::whereDate('created_at', today())
            ->whereIn('status', ['approved', 'completed'])
            ->get(['refund_method', 'refund_amount']);

        $returnCash  = $returnOrders->where('refund_method', 'cash')->sum('refund_amount');
        $returnBank  = $returnOrders->where('refund_method', 'bank_transfer')->sum('refund_amount');
        $returnTotal = $returnOrders->sum('refund_amount');

        $purchasesForPrint   = Purchase::whereDate('purchase_date', today())->get(['payment_method', 'total', 'amount_paid', 'payment_status', 'bank_account_id']);
        $purchasesTotalPrint = (float) $purchasesForPrint->sum('total');
        $purchasesPaidPrint  = (float) $purchasesForPrint->sum('amount_paid');
        $purchasesCashPrint  = (float) $purchasesForPrint->filter(fn($p) => $p->payment_method === 'cash' || ($p->payment_method === 'partial' && !$p->bank_account_id))->sum('amount_paid');
        $purchasesBankPrint  = (float) $purchasesForPrint->filter(fn($p) => $p->payment_method === 'bank_transfer' || ($p->payment_method === 'partial' && $p->bank_account_id))->sum('amount_paid');

        $khataCash  = $khataEntries->where('payment_method', 'cash')->sum('amount');
        $khataBank  = $khataEntries->where('payment_method', 'bank_transfer')->sum('amount');
        $khataTotal = $khataEntries->sum('amount');

        $printExpenses     = Expense::whereDate('expense_date', today())->get(['payment_method', 'amount']);
        $printExpenseTotal = (float) $printExpenses->sum('amount');
        $printExpenseCash  = (float) $printExpenses->where('payment_method', 'cash')->sum('amount');
        $printExpenseBank  = (float) $printExpenses->where('payment_method', 'bank_transfer')->sum('amount');

        $printVendorPay     = VendorLedger::whereDate('created_at', today())
            ->where('type', 'debit')->whereNull('purchase_id')->whereNotNull('payment_method')
            ->get(['payment_method', 'amount']);
        $printVendorPayCash  = (float) $printVendorPay->where('payment_method', 'cash')->sum('amount');
        $printVendorPayBank  = (float) $printVendorPay->where('payment_method', 'bank_transfer')->sum('amount');
        $printVendorPayTotal = (float) $printVendorPay->sum('amount');

        $printVendorRecv     = VendorLedger::whereDate('created_at', today())
            ->where('type', 'credit')->whereNotNull('payment_method')
            ->get(['payment_method', 'amount']);
        $printVendorRecvCash  = (float) $printVendorRecv->where('payment_method', 'cash')->sum('amount');
        $printVendorRecvBank  = (float) $printVendorRecv->where('payment_method', 'bank_transfer')->sum('amount');
        $printVendorRecvTotal = (float) $printVendorRecv->sum('amount');

        // Cash/bank paid out to customers (manual khata debits with a payment method)
        $printPayouts     = AccountLedger::whereDate('created_at', today())
            ->where('type', 'debit')->whereNotNull('payment_method')
            ->get(['payment_method', 'amount']);
        $printPayoutCash  = (float) $printPayouts->where('payment_method', 'cash')->sum('amount');
        $printPayoutBank  = (float) $printPayouts->where('payment_method', 'bank_transfer')->sum('amount');
        $printPayoutTotal = (float) $printPayouts->sum('amount');

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
            'pos_total'           => $posOrders->sum('total'),
            'pos_cash'            => $posCash,
            'pos_bank'            => $posBank,
            'expenses'            => $printExpenseTotal,
            'expense_cash'        => $printExpenseCash,
            'expense_bank'        => $printExpenseBank,
            'khata_total'         => $khataTotal,
            'khata_cash'          => $khataCash,
            'khata_bank'          => $khataBank,
            'khata_other'         => $khataEntries->whereNotIn('payment_method', ['cash','bank_transfer'])->sum('amount'),
            'khata_by_bank'       => $khataByBank,
            'return_total'        => $returnTotal,
            'return_cash'         => $returnCash,
            'return_bank'         => $returnBank,
            'purchases_total'     => $purchasesTotalPrint,
            'purchases_paid'      => $purchasesPaidPrint,
            'purchases_due'       => $purchasesTotalPrint - $purchasesPaidPrint,
            'purchases_cash'      => $purchasesCashPrint,
            'purchases_bank'      => $purchasesBankPrint,
            'vendor_pay_total'    => $printVendorPayTotal,
            'vendor_pay_cash'     => $printVendorPayCash,
            'vendor_pay_bank'     => $printVendorPayBank,
            'vendor_recv_total'   => $printVendorRecvTotal,
            'vendor_recv_cash'    => $printVendorRecvCash,
            'vendor_recv_bank'    => $printVendorRecvBank,
            'payout_total'        => $printPayoutTotal,
            'payout_cash'         => $printPayoutCash,
            'payout_bank'         => $printPayoutBank,
            'total_cash'          => $posCash + $khataCash + $printVendorRecvCash - $returnCash - $purchasesCashPrint - $printExpenseCash - $printVendorPayCash - $printPayoutCash,
            'total_bank'          => $posBank + $khataBank + $printVendorRecvBank - $returnBank - $purchasesBankPrint - $printExpenseBank - $printVendorPayBank - $printPayoutBank,
            'grand_total'         => $posOrders->sum('total') + $khataTotal + $printVendorRecvTotal - $returnTotal - $purchasesPaidPrint - $printExpenseTotal - $printVendorPayTotal - $printPayoutTotal,
            'store_name'          => Setting::get('shop_name', config('app.name')),
            'store_phone'         => Setting::get('shop_phone', ''),
            'date'                => today()->format('d M Y'),
        ];

        return view('admin.dashboard.today-report-print', compact('todayReport'));
    }

    public function salesmanTodayReportPrint()
    {
        $user = Auth::user();

        $posOrders = Order::where('served_by', $user->id)
            ->whereDate('created_at', today())
            ->where('source', 'pos')->where('status', 'delivered')
            ->get(['payment_method', 'total', 'cash_amount', 'bank_amount', 'amount_paid', 'bank_account_id']);

        $posCash = $posOrders->sum(fn($o) => match($o->payment_method) {
            'cash'    => (float) $o->total, 'split' => (float) $o->cash_amount,
            'partial' => $o->bank_account_id ? 0 : (float) $o->amount_paid, default => 0,
        });
        $posBank = $posOrders->sum(fn($o) => match($o->payment_method) {
            'bank_transfer' => (float) $o->total, 'split' => (float) $o->bank_amount,
            'partial'       => $o->bank_account_id ? (float) $o->amount_paid : 0, default => 0,
        });

        $returnOrders = ReturnOrder::where('processed_by', $user->id)
            ->whereDate('created_at', today())->whereIn('status', ['approved', 'completed'])
            ->get(['refund_method', 'refund_amount']);
        $returnTotal = (float) $returnOrders->sum('refund_amount');
        $returnCash  = (float) $returnOrders->where('refund_method', 'cash')->sum('refund_amount');
        $returnBank  = (float) $returnOrders->where('refund_method', 'bank_transfer')->sum('refund_amount');

        $khataEntries = AccountLedger::where('user_id', $user->id)
            ->whereDate('created_at', today())->where('type', 'credit')
            ->whereNull('return_id')->whereNull('order_id')
            ->get(['payment_method', 'amount']);
        $khataTotal = (float) $khataEntries->sum('amount');
        $khataCash  = (float) $khataEntries->where('payment_method', 'cash')->sum('amount');
        $khataBank  = (float) $khataEntries->where('payment_method', 'bank_transfer')->sum('amount');

        $printSalesmanExpenses     = Expense::whereDate('expense_date', today())->get(['payment_method', 'amount']);
        $printSalesmanExpenseTotal = (float) $printSalesmanExpenses->sum('amount');
        $printSalesmanExpenseCash  = (float) $printSalesmanExpenses->where('payment_method', 'cash')->sum('amount');
        $printSalesmanExpenseBank  = (float) $printSalesmanExpenses->where('payment_method', 'bank_transfer')->sum('amount');

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
            'expenses'     => $printSalesmanExpenseTotal,
            'expense_cash' => $printSalesmanExpenseCash,
            'expense_bank' => $printSalesmanExpenseBank,
            'total_cash'   => $posCash + $khataCash - $returnCash - $printSalesmanExpenseCash,
            'total_bank'   => $posBank + $khataBank - $returnBank - $printSalesmanExpenseBank,
            'grand_total'  => (float) $posOrders->sum('total') + $khataTotal - $returnTotal - $printSalesmanExpenseTotal,
            'purchases_total' => 0,
            'purchases_paid'  => 0,
            'purchases_due'   => 0,
            'purchases_cash'  => 0,
            'purchases_bank'  => 0,
            'vendor_pay_total' => 0, 'vendor_pay_cash' => 0, 'vendor_pay_bank' => 0,
            'vendor_recv_total' => 0, 'vendor_recv_cash' => 0, 'vendor_recv_bank' => 0,
            'payout_total' => 0, 'payout_cash' => 0, 'payout_bank' => 0,
            'store_name'   => Setting::get('shop_name', config('app.name')),
            'store_phone'  => Setting::get('shop_phone', ''),
            'salesman_name'=> $user->name,
            'date'         => today()->format('d M Y'),
        ];

        if ($user->can('purchases.view')) {
            $printPurchases = Purchase::whereDate('purchase_date', today())->get(['payment_method', 'total', 'amount_paid', 'bank_account_id']);
            $todayReport['purchases_total'] = (float) $printPurchases->sum('total');
            $todayReport['purchases_paid']  = (float) $printPurchases->sum('amount_paid');
            $todayReport['purchases_due']   = $todayReport['purchases_total'] - $todayReport['purchases_paid'];
            $todayReport['purchases_cash']  = (float) $printPurchases->filter(fn($p) => $p->payment_method === 'cash' || ($p->payment_method === 'partial' && !$p->bank_account_id))->sum('amount_paid');
            $todayReport['purchases_bank']  = (float) $printPurchases->filter(fn($p) => $p->payment_method === 'bank_transfer' || ($p->payment_method === 'partial' && $p->bank_account_id))->sum('amount_paid');
            $todayReport['total_cash']     -= $todayReport['purchases_cash'];
            $todayReport['total_bank']     -= $todayReport['purchases_bank'];
            $todayReport['grand_total']    -= $todayReport['purchases_paid'];
        }

        return view('salesman.today-report-print', compact('todayReport'));
    }

    public function dateRangeReport(Request $request)
    {
        $from = $request->filled('from') ? $request->date('from') : today();
        $to   = $request->filled('to')   ? $request->date('to')   : today();
        if ($to->lt($from)) $to = $from;

        // ── POS sales ────────────────────────────────────────────────────────
        $posOrders = Order::where('source', 'pos')
            ->where('status', 'delivered')
            ->whereBetween('created_at', [$from->startOfDay()->copy(), $to->copy()->endOfDay()])
            ->get(['payment_method', 'total', 'cash_amount', 'bank_amount', 'amount_paid', 'bank_account_id']);

        $posCash  = $posOrders->sum(fn($o) => match($o->payment_method) {
            'cash'    => (float) $o->total, 'split' => (float) $o->cash_amount,
            'partial' => $o->bank_account_id ? 0 : (float) $o->amount_paid, default => 0,
        });
        $posBank  = $posOrders->sum(fn($o) => match($o->payment_method) {
            'bank_transfer' => (float) $o->total, 'split' => (float) $o->bank_amount,
            'partial'       => $o->bank_account_id ? (float) $o->amount_paid : 0, default => 0,
        });
        $posTotal = (float) $posOrders->sum('total');

        // ── Khata receipts ───────────────────────────────────────────────────
        $khataEntries = AccountLedger::where('type', 'credit')
            ->whereNull('return_id')->whereNull('order_id')
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get(['payment_method', 'amount']);

        $khataCash  = (float) $khataEntries->where('payment_method', 'cash')->sum('amount');
        $khataBank  = (float) $khataEntries->where('payment_method', 'bank_transfer')->sum('amount');
        $khataOther = (float) $khataEntries->whereNotIn('payment_method', ['cash', 'bank_transfer'])->sum('amount');
        $khataTotal = (float) $khataEntries->sum('amount');

        // ── Expenses ─────────────────────────────────────────────────────────
        $expensesData = Expense::whereBetween('expense_date', [$from->copy(), $to->copy()])
            ->get(['payment_method', 'amount']);
        $expenseTotal = (float) $expensesData->sum('amount');
        $expenseCash  = (float) $expensesData->where('payment_method', 'cash')->sum('amount');
        $expenseBank  = (float) $expensesData->where('payment_method', 'bank_transfer')->sum('amount');

        // ── Returns ──────────────────────────────────────────────────────────
        $returnOrders = ReturnOrder::whereIn('status', ['approved', 'completed'])
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get(['refund_method', 'refund_amount']);
        $returnCash  = (float) $returnOrders->where('refund_method', 'cash')->sum('refund_amount');
        $returnBank  = (float) $returnOrders->where('refund_method', 'bank_transfer')->sum('refund_amount');
        $returnTotal = (float) $returnOrders->sum('refund_amount');

        // ── Purchases ────────────────────────────────────────────────────────
        $purchasesData    = Purchase::whereBetween('purchase_date', [$from->copy(), $to->copy()])
            ->get(['payment_method', 'total', 'amount_paid', 'bank_account_id']);
        $purchasesTotal   = (float) $purchasesData->sum('total');
        $purchasesPaid    = (float) $purchasesData->sum('amount_paid');
        $purchasesCash    = (float) $purchasesData->filter(fn($p) => $p->payment_method === 'cash' || ($p->payment_method === 'partial' && !$p->bank_account_id))->sum('amount_paid');
        $purchasesBank    = (float) $purchasesData->filter(fn($p) => $p->payment_method === 'bank_transfer' || ($p->payment_method === 'partial' && $p->bank_account_id))->sum('amount_paid');
        $purchasesDue     = $purchasesTotal - $purchasesPaid;

        // ── Manual vendor payments ────────────────────────────────────────────
        $rangeVendorPay     = VendorLedger::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->where('type', 'debit')->whereNull('purchase_id')->whereNotNull('payment_method')
            ->get(['payment_method', 'amount']);
        $rangeVendorPayCash  = (float) $rangeVendorPay->where('payment_method', 'cash')->sum('amount');
        $rangeVendorPayBank  = (float) $rangeVendorPay->where('payment_method', 'bank_transfer')->sum('amount');
        $rangeVendorPayTotal = (float) $rangeVendorPay->sum('amount');

        // ── Cash/bank received from vendors (refunds, damage returns) ────────
        $rangeVendorRecv     = VendorLedger::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->where('type', 'credit')->whereNotNull('payment_method')
            ->get(['payment_method', 'amount']);
        $rangeVendorRecvCash  = (float) $rangeVendorRecv->where('payment_method', 'cash')->sum('amount');
        $rangeVendorRecvBank  = (float) $rangeVendorRecv->where('payment_method', 'bank_transfer')->sum('amount');
        $rangeVendorRecvTotal = (float) $rangeVendorRecv->sum('amount');

        // ── Customer payouts (manual khata debits with a payment method) ─────
        $rangePayouts     = AccountLedger::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->where('type', 'debit')->whereNotNull('payment_method')
            ->get(['payment_method', 'amount']);
        $rangePayoutCash  = (float) $rangePayouts->where('payment_method', 'cash')->sum('amount');
        $rangePayoutBank  = (float) $rangePayouts->where('payment_method', 'bank_transfer')->sum('amount');
        $rangePayoutTotal = (float) $rangePayouts->sum('amount');

        // ── Products sold ────────────────────────────────────────────────────
        $productsSold = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'delivered')
            ->whereBetween('orders.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->whereNull('orders.deleted_at')
            ->select(
                'order_items.product_id',
                'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.line_total) as total_revenue')
            )
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('total_revenue')
            ->get();

        // ── Summary totals ───────────────────────────────────────────────────
        $report = [
            'pos_total'          => $posTotal,
            'pos_cash'           => $posCash,
            'pos_bank'           => $posBank,
            'khata_total'        => $khataTotal,
            'khata_cash'         => $khataCash,
            'khata_bank'         => $khataBank,
            'khata_other'        => $khataOther,
            'expense_total'      => $expenseTotal,
            'expense_cash'       => $expenseCash,
            'expense_bank'       => $expenseBank,
            'return_total'       => $returnTotal,
            'return_cash'        => $returnCash,
            'return_bank'        => $returnBank,
            'purchases_total'    => $purchasesTotal,
            'purchases_paid'     => $purchasesPaid,
            'purchases_due'      => $purchasesDue,
            'purchases_cash'     => $purchasesCash,
            'purchases_bank'     => $purchasesBank,
            'vendor_pay_total'   => $rangeVendorPayTotal,
            'vendor_pay_cash'    => $rangeVendorPayCash,
            'vendor_pay_bank'    => $rangeVendorPayBank,
            'vendor_recv_total'  => $rangeVendorRecvTotal,
            'vendor_recv_cash'   => $rangeVendorRecvCash,
            'vendor_recv_bank'   => $rangeVendorRecvBank,
            'payout_total'       => $rangePayoutTotal,
            'payout_cash'        => $rangePayoutCash,
            'payout_bank'        => $rangePayoutBank,
            'total_cash'         => $posCash + $khataCash + $rangeVendorRecvCash - $returnCash - $purchasesCash - $expenseCash - $rangeVendorPayCash - $rangePayoutCash,
            'total_bank'         => $posBank + $khataBank + $rangeVendorRecvBank - $returnBank - $purchasesBank - $expenseBank - $rangeVendorPayBank - $rangePayoutBank,
            'grand_total'        => $posTotal + $khataTotal + $rangeVendorRecvTotal - $returnTotal - $purchasesPaid - $expenseTotal - $rangeVendorPayTotal - $rangePayoutTotal,
            'total_orders'       => $posOrders->count(),
            'date_from'          => $from->format('d M Y'),
            'date_to'            => $to->format('d M Y'),
            'is_single_day'      => $from->eq($to),
        ];

        $isSingleDay = $from->eq($to);

        return view('admin.reports.date-range', compact('report', 'productsSold', 'from', 'to', 'isSingleDay'));
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
            ->get(['payment_method', 'total', 'cash_amount', 'bank_amount', 'amount_paid', 'bank_account_id']);

        $posCash = $posOrders->sum(fn($o) => match($o->payment_method) {
            'cash'    => (float) $o->total,
            'split'   => (float) $o->cash_amount,
            'partial' => $o->bank_account_id ? 0 : (float) $o->amount_paid,
            default   => 0,
        });
        $posBank = $posOrders->sum(fn($o) => match($o->payment_method) {
            'bank_transfer' => (float) $o->total,
            'split'         => (float) $o->bank_amount,
            'partial'       => $o->bank_account_id ? (float) $o->amount_paid : 0,
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
            ->whereNull('return_id')->whereNull('order_id')
            ->get(['payment_method', 'amount']);
        $khataTotal = (float) $khataEntries->sum('amount');
        $khataCash  = (float) $khataEntries->where('payment_method', 'cash')->sum('amount');
        $khataBank  = (float) $khataEntries->where('payment_method', 'bank_transfer')->sum('amount');

        $salesmanExpenses     = Expense::whereDate('expense_date', today())->get(['payment_method', 'amount']);
        $salesmanExpenseTotal = (float) $salesmanExpenses->sum('amount');
        $salesmanExpenseCash  = (float) $salesmanExpenses->where('payment_method', 'cash')->sum('amount');
        $salesmanExpenseBank  = (float) $salesmanExpenses->where('payment_method', 'bank_transfer')->sum('amount');

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
            'expenses'     => $salesmanExpenseTotal,
            'expense_cash' => $salesmanExpenseCash,
            'expense_bank' => $salesmanExpenseBank,
            'total_cash'   => $posCash + $khataCash - $returnCash - $salesmanExpenseCash,
            'total_bank'   => $posBank + $khataBank - $returnBank - $salesmanExpenseBank,
            'grand_total'  => (float) $posOrders->sum('total') + $khataTotal - $returnTotal - $salesmanExpenseTotal,
            'date'         => today()->format('d M Y'),
            // purchases keys initialised; adjusted after permission check below
            'purchases_total' => 0, 'purchases_paid' => 0, 'purchases_due' => 0,
            'purchases_cash'  => 0, 'purchases_bank' => 0,
        ];

        // Purchases (only if permission granted)
        if ($user->can('purchases.view')) {
            $salePurchases = Purchase::whereDate('purchase_date', today())->get(['payment_method', 'total', 'amount_paid', 'bank_account_id']);
            $todayReport['purchases_total'] = (float) $salePurchases->sum('total');
            $todayReport['purchases_paid']  = (float) $salePurchases->sum('amount_paid');
            $todayReport['purchases_due']   = $todayReport['purchases_total'] - $todayReport['purchases_paid'];
            $todayReport['purchases_cash']  = (float) $salePurchases->filter(fn($p) => $p->payment_method === 'cash' || ($p->payment_method === 'partial' && !$p->bank_account_id))->sum('amount_paid');
            $todayReport['purchases_bank']  = (float) $salePurchases->filter(fn($p) => $p->payment_method === 'bank_transfer' || ($p->payment_method === 'partial' && $p->bank_account_id))->sum('amount_paid');
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
            ->whereNull('return_id')->whereNull('order_id')
            ->with(['customer', 'bankAccount'])->latest()->get();

        $todayPurchasesList = $user->can('purchases.view')
            ? Purchase::whereDate('purchase_date', today())->with(['vendor', 'items'])->latest()->get()
            : collect();

        // ── Khata payment reminders ───────────────────────────────────────
        $khataReminders = AccountLedger::where('type', 'debit')
            ->whereNotNull('promise_date')
            ->where('promise_date', '<=', today()->addDays(5))
            ->whereHas('customer', fn($q) => $q->where('credit_balance', '<', 0))
            ->with('customer')
            ->orderBy('promise_date')
            ->get();

        return view('salesman.dashboard', compact(
            'todaySales', 'todayOrders', 'monthSales', 'lowStockCount',
            'recentOrders', 'lowStockItems', 'pendingOrders',
            'todayReport', 'todayPosOrders', 'todayReturnsList',
            'todayKhataEntries', 'todayPurchasesList', 'khataReminders'
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sub shop panel
    // ─────────────────────────────────────────────────────────────────────────

    public function subshopDashboard()
    {
        $user   = Auth::user();
        $shopId = $user->shopId();
        $shop   = $user->shop;

        $todaySales  = Order::forShop($shopId)->whereDate('created_at', today())
                            ->where('status', 'delivered')->sum('total');
        $todayOrders = Order::forShop($shopId)->whereDate('created_at', today())->count();
        $monthSales  = Order::forShop($shopId)
                            ->whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year)
                            ->where('status', 'delivered')
                            ->sum('total');

        $customersCount   = Customer::forShop($shopId)->count();
        $outstandingKhata = abs((float) Customer::forShop($shopId)->where('credit_balance', '<', 0)->sum('credit_balance'));

        $stockUnits = (int) ShopStock::where('shop_id', $shopId)->sum('quantity')
            + SerialNumber::forShop($shopId)->where('status', 'in_stock')->count();

        $recentOrders = Order::forShop($shopId)->with('customer')->latest()->take(8)->get();

        $todayReport = $this->buildShopTodayReport($shopId);

        // Khata payment reminders for this shop's customers
        $khataReminders = AccountLedger::where('type', 'debit')
            ->whereNotNull('promise_date')
            ->where('promise_date', '<=', today()->addDays(5))
            ->whereHas('customer', fn($q) => $q->forShop($shopId)->where('credit_balance', '<', 0))
            ->with('customer')
            ->orderBy('promise_date')
            ->get();

        return view('shop.dashboard', compact(
            'shop', 'todaySales', 'todayOrders', 'monthSales',
            'customersCount', 'outstandingKhata', 'stockUnits',
            'recentOrders', 'todayReport', 'khataReminders'
        ));
    }

    public function subshopTodayReportPrint()
    {
        $user   = Auth::user();
        $shopId = $user->shopId();

        $todayReport = $this->buildShopTodayReport($shopId) + [
            'store_name'    => $user->shop?->name ?? Setting::get('shop_name', config('app.name')),
            'store_phone'   => $user->shop?->phone ?? '',
            'salesman_name' => $user->name,
        ];

        return view('salesman.today-report-print', compact('todayReport'));
    }

    /**
     * Same daily cash reconciliation as the salesman report, but scoped by
     * shop instead of user. Sub shops never purchase, so purchase keys are 0.
     */
    private function buildShopTodayReport(?int $shopId): array
    {
        $posOrders = Order::forShop($shopId)
            ->whereDate('created_at', today())
            ->where('source', 'pos')->where('status', 'delivered')
            ->get(['payment_method', 'total', 'cash_amount', 'bank_amount', 'amount_paid', 'bank_account_id']);

        $posCash = $posOrders->sum(fn($o) => match($o->payment_method) {
            'cash'    => (float) $o->total, 'split' => (float) $o->cash_amount,
            'partial' => $o->bank_account_id ? 0 : (float) $o->amount_paid, default => 0,
        });
        $posBank = $posOrders->sum(fn($o) => match($o->payment_method) {
            'bank_transfer' => (float) $o->total, 'split' => (float) $o->bank_amount,
            'partial'       => $o->bank_account_id ? (float) $o->amount_paid : 0, default => 0,
        });

        $returnOrders = ReturnOrder::whereHas('order', fn($q) => $q->forShop($shopId))
            ->whereDate('created_at', today())->whereIn('status', ['approved', 'completed'])
            ->get(['refund_method', 'refund_amount']);
        $returnTotal = (float) $returnOrders->sum('refund_amount');
        $returnCash  = (float) $returnOrders->where('refund_method', 'cash')->sum('refund_amount');
        $returnBank  = (float) $returnOrders->where('refund_method', 'bank_transfer')->sum('refund_amount');

        $khataEntries = AccountLedger::whereHas('customer', fn($q) => $q->forShop($shopId))
            ->whereDate('created_at', today())->where('type', 'credit')
            ->whereNull('return_id')->whereNull('order_id')
            ->get(['payment_method', 'amount']);
        $khataTotal = (float) $khataEntries->sum('amount');
        $khataCash  = (float) $khataEntries->where('payment_method', 'cash')->sum('amount');
        $khataBank  = (float) $khataEntries->where('payment_method', 'bank_transfer')->sum('amount');

        $expenses     = Expense::forShop($shopId)->whereDate('expense_date', today())->get(['payment_method', 'amount']);
        $expenseTotal = (float) $expenses->sum('amount');
        $expenseCash  = (float) $expenses->where('payment_method', 'cash')->sum('amount');
        $expenseBank  = (float) $expenses->where('payment_method', 'bank_transfer')->sum('amount');

        // Cash/bank paid out to this shop's customers (manual khata debits with a method)
        $payouts     = AccountLedger::whereHas('customer', fn($q) => $q->forShop($shopId))
            ->whereDate('created_at', today())
            ->where('type', 'debit')->whereNotNull('payment_method')
            ->get(['payment_method', 'amount']);
        $payoutTotal = (float) $payouts->sum('amount');
        $payoutCash  = (float) $payouts->where('payment_method', 'cash')->sum('amount');
        $payoutBank  = (float) $payouts->where('payment_method', 'bank_transfer')->sum('amount');

        return [
            'pos_total'    => (float) $posOrders->sum('total'),
            'pos_cash'     => $posCash,
            'pos_bank'     => $posBank,
            'return_total' => $returnTotal,
            'return_cash'  => $returnCash,
            'return_bank'  => $returnBank,
            'khata_total'  => $khataTotal,
            'khata_cash'   => $khataCash,
            'khata_bank'   => $khataBank,
            'expenses'     => $expenseTotal,
            'expense_cash' => $expenseCash,
            'expense_bank' => $expenseBank,
            'payout_total' => $payoutTotal,
            'payout_cash'  => $payoutCash,
            'payout_bank'  => $payoutBank,
            'total_cash'   => $posCash + $khataCash - $returnCash - $expenseCash - $payoutCash,
            'total_bank'   => $posBank + $khataBank - $returnBank - $expenseBank - $payoutBank,
            'grand_total'  => (float) $posOrders->sum('total') + $khataTotal - $returnTotal - $expenseTotal - $payoutTotal,
            'purchases_total' => 0, 'purchases_paid' => 0, 'purchases_due' => 0,
            'purchases_cash'  => 0, 'purchases_bank' => 0,
            'vendor_pay_total' => 0, 'vendor_pay_cash' => 0, 'vendor_pay_bank' => 0,
            'vendor_recv_total' => 0, 'vendor_recv_cash' => 0, 'vendor_recv_bank' => 0,
            'date'         => today()->format('d M Y'),
        ];
    }
}
