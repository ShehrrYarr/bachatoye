<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::withCount('expenses')->get();
        return view('admin.expense-categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:100|unique:expense_categories',
            'color' => 'required|string|size:7',
        ]);
        ExpenseCategory::create($data);
        return back()->with('success', 'Category added.');
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:100|unique:expense_categories,name,' . $expenseCategory->id,
            'color' => 'required|string|size:7',
        ]);
        $expenseCategory->update($data);
        return back()->with('success', 'Category updated.');
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        if ($expenseCategory->expenses()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete category with existing expenses.']);
        }
        $expenseCategory->delete();
        return back()->with('success', 'Category deleted.');
    }
}
