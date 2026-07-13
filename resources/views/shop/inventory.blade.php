@extends('layouts.admin')
@section('title', $lowOnly ? 'Low Stock' : 'Shop Stock')

@section('content')
<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <h1 class="text-xl font-bold text-gray-900">{{ $lowOnly ? 'Low Stock at Shop' : 'Stock at Shop' }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ number_format($totalUnits) }} total units at this shop. Stock arrives via transfers from the main shop.</p>
    </div>
    <div class="flex gap-2">
        @if($lowOnly)
        <a href="{{ route('shop.inventory.index') }}" class="btn-outline btn-sm">All Stock</a>
        @else
        <a href="{{ route('shop.inventory.low_stock') }}" class="btn-outline btn-sm text-orange-600 border-orange-200"><i class="fas fa-exclamation-triangle mr-1"></i> Low Stock</a>
        @endif
        <a href="{{ route('shop.transfers.index') }}" class="btn-outline btn-sm"><i class="fas fa-exchange-alt mr-1"></i> Transfers</a>
    </div>
</div>

<div class="card p-4 mb-5">
    <form method="GET" action="{{ $lowOnly ? route('shop.inventory.low_stock') : route('shop.inventory.index') }}" class="flex flex-wrap gap-3 items-end">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by name, SKU, barcode..."
               class="form-input text-sm flex-1 min-w-48">
        <button type="submit" class="btn-primary btn-sm">Search</button>
        @if(request('q'))
        <a href="{{ $lowOnly ? route('shop.inventory.low_stock') : route('shop.inventory.index') }}" class="btn-outline btn-sm">Clear</a>
        @endif
    </form>
</div>

<div class="card">
    <div class="overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Colors / Serials</th>
                <th class="text-center">Qty at Shop</th>
                <th class="text-right">Selling Price</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
            @php
                $qty = $product->is_serialized
                    ? $product->serialNumbers->count()
                    : (int) $product->shopStocks->sum('quantity');
            @endphp
            <tr>
                <td>
                    <div class="font-semibold text-gray-800">{{ $product->name }}</div>
                    <div class="text-xs text-gray-400 font-mono">{{ $product->sku }}</div>
                </td>
                <td class="text-sm text-gray-500">{{ $product->category?->name ?? '—' }}</td>
                <td>
                    @if($product->is_serialized)
                        <div class="flex flex-wrap gap-1 max-w-md">
                            @foreach($product->serialNumbers->take(6) as $sn)
                            <span class="badge bg-gray-100 text-gray-600 font-mono text-xs">{{ $sn->serial_number }}</span>
                            @endforeach
                            @if($product->serialNumbers->count() > 6)
                            <span class="badge bg-indigo-50 text-indigo-600 text-xs">+{{ $product->serialNumbers->count() - 6 }} more</span>
                            @endif
                        </div>
                    @elseif($product->shopStocks->whereNotNull('product_color_id')->count())
                        <div class="flex flex-wrap gap-1">
                            @foreach($product->shopStocks->whereNotNull('product_color_id') as $row)
                            <span class="badge bg-purple-50 text-purple-600 text-xs">{{ $row->color?->name ?? '—' }}: {{ $row->quantity }}</span>
                            @endforeach
                        </div>
                    @else
                        <span class="text-gray-300">—</span>
                    @endif
                </td>
                <td class="text-center">
                    <span class="font-bold {{ $product->track_inventory && $qty <= $product->low_stock_threshold ? 'text-orange-600' : 'text-gray-800' }}">
                        {{ $qty }}
                    </span>
                </td>
                <td class="text-right text-sm font-semibold">Rs. {{ number_format($product->price) }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center py-12 text-gray-400">
                {{ $lowOnly ? 'Nothing is low on stock.' : 'No stock at this shop yet — the main shop can send items via a transfer.' }}
            </td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    @if($products->hasPages())
    <div class="p-4 border-t border-gray-200">{{ $products->links() }}</div>
    @endif
</div>
@endsection
