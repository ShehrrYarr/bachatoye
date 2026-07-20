<?php

namespace App\Services;

use App\Models\AccountLedger;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\ReturnOrder;
use App\Models\Setting;
use App\Models\Shop;
use App\Models\VendorLedger;

/**
 * Cash + per-bank balances for one location. NULL = main shop, which must
 * reproduce the exact numbers the old BankAccountController::index computed.
 * Purchases and ecommerce terms are main-shop-only (sub shops never purchase
 * and never take online orders).
 */
class CashBalanceService
{
    public function forShop(?int $shopId): array
    {
        $banks = BankAccount::forShop($shopId)->orderBy('sort_order')->orderBy('id')->get();

        // ── Cash balance ────────────────────────────────────────────────────
        $cashOpening = $shopId
            ? (float) (Shop::find($shopId)?->cash_opening_balance ?? 0)
            : (float) Setting::get('cash_opening_balance', 0);

        $cashInPos = Order::where('status', 'delivered')
            ->forShop($shopId)
            ->whereNull('deleted_at')
            ->whereIn('payment_method', ['cash', 'split'])
            ->get(['payment_method', 'total', 'cash_amount'])
            ->sum(fn($o) => $o->payment_method === 'split' ? (float) $o->cash_amount : (float) $o->total);

        $cashInEcom = $shopId ? 0.0 : (float) Order::where('status', 'delivered')
            ->where('source', 'ecommerce')
            ->where('payment_method', 'cash')
            ->whereNull('deleted_at')
            ->sum('total');

        // Genuine repayments only — sale-linked rows (order_id set) are already
        // counted through the order itself
        $cashInKhata = (float) AccountLedger::where('type', 'credit')
            ->whereNull('return_id')
            ->whereNull('order_id')
            ->where('payment_method', 'cash')
            ->whereHas('customer', fn($q) => $q->forShop($shopId))
            ->sum('amount');

        $cashOutExpenses = (float) Expense::forShop($shopId)->where('payment_method', 'cash')->sum('amount');

        $cashOutPurchases = $shopId ? 0.0
            : (float) Purchase::whereIn('payment_method', ['cash', 'partial'])->sum('amount_paid');

        $cashOutReturns = (float) ReturnOrder::where('refund_method', 'cash')
            ->whereHas('order', fn($q) => $q->forShop($shopId))
            ->whereIn('status', ['approved', 'completed'])
            ->sum('refund_amount');

        // Manual khata payouts to customers (debits with a payment method —
        // POS khata-sale debits carry no method and move no money)
        $cashOutKhata = (float) AccountLedger::where('type', 'debit')
            ->where('payment_method', 'cash')
            ->whereHas('customer', fn($q) => $q->forShop($shopId))
            ->sum('amount');

        // Payments made to vendors against their khata (main shop only)
        $cashOutVendor = $shopId ? 0.0
            : (float) VendorLedger::where('type', 'debit')
                ->where('payment_method', 'cash')
                ->sum('amount');

        $cashSummary = [
            'opening'    => $cashOpening,
            'in_pos'     => $cashInPos,
            'in_ecom'    => $cashInEcom,
            'in_khata'   => $cashInKhata,
            'out_exp'    => $cashOutExpenses,
            'out_pur'    => $cashOutPurchases,
            'out_ret'    => $cashOutReturns,
            'out_khata'  => $cashOutKhata,
            'out_vendor' => $cashOutVendor,
            'balance'    => $cashOpening + $cashInPos + $cashInEcom + $cashInKhata
                          - $cashOutExpenses - $cashOutPurchases - $cashOutReturns
                          - $cashOutKhata - $cashOutVendor,
        ];

        // ── Per-bank balances ───────────────────────────────────────────────
        foreach ($banks as $bank) {
            $bankInSales = (float) Order::where('status', 'delivered')
                ->where('bank_account_id', $bank->id)
                ->where('payment_method', 'bank_transfer')
                ->whereNull('deleted_at')
                ->sum('total');

            $bankInSplit = (float) Order::where('status', 'delivered')
                ->where('bank_account_id', $bank->id)
                ->where('payment_method', 'split')
                ->whereNull('deleted_at')
                ->sum('bank_amount');

            $bankInPartial = (float) Order::where('status', 'delivered')
                ->where('bank_account_id', $bank->id)
                ->where('payment_method', 'partial')
                ->whereNull('deleted_at')
                ->sum('amount_paid');

            $bankInKhata = (float) AccountLedger::where('type', 'credit')
                ->whereNull('return_id')
                ->whereNull('order_id')
                ->where('payment_method', 'bank_transfer')
                ->where('bank_account_id', $bank->id)
                ->sum('amount');

            $bankOutPurchases = (float) Purchase::where('payment_method', 'bank_transfer')
                ->where('bank_account_id', $bank->id)
                ->sum('amount_paid');

            $bankOutReturns = (float) ReturnOrder::where('refund_method', 'bank_transfer')
                ->where('bank_account_id', $bank->id)
                ->whereIn('status', ['approved', 'completed'])
                ->sum('refund_amount');

            $bankOutExpenses = (float) Expense::where('payment_method', 'bank_transfer')
                ->where('bank_account_id', $bank->id)
                ->sum('amount');

            $bankOutKhata = (float) AccountLedger::where('type', 'debit')
                ->where('payment_method', 'bank_transfer')
                ->where('bank_account_id', $bank->id)
                ->sum('amount');

            $bankOutVendor = (float) VendorLedger::where('type', 'debit')
                ->where('payment_method', 'bank_transfer')
                ->where('bank_account_id', $bank->id)
                ->sum('amount');

            $bank->computed_in           = $bankInSales + $bankInSplit + $bankInPartial + $bankInKhata;
            $bank->computed_out          = $bankOutPurchases + $bankOutReturns + $bankOutExpenses + $bankOutKhata + $bankOutVendor;
            $bank->computed_out_expenses = $bankOutExpenses;
            $bank->computed_balance      = (float) $bank->opening_balance + $bank->computed_in - $bank->computed_out;
        }

        // ── Unattributed bank totals (ecom orders not linked to an account) ─
        $unattributedEcomBank = $shopId ? 0.0 : (float) Order::where('status', 'delivered')
            ->where('source', 'ecommerce')
            ->where('payment_method', 'bank_transfer')
            ->whereNull('deleted_at')
            ->sum('total');

        return compact('banks', 'cashSummary', 'unattributedEcomBank');
    }
}
