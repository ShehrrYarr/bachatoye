@extends('layouts.admin')
@section('title', 'Expense Report')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Expense Report</h1>
    <button onclick="window.print()" class="btn-outline btn-sm no-print">
        <i class="fas fa-print mr-1"></i> Print
    </button>
</div>

<div class="card p-4 mb-5 no-print">
    <form method="GET" action="{{ route('admin.reports.expenses') }}" class="flex flex-wrap gap-3 items-end">
        <div>
            <select name="period" class="form-select text-sm" onchange="this.form.submit()">
                @foreach(['this_month'=>'This Month','last_month'=>'Last Month','this_year'=>'This Year'] as $v=>$l)
                <option value="{{ $v }}" {{ request('period', 'this_month') === $v ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input text-sm">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input text-sm">
            <button type="submit" class="btn-primary btn-sm">Apply</button>
        </div>
    </form>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <div class="stat-icon bg-red-100"><i class="fas fa-receipt text-red-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold">Rs. {{ number_format($totalExpenses) }}</div>
            <div class="text-sm text-gray-500">Total Expenses</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-orange-100"><i class="fas fa-list text-orange-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold">{{ $totalCount }}</div>
            <div class="text-sm text-gray-500">Transactions</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-yellow-100"><i class="fas fa-calculator text-yellow-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold">Rs. {{ number_format($avgExpense) }}</div>
            <div class="text-sm text-gray-500">Average per Entry</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-purple-100"><i class="fas fa-tag text-purple-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold">{{ $categoryCount }}</div>
            <div class="text-sm text-gray-500">Categories</div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    {{-- By category --}}
    <div class="card">
        <div class="card-header"><h2 class="font-semibold text-gray-800">By Category</h2></div>
        <div class="card-body space-y-3">
            @forelse($byCategory as $cat)
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="font-medium text-gray-800">{{ $cat->category_name ?? 'Uncategorized' }}</span>
                    <span class="font-semibold">Rs. {{ number_format($cat->total) }}</span>
                </div>
                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-2 bg-red-400 rounded-full"
                         style="width: {{ $totalExpenses > 0 ? ($cat->total / $totalExpenses * 100) : 0 }}%"></div>
                </div>
                <div class="text-xs text-gray-400 mt-0.5">{{ $cat->count }} transactions ({{ $totalExpenses > 0 ? round($cat->total / $totalExpenses * 100, 1) : 0 }}%)</div>
            </div>
            @empty
            <p class="text-gray-400 text-sm">No data available.</p>
            @endforelse
        </div>
    </div>

    {{-- By payment method --}}
    <div class="card">
        <div class="card-header"><h2 class="font-semibold text-gray-800">By Payment Method</h2></div>
        <table class="data-table">
            <thead><tr><th>Method</th><th class="text-center">Count</th><th class="text-right">Total</th></tr></thead>
            <tbody>
                @forelse($byMethod as $method)
                <tr>
                    <td class="font-medium">{{ ucfirst($method->payment_method ?? 'cash') }}</td>
                    <td class="text-center">{{ $method->count }}</td>
                    <td class="text-right font-semibold">Rs. {{ number_format($method->total) }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center py-6 text-gray-400">No data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Expense detail --}}
<div class="card overflow-hidden">
    <div class="card-header"><h2 class="font-semibold text-gray-800">All Expenses</h2></div>
    <table class="data-table text-sm">
        <thead>
            <tr><th>Date</th><th>Description</th><th>Category</th><th>By</th><th class="text-right">Amount</th></tr>
        </thead>
        <tbody>
            @forelse($expenses as $expense)
            <tr>
                <td class="text-xs text-gray-500 whitespace-nowrap">{{ $expense->expense_date->format('d M Y') }}</td>
                <td class="font-medium text-gray-800">{{ $expense->description }}</td>
                <td>{{ $expense->category?->name ?? '—' }}</td>
                <td class="text-gray-500">{{ $expense->user?->name ?? '—' }}</td>
                <td class="text-right font-semibold text-gray-800">Rs. {{ number_format($expense->amount) }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center py-8 text-gray-400">No expenses in this period.</td></tr>
            @endforelse
        </tbody>
        @if($expenses->count() > 0)
        <tfoot>
            <tr class="bg-gray-50 font-bold">
                <td colspan="4" class="text-right">Total</td>
                <td class="text-right text-red-600">Rs. {{ number_format($totalExpenses) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>
@endsection
