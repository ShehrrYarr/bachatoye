@extends('layouts.admin')
@section('title', 'Deleted Sales')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.orders.index') }}" class="btn-outline btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Orders
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-900">Deleted Sales</h1>
            <p class="text-sm text-gray-500 mt-0.5">POS sales that have been deleted — stock was restored for each</p>
        </div>
    </div>
    <div class="text-right">
        <div class="text-xs text-gray-400">Total deleted value</div>
        <div class="text-lg font-bold text-red-600">Rs. {{ number_format($totalValue) }}</div>
    </div>
</div>

{{-- Filters --}}
<div class="card p-4 mb-5">
    <form method="GET" action="{{ route('admin.orders.deleted') }}" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-48">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Order #, customer name or phone..."
                   class="form-input text-sm">
        </div>
        <div>
            <label class="form-label text-xs">Deleted From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input text-sm">
        </div>
        <div>
            <label class="form-label text-xs">Deleted To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input text-sm">
        </div>
        <button type="submit" class="btn-primary btn-sm">Filter</button>
        @if(request()->hasAny(['search', 'date_from', 'date_to']))
        <a href="{{ route('admin.orders.deleted') }}" class="btn-outline btn-sm">Clear</a>
        @endif
    </form>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Sale Date</th>
                    <th>Customer</th>
                    <th>Items Sold</th>
                    <th>Payment</th>
                    <th class="text-right">Total</th>
                    <th>Deleted By</th>
                    <th>Deleted At</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    {{-- Order number --}}
                    <td>
                        <span class="font-mono text-sm font-semibold text-gray-700">
                            {{ $order->order_number }}
                        </span>
                        @if($order->servedBy)
                        <div class="text-xs text-gray-400 mt-0.5">
                            Sold by {{ $order->servedBy->name }}
                        </div>
                        @endif
                    </td>

                    {{-- Sale date --}}
                    <td class="text-sm text-gray-600 whitespace-nowrap">
                        {{ $order->created_at->format('d M Y') }}
                        <div class="text-xs text-gray-400">{{ $order->created_at->format('H:i') }}</div>
                    </td>

                    {{-- Customer --}}
                    <td>
                        <div class="text-sm font-medium text-gray-800">{{ $order->customer_name }}</div>
                        @if($order->customer_phone && $order->customer_phone !== '-')
                        <div class="text-xs text-gray-400">{{ $order->customer_phone }}</div>
                        @endif
                    </td>

                    {{-- Items --}}
                    <td class="max-w-xs">
                        <div class="space-y-0.5">
                            @foreach($order->items as $item)
                            <div class="text-xs text-gray-700 flex items-center gap-1.5">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-gray-100 text-gray-600 font-bold text-[10px] shrink-0">
                                    {{ $item->quantity }}
                                </span>
                                <span class="truncate">{{ $item->product_name }}</span>
                                @if($item->color_name)
                                <span class="text-gray-400 shrink-0">· {{ $item->color_name }}</span>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </td>

                    {{-- Payment --}}
                    <td>
                        <span class="badge text-xs
                            @if($order->payment_method === 'cash') bg-green-100 text-green-700
                            @elseif($order->payment_method === 'bank_transfer') bg-blue-100 text-blue-700
                            @elseif($order->payment_method === 'khata') bg-red-100 text-red-700
                            @elseif($order->payment_method === 'partial') bg-orange-100 text-orange-700
                            @elseif($order->payment_method === 'split') bg-teal-100 text-teal-700
                            @else bg-gray-100 text-gray-600 @endif">
                            {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}
                        </span>
                    </td>

                    {{-- Total --}}
                    <td class="text-right">
                        <span class="font-semibold text-gray-800">Rs. {{ number_format($order->total) }}</span>
                        @if($order->discount_amount > 0)
                        <div class="text-xs text-red-400">-Rs. {{ number_format($order->discount_amount) }} disc.</div>
                        @endif
                    </td>

                    {{-- Deleted by --}}
                    <td>
                        @if($order->deletedBy)
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                                <i class="fas fa-user text-red-500 text-xs"></i>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-800">{{ $order->deletedBy->name }}</div>
                                <div class="text-xs text-gray-400">
                                    {{ $order->deletedBy->hasRole('admin') ? 'Admin' : 'Salesman' }}
                                </div>
                            </div>
                        </div>
                        @else
                        <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>

                    {{-- Deleted at --}}
                    <td class="whitespace-nowrap">
                        <div class="text-sm text-gray-700">{{ $order->deleted_at->format('d M Y') }}</div>
                        <div class="text-xs text-gray-400">{{ $order->deleted_at->format('H:i') }}</div>
                        <div class="text-xs text-gray-400">
                            {{ $order->deleted_at->diffForHumans() }}
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-14">
                        <i class="fas fa-trash-alt text-4xl text-gray-200 mb-3 block"></i>
                        <p class="text-gray-400 text-sm">No deleted sales found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())
    <div class="p-4 border-t border-gray-200">
        {{ $orders->withQueryString()->links() }}
    </div>
    @endif
</div>

@endsection
