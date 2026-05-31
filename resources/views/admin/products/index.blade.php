@extends('layouts.admin')
@section('title', 'Products')

@section('content')

@if(!isset($products))
{{-- ===== CATEGORY GRID VIEW ===== --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-900">Products</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $categories->sum('products_count') + $uncategorizedCount }} products across {{ $categories->count() }} categories</p>
    </div>
    <a href="{{ route('admin.products.create') }}" class="btn-primary">
        <i class="fas fa-plus mr-2"></i> Add Product
    </a>
</div>

<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-4">

    @forelse($categories as $cat)
    <a href="{{ route('admin.products.index', ['category' => $cat->id]) }}"
       class="group bg-white rounded-2xl border border-gray-200 shadow-sm hover:border-primary-400 hover:shadow-md transition-all overflow-hidden flex flex-col">

        {{-- Category image --}}
        <div class="h-28 bg-gray-100 overflow-hidden">
            @if($cat->image)
            <img src="{{ Storage::url($cat->image) }}" alt="{{ $cat->name }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
            @else
            <div class="w-full h-full flex items-center justify-center">
                <i class="fas fa-tag text-3xl text-gray-300"></i>
            </div>
            @endif
        </div>

        {{-- Category info --}}
        <div class="p-3 flex-1 flex flex-col justify-between">
            <div class="font-semibold text-gray-800 text-sm leading-tight group-hover:text-primary-700 transition-colors">
                {{ $cat->name }}
            </div>
            <div class="mt-2 flex items-center justify-between">
                <span class="text-xs text-gray-500">{{ $cat->products_count }} {{ Str::plural('product', $cat->products_count) }}</span>
                <i class="fas fa-chevron-right text-gray-300 text-xs group-hover:text-primary-500 group-hover:translate-x-0.5 transition-all"></i>
            </div>
        </div>
    </a>
    @empty
    <div class="col-span-5 text-center py-16 text-gray-400">
        <i class="fas fa-tags text-4xl mb-3"></i>
        <p>No categories found. <a href="{{ route('admin.categories.index') }}" class="text-primary-600 hover:underline">Add categories first.</a></p>
    </div>
    @endforelse

    {{-- Uncategorized card --}}
    @if($uncategorizedCount > 0)
    <a href="{{ route('admin.products.index', ['category' => 'uncategorized']) }}"
       class="group bg-white rounded-2xl border border-dashed border-gray-300 hover:border-gray-400 hover:shadow-md transition-all overflow-hidden flex flex-col">
        <div class="h-28 bg-gray-50 flex items-center justify-center">
            <i class="fas fa-question-circle text-3xl text-gray-300"></i>
        </div>
        <div class="p-3 flex-1 flex flex-col justify-between">
            <div class="font-semibold text-gray-500 text-sm">Uncategorized</div>
            <div class="mt-2 flex items-center justify-between">
                <span class="text-xs text-gray-400">{{ $uncategorizedCount }} {{ Str::plural('product', $uncategorizedCount) }}</span>
                <i class="fas fa-chevron-right text-gray-300 text-xs group-hover:translate-x-0.5 transition-transform"></i>
            </div>
        </div>
    </a>
    @endif

</div>

@else
{{-- ===== PRODUCTS TABLE VIEW (category selected) ===== --}}

{{-- Breadcrumb + header --}}
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.products.index') }}"
           class="text-gray-400 hover:text-gray-700 transition-colors text-sm flex items-center gap-1.5">
            <i class="fas fa-arrow-left text-xs"></i> Categories
        </a>
        <span class="text-gray-300">/</span>
        <div>
            <h1 class="text-xl font-bold text-gray-900">{{ $selectedCat?->name ?? 'All Products' }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $products->total() }} products</p>
        </div>
    </div>
    <a href="{{ route('admin.products.create') }}" class="btn-primary">
        <i class="fas fa-plus mr-2"></i> Add Product
    </a>
</div>

{{-- Inline search within category --}}
<div class="card p-4 mb-5">
    <form method="GET" action="{{ route('admin.products.index') }}" class="flex flex-wrap gap-3 items-end">
        @if(request('category'))
        <input type="hidden" name="category" value="{{ request('category') }}">
        @endif
        <div class="flex-1 min-w-48">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by name, SKU, barcode..."
                   class="form-input text-sm">
        </div>
        <div>
            <select name="status" class="form-select text-sm">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <button type="submit" class="btn-primary btn-sm">Filter</button>
        @if(request()->hasAny(['q','status']))
        <a href="{{ route('admin.products.index', ['category' => request('category')]) }}" class="btn-outline btn-sm">Clear</a>
        @endif
    </form>
</div>

{{-- Products table --}}
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-12"></th>
                    <th>Product</th>
                    <th>SKU / Barcode</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr>
                    <td>
                        <img src="{{ $product->primary_image_url }}" class="w-10 h-10 object-cover rounded-lg bg-gray-100">
                    </td>
                    <td>
                        <div class="font-semibold text-gray-800">{{ $product->name }}</div>
                        @if($product->brand)
                        <div class="text-xs text-gray-400">{{ $product->brand->name }}</div>
                        @endif
                        @if($product->is_featured)
                        <span class="badge bg-yellow-100 text-yellow-700 text-xs">Featured</span>
                        @endif
                    </td>
                    <td class="text-xs text-gray-500 font-mono">
                        @if($product->sku) <div>{{ $product->sku }}</div> @endif
                        @if($product->barcode) <div>{{ $product->barcode }}</div> @endif
                    </td>
                    <td>
                        <div class="font-semibold text-gray-800">Rs. {{ number_format($product->price) }}</div>
                        @if($product->cost_price)
                        <div class="text-xs text-gray-400">Cost: Rs. {{ number_format($product->cost_price) }}</div>
                        @endif
                    </td>
                    <td>
                        @if($product->track_inventory)
                            @if($product->stock_quantity <= 0)
                                <span class="badge bg-red-100 text-red-700">Out of Stock</span>
                            @elseif($product->isLowStock())
                                <span class="badge bg-orange-100 text-orange-700">{{ $product->stock_quantity }} (Low)</span>
                            @else
                                <span class="text-sm font-medium text-gray-700">{{ number_format($product->stock_quantity) }}</span>
                            @endif
                        @else
                            <span class="text-xs text-gray-400">Not tracked</span>
                        @endif
                    </td>
                    <td>
                        @if($product->is_active)
                            <span class="badge bg-green-100 text-green-700">Active</span>
                        @else
                            <span class="badge bg-gray-100 text-gray-500">Inactive</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.products.show', $product) }}" class="btn-outline btn-sm" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.products.edit', $product) }}" class="btn-outline btn-sm" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($product->barcode)
                            <a href="{{ route('admin.products.print-barcode', $product) }}" target="_blank"
                               class="btn-outline btn-sm" title="Print Barcode">
                                <i class="fas fa-barcode"></i>
                            </a>
                            @endif
                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                                  onsubmit="return confirm('Delete this product?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger btn-sm" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-12 text-gray-400">
                        <i class="fas fa-box-open text-4xl mb-3"></i>
                        <p>No products found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-gray-200">
        {{ $products->withQueryString()->links() }}
    </div>
</div>

@endif
@endsection
