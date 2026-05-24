@extends('layouts.admin')
@section('title', $salesman->name)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.salesmen.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-xl font-bold text-gray-900">{{ $salesman->name }}</h1>
        @if($salesman->is_active)
            <span class="badge bg-green-100 text-green-700">Active</span>
        @else
            <span class="badge bg-red-100 text-red-700">Inactive</span>
        @endif
    </div>
    <a href="{{ route('admin.salesmen.edit', $salesman) }}" class="btn-primary btn-sm">
        <i class="fas fa-edit mr-1"></i> Edit
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-5">

        {{-- Login history --}}
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-gray-800">Login History</h2>
                <a href="{{ route('admin.salesmen.login_log', $salesman) }}" class="text-sm text-primary-600 hover:underline">View All</a>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Logged In</th>
                        <th>Logged Out</th>
                        <th>Duration</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($salesman->loginLogs->take(15) as $log)
                    <tr>
                        <td class="text-xs text-gray-500">{{ $log->logged_in_at->format('d M Y') }}</td>
                        <td class="text-sm">{{ $log->logged_in_at->format('H:i:s') }}</td>
                        <td class="text-sm">{{ $log->logged_out_at ? $log->logged_out_at->format('H:i:s') : '<span class="text-green-500">Active</span>' }}</td>
                        <td class="text-sm text-gray-500">{{ $log->duration ?? '—' }}</td>
                        <td class="text-xs font-mono text-gray-400">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-8 text-gray-400">No login history.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Recent sales --}}
        <div class="card">
            <div class="card-header"><h2 class="font-semibold text-gray-800">Recent Sales</h2></div>
            <table class="data-table">
                <thead>
                    <tr><th>Order #</th><th>Customer</th><th>Total</th><th>Date</th></tr>
                </thead>
                <tbody>
                    @forelse($salesman->orders->take(10) as $order)
                    <tr>
                        <td><a href="{{ route('admin.orders.show', $order) }}" class="text-primary-600 hover:underline font-mono text-sm">{{ $order->order_number }}</a></td>
                        <td class="text-sm">{{ $order->customer_name }}</td>
                        <td class="text-sm font-semibold">Rs. {{ number_format($order->total) }}</td>
                        <td class="text-xs text-gray-500">{{ $order->created_at->format('d M Y') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center py-8 text-gray-400">No sales yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="space-y-5">
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Details</h2>
            <dl class="space-y-3 text-sm">
                <div><dt class="text-gray-500 text-xs">Email</dt><dd>{{ $salesman->email }}</dd></div>
                @if($salesman->phone)<div><dt class="text-gray-500 text-xs">Phone</dt><dd class="font-mono">{{ $salesman->phone }}</dd></div>@endif
                <div><dt class="text-gray-500 text-xs">Joined</dt><dd class="text-xs">{{ $salesman->created_at->format('d M Y') }}</dd></div>
            </dl>
        </div>

        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Permissions</h2>
            <div class="space-y-1.5">
                @foreach($salesman->permissions as $perm)
                <div class="flex items-center gap-2">
                    <i class="fas fa-check-circle text-green-500 text-xs"></i>
                    <span class="text-xs text-gray-700">{{ ucwords(str_replace(['.','_'], ' ', $perm->name)) }}</span>
                </div>
                @endforeach
                @if($salesman->permissions->isEmpty())
                <p class="text-xs text-gray-400">No permissions assigned.</p>
                @endif
            </div>
        </div>

        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Performance</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Total Orders</dt>
                    <dd class="font-semibold">{{ $salesman->orders->count() }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Total Sales</dt>
                    <dd class="font-semibold text-primary-700">Rs. {{ number_format($salesman->orders->sum('total')) }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection
