@extends('layouts.admin')
@section('title', 'Sales Report')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Sales Report</h1>
    <button onclick="window.print()" class="btn-outline btn-sm no-print">
        <i class="fas fa-print mr-1"></i> Print
    </button>
</div>

{{-- Date filters --}}
<div class="card p-4 mb-5 no-print">
    <form method="GET" action="{{ route('admin.reports.sales') }}" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="form-label text-xs">Period</label>
            <select name="period" class="form-select text-sm" onchange="toggleCustom(this.value)">
                @foreach(['today'=>'Today','yesterday'=>'Yesterday','this_week'=>'This Week','last_week'=>'Last Week','this_month'=>'This Month','last_month'=>'Last Month','this_year'=>'This Year','custom'=>'Custom Range'] as $v=>$l)
                <option value="{{ $v }}" {{ request('period', 'this_month') === $v ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div id="customRange" class="{{ request('period') === 'custom' ? 'flex' : 'hidden' }} gap-2">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input text-sm">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input text-sm">
        </div>
        <div>
            <select name="source" class="form-select text-sm">
                <option value="">All Sources</option>
                <option value="ecommerce" {{ request('source') === 'ecommerce' ? 'selected' : '' }}>Ecommerce</option>
                <option value="pos" {{ request('source') === 'pos' ? 'selected' : '' }}>POS</option>
            </select>
        </div>
        <button type="submit" class="btn-primary btn-sm">Apply</button>
    </form>
</div>

{{-- Summary stats --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <div class="stat-icon bg-primary-100"><i class="fas fa-chart-line text-primary-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900">Rs. {{ number_format($totalRevenue) }}</div>
            <div class="text-sm text-gray-500">Total Revenue</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-blue-100"><i class="fas fa-shopping-bag text-blue-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900">{{ number_format($totalOrders) }}</div>
            <div class="text-sm text-gray-500">Total Orders</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-green-100"><i class="fas fa-receipt text-green-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900">Rs. {{ number_format($avgOrderValue) }}</div>
            <div class="text-sm text-gray-500">Avg. Order Value</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-purple-100"><i class="fas fa-box text-purple-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900">{{ number_format($itemsSold) }}</div>
            <div class="text-sm text-gray-500">Items Sold</div>
        </div>
    </div>
</div>

{{-- Daily breakdown chart --}}
<div class="card mb-6">
    <div class="card-header"><h2 class="font-semibold text-gray-800">Daily Sales</h2></div>
    <div class="card-body">
        <div class="flex items-end gap-1 h-40 overflow-x-auto">
            @php $maxDay = collect($dailyData)->max('total') ?: 1; @endphp
            @foreach($dailyData as $day)
            <div class="flex flex-col items-center gap-1 shrink-0" style="min-width: 32px;">
                <div class="text-xs text-gray-400 whitespace-nowrap" style="font-size:9px;">
                    Rs.{{ number_format($day['total']/1000, 1) }}k
                </div>
                <div class="bg-primary-500 rounded-t w-6 transition-all hover:bg-primary-600"
                     style="height: {{ max(4, ($day['total'] / $maxDay) * 120) }}px;"
                     title="{{ $day['date'] }}: Rs.{{ number_format($day['total']) }}"></div>
                <div class="text-xs text-gray-400" style="font-size:9px; writing-mode:vertical-lr; transform:rotate(180deg);">
                    {{ \Carbon\Carbon::parse($day['date'])->format('d M') }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    {{-- Top products --}}
    <div class="card">
        <div class="card-header"><h2 class="font-semibold text-gray-800">Top Products</h2></div>
        <table class="data-table">
            <thead><tr><th>Product</th><th class="text-center">Qty</th><th class="text-right">Revenue</th></tr></thead>
            <tbody>
                @forelse($topProducts as $prod)
                <tr>
                    <td class="font-medium text-gray-800 text-sm">{{ $prod->product_name }}</td>
                    <td class="text-center text-sm">{{ $prod->total_qty }}</td>
                    <td class="text-right font-semibold text-sm">Rs. {{ number_format($prod->total_revenue) }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center py-6 text-gray-400">No data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Sales by payment method --}}
    <div class="card">
        <div class="card-header"><h2 class="font-semibold text-gray-800">By Payment Method</h2></div>
        <div class="card-body space-y-3">
            @foreach($byPayment as $method => $data)
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="font-medium text-gray-700">{{ ucfirst(str_replace('_', ' ', $method)) }}</span>
                    <span class="font-semibold">Rs. {{ number_format($data['total']) }} ({{ $data['count'] }} orders)</span>
                </div>
                <div class="h-2 bg-gray-100 rounded-full">
                    <div class="h-2 bg-primary-500 rounded-full"
                         style="width: {{ $totalRevenue > 0 ? ($data['total'] / $totalRevenue * 100) : 0 }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Orders table --}}
<div class="card overflow-hidden">
    <div class="card-header"><h2 class="font-semibold text-gray-800">Order Details</h2></div>
    <div class="overflow-x-auto">
        <table class="data-table text-sm">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Source</th>
                    <th>Payment</th>
                    <th class="text-right">Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td class="font-mono">{{ $order->order_number }}</td>
                    <td>{{ $order->customer_name }}</td>
                    <td><span class="badge {{ $order->source === 'pos' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">{{ strtoupper($order->source) }}</span></td>
                    <td>{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</td>
                    <td class="text-right font-semibold">Rs. {{ number_format($order->total) }}</td>
                    <td><span class="badge {{ $order->status === 'delivered' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">{{ ucfirst($order->status) }}</span></td>
                    <td class="text-xs text-gray-500">{{ $order->created_at->format('d M Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-8 text-gray-400">No orders in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($orders, 'links'))
    <div class="p-4 border-t border-gray-200">{{ $orders->withQueryString()->links() }}</div>
    @endif
</div>

@push('scripts')
<script>
function toggleCustom(val) {
    document.getElementById('customRange').classList.toggle('hidden', val !== 'custom');
    document.getElementById('customRange').classList.toggle('flex', val === 'custom');
}
</script>
@endpush
@endsection
