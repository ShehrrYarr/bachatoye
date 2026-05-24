@extends('layouts.admin')
@section('title', 'Products')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-900">Products</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $products->total() }} products total</p>
    </div>
    <a href="{{ route('admin.products.create') }}" class="btn-primary">
        <i class="fas fa-plus mr-2"></i> Add Product
    </a>
</div>

{{-- Filters --}}
<div class="card p-4 mb-5">
    <form method="GET" action="{{ route('admin.products.index') }}" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-48">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by name, SKU, barcode..."
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
            <select name="brand" class="form-select text-sm">
                <option value="">All Brands</option>
                @foreach($brands as $brand)
                <option value="{{ $brand->id }}" {{ request('brand') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <select name="status" class="form-select text-sm">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="low_stock" {{ request('status') === 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                <option value="out_of_stock" {{ request('status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
            </select>
        </div>
        <button type="submit" class="btn-primary btn-sm">Filter</button>
        @if(request()->hasAny(['q','category','brand','status']))
        <a href="{{ route('admin.products.index') }}" class="btn-outline btn-sm">Clear</a>
        @endif
    </form>
</div>

{{-- Table --}}
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-12"></th>
                    <th>Product</th>
                    <th>SKU / Barcode</th>
                    <th>Category / Brand</th>
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
                        @if($product->is_featured)
                        <span class="badge bg-yellow-100 text-yellow-700 text-xs">Featured</span>
                        @endif
                    </td>
                    <td class="text-xs text-gray-500 font-mono">
                        @if($product->sku) <div>{{ $product->sku }}</div> @endif
                        @if($product->barcode) <div>{{ $product->barcode }}</div> @endif
                    </td>
                    <td class="text-sm text-gray-600">
                        <div>{{ $product->category?->name ?? '—' }}</div>
                        <div class="text-xs text-gray-400">{{ $product->brand?->name ?? '' }}</div>
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
                    <td colspan="8" class="text-center py-12 text-gray-400">
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
@endsection
