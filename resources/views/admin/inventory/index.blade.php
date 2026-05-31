@extends('layouts.admin')
@section('title', 'Inventory')

@section('content')
@php $rPrefix = auth()->user()->hasRole('admin') ? 'admin' : 'salesman'; @endphp
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Inventory Management</h1>
    <div class="flex gap-2">
        <a href="{{ route("{$rPrefix}.inventory.low_stock") }}" class="btn-outline btn-sm">
            <i class="fas fa-exclamation-triangle text-orange-500 mr-1"></i> Low Stock
        </a>
        <a href="{{ route('admin.inventory.barcode_print') }}" class="btn-outline btn-sm">
            <i class="fas fa-print mr-1"></i> Print Labels
        </a>
    </div>
</div>

{{-- Filters --}}
<div class="card p-4 mb-5">
    <form method="GET" action="{{ route("{$rPrefix}.inventory.index") }}" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-48">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name, SKU, barcode..."
                   class="form-input text-sm">
        </div>
        <div>
            <select name="category" class="form-select text-sm">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <select name="stock_status" class="form-select text-sm">
                <option value="">All Stock</option>
                <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                <option value="low_stock" {{ request('stock_status') === 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
            </select>
        </div>
        <button type="submit" class="btn-primary btn-sm">Filter</button>
        @if(request()->hasAny(['q','category','stock_status']))
        <a href="{{ route("{$rPrefix}.inventory.index") }}" class="btn-outline btn-sm">Clear</a>
        @endif
    </form>
</div>

{{-- Barcode scanner quick lookup --}}
<div class="card p-4 mb-5" x-data="{
    code: '', result: null, error: '',
    async lookup() {
        if (!this.code.trim()) return;
        this.error = ''; this.result = null;
        try {
            const res = await fetch(`/admin/inventory/barcode-scan?code=${encodeURIComponent(this.code)}`);
            const data = await res.json();
            if (data && data.id) { this.result = data; } else { this.error = 'Product not found.'; }
        } catch(e) { this.error = 'Lookup failed.'; }
    }
}">
    <h3 class="text-sm font-semibold text-gray-700 mb-3">Quick Barcode Lookup</h3>
    <div class="flex gap-3">
        <input type="text" x-model="code" @keydown.enter="lookup()"
               placeholder="Scan or enter barcode..."
               class="form-input flex-1 font-mono text-sm">
        <button @click="lookup()" class="btn-primary btn-sm px-5">
            <i class="fas fa-search mr-1"></i> Lookup
        </button>
    </div>
    <div x-show="result" class="mt-3 p-3 bg-green-50 border border-green-200 rounded-xl flex items-center justify-between">
        <div>
            <div class="font-semibold text-gray-800 text-sm" x-text="result?.name"></div>
            <div class="text-xs text-gray-500 mt-0.5">
                Stock: <span class="font-semibold" x-text="result?.stock_quantity"></span> |
                Price: <span x-text="`Rs. ${Number(result?.price || 0).toLocaleString()}`"></span>
            </div>
        </div>
        <a :href="`/admin/inventory/${result?.id}/adjust`" class="btn-primary btn-sm">Adjust</a>
    </div>
    <div x-show="error" class="mt-3 alert-error text-red-600 text-sm" x-text="error"></div>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Barcode</th>
                    <th>Category</th>
                    <th class="text-center">Stock</th>
                    <th class="text-center">Threshold</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <img src="{{ $product->primary_image_url }}" class="w-9 h-9 object-cover rounded-lg bg-gray-100 shrink-0">
                            <div class="font-medium text-gray-800 text-sm">{{ $product->name }}</div>
                        </div>
                    </td>
                    <td class="text-xs font-mono text-gray-500">{{ $product->sku ?? '—' }}</td>
                    <td class="text-xs font-mono text-gray-500">{{ $product->barcode ?? '—' }}</td>
                    <td class="text-sm text-gray-600">{{ $product->category?->name ?? '—' }}</td>
                    <td class="text-center">
                        @if($product->track_inventory)
                            @if($product->stock_quantity <= 0)
                                <span class="badge bg-red-100 text-red-700">0</span>
                            @elseif($product->isLowStock())
                                <span class="badge bg-orange-100 text-orange-700">{{ $product->stock_quantity }}</span>
                            @else
                                <span class="font-semibold text-gray-700">{{ number_format($product->stock_quantity) }}</span>
                            @endif
                        @else
                            <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="text-center text-sm text-gray-500">{{ $product->low_stock_threshold ?? '—' }}</td>
                    <td class="text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route("{$rPrefix}.inventory.adjust.form", $product) }}" class="btn-primary btn-sm">
                                <i class="fas fa-edit mr-1"></i> Adjust
                            </a>
                            <a href="{{ route("{$rPrefix}.inventory.history", $product) }}" class="btn-outline btn-sm">
                                History
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-12 text-gray-400">No products found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-gray-200">
        {{ $products->withQueryString()->links() }}
    </div>
</div>

@endsection
