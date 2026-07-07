@extends('layouts.admin')
@section('title', 'Returns & Refunds')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Returns & Refunds</h1>
</div>

<div class="card p-4 mb-5">
    <form method="GET" action="{{ route('admin.returns.index') }}" class="flex flex-wrap gap-3 items-end">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Return #, order #..."
               class="form-input text-sm flex-1 min-w-48">
        <select name="refund_method" class="form-select text-sm">
            <option value="">All Refund Types</option>
            <option value="cash" {{ request('refund_method') === 'cash' ? 'selected' : '' }}>Cash</option>
            <option value="khata_credit" {{ request('refund_method') === 'khata_credit' ? 'selected' : '' }}>Khata Credit</option>
            <option value="exchange" {{ request('refund_method') === 'exchange' ? 'selected' : '' }}>Exchange</option>
        </select>
        <button type="submit" class="btn-primary btn-sm">Filter</button>
        @if(request()->hasAny(['q','refund_method']))
        <a href="{{ route('admin.returns.index') }}" class="btn-outline btn-sm">Clear</a>
        @endif
    </form>
</div>

<div class="card">
    <div class="overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>Return #</th>
                <th>Original Order</th>
                <th>Customer</th>
                <th>Items</th>
                <th>Refund</th>
                <th>Method</th>
                <th>Reason</th>
                <th>Date</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($returns as $return)
            <tr>
                <td class="font-mono text-sm font-semibold">{{ $return->return_number }}</td>
                <td>
                    <a href="{{ route('admin.orders.show', $return->order) }}" class="text-primary-600 hover:underline text-sm">
                        {{ $return->order->order_number }}
                    </a>
                </td>
                <td class="text-sm text-gray-600">{{ $return->order->customer_name }}</td>
                <td class="text-sm">{{ $return->items->count() }}</td>
                <td class="font-semibold text-gray-800">Rs. {{ number_format($return->refund_amount) }}</td>
                <td>
                    <span class="badge
                        @if($return->refund_method === 'cash') bg-green-100 text-green-700
                        @elseif($return->refund_method === 'khata_credit') bg-blue-100 text-blue-700
                        @else bg-gray-100 text-gray-600 @endif">
                        {{ ucfirst(str_replace('_', ' ', $return->refund_method)) }}
                    </span>
                </td>
                <td class="text-xs text-gray-500">{{ ucfirst(str_replace('_', ' ', $return->reason)) }}</td>
                <td class="text-xs text-gray-500">{{ $return->created_at->format('d M Y') }}</td>
                <td class="text-right">
                    <a href="{{ route('admin.returns.show', $return) }}" class="btn-outline btn-sm">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center py-12 text-gray-400">No returns recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="p-4 border-t border-gray-200">{{ $returns->withQueryString()->links() }}</div>
</div>
@endsection
