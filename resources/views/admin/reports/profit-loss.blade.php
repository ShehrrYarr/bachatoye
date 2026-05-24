@extends('layouts.admin')
@section('title', 'Profit & Loss Report')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Profit & Loss Report</h1>
    <button onclick="window.print()" class="btn-outline btn-sm no-print">
        <i class="fas fa-print mr-1"></i> Print
    </button>
</div>

<div class="card p-4 mb-5 no-print">
    <form method="GET" action="{{ route('admin.reports.profit_loss') }}" class="flex flex-wrap gap-3 items-end">
        <div>
            <select name="period" class="form-select text-sm" onchange="this.form.submit()">
                @foreach(['this_month'=>'This Month','last_month'=>'Last Month','this_year'=>'This Year','last_year'=>'Last Year'] as $v=>$l)
                <option value="{{ $v }}" {{ request('period', 'this_month') === $v ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input text-sm" placeholder="From">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input text-sm" placeholder="To">
            <button type="submit" class="btn-primary btn-sm">Apply</button>
        </div>
    </form>
</div>

{{-- P&L Summary --}}
<div class="card mb-6">
    <div class="card-header">
        <h2 class="font-semibold text-gray-800">Summary: {{ $periodLabel }}</h2>
    </div>
    <div class="card-body">
        <div class="space-y-3">
            <div class="flex justify-between text-sm py-2 border-b border-gray-100">
                <span class="text-gray-600">Gross Revenue</span>
                <span class="font-semibold text-gray-900">Rs. {{ number_format($grossRevenue) }}</span>
            </div>
            <div class="flex justify-between text-sm py-2 border-b border-gray-100">
                <span class="text-gray-600">Discounts Given</span>
                <span class="font-semibold text-red-600">– Rs. {{ number_format($totalDiscounts) }}</span>
            </div>
            <div class="flex justify-between text-sm py-2 border-b border-gray-200 font-semibold">
                <span>Net Revenue</span>
                <span class="text-gray-900">Rs. {{ number_format($netRevenue) }}</span>
            </div>
            <div class="flex justify-between text-sm py-2 border-b border-gray-100">
                <span class="text-gray-600">Cost of Goods Sold (COGS)</span>
                <span class="font-semibold text-red-600">– Rs. {{ number_format($totalCogs) }}</span>
            </div>
            <div class="flex justify-between text-sm py-2 border-b border-gray-200 font-semibold">
                <span>Gross Profit</span>
                <span class="{{ $grossProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">Rs. {{ number_format($grossProfit) }}</span>
            </div>
            <div class="flex justify-between text-sm py-2 border-b border-gray-100">
                <span class="text-gray-600">Total Expenses</span>
                <span class="font-semibold text-red-600">– Rs. {{ number_format($totalExpenses) }}</span>
            </div>
            <div class="flex justify-between text-sm py-2 border-b border-gray-100">
                <span class="text-gray-600">Delivery Income</span>
                <span class="font-semibold text-green-600">+ Rs. {{ number_format($deliveryIncome) }}</span>
            </div>
            <div class="flex justify-between py-3 border-t-2 border-gray-300">
                <span class="font-bold text-base">Net Profit / Loss</span>
                <span class="font-extrabold text-xl {{ $netProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    Rs. {{ number_format($netProfit) }}
                </span>
            </div>
        </div>

        {{-- Margin --}}
        @if($netRevenue > 0)
        <div class="mt-4 p-3 bg-gray-50 rounded-xl flex items-center gap-4">
            <div class="text-sm text-gray-600">Net Margin:</div>
            <div class="font-bold text-lg {{ $netProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                {{ round(($netProfit / $netRevenue) * 100, 1) }}%
            </div>
            <div class="flex-1 h-3 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-3 {{ $netProfit >= 0 ? 'bg-green-500' : 'bg-red-500' }} rounded-full"
                     style="width: {{ min(100, abs(round(($netProfit / $netRevenue) * 100, 1))) }}%"></div>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Expenses breakdown --}}
    <div class="card">
        <div class="card-header"><h2 class="font-semibold text-gray-800">Expenses by Category</h2></div>
        <table class="data-table">
            <thead><tr><th>Category</th><th class="text-center">Count</th><th class="text-right">Total</th></tr></thead>
            <tbody>
                @forelse($expensesByCategory as $cat)
                <tr>
                    <td class="font-medium text-gray-800">{{ $cat->category_name ?? 'Uncategorized' }}</td>
                    <td class="text-center text-sm">{{ $cat->count }}</td>
                    <td class="text-right font-semibold text-sm">Rs. {{ number_format($cat->total) }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center py-6 text-gray-400">No expenses</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Monthly trend (if year view) --}}
    <div class="card">
        <div class="card-header"><h2 class="font-semibold text-gray-800">Monthly Revenue vs COGS</h2></div>
        <div class="card-body">
            <div class="space-y-3">
                @foreach($monthlyData as $month)
                <div>
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>{{ \Carbon\Carbon::createFromDate(null, $month['month'], 1)->format('F') }}</span>
                        <span>Rev: Rs.{{ number_format($month['revenue']/1000, 1) }}k | COGS: Rs.{{ number_format($month['cogs']/1000, 1) }}k</span>
                    </div>
                    <div class="flex gap-1 h-4">
                        @php $maxM = collect($monthlyData)->max('revenue') ?: 1; @endphp
                        <div class="bg-primary-400 rounded h-full" style="width: {{ ($month['revenue'] / $maxM) * 100 }}%"></div>
                    </div>
                </div>
                @endforeach
                @if(empty($monthlyData))
                <p class="text-gray-400 text-sm text-center py-4">No monthly data available.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
