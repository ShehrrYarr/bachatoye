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
            <label class="form-label text-xs">Shop</label>
            @include('admin.partials.shop-filter')
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
<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 mb-2">
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
<div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <div class="stat-icon bg-orange-100"><i class="fas fa-sync-alt text-orange-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900">Rs. {{ number_format($totalExchangeValue) }}</div>
            <div class="text-sm text-gray-500">Exchange Value</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-amber-100"><i class="fas fa-undo text-amber-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900">Rs. {{ number_format($totalRefunds) }}</div>
            <div class="text-sm text-gray-500">Refunds (Returns)</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-red-100"><i class="fas fa-shopping-cart text-red-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900">Rs. {{ number_format($totalCogs) }}</div>
            <div class="text-sm text-gray-500">Total Cost (COGS)</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon {{ $totalProfit >= 0 ? 'bg-emerald-100' : 'bg-red-100' }}">
            <i class="fas fa-hand-holding-usd {{ $totalProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }}"></i>
        </div>
        <div>
            <div class="text-2xl font-extrabold {{ $totalProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                Rs. {{ number_format(abs($totalProfit)) }}
            </div>
            <div class="text-sm text-gray-500">
                {{ $totalProfit >= 0 ? 'Total Profit' : 'Total Loss' }}
                <span class="ml-1 text-xs font-bold {{ $totalProfit >= 0 ? 'text-emerald-500' : 'text-red-500' }}">
                    ({{ $totalMarginPct }}%)
                </span>
            </div>
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

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-6">

    {{-- Products sold, one card per section --}}
    @forelse($sectionProducts as $section)
    <div class="card" x-data="{ expanded: false }">
        <div class="card-header"><h2 class="font-semibold text-gray-800">{{ $section['name'] }}</h2></div>
        <table class="data-table">
            <thead><tr><th>Product</th><th class="text-center">Qty</th><th class="text-right">Revenue</th></tr></thead>
            <tbody>
                @foreach($section['products'] as $prod)
                <tr @if($loop->iteration > 5) x-show="expanded" @endif>
                    <td class="font-medium text-gray-800 text-sm">{{ $prod->product_name }}</td>
                    <td class="text-center text-sm">{{ $prod->total_qty }}</td>
                    <td class="text-right font-semibold text-sm">Rs. {{ number_format($prod->total_revenue) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($section['products']->count() > 5)
        <button @click="expanded = !expanded" class="w-full text-center text-sm text-primary-600 hover:text-primary-700 font-medium py-2 border-t border-gray-100">
            <span x-show="!expanded">Click to see more ({{ $section['products']->count() - 5 }} more)</span>
            <span x-show="expanded">Show less</span>
        </button>
        @endif
    </div>
    @empty
    <div class="card">
        <div class="card-header"><h2 class="font-semibold text-gray-800">Products Sold</h2></div>
        <div class="p-6 text-center text-gray-400">No data</div>
    </div>
    @endforelse
</div>

{{-- Sales by payment method --}}
<div class="card mb-6">
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
                    <th class="text-right">Cost</th>
                    <th class="text-right">Margin</th>
                    <th class="text-right">Exchange</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                @php
                    // Returns-aware: a returned line contributes (sale − refund) revenue
                    // and drops its cost basis back out if the unit was restocked —
                    // see App\Services\OrderProfitCalculator for the shared formula.
                    $itemProfits    = $order->items->map(fn($i) => \App\Services\OrderProfitCalculator::itemProfit($i));
                    $orderCogs      = $itemProfits->sum('cogs');
                    $orderRefund    = $itemProfits->sum('refund');
                    $orderProfit    = $itemProfits->sum('profit');
                    $orderMarginPct = (float) $order->total > 0 ? round($orderProfit / (float) $order->total * 100, 1) : 0;
                    $bankLabel = $order->bankAccount
                        ? $order->bankAccount->label . ($order->bankAccount->account_number ? ' — ' . $order->bankAccount->account_number : '')
                        : null;
                    $orderData = [
                        'number'      => $order->order_number,
                        'date'        => $order->created_at->format('d M Y, h:i A'),
                        'customer'    => $order->customer_name ?: 'Walk-in',
                        'salesman'    => $order->servedBy?->name ?? '—',
                        'payment'     => ucfirst(str_replace('_', ' ', $order->payment_method))
                                            . ($order->payment_method === 'bank_transfer' && $bankLabel ? " ({$bankLabel})" : ''),
                        // Show the cash/bank breakdown row for split, and for partial
                        // whenever a bank portion was actually used.
                        'is_split'    => in_array($order->payment_method, ['split', 'partial']) && (float) $order->bank_amount > 0,
                        'cash'        => (float) $order->cash_amount,
                        'bank'        => (float) $order->bank_amount,
                        'bank_label'  => $bankLabel,
                        'total'    => (float) $order->total,
                        'discount' => (float) $order->discount_amount,
                        'refund'   => $orderRefund,
                        'cogs'     => $orderCogs,
                        'profit'   => $orderProfit,
                        'margin'   => $orderMarginPct,
                        'items'    => $order->items->map(function ($i) {
                            $p = \App\Services\OrderProfitCalculator::itemProfit($i);
                            return [
                                'name'       => $i->product_name . ($i->color_name ? ' (' . $i->color_name . ')' : ''),
                                'qty'        => (int) $i->quantity,
                                'unit_price' => (float) $i->unit_price,
                                'cost_price' => (float) $i->cost_price,
                                'total'      => (float) $i->unit_price * (int) $i->quantity,
                                'refund'     => $p['refund'],
                                'profit'     => $p['profit'],
                            ];
                        })->values()->toArray(),
                    ];
                @endphp
                <tr class="cursor-pointer hover:bg-primary-50 transition-colors"
                    data-order="{{ json_encode($orderData) }}"
                    onclick="openOrderModal(JSON.parse(this.dataset.order))">
                    <td class="font-mono">{{ $order->order_number }}</td>
                    <td>{{ $order->customer_name }}</td>
                    <td><span class="badge {{ $order->source === 'pos' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">{{ strtoupper($order->source) }}</span></td>
                    <td>{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</td>
                    <td class="text-right font-semibold">Rs. {{ number_format($order->total) }}</td>
                    <td class="text-right text-gray-600">Rs. {{ number_format($orderCogs) }}</td>
                    <td class="text-right">
                        <div class="font-semibold {{ $orderProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                            Rs. {{ number_format(abs($orderProfit)) }}
                        </div>
                        <div class="text-xs {{ $orderMarginPct >= 0 ? 'text-emerald-500' : 'text-red-400' }}">
                            {{ $orderMarginPct }}%
                        </div>
                    </td>
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
                <tr><td colspan="10" class="text-center py-8 text-gray-400">No orders in this period.</td></tr>
                @endforelse
            </tbody>
            @if($orders->count() > 1)
            <tfoot class="bg-gray-50 border-t-2 border-gray-200 font-semibold text-sm">
                <tr>
                    <td colspan="4" class="px-4 py-3 text-gray-700">
                        {{ $totalOrders }} orders total
                    </td>
                    <td class="px-4 py-3 text-right text-gray-900">Rs. {{ number_format($totalRevenue) }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">Rs. {{ number_format($totalCogs) }}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="{{ $totalProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                            Rs. {{ number_format(abs($totalProfit)) }}
                        </div>
                        <div class="text-xs font-normal {{ $totalMarginPct >= 0 ? 'text-emerald-500' : 'text-red-400' }}">
                            {{ $totalMarginPct }}%
                        </div>
                    </td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    @if(method_exists($orders, 'links'))
    <div class="p-4 border-t border-gray-200">{{ $orders->withQueryString()->links() }}</div>
    @endif
</div>

{{-- Order detail modal --}}
<div id="orderModalBackdrop"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 hidden"
     onclick="if(event.target===this) closeOrderModal()">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden">
        {{-- Header --}}
        <div class="flex items-start justify-between px-6 py-4 border-b border-gray-200">
            <div>
                <h3 class="text-lg font-bold text-gray-900 font-mono" id="omOrderNumber"></h3>
                <p class="text-xs text-gray-400 mt-0.5" id="omDate"></p>
            </div>
            <button onclick="closeOrderModal()"
                    class="text-gray-400 hover:text-gray-700 text-2xl leading-none ml-4 mt-0.5 transition-colors">&times;</button>
        </div>
        {{-- Meta info --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 px-6 py-3 border-b border-gray-100 text-sm">
            <div>
                <div class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Customer</div>
                <div class="font-medium text-gray-800 mt-0.5" id="omCustomer"></div>
            </div>
            <div>
                <div class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Salesman</div>
                <div class="font-medium text-gray-800 mt-0.5" id="omSalesman"></div>
            </div>
            <div>
                <div class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Payment</div>
                <div class="font-medium text-gray-800 mt-0.5" id="omPayment"></div>
            </div>
            <div id="omDiscountWrap" class="hidden">
                <div class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Discount</div>
                <div class="font-medium text-red-600 mt-0.5" id="omDiscount"></div>
            </div>
            <div id="omRefundWrap" class="hidden">
                <div class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Refunded (Returns)</div>
                <div class="font-medium text-amber-600 mt-0.5" id="omRefund"></div>
            </div>
        </div>
        {{-- Split payment breakdown (shown only for split orders) --}}
        <div id="omSplitWrap" class="hidden px-6 py-2 bg-blue-50 border-b border-blue-100 text-sm flex gap-6">
            <span class="text-gray-600">Cash: <span class="font-semibold text-gray-900" id="omCashAmt"></span></span>
            <span class="text-gray-600"><span id="omBankLabel">Bank</span>: <span class="font-semibold text-gray-900" id="omBankAmt"></span></span>
        </div>
        {{-- Items table --}}
        <div class="overflow-y-auto flex-1 px-6 py-4">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-gray-200 text-xs text-gray-400 uppercase tracking-wide">
                        <th class="text-left pb-2 font-semibold">Item</th>
                        <th class="text-center pb-2 font-semibold">Qty</th>
                        <th class="text-right pb-2 font-semibold">Unit Price</th>
                        <th class="text-right pb-2 font-semibold">Cost</th>
                        <th class="text-right pb-2 font-semibold">Total</th>
                        <th class="text-right pb-2 font-semibold">Profit</th>
                    </tr>
                </thead>
                <tbody id="omItemsBody"></tbody>
            </table>
        </div>
        {{-- Footer totals --}}
        <div class="border-t-2 border-gray-200 px-6 py-4 bg-gray-50 grid grid-cols-3 gap-4 text-sm">
            <div class="text-center">
                <div class="text-xs text-gray-400 uppercase tracking-wide font-semibold mb-1">Revenue</div>
                <div class="font-bold text-gray-900 text-base" id="omTotal"></div>
            </div>
            <div class="text-center">
                <div class="text-xs text-gray-400 uppercase tracking-wide font-semibold mb-1">Cost (COGS)</div>
                <div class="font-semibold text-gray-600 text-base" id="omCogs"></div>
            </div>
            <div class="text-center">
                <div class="text-xs text-gray-400 uppercase tracking-wide font-semibold mb-1">Profit</div>
                <div class="font-bold text-base" id="omProfit"></div>
            </div>
        </div>
    </div>
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

const _fmt = n => 'Rs. ' + Math.round(Math.abs(n)).toLocaleString('en-PK');

function openOrderModal(data) {
    document.getElementById('omOrderNumber').textContent = data.number;
    document.getElementById('omDate').textContent = data.date;
    document.getElementById('omCustomer').textContent = data.customer;
    document.getElementById('omSalesman').textContent = data.salesman;
    document.getElementById('omPayment').textContent = data.payment;

    const discountWrap = document.getElementById('omDiscountWrap');
    if (data.discount > 0) {
        discountWrap.classList.remove('hidden');
        document.getElementById('omDiscount').textContent = '− ' + _fmt(data.discount);
    } else {
        discountWrap.classList.add('hidden');
    }

    const refundWrap = document.getElementById('omRefundWrap');
    if (data.refund > 0) {
        refundWrap.classList.remove('hidden');
        document.getElementById('omRefund').textContent = '− ' + _fmt(data.refund);
    } else {
        refundWrap.classList.add('hidden');
    }

    const splitWrap = document.getElementById('omSplitWrap');
    if (data.is_split) {
        splitWrap.classList.remove('hidden');
        document.getElementById('omCashAmt').textContent = _fmt(data.cash);
        document.getElementById('omBankLabel').textContent = data.bank_label ? `Bank (${data.bank_label})` : 'Bank';
        document.getElementById('omBankAmt').textContent = _fmt(data.bank);
    } else {
        splitWrap.classList.add('hidden');
    }

    const tbody = document.getElementById('omItemsBody');
    tbody.innerHTML = data.items.map(item => {
        const profitColor = item.profit >= 0 ? 'text-emerald-600' : 'text-red-600';
        return `<tr class="border-b border-gray-100 hover:bg-gray-50">
            <td class="py-2 pr-3 font-medium text-gray-800">${item.name}</td>
            <td class="py-2 text-center text-gray-600">${item.qty}</td>
            <td class="py-2 text-right text-gray-700">${_fmt(item.unit_price)}</td>
            <td class="py-2 text-right text-gray-500">${_fmt(item.cost_price)}</td>
            <td class="py-2 text-right font-semibold text-gray-900">${_fmt(item.total)}</td>
            <td class="py-2 text-right font-semibold ${profitColor}">${_fmt(item.profit)}</td>
        </tr>`;
    }).join('');

    document.getElementById('omTotal').textContent = _fmt(data.total);
    document.getElementById('omCogs').textContent  = _fmt(data.cogs);

    const profitEl = document.getElementById('omProfit');
    profitEl.textContent = _fmt(data.profit) + ' (' + data.margin + '%)';
    profitEl.className   = 'font-bold text-base ' + (data.profit >= 0 ? 'text-emerald-600' : 'text-red-600');

    document.getElementById('orderModalBackdrop').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeOrderModal() {
    document.getElementById('orderModalBackdrop').classList.add('hidden');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeOrderModal(); });
</script>
@endpush
@endsection
