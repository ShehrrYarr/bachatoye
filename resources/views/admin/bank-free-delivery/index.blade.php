@extends('layouts.admin')
@section('title', 'Bank Free Delivery')
@section('page-title', 'Bank Free Delivery')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-900">Bank Free Delivery</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            When ALL products in the cart are enabled here, customers get free delivery by choosing <strong>Bank Transfer</strong>.
        </p>
    </div>
    <div class="shrink-0">
        <span class="inline-flex items-center gap-2 bg-blue-50 border border-blue-200 text-blue-700 text-sm font-semibold px-4 py-2 rounded-xl">
            <i class="fas fa-university"></i>
            {{ $enabledCount }} {{ Str::plural('product', $enabledCount) }} enabled
        </span>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-5">
    <div class="card-body py-3">
        <form method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Search products by name..."
                       class="form-input pl-10">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            </div>
            <select name="filter" class="form-input sm:w-44">
                <option value="all"     {{ $filter === 'all'     ? 'selected' : '' }}>All Products</option>
                <option value="enabled" {{ $filter === 'enabled' ? 'selected' : '' }}>Enabled</option>
                <option value="disabled"{{ $filter === 'disabled'? 'selected' : '' }}>Disabled</option>
            </select>
            <button type="submit" class="btn-primary shrink-0">Filter</button>
            @if($search || $filter !== 'all')
                <a href="{{ route('admin.bank-free-delivery.index') }}" class="btn-outline shrink-0">Clear</a>
            @endif
        </form>
    </div>
</div>

{{-- Product list --}}
<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th class="w-12"></th>
                <th>Product</th>
                <th>Category</th>
                <th>Price</th>
                <th class="text-center">Bank Free Delivery</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
            <tr x-data="{ enabled: {{ $product->bank_free_delivery ? 'true' : 'false' }}, loading: false }" class="transition-opacity" :class="loading ? 'opacity-60' : ''">
                <td class="w-12">
                    @if($product->primaryImage)
                        <img src="{{ asset('storage/' . $product->primaryImage->path) }}"
                             loading="lazy"
                             class="w-10 h-10 object-cover rounded-lg border border-gray-200">
                    @else
                        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-box text-gray-300 text-sm"></i>
                        </div>
                    @endif
                </td>
                <td>
                    <div class="font-semibold text-gray-800">{{ $product->name }}</div>
                    @if($product->sku)
                        <div class="text-xs text-gray-400">{{ $product->sku }}</div>
                    @endif
                </td>
                <td class="text-sm text-gray-600">{{ $product->category?->name ?? '—' }}</td>
                <td class="text-sm font-semibold text-gray-800">Rs. {{ number_format($product->price) }}</td>
                <td class="text-center">
                    <button
                        @click="
                            loading = true;
                            fetch('{{ route('admin.bank-free-delivery.toggle', $product) }}', {
                                method: 'PATCH',
                                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
                            })
                            .then(r => r.json())
                            .then(d => { enabled = d.enabled; loading = false; })
                            .catch(() => loading = false);
                        "
                        :title="enabled ? 'Click to disable' : 'Click to enable'"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                        :class="enabled ? 'bg-primary-600' : 'bg-gray-300'">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                              :class="enabled ? 'translate-x-6' : 'translate-x-1'"></span>
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center py-12 text-gray-400">
                    <i class="fas fa-box-open text-3xl mb-3"></i>
                    <p class="text-sm">No products found</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($products->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $products->links() }}
    </div>
    @endif
</div>

@endsection
