@extends('layouts.admin')
@section('title', 'Salesman Performance')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Salesman Performance</h1>
    <button onclick="window.print()" class="btn-outline btn-sm no-print">
        <i class="fas fa-print mr-1"></i> Print
    </button>
</div>

<div class="card p-4 mb-5 no-print">
    <form method="GET" action="{{ route('admin.reports.salesman_performance') }}" class="flex flex-wrap gap-3 items-end">
        <div>
            <select name="period" class="form-select text-sm" onchange="this.form.submit()">
                @foreach(['today'=>'Today','this_week'=>'This Week','this_month'=>'This Month','last_month'=>'Last Month','this_year'=>'This Year'] as $v=>$l)
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

{{-- Performance cards --}}
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 mb-6">
    @forelse($salesmen as $s)
    <div class="card p-5">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center shrink-0">
                <span class="font-bold text-primary-600 text-lg">{{ strtoupper(substr($s->name, 0, 2)) }}</span>
            </div>
            <div>
                <div class="font-bold text-gray-900">{{ $s->name }}</div>
                <div class="text-xs text-gray-400">{{ $s->email }}</div>
                @if($s->is_active)
                    <span class="badge bg-green-100 text-green-700 text-xs">Active</span>
                @else
                    <span class="badge bg-gray-100 text-gray-500 text-xs">Inactive</span>
                @endif
            </div>
        </div>

        <dl class="space-y-2 text-sm">
            <div class="flex justify-between border-b border-gray-100 pb-2">
                <dt class="text-gray-500">Total Orders</dt>
                <dd class="font-bold text-gray-900">{{ number_format($s->period_orders_count ?? 0) }}</dd>
            </div>
            <div class="flex justify-between border-b border-gray-100 pb-2">
                <dt class="text-gray-500">Total Sales</dt>
                <dd class="font-bold text-primary-700">Rs. {{ number_format($s->period_sales_total ?? 0) }}</dd>
            </div>
            <div class="flex justify-between border-b border-gray-100 pb-2">
                <dt class="text-gray-500">Avg. Order</dt>
                <dd class="font-semibold">
                    Rs. {{ $s->period_orders_count > 0 ? number_format(($s->period_sales_total ?? 0) / $s->period_orders_count) : 0 }}
                </dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Login Hours</dt>
                <dd class="font-semibold text-gray-700">{{ $s->total_login_hours ?? '0h' }}</dd>
            </div>
        </dl>

        <a href="{{ route('admin.salesmen.show', $s) }}" class="block mt-4 text-center text-xs text-primary-600 hover:underline">
            View Full Profile →
        </a>
    </div>
    @empty
    <div class="col-span-3 text-center py-16 text-gray-400">
        <i class="fas fa-user-tie text-5xl mb-4"></i>
        <p>No salesmen found.</p>
    </div>
    @endforelse
</div>

{{-- Comparison table --}}
@if($salesmen->count() > 1)
<div class="card">
    <div class="card-header"><h2 class="font-semibold text-gray-800">Comparison: {{ $periodLabel ?? 'This Month' }}</h2></div>
    <div class="overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>Salesman</th>
                <th class="text-center">Orders</th>
                <th class="text-right">Revenue</th>
                <th class="text-right">Avg. Order</th>
                <th class="text-center">Login Days</th>
                <th class="text-right">Share</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = $salesmen->sum('period_sales_total'); @endphp
            @foreach($salesmen as $s)
            <tr>
                <td>
                    <div class="font-semibold text-gray-800">{{ $s->name }}</div>
                    <div class="text-xs text-gray-400">{{ $s->email }}</div>
                </td>
                <td class="text-center font-semibold">{{ $s->period_orders_count ?? 0 }}</td>
                <td class="text-right font-bold text-primary-700">Rs. {{ number_format($s->period_sales_total ?? 0) }}</td>
                <td class="text-right">
                    Rs. {{ $s->period_orders_count > 0 ? number_format(($s->period_sales_total ?? 0) / $s->period_orders_count) : 0 }}
                </td>
                <td class="text-center">{{ $s->login_days ?? 0 }}</td>
                <td class="text-right">
                    <div class="flex items-center justify-end gap-2">
                        <div class="w-16 h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-2 bg-primary-500 rounded-full"
                                 style="width: {{ $grandTotal > 0 ? (($s->period_sales_total ?? 0) / $grandTotal * 100) : 0 }}%"></div>
                        </div>
                        <span class="text-xs font-semibold">{{ $grandTotal > 0 ? round((($s->period_sales_total ?? 0) / $grandTotal) * 100, 1) : 0 }}%</span>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
</div>
@endif

{{-- Login log section --}}
<div class="card mt-6">
    <div class="card-header"><h2 class="font-semibold text-gray-800">Login Activity (Recent)</h2></div>
    <div class="overflow-x-auto">
    <table class="data-table text-sm">
        <thead>
            <tr><th>Salesman</th><th>Date</th><th>Login</th><th>Logout</th><th>Duration</th><th>IP</th></tr>
        </thead>
        <tbody>
            @forelse($recentLogins as $log)
            <tr>
                <td class="font-medium">{{ $log->user?->name ?? 'Unknown' }}</td>
                <td class="text-xs text-gray-500">{{ $log->logged_in_at->format('d M Y') }}</td>
                <td>{{ $log->logged_in_at->format('H:i') }}</td>
                <td>{{ $log->logged_out_at?->format('H:i') ?? '<span class="text-green-500 text-xs">Active</span>' }}</td>
                <td class="text-gray-500">{{ $log->duration ?? '—' }}</td>
                <td class="font-mono text-xs text-gray-400">{{ $log->ip_address ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center py-6 text-gray-400">No login records.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection
