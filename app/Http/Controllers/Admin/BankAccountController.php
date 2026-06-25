<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountLedger;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\ReturnOrder;
use App\Models\Setting;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index()
    {
        $banks = BankAccount::orderBy('sort_order')->orderBy('id')->get();

        // ── Cash balance ────────────────────────────────────────────────────
        $cashOpening = (float) Setting::get('cash_opening_balance', 0);

        $cashInPos = Order::where('status', 'delivered')
            ->whereNull('deleted_at')
            ->whereIn('payment_method', ['cash', 'split'])
            ->get(['payment_method', 'total', 'cash_amount'])
            ->sum(fn($o) => $o->payment_method === 'split' ? (float) $o->cash_amount : (float) $o->total);

        $cashInEcom = (float) Order::where('status', 'delivered')
            ->where('source', 'ecommerce')
            ->where('payment_method', 'cash')
            ->whereNull('deleted_at')
            ->sum('total');

        $cashInKhata = (float) AccountLedger::where('type', 'credit')
            ->whereNull('return_id')
            ->where('payment_method', 'cash')
            ->sum('amount');

        $cashOutExpenses = (float) Expense::where('payment_method', 'cash')->sum('amount');

        $cashOutPurchases = (float) Purchase::whereIn('payment_method', ['cash', 'partial'])->sum('amount_paid');

        $cashOutReturns = (float) ReturnOrder::where('refund_method', 'cash')
            ->whereIn('status', ['approved', 'completed'])
            ->sum('refund_amount');

        $cashSummary = [
            'opening'     => $cashOpening,
            'in_pos'      => $cashInPos,
            'in_ecom'     => $cashInEcom,
            'in_khata'    => $cashInKhata,
            'out_exp'     => $cashOutExpenses,
            'out_pur'     => $cashOutPurchases,
            'out_ret'     => $cashOutReturns,
            'balance'     => $cashOpening + $cashInPos + $cashInEcom + $cashInKhata
                             - $cashOutExpenses - $cashOutPurchases - $cashOutReturns,
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

            $bank->computed_in          = $bankInSales + $bankInSplit + $bankInPartial + $bankInKhata;
            $bank->computed_out         = $bankOutPurchases + $bankOutReturns + $bankOutExpenses;
            $bank->computed_out_expenses = $bankOutExpenses;
            $bank->computed_balance     = (float) $bank->opening_balance + $bank->computed_in - $bank->computed_out;
        }

        // ── Unattributed bank totals (ecom orders not linked to a specific account) ─
        $unattributedEcomBank = (float) Order::where('status', 'delivered')
            ->where('source', 'ecommerce')
            ->where('payment_method', 'bank_transfer')
            ->whereNull('deleted_at')
            ->sum('total');

        return view('admin.bank-accounts.index', compact(
            'banks', 'cashSummary', 'unattributedEcomBank'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label'           => 'required|string|max:100',
            'bank_name'       => 'required|string|max:100',
            'account_title'   => 'required|string|max:150',
            'account_number'  => 'nullable|string|max:100',
            'iban'            => 'nullable|string|max:100',
            'sort_order'      => 'integer|min:0',
            'is_active'       => 'boolean',
            'opening_balance' => 'numeric|min:0',
        ]);

        $data['is_active']       = $request->boolean('is_active', true);
        $data['sort_order']      = $request->integer('sort_order', 0);
        $data['opening_balance'] = $request->input('opening_balance', 0);

        BankAccount::create($data);
        return redirect()->route('admin.bank-accounts.index')->with('success', 'Bank account added.');
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $data = $request->validate([
            'label'           => 'required|string|max:100',
            'bank_name'       => 'required|string|max:100',
            'account_title'   => 'required|string|max:150',
            'account_number'  => 'nullable|string|max:100',
            'iban'            => 'nullable|string|max:100',
            'sort_order'      => 'integer|min:0',
            'is_active'       => 'boolean',
            'opening_balance' => 'numeric|min:0',
        ]);

        $data['is_active']       = $request->boolean('is_active', true);
        $data['sort_order']      = $request->integer('sort_order', 0);
        $data['opening_balance'] = $request->input('opening_balance', $bankAccount->opening_balance);

        $bankAccount->update($data);
        return redirect()->route('admin.bank-accounts.index')->with('success', 'Bank account updated.');
    }

    public function updateCashOpening(Request $request)
    {
        $request->validate(['cash_opening_balance' => 'required|numeric|min:0']);
        Setting::set('cash_opening_balance', $request->cash_opening_balance);
        return redirect()->route('admin.bank-accounts.index')->with('success', 'Cash opening balance updated.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        $bankAccount->delete();
        return redirect()->route('admin.bank-accounts.index')->with('success', 'Bank account deleted.');
    }
}
