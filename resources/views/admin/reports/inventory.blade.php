@extends('layouts.admin')
@section('title', 'Inventory Report')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Inventory Report</h1>
    <button onclick="window.print()" class="btn-outline btn-sm no-print">
        <i class="fas fa-print mr-1"></i> Print
    </button>
</div>

{{-- Summary --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <div class="stat-icon bg-blue-100"><i class="fas fa-box text-blue-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold">{{ number_format($totalProducts) }}</div>
            <div class="text-sm text-gray-500">Total Products</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-green-100"><i class="fas fa-cubes text-green-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold">{{ number_format($totalStockUnits) }}</div>
            <div class="text-sm text-gray-500">Stock Units</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-primary-100"><i class="fas fa-dollar-sign text-primary-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold">Rs. {{ number_format($stockValue/1000, 1) }}k</div>
            <div class="text-sm text-gray-500">Stock Value (Cost)</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-orange-100"><i class="fas fa-exclamation-triangle text-orange-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-orange-600">{{ $lowStockCount }}</div>
            <div class="text-sm text-gray-500">Low Stock Items</div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    {{-- Out of stock --}}
    @if($outOfStock->count())
    <div class="card">
        <div class="card-header">
            <h2 class="font-semibold text-gray-800 text-red-700"><i class="fas fa-times-circle text-red-500 mr-1"></i> Out of Stock</h2>
            <span class="badge bg-red-100 text-red-700">{{ $outOfStock->count() }}</span>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($outOfStock->take(10) as $p)
            <div class="px-4 py-2.5 flex items-center justify-between">
                <span class="text-sm font-medium text-gray-800 truncate max-w-xs">{{ $p->name }}</span>
                <a href="{{ route('admin.inventory.adjust.form', $p) }}" class="btn-primary btn-sm shrink-0 ml-2">Restock</a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Low stock --}}
    @if($lowStock->count())
    <div class="card">
        <div class="card-header">
            <h2 class="font-semibold text-orange-700"><i class="fas fa-exclamation-triangle text-orange-500 mr-1"></i> Low Stock</h2>
            <span class="badge bg-orange-100 text-orange-700">{{ $lowStock->count() }}</span>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($lowStock->take(10) as $p)
            <div class="px-4 py-2.5 flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-800 truncate max-w-xs">{{ $p->name }}</div>
                    <div class="text-xs text-orange-600">{{ $p->stock_quantity }} left</div>
                </div>
                <a href="{{ route('admin.inventory.adjust.form', $p) }}" class="btn-outline btn-sm shrink-0 ml-2">Adjust</a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Category breakdown --}}
    <div class="card">
        <div class="card-header"><h2 class="font-semibold text-gray-800">By Category</h2></div>
        <table class="data-table text-sm">
            <thead><tr><th>Category</th><th class="text-center">Products</th><th class="text-right">Units</th></tr></thead>
            <tbody>
                @foreach($byCategory as $cat)
                <tr>
                    <td class="font-medium">{{ $cat->name ?? 'Uncategorized' }}</td>
                    <td class="text-center">{{ $cat->products_count }}</td>
                    <td class="text-right font-semibold">{{ number_format($cat->total_stock) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Full inventory table --}}
<div class="card overflow-hidden">
    <div class="card-header"><h2 class="font-semibold text-gray-800">Full Inventory</h2></div>
    <div class="overflow-x-auto">
        <table class="data-table text-sm">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th class="text-right">Cost</th>
                    <th class="text-right">Price</th>
                    <th class="text-center">Stock</th>
                    <th class="text-right">Stock Value</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                <tr>
                    <td class="font-medium text-gray-800">{{ $product->name }}</td>
                    <td class="font-mono text-xs text-gray-400">{{ $product->sku ?? '—' }}</td>
                    <td class="text-gray-500">{{ $product->category?->name ?? '—' }}</td>
                    <td class="text-right">{{ $product->cost_price ? 'Rs. '.number_format($product->cost_price) : '—' }}</td>
                    <td class="text-right font-semibold text-primary-700">Rs. {{ number_format($product->price) }}</td>
                    <td class="text-center font-semibold {{ $product->stock_quantity <= 0 ? 'text-red-600' : ($product->isLowStock() ? 'text-orange-600' : 'text-gray-800') }}">
                        {{ $product->track_inventory ? number_format($product->stock_quantity) : '—' }}
                    </td>
                    <td class="text-right text-gray-600">
                        {{ ($product->cost_price && $product->track_inventory) ? 'Rs. '.number_format($product->cost_price * $product->stock_quantity) : '—' }}
                    </td>
                    <td>
                        @if(!$product->track_inventory)
                            <span class="badge bg-gray-100 text-gray-400">Not tracked</span>
                        @elseif($product->stock_quantity <= 0)
                            <span class="badge bg-red-100 text-red-700">Out</span>
                        @elseif($product->isLowStock())
                            <span class="badge bg-orange-100 text-orange-700">Low</span>
                        @else
                            <span class="badge bg-green-100 text-green-700">OK</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
