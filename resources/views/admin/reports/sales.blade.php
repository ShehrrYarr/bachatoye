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
<div class="card p-4 mb-5 no-print" x-data="salesFilter()">

    {{-- Quick period chips --}}
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide shrink-0">Quick:</span>
        @foreach([
            'today'      => 'Today',
            'yesterday'  => 'Yesterday',
            'this_week'  => 'This Week',
            'last_week'  => 'Last Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_year'  => 'This Year',
        ] as $key => $label)
        <button type="button" @click="setRange('{{ $key }}')"
                class="text-xs px-3 py-1.5 rounded-lg border transition-all font-medium
                       {{ ($from === now()->toDateString() && $to === now()->toDateString() && $key === 'today')
                          || ($from === now()->startOfMonth()->toDateString() && $key === 'this_month')
                          ? 'bg-primary-600 text-white border-primary-600'
                          : 'border-gray-300 text-gray-600 hover:border-primary-400 hover:text-primary-600' }}">
            {{ $label }}
        </button>
        @endforeach
    </div>

    {{-- Date range inputs + source + submit --}}
    @php $salesRoute = auth()->user()->hasRole('admin') ? 'admin.reports.sales' : 'salesman.reports.sales'; @endphp
    <form method="GET" action="{{ route($salesRoute) }}" id="salesFilterForm"
          class="flex flex-wrap items-end gap-3">
        <div>
            <label class="form-label text-xs">From</label>
            <input type="date" name="date_from" x-model="dateFrom"
                   class="form-input text-sm" required>
        </div>
        <div>
            <label class="form-label text-xs">To</label>
            <input type="date" name="date_to" x-model="dateTo"
                   class="form-input text-sm" required>
        </div>
        <div>
            <label class="form-label text-xs">Source</label>
            <select name="source" class="form-select text-sm">
                <option value="">All Sources</option>
                <option value="ecommerce" {{ request('source') === 'ecommerce' ? 'selected' : '' }}>Ecommerce</option>
                <option value="pos" {{ request('source') === 'pos' ? 'selected' : '' }}>POS</option>
            </select>
        </div>
        <div>
            <label class="form-label text-xs">Section</label>
            <select name="section" class="form-select text-sm">
                <option value="">All Sections</option>
                @foreach($sections as $section)
                    <option value="{{ $section->id }}" {{ $sectionId == $section->id ? 'selected' : '' }}>
                        {{ $section->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-primary btn-sm">
            <i class="fas fa-filter mr-1"></i> Filter
        </button>
        <div class="text-xs text-gray-400 self-center ml-1">
            Showing: <span class="font-semibold text-gray-700">{{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</span>
        </div>
    </form>
</div>

{{-- Summary stats --}}
<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4 mb-6">
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
    <div class="stat-card">
        <div class="stat-icon bg-orange-100"><i class="fas fa-sync-alt text-orange-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900">Rs. {{ number_format($totalExchangeValue) }}</div>
            <div class="text-sm text-gray-500">Exchange Value</div>
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
                    <th class="text-right">Exchange</th>
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
                    <td class="text-right">
                        @if($order->exchange_value > 0)
                            <div class="text-xs font-semibold text-orange-600">Rs. {{ number_format($order->exchange_value) }}</div>
                            @if($order->exchange_item_name)
                                <div class="text-xs text-gray-400 max-w-[120px] truncate" title="{{ $order->exchange_item_name }}">{{ $order->exchange_item_name }}</div>
                            @endif
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td><span class="badge {{ $order->status === 'delivered' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">{{ ucfirst($order->status) }}</span></td>
                    <td class="text-xs text-gray-500">{{ $order->created_at->format('d M Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-8 text-gray-400">No orders in this period.</td></tr>
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
function salesFilter() {
    return {
        dateFrom: '{{ $from }}',
        dateTo:   '{{ $to }}',

        setRange(period) {
            const today = new Date();
            const fmt   = d => d.toISOString().slice(0, 10);

            const startOfWeek = d => {
                const copy = new Date(d);
                copy.setDate(d.getDate() - ((d.getDay() + 6) % 7)); // Monday
                return copy;
            };

            let from, to;
            switch (period) {
                case 'today':
                    from = to = fmt(today); break;
                case 'yesterday': {
                    const y = new Date(today); y.setDate(today.getDate() - 1);
                    from = to = fmt(y); break;
                }
                case 'this_week': {
                    from = fmt(startOfWeek(today)); to = fmt(today); break;
                }
                case 'last_week': {
                    const lws = startOfWeek(today); lws.setDate(lws.getDate() - 7);
                    const lwe = new Date(lws); lwe.setDate(lws.getDate() + 6);
                    from = fmt(lws); to = fmt(lwe); break;
                }
                case 'this_month':
                    from = fmt(new Date(today.getFullYear(), today.getMonth(), 1));
                    to   = fmt(today); break;
                case 'last_month': {
                    const lm = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    const lme = new Date(today.getFullYear(), today.getMonth(), 0);
                    from = fmt(lm); to = fmt(lme); break;
                }
                case 'this_year':
                    from = fmt(new Date(today.getFullYear(), 0, 1));
                    to   = fmt(today); break;
            }
            this.dateFrom = from;
            this.dateTo   = to;
            this.$nextTick(() => document.getElementById('salesFilterForm').submit());
        },
    };
}
</script>
@endpush
@endsection
