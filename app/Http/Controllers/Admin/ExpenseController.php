<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with(['category', 'user', 'bankAccount'])->latest();

        if ($request->filled('category')) {
            $query->where('expense_category_id', $request->category);
        }
        if ($request->filled('date_from')) {
            $query->where('expense_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('expense_date', '<=', $request->date_to);
        }

        $expenses   = $query->paginate(25)->withQueryString();
        $categories = ExpenseCategory::orderBy('name')->get();
        $todayTotal = Expense::whereDate('expense_date', today())->sum('amount');
        $monthTotal = Expense::whereYear('expense_date', now()->year)->whereMonth('expense_date', now()->month)->sum('amount');
        $yearTotal  = Expense::whereYear('expense_date', now()->year)->sum('amount');

        return view('admin.expenses.index', compact('expenses', 'categories', 'todayTotal', 'monthTotal', 'yearTotal'));
    }

    public function create()
    {
        $categories = ExpenseCategory::all();
        $banks      = BankAccount::active()->orderBy('sort_order')->get();
        return view('admin.expenses.create', compact('categories', 'banks'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'amount'              => 'required|numeric|min:0.01',
            'description'         => 'required|string|max:255',
            'notes'               => 'nullable|string|max:1000',
            'expense_date'        => 'required|date',
            'payment_method'      => 'required|in:cash,bank_transfer',
            'bank_account_id'     => 'required_if:payment_method,bank_transfer|nullable|exists:bank_accounts,id',
            'receipt_image'       => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('receipt_image')) {
            $data['receipt_image'] = $request->file('receipt_image')->store('expenses', 'public');
        }

        if ($data['payment_method'] !== 'bank_transfer') {
            $data['bank_account_id'] = null;
        }

        $data['user_id'] = Auth::id();
        Expense::create($data);

        $rPrefix = Auth::user()->hasRole('admin') ? 'admin' : 'salesman';
        return redirect()->route("{$rPrefix}.expenses.index")->with('success', 'Expense recorded.');
    }

    public function edit(Expense $expense)
    {
        $categories = ExpenseCategory::all();
        $banks      = BankAccount::active()->orderBy('sort_order')->get();
        return view('admin.expenses.edit', compact('expense', 'categories', 'banks'));
    }

    public function update(Request $request, Expense $expense)
    {
        $data = $request->validate([
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'amount'              => 'required|numeric|min:0.01',
            'description'         => 'required|string|max:255',
            'notes'               => 'nullable|string|max:1000',
            'expense_date'        => 'required|date',
            'payment_method'      => 'required|in:cash,bank_transfer',
            'bank_account_id'     => 'required_if:payment_method,bank_transfer|nullable|exists:bank_accounts,id',
        ]);

        if ($data['payment_method'] !== 'bank_transfer') {
            $data['bank_account_id'] = null;
        }

        $expense->update($data);
        $rPrefix = Auth::user()->hasRole('admin') ? 'admin' : 'salesman';
        return redirect()->route("{$rPrefix}.expenses.index")->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return back()->with('success', 'Expense deleted.');
    }
}
