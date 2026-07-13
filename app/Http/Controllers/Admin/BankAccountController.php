<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Setting;
use App\Models\Shop;
use App\Services\CashBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankAccountController extends Controller
{
    /** Sub shop logins may only touch their own shop's bank accounts. */
    private function guardShop(BankAccount $bankAccount): void
    {
        $user = Auth::user();
        abort_if($user->isSubshop() && $bankAccount->shop_id !== $user->shopId(), 404);
    }

    public function index(CashBalanceService $balances)
    {
        $shopId = Auth::user()->shopId();

        $data = $balances->forShop($shopId);
        $data['currentShop'] = $shopId ? Auth::user()->shop : null;

        return view('admin.bank-accounts.index', $data);
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

        $data['shop_id']         = Auth::user()->shopId();
        $data['is_active']       = $request->boolean('is_active', true);
        $data['sort_order']      = $request->integer('sort_order', 0);
        $data['opening_balance'] = $request->input('opening_balance', 0);

        BankAccount::create($data);
        return redirect()->route(Auth::user()->panelPrefix() . '.bank-accounts.index')->with('success', 'Bank account added.');
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $this->guardShop($bankAccount);

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
        return redirect()->route(Auth::user()->panelPrefix() . '.bank-accounts.index')->with('success', 'Bank account updated.');
    }

    public function updateCashOpening(Request $request)
    {
        $request->validate(['cash_opening_balance' => 'required|numeric|min:0']);

        $shopId = Auth::user()->shopId();
        if ($shopId) {
            Shop::find($shopId)?->update(['cash_opening_balance' => $request->cash_opening_balance]);
        } else {
            Setting::set('cash_opening_balance', $request->cash_opening_balance);
        }

        return redirect()->route(Auth::user()->panelPrefix() . '.bank-accounts.index')->with('success', 'Cash opening balance updated.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        $this->guardShop($bankAccount);
        $bankAccount->delete();
        return redirect()->route(Auth::user()->panelPrefix() . '.bank-accounts.index')->with('success', 'Bank account deleted.');
    }
}
