@extends('layouts.admin')
@section('title', $customer->name)

@section('content')
@php $isAdmin = auth()->user()->hasRole('admin'); @endphp
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ $isAdmin ? route('admin.customers.index') : route('salesman.customers.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-xl font-bold text-gray-900">{{ $customer->name }}</h1>
        @if(!$customer->is_active)
        <span class="badge bg-gray-100 text-gray-500">Inactive</span>
        @endif
    </div>
    <div class="flex gap-2">
        @if($hasPosActivity)
        <a href="{{ $isAdmin ? route('admin.customers.ledger', $customer) : route('salesman.customers.ledger', $customer) }}" class="btn-outline btn-sm">
            <i class="fas fa-book mr-1"></i> Khata Ledger
        </a>
        @endif
        @if($isAdmin)
        <a href="{{ route('admin.customers.edit', $customer) }}" class="btn-primary btn-sm">
            <i class="fas fa-edit mr-1"></i> Edit
        </a>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-5">

        {{-- Recent orders --}}
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-gray-800">Recent Orders</h2>
                <span class="text-sm text-gray-500">{{ $customer->orders->count() }} total</span>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customer->orders->take(10) as $order)
                        <tr>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}" class="text-primary-600 hover:underline font-mono text-sm">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="text-sm">{{ $order->items->count() }}</td>
                            <td class="font-semibold text-sm">Rs. {{ number_format($order->total) }}</td>
                            <td>
                                <span class="badge {{ $order->status === 'delivered' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="text-xs text-gray-500">{{ $order->created_at->format('d M Y') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-8 text-gray-400">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent ledger entries — POS customers only --}}
        @if($hasPosActivity)
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-gray-800">Khata Ledger</h2>
                <a href="{{ $isAdmin ? route('admin.customers.ledger', $customer) : route('salesman.customers.ledger', $customer) }}" class="text-sm text-primary-600 hover:underline">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr><th>Date</th><th>Description</th><th>Debit</th><th>Credit</th><th>Balance</th></tr>
                    </thead>
                    <tbody>
                        @forelse($customer->ledgerEntries->take(8) as $entry)
                        <tr>
                            <td class="text-xs">{{ $entry->created_at->format('d M Y') }}</td>
                            <td class="text-sm">{{ $entry->description }}</td>
                            <td class="text-sm text-red-600">{{ $entry->type === 'debit' ? 'Rs. '.number_format($entry->amount) : '—' }}</td>
                            <td class="text-sm text-green-600">{{ $entry->type === 'credit' ? 'Rs. '.number_format($entry->amount) : '—' }}</td>
                            <td class="text-sm font-semibold {{ $entry->balance_after < 0 ? 'text-red-600' : 'text-gray-800' }}">
                                Rs. {{ number_format($entry->balance_after) }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-8 text-gray-400">No khata entries yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    {{-- Sidebar --}}
    <div class="space-y-5">
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Customer Info</h2>
            <dl class="space-y-3 text-sm">
                <div><dt class="text-gray-500 text-xs">Phone</dt><dd class="font-medium font-mono">{{ $customer->phone }}</dd></div>
                @if($customer->email)<div><dt class="text-gray-500 text-xs">Email</dt><dd>{{ $customer->email }}</dd></div>@endif
                @if($customer->address)<div><dt class="text-gray-500 text-xs">Address</dt><dd>{{ $customer->address }}</dd></div>@endif
                @if($customer->city)<div><dt class="text-gray-500 text-xs">City</dt><dd>{{ $customer->city }}</dd></div>@endif
                <div><dt class="text-gray-500 text-xs">Customer Since</dt><dd class="text-xs">{{ $customer->created_at->format('d M Y') }}</dd></div>
            </dl>
        </div>

        {{-- Balance card — POS customers only --}}
        @if($hasPosActivity)
        <div class="card p-5 {{ $customer->credit_balance < 0 ? 'border-red-200 bg-red-50' : ($customer->credit_balance > 0 ? 'border-green-200 bg-green-50' : '') }} border-2">
            <h2 class="font-semibold text-gray-800 mb-2">Khata Balance</h2>
            <div class="text-2xl font-extrabold {{ $customer->credit_balance < 0 ? 'text-red-600' : ($customer->credit_balance > 0 ? 'text-green-600' : 'text-gray-500') }}">
                Rs. {{ number_format(abs($customer->credit_balance)) }}
            </div>
            <div class="text-sm text-gray-500 mt-1">
                {{ $customer->credit_balance < 0 ? 'Customer owes this amount' : ($customer->credit_balance > 0 ? 'Credit available' : 'Settled') }}
            </div>
            <a href="{{ $isAdmin ? route('admin.customers.ledger', $customer) : route('salesman.customers.ledger', $customer) }}" class="btn-outline btn-sm mt-3 w-full justify-center">
                <i class="fas fa-plus mr-1"></i> Add Ledger Entry
            </a>
        </div>
        @endif

        {{-- Stats --}}
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Stats</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Total Orders</dt>
                    <dd class="font-semibold">{{ $customer->orders->count() }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Total Spent</dt>
                    <dd class="font-semibold text-primary-700">Rs. {{ number_format($customer->orders->sum('total')) }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection
