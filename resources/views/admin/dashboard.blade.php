@extends('layouts.admin')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 md:gap-4">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-3 md:p-6 flex items-start gap-3">
            <div class="stat-icon bg-blue-50 shrink-0">
                <i class="fas fa-shopping-bag text-blue-600 text-xl"></i>
            </div>
            <div class="min-w-0">
                <div class="text-lg md:text-2xl font-bold text-gray-900 truncate">{{ number_format($stats['today_sales']) }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Today Sale (Rs.)</div>
                <div class="text-xs text-gray-400 mt-1">{{ $stats['today_orders'] }} orders</div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-3 md:p-6 flex items-start gap-3">
            <div class="stat-icon bg-yellow-50 shrink-0">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
            <div class="min-w-0">
                <div class="text-lg md:text-2xl font-bold text-gray-900">{{ $stats['pending_orders'] }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Pending Orders</div>
                <div class="text-xs">
                    <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="text-blue-600 hover:underline">View all</a>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-3 md:p-6 flex items-start gap-3">
            <div class="stat-icon bg-red-50 shrink-0">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <div class="min-w-0">
                <div class="text-lg md:text-2xl font-bold text-gray-900">{{ $stats['low_stock'] }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Low Stock Items</div>
                <div class="text-xs">
                    <a href="{{ route('admin.inventory.low_stock') }}" class="text-red-600 hover:underline">View</a>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-3 md:p-6 flex items-start gap-3">
            <div class="stat-icon bg-orange-50 shrink-0">
                <i class="fas fa-hand-holding-usd text-orange-600 text-xl"></i>
            </div>
            <div class="min-w-0">
                <div class="text-lg md:text-2xl font-bold text-gray-900 truncate">{{ number_format($stats['outstanding_khata']) }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Outstanding Khata (Rs.)</div>
                <div class="text-xs">
                    <a href="{{ route('admin.reports.accounts') }}" class="text-orange-600 hover:underline">View all</a>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-3 md:p-6 flex items-start gap-3">
            <div class="stat-icon bg-pink-50 shrink-0">
                <i class="fas fa-receipt text-pink-600 text-xl"></i>
            </div>
            <div class="min-w-0">
                <div class="text-lg md:text-2xl font-bold text-gray-900 truncate">{{ number_format($stats['today_expenses']) }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Today's Expenses (Rs.)</div>
            </div>
        </div>
    </div>

    {{-- Sales Chart + Recent Orders --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        {{-- 7-Day Sales Chart --}}
        <div class="card lg:col-span-3">
            <div class="card-header">
                <h3 class="font-semibold text-gray-800">Sales (Last 7 Days)</h3>
            </div>
            <div class="card-body">
                <div class="flex items-end gap-2 h-40">
                    @php $maxVal = $salesChart->max('total') ?: 1; @endphp
                    @foreach($salesChart as $day)
                        <div class="flex-1 flex flex-col items-center gap-1">
                            <div class="text-xs text-gray-500">{{ number_format($day['total'] / 1000, 1) }}k</div>
                            <div class="w-full bg-primary-500 rounded-t-sm transition-all"
                                 style="height: {{ max(4, ($day['total'] / $maxVal) * 120) }}px"
                                 title="Rs. {{ number_format($day['total']) }}"></div>
                            <div class="text-xs text-gray-400">{{ $day['date'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Low Stock Alerts --}}
        <div class="card lg:col-span-2">
            <div class="card-header">
                <h3 class="font-semibold text-gray-800">Low Stock</h3>
                <a href="{{ route('admin.inventory.low_stock') }}" class="text-xs text-primary-600 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($lowStockItems as $item)
                    <div class="px-4 py-2.5 flex items-center justify-between">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-800 truncate">{{ $item->name }}</div>
                            <div class="text-xs text-gray-500">{{ $item->category?->name }}</div>
                        </div>
                        <span class="badge {{ $item->stock_quantity <= 0 ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' }} ml-2 shrink-0">
                            {{ $item->stock_quantity }} left
                        </span>
                    </div>
                @empty
                    <div class="px-4 py-6 text-center text-sm text-gray-500">All stock levels are healthy.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Recent Orders --}}
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-gray-800">Recent Orders</h3>
            <a href="{{ route('admin.orders.index') }}" class="btn-outline btn-sm">View All</a>
        </div>

        {{-- Mobile card view (hidden on md+) --}}
        <div class="md:hidden divide-y divide-gray-100">
            @forelse($recentOrders as $order)
            <div class="px-4 py-3">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="font-mono text-xs text-gray-500">{{ $order->order_number }}</span>
                    <span class="badge
                        @if($order->status === 'delivered') bg-green-100 text-green-700
                        @elseif($order->status === 'cancelled') bg-red-100 text-red-700
                        @elseif($order->status === 'shipped') bg-purple-100 text-purple-700
                        @else bg-blue-100 text-blue-700
                        @endif">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="min-w-0 flex-1 mr-3">
                        <div class="text-sm font-medium text-gray-800 truncate">{{ $order->customer_name }}</div>
                        <div class="text-xs text-gray-400">{{ $order->created_at->format('d M, H:i') }}</div>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-sm font-bold text-gray-900">Rs. {{ number_format($order->total) }}</div>
                        <a href="{{ route('admin.orders.show', $order) }}" class="text-xs text-primary-600 hover:underline">View →</a>
                    </div>
                </div>
            </div>
            @empty
            <div class="px-4 py-8 text-center text-sm text-gray-400">No orders yet.</div>
            @endforelse
        </div>

        {{-- Desktop table view (hidden on mobile) --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="table-auto w-full">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                        <tr>
                            <td class="font-mono text-xs">{{ $order->order_number }}</td>
                            <td>
                                <div class="font-medium">{{ $order->customer_name }}</div>
                                <div class="text-xs text-gray-400">{{ $order->customer_phone }}</div>
                            </td>
                            <td class="font-semibold">Rs. {{ number_format($order->total) }}</td>
                            <td>
                                <span class="badge {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                    {{ ucfirst($order->payment_status) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge
                                    @if($order->status === 'delivered') bg-green-100 text-green-700
                                    @elseif($order->status === 'cancelled') bg-red-100 text-red-700
                                    @elseif($order->status === 'shipped') bg-purple-100 text-purple-700
                                    @else bg-blue-100 text-blue-700
                                    @endif">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="text-xs text-gray-500">{{ $order->created_at->format('d M, H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}" class="text-primary-600 hover:underline text-xs">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-8 text-gray-400">No orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
