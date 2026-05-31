@extends('layouts.admin')
@section('title', 'Customers')

@section('content')
@php $isAdmin = auth()->user()->hasRole('admin'); @endphp
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Customers</h1>
    <a href="{{ route('admin.customers.create') }}" class="btn-primary"><i class="fas fa-plus mr-2"></i> Add Customer</a>
</div>

{{-- Search / Filter --}}
<div class="card p-4 mb-6">
    <form method="GET" action="{{ route('admin.customers.index') }}" class="flex flex-wrap gap-3 items-end">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Name, phone, email..."
               class="form-input text-sm flex-1 min-w-48">
        <select name="balance" class="form-select text-sm">
            <option value="">All Balances</option>
            <option value="outstanding" {{ request('balance') === 'outstanding' ? 'selected' : '' }}>Khata Outstanding</option>
            <option value="credit" {{ request('balance') === 'credit' ? 'selected' : '' }}>With Credit</option>
        </select>
        <button type="submit" class="btn-primary btn-sm">Filter</button>
        @if(request()->hasAny(['q', 'balance']))
            <a href="{{ route('admin.customers.index') }}" class="btn-outline btn-sm">Clear</a>
        @endif
    </form>
</div>

{{-- ───────────── POS / WALK-IN CUSTOMERS ───────────── --}}
<div class="mb-8">
    <div class="flex items-center gap-3 mb-3">
        <div class="w-3 h-3 rounded-full bg-blue-500"></div>
        <h2 class="text-base font-bold text-gray-800">Walk-in / POS Customers</h2>
        <span class="text-sm text-gray-400">({{ $posCustomers->total() }} total)</span>
        <span class="badge bg-blue-100 text-blue-700 ml-1">Khata Enabled</span>
    </div>

    <div class="card overflow-hidden">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>City</th>
                    <th class="text-center">Orders</th>
                    <th class="text-right">Khata Balance</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($posCustomers as $customer)
                <tr>
                    <td>
                        <div class="font-semibold text-gray-800">{{ $customer->name }}</div>
                        @if($customer->email)<div class="text-xs text-gray-400">{{ $customer->email }}</div>@endif
                    </td>
                    <td class="text-sm font-mono">{{ $customer->phone }}</td>
                    <td class="text-sm text-gray-600">{{ $customer->city ?? '—' }}</td>
                    <td class="text-center text-sm">{{ $customer->orders_count }}</td>
                    <td class="text-right">
                        @if($customer->credit_balance < 0)
                            <span class="font-semibold text-red-600">Rs. {{ number_format(abs($customer->credit_balance)) }} owed</span>
                        @elseif($customer->credit_balance > 0)
                            <span class="font-semibold text-green-600">Rs. {{ number_format($customer->credit_balance) }} credit</span>
                        @else
                            <span class="text-gray-400">Settled</span>
                        @endif
                    </td>
                    <td>
                        @if($customer->is_active)
                            <span class="badge bg-green-100 text-green-700">Active</span>
                        @else
                            <span class="badge bg-gray-100 text-gray-500">Inactive</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ $isAdmin ? route('admin.customers.show', $customer) : route('salesman.customers.show', $customer) }}" class="btn-outline btn-sm" title="View"><i class="fas fa-eye"></i></a>
                            <a href="{{ $isAdmin ? route('admin.customers.ledger', $customer) : route('salesman.customers.ledger', $customer) }}" class="btn-outline btn-sm" title="Khata Ledger"><i class="fas fa-book"></i></a>
                            @if($isAdmin)<a href="{{ route('admin.customers.edit', $customer) }}" class="btn-outline btn-sm" title="Edit"><i class="fas fa-edit"></i></a>@endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-10 text-gray-400">
                        No walk-in customers found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($posCustomers->hasPages())
        <div class="p-4 border-t border-gray-200">{{ $posCustomers->withQueryString()->links() }}</div>
        @endif
    </div>
</div>

{{-- ───────────── ONLINE CUSTOMERS ───────────── --}}
<div>
    <div class="flex items-center gap-3 mb-3">
        <div class="w-3 h-3 rounded-full bg-gray-400"></div>
        <h2 class="text-base font-bold text-gray-800">Online Customers</h2>
        <span class="text-sm text-gray-400">({{ $onlineCustomers->total() }} total)</span>
        <span class="badge bg-gray-100 text-gray-500 ml-1">Ecommerce Orders Only</span>
    </div>

    <div class="card overflow-hidden">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>City</th>
                    <th class="text-center">Orders</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($onlineCustomers as $customer)
                <tr>
                    <td>
                        <div class="font-semibold text-gray-800">{{ $customer->name }}</div>
                        @if($customer->email)<div class="text-xs text-gray-400">{{ $customer->email }}</div>@endif
                    </td>
                    <td class="text-sm font-mono">{{ $customer->phone }}</td>
                    <td class="text-sm text-gray-600">{{ $customer->city ?? '—' }}</td>
                    <td class="text-center text-sm">{{ $customer->orders_count }}</td>
                    <td>
                        @if($customer->is_active)
                            <span class="badge bg-green-100 text-green-700">Active</span>
                        @else
                            <span class="badge bg-gray-100 text-gray-500">Inactive</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ $isAdmin ? route('admin.customers.show', $customer) : route('salesman.customers.show', $customer) }}" class="btn-outline btn-sm" title="View"><i class="fas fa-eye"></i></a>
                            @if($isAdmin)<a href="{{ route('admin.customers.edit', $customer) }}" class="btn-outline btn-sm" title="Edit"><i class="fas fa-edit"></i></a>@endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-10 text-gray-400">
                        No online customers found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($onlineCustomers->hasPages())
        <div class="p-4 border-t border-gray-200">{{ $onlineCustomers->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
