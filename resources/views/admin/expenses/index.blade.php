@extends('layouts.admin')
@section('title', 'Expenses')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Expenses</h1>
    <a href="{{ route('admin.expenses.create') }}" class="btn-primary"><i class="fas fa-plus mr-2"></i> Add Expense</a>
</div>

{{-- Summary stats --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="stat-card">
        <div class="stat-icon bg-red-100"><i class="fas fa-receipt text-red-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900">Rs. {{ number_format($todayTotal) }}</div>
            <div class="text-sm text-gray-500">Today's Expenses</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-orange-100"><i class="fas fa-calendar-week text-orange-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900">Rs. {{ number_format($monthTotal) }}</div>
            <div class="text-sm text-gray-500">This Month</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-yellow-100"><i class="fas fa-calendar text-yellow-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900">Rs. {{ number_format($yearTotal) }}</div>
            <div class="text-sm text-gray-500">This Year</div>
        </div>
    </div>
</div>

<div class="card p-4 mb-5">
    <form method="GET" action="{{ route('admin.expenses.index') }}" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-48">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by description..." class="form-input text-sm">
        </div>
        <div>
            <select name="category" class="form-select text-sm">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input text-sm">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input text-sm">
        </div>
        <button type="submit" class="btn-primary btn-sm">Filter</button>
        @if(request()->hasAny(['q','category','date_from','date_to']))
        <a href="{{ route('admin.expenses.index') }}" class="btn-outline btn-sm">Clear</a>
        @endif
    </form>
</div>

<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Category</th>
                <th class="text-right">Amount</th>
                <th>Paid Via</th>
                <th>Date</th>
                <th>By</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $expense)
            <tr>
                <td class="font-medium text-gray-800">{{ $expense->description }}</td>
                <td>
                    @if($expense->category)
                    <span class="badge bg-gray-100 text-gray-600">{{ $expense->category->name }}</span>
                    @else <span class="text-gray-400">—</span> @endif
                </td>
                <td class="text-right font-semibold text-gray-800">Rs. {{ number_format($expense->amount) }}</td>
                <td class="text-sm text-gray-500">{{ ucfirst($expense->payment_method ?? 'cash') }}</td>
                <td class="text-sm text-gray-500">{{ $expense->expense_date->format('d M Y') }}</td>
                <td class="text-sm text-gray-500">{{ $expense->user?->name ?? '—' }}</td>
                <td class="text-right">
                    <form method="POST" action="{{ route('admin.expenses.destroy', $expense) }}"
                          onsubmit="return confirm('Delete this expense?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center py-12 text-gray-400">No expenses recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4 border-t border-gray-200">{{ $expenses->withQueryString()->links() }}</div>
</div>
@endsection
