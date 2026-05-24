<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with(['category', 'user'])->latest();

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
        return view('admin.expenses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'amount'              => 'required|numeric|min:0.01',
            'description'         => 'required|string|max:255',
            'notes'               => 'nullable|string|max:1000',
            'expense_date'        => 'required|date',
            'payment_method'      => 'nullable|in:cash,bank_transfer,card,other',
            'receipt_image'       => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('receipt_image')) {
            $data['receipt_image'] = $request->file('receipt_image')->store('expenses', 'public');
        }

        $data['user_id'] = Auth::id();
        Expense::create($data);

        return redirect()->route('admin.expenses.index')->with('success', 'Expense recorded.');
    }

    public function edit(Expense $expense)
    {
        $categories = ExpenseCategory::all();
        return view('admin.expenses.edit', compact('expense', 'categories'));
    }

    public function update(Request $request, Expense $expense)
    {
        $data = $request->validate([
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'amount'              => 'required|numeric|min:0.01',
            'description'         => 'required|string|max:255',
            'notes'               => 'nullable|string|max:1000',
            'expense_date'        => 'required|date',
            'payment_method'      => 'nullable|in:cash,bank_transfer,card,other',
        ]);

        $expense->update($data);
        return redirect()->route('admin.expenses.index')->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return back()->with('success', 'Expense deleted.');
    }
}
