@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')
<div class="mb-6">
    <h1 class="text-xl font-bold text-gray-900">Welcome, {{ auth()->user()->name }}</h1>
    <p class="text-sm text-gray-500 mt-0.5">{{ now()->format('l, d F Y') }}</p>
</div>

{{-- Quick access buttons --}}
<div class="flex flex-wrap gap-3 mb-6">
    @can('pos.access')
    <a href="{{ route('pos.index') }}" class="btn-primary btn-lg">
        <i class="fas fa-cash-register mr-2"></i> Open POS
    </a>
    @endcan
    @can('orders.view')
    <a href="{{ route('admin.orders.index') }}" class="btn-outline btn-lg">
        <i class="fas fa-shopping-bag mr-2"></i> Orders
    </a>
    @endcan
    @can('inventory.view')
    <a href="{{ route('admin.inventory.index') }}" class="btn-outline btn-lg">
        <i class="fas fa-boxes mr-2"></i> Inventory
    </a>
    @endcan
    @can('customers.view')
    <a href="{{ route('admin.customers.index') }}" class="btn-outline btn-lg">
        <i class="fas fa-users mr-2"></i> Customers
    </a>
    @endcan
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <div class="stat-icon bg-primary-100"><i class="fas fa-chart-line text-primary-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900">Rs. {{ number_format($todaySales) }}</div>
            <div class="text-sm text-gray-500">My Sales Today</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-blue-100"><i class="fas fa-receipt text-blue-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900">{{ $todayOrders }}</div>
            <div class="text-sm text-gray-500">Orders Today</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-green-100"><i class="fas fa-calendar-week text-green-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900">Rs. {{ number_format($monthSales) }}</div>
            <div class="text-sm text-gray-500">This Month</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-orange-100"><i class="fas fa-exclamation-triangle text-orange-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-orange-600">{{ $lowStockCount }}</div>
            <div class="text-sm text-gray-500">Low Stock Alerts</div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Recent orders --}}
    <div class="card">
        <div class="card-header">
            <h2 class="font-semibold text-gray-800">My Recent Orders</h2>
            @can('orders.view')
            <a href="{{ route('admin.orders.index') }}" class="text-sm text-primary-600 hover:underline">View All</a>
            @endcan
        </div>
        <table class="data-table text-sm">
            <thead><tr><th>Order #</th><th>Customer</th><th class="text-right">Total</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($recentOrders as $order)
                <tr>
                    <td>
                        @can('orders.view')
                        <a href="{{ route('admin.orders.show', $order) }}" class="text-primary-600 hover:underline font-mono">{{ $order->order_number }}</a>
                        @else
                        <span class="font-mono">{{ $order->order_number }}</span>
                        @endcan
                    </td>
                    <td>{{ $order->customer_name }}</td>
                    <td class="text-right font-semibold">Rs. {{ number_format($order->total) }}</td>
                    <td>
                        <span class="badge {{ $order->status === 'delivered' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-6 text-gray-400">No orders today.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Low stock (if inventory access) --}}
    @can('inventory.view')
    <div class="card">
        <div class="card-header">
            <h2 class="font-semibold text-gray-800">Low Stock Alerts</h2>
            <a href="{{ route('admin.inventory.low_stock') }}" class="text-sm text-orange-600 hover:underline">View All</a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($lowStockItems as $product)
            <div class="flex items-center justify-between px-4 py-3">
                <div>
                    <div class="text-sm font-medium text-gray-800">{{ $product->name }}</div>
                    <div class="text-xs {{ $product->stock_quantity <= 0 ? 'text-red-500' : 'text-orange-500' }}">
                        {{ $product->stock_quantity <= 0 ? 'Out of Stock' : $product->stock_quantity.' units left' }}
                    </div>
                </div>
                @can('inventory.manage')
                <a href="{{ route('admin.inventory.adjust.form', $product) }}" class="btn-outline btn-sm">Restock</a>
                @endcan
            </div>
            @empty
            <div class="px-4 py-6 text-center text-gray-400 text-sm">
                <i class="fas fa-check-circle text-green-400 text-2xl mb-2"></i>
                <p>All products are well stocked</p>
            </div>
            @endforelse
        </div>
    </div>
    @else
    {{-- Pending orders if no inventory access --}}
    <div class="card">
        <div class="card-header"><h2 class="font-semibold text-gray-800">Pending Orders</h2></div>
        <table class="data-table text-sm">
            <thead><tr><th>Order #</th><th>Customer</th><th class="text-right">Total</th><th>Date</th></tr></thead>
            <tbody>
                @forelse($pendingOrders as $order)
                <tr>
                    <td class="font-mono">{{ $order->order_number }}</td>
                    <td>{{ $order->customer_name }}</td>
                    <td class="text-right font-semibold">Rs. {{ number_format($order->total) }}</td>
                    <td class="text-xs text-gray-500">{{ $order->created_at->format('d M') }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-6 text-gray-400">No pending orders.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endcan
</div>
@endsection
