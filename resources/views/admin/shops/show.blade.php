@extends('layouts.admin')
@section('title', $shop->name)

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.shops.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">{{ $shop->name }}</h1>
    <span class="badge bg-indigo-50 text-indigo-700 font-mono">{{ $shop->code }}</span>
    @if($shop->is_active)
        <span class="badge bg-green-100 text-green-700">Active</span>
    @else
        <span class="badge bg-red-100 text-red-700">Inactive</span>
    @endif
    <div class="ml-auto">
        <a href="{{ route('admin.shops.edit', $shop) }}" class="btn-outline btn-sm"><i class="fas fa-edit mr-1"></i> Edit</a>
    </div>
</div>

{{-- Stats --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 mb-6">
    <div class="stat-card">
        <div class="stat-icon bg-green-100"><i class="fas fa-money-bill-wave text-green-600"></i></div>
        <div>
            <div class="text-xl font-extrabold text-gray-900">Rs. {{ number_format($stats['today_sales']) }}</div>
            <div class="text-sm text-gray-500">Today's Sales</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-blue-100"><i class="fas fa-calendar text-blue-600"></i></div>
        <div>
            <div class="text-xl font-extrabold text-gray-900">Rs. {{ number_format($stats['month_sales']) }}</div>
            <div class="text-sm text-gray-500">This Month</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-purple-100"><i class="fas fa-shopping-bag text-purple-600"></i></div>
        <div>
            <div class="text-xl font-extrabold text-gray-900">{{ number_format($stats['total_orders']) }}</div>
            <div class="text-sm text-gray-500">Total Orders</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-orange-100"><i class="fas fa-users text-orange-600"></i></div>
        <div>
            <div class="text-xl font-extrabold text-gray-900">{{ number_format($stats['customers']) }}</div>
            <div class="text-sm text-gray-500">Customers</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-indigo-100"><i class="fas fa-boxes text-indigo-600"></i></div>
        <div>
            <div class="text-xl font-extrabold text-gray-900">{{ number_format($stats['stock_units']) }}</div>
            <div class="text-sm text-gray-500">Stock Units</div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        {{-- Recent orders --}}
        <div class="card">
            <div class="card-header"><h2 class="font-semibold text-gray-800">Recent Orders</h2></div>
            <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr><th>Order #</th><th class="text-right">Total</th><th>Payment</th><th>Status</th><th>Date</th></tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('admin.orders.show', $order) }}" class="text-primary-600 hover:underline font-mono text-sm">{{ $order->order_number }}</a>
                        </td>
                        <td class="text-right font-semibold">Rs. {{ number_format($order->total) }}</td>
                        <td><span class="badge bg-gray-100 text-gray-600">{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</span></td>
                        <td>{!! $order->status_badge !!}</td>
                        <td class="text-xs text-gray-500">{{ $order->created_at->format('d M Y h:i A') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-8 text-gray-400">No orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        {{-- Recent transfers --}}
        <div class="card">
            <div class="card-header"><h2 class="font-semibold text-gray-800">Recent Transfers</h2></div>
            <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr><th>Transfer #</th><th>Direction</th><th class="text-center">Items</th><th class="text-center">Qty</th><th>Date</th></tr>
                </thead>
                <tbody>
                    @forelse($recentTransfers as $transfer)
                    <tr>
                        <td class="font-mono text-sm">{{ $transfer->transfer_number }}</td>
                        <td class="text-sm">
                            {{ $transfer->from_label }} <i class="fas fa-arrow-right text-gray-300 mx-1 text-xs"></i> {{ $transfer->to_label }}
                        </td>
                        <td class="text-center text-sm">{{ $transfer->total_items }}</td>
                        <td class="text-center text-sm">{{ $transfer->total_qty }}</td>
                        <td class="text-xs text-gray-500">{{ $transfer->created_at->format('d M Y') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-8 text-gray-400">No transfers yet — send stock to this shop from the Transfers page.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    {{-- Info --}}
    <div class="space-y-6">
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Shop Info</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between gap-3"><span class="text-gray-500">Phone</span><span>{{ $shop->phone ?? '—' }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-gray-500">Address</span><span class="text-right">{{ $shop->address ?? '—' }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-gray-500">Opening Cash</span><span>Rs. {{ number_format($shop->cash_opening_balance) }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-gray-500">Created</span><span>{{ $shop->created_at->format('d M Y') }}</span></div>
            </div>
        </div>

        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Login Account</h2>
            @if($shop->loginUser)
            <div class="space-y-2 text-sm" x-data="{ show: false, pwd: {{ json_encode($shop->loginUser->password_plain ?? '') }} }">
                <div class="flex justify-between gap-3"><span class="text-gray-500">Name</span><span>{{ $shop->loginUser->name }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-gray-500">Email</span><span class="font-mono text-xs">{{ $shop->loginUser->email }}</span></div>
                <div class="flex justify-between gap-3 items-center">
                    <span class="text-gray-500">Password</span>
                    <span class="flex items-center gap-2">
                        <span class="font-mono text-xs" x-text="show ? pwd : '••••••••'"></span>
                        <button @click="show = !show" class="text-gray-400 hover:text-gray-700 text-xs"><i :class="show ? 'fas fa-eye-slash' : 'fas fa-eye'"></i></button>
                    </span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-gray-500">Status</span>
                    <span>{!! $shop->loginUser->is_active ? '<span class="badge bg-green-100 text-green-700">Active</span>' : '<span class="badge bg-red-100 text-red-700">Blocked</span>' !!}</span>
                </div>
            </div>
            @else
            <p class="text-sm text-gray-400">No login account — edit the shop to add one.</p>
            @endif
        </div>
    </div>
</div>
@endsection
