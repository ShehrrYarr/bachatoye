@extends('layouts.admin')
@section('title', 'Orders')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Orders</h1>
    <div class="flex items-center gap-3">
        <span class="text-sm text-gray-500">{{ $orders->total() }} total orders</span>
        <a href="{{ route('admin.orders.deleted') }}"
           class="btn-outline btn-sm text-red-600 border-red-200 hover:bg-red-50">
            <i class="fas fa-trash-alt mr-1.5"></i> Deleted Sales
        </a>
    </div>
</div>

<div class="card p-4 mb-5">
    <form method="GET" action="{{ route('admin.orders.index') }}" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-48">
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="Order #, customer name, phone..."
                   class="form-input text-sm">
        </div>
        <div>
            <select name="status" class="form-select text-sm">
                <option value="">All Status</option>
                @foreach(['pending','processing','shipped','delivered','cancelled'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <select name="source" class="form-select text-sm">
                <option value="">All Sources</option>
                <option value="ecommerce" {{ request('source') === 'ecommerce' ? 'selected' : '' }}>Ecommerce</option>
                <option value="pos" {{ request('source') === 'pos' ? 'selected' : '' }}>POS</option>
            </select>
        </div>
        <div>
            <select name="payment" class="form-select text-sm">
                <option value="">All Payments</option>
                <option value="cash" {{ request('payment') === 'cash' ? 'selected' : '' }}>Cash</option>
                <option value="bank_transfer" {{ request('payment') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                <option value="khata" {{ request('payment') === 'khata' ? 'selected' : '' }}>Khata</option>
            </select>
        </div>
        <div class="flex gap-2">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input text-sm">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input text-sm">
        </div>
        <button type="submit" class="btn-primary btn-sm">Filter</button>
        @if(request()->hasAny(['q','status','source','payment','date_from','date_to']))
        <a href="{{ route('admin.orders.index') }}" class="btn-outline btn-sm">Clear</a>
        @endif
    </form>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Source</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td class="font-mono text-sm font-semibold text-gray-800">{{ $order->order_number }}</td>
                    <td>
                        <div class="font-medium text-gray-800 text-sm">{{ $order->customer_name }}</div>
                        <div class="text-xs text-gray-400">{{ $order->customer_phone }}</div>
                    </td>
                    <td>
                        <span class="badge {{ $order->source === 'pos' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ strtoupper($order->source) }}
                        </span>
                    </td>
                    <td class="text-sm text-gray-600">{{ $order->items->count() }} item(s)</td>
                    <td class="font-semibold text-gray-800">Rs. {{ number_format($order->total) }}</td>
                    <td>
                        <div class="text-xs">
                            <div>{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</div>
                            <span class="badge {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ ucfirst($order->payment_status) }}
                            </span>
                        </div>
                    </td>
                    <td>
                        <span class="badge
                            @if($order->status === 'delivered') bg-green-100 text-green-700
                            @elseif($order->status === 'cancelled') bg-red-100 text-red-700
                            @elseif($order->status === 'shipped') bg-purple-100 text-purple-700
                            @elseif($order->status === 'processing') bg-blue-100 text-blue-700
                            @else bg-yellow-100 text-yellow-700 @endif">
                            {{ ucfirst($order->status) }}
                        </span>
                    </td>
                    <td class="text-xs text-gray-500">{{ $order->created_at->format('d M Y') }}</td>
                    <td class="text-right">
                        <a href="{{ route('admin.orders.show', $order) }}" class="btn-outline btn-sm">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center py-12 text-gray-400">No orders found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-gray-200">{{ $orders->withQueryString()->links() }}</div>
</div>
@endsection
