<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index()
    {
        $banks = BankAccount::orderBy('sort_order')->orderBy('id')->get();
        return view('admin.bank-accounts.index', compact('banks'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label'          => 'required|string|max:100',
            'bank_name'      => 'required|string|max:100',
            'account_title'  => 'required|string|max:150',
            'account_number' => 'nullable|string|max:100',
            'iban'           => 'nullable|string|max:100',
            'sort_order'     => 'integer|min:0',
            'is_active'      => 'boolean',
        ]);

        $data['is_active']  = $request->boolean('is_active', true);
        $data['sort_order'] = $request->integer('sort_order', 0);

        BankAccount::create($data);
        return redirect()->route('admin.bank-accounts.index')->with('success', 'Bank account added.');
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $data = $request->validate([
            'label'          => 'required|string|max:100',
            'bank_name'      => 'required|string|max:100',
            'account_title'  => 'required|string|max:150',
            'account_number' => 'nullable|string|max:100',
            'iban'           => 'nullable|string|max:100',
            'sort_order'     => 'integer|min:0',
            'is_active'      => 'boolean',
        ]);

        $data['is_active']  = $request->boolean('is_active', true);
        $data['sort_order'] = $request->integer('sort_order', 0);

        $bankAccount->update($data);
        return redirect()->route('admin.bank-accounts.index')->with('success', 'Bank account updated.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        $bankAccount->delete();
        return redirect()->route('admin.bank-accounts.index')->with('success', 'Bank account deleted.');
    }
}
