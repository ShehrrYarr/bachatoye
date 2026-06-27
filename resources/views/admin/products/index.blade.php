@extends('layouts.admin')
@section('title', 'Products')

@section('content')
@php
    $rPrefix       = auth()->user()->hasRole('admin') ? 'admin' : 'salesman';
    $toggleCatUrl  = route("{$rPrefix}.products.index");
    $toggleListUrl = route("{$rPrefix}.products.index") . '?view=list';
@endphp

{{-- ── localStorage view-mode preference ──────────────────────────────── --}}
<script>
(function() {
    var pref = localStorage.getItem('admin_products_view');
    var params = new URLSearchParams(window.location.search);
    if (!params.toString() && pref === 'list') {
        window.location.replace('{{ $toggleListUrl }}');
    }
})();
</script>

@if(!isset($products) && !isset($subcategories))
{{-- ===== PARENT CATEGORY GRID VIEW ===== --}}
<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <h1 class="text-xl font-bold text-gray-900">Products</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $categories->sum('products_count') + $uncategorizedCount }} products across {{ $categories->count() }} categories</p>
    </div>
    <div class="flex items-center gap-3">
        {{-- View toggle --}}
        <div class="inline-flex rounded-xl border border-gray-200 overflow-hidden shadow-sm">
            <span onclick="localStorage.setItem('admin_products_view','categories')"
                  class="flex items-center gap-1.5 px-4 py-2 text-sm font-semibold cursor-default text-white"
                  style="background: var(--app-gradient, #e11d48);">
                <i class="fas fa-th-large text-xs"></i> Categories
            </span>
            <a href="{{ $toggleListUrl }}"
               onclick="localStorage.setItem('admin_products_view','list')"
               class="flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors bg-white">
                <i class="fas fa-list text-xs"></i> All Products
            </a>
        </div>
        @can('products.manage')
        <a href="{{ route("{$rPrefix}.products.create") }}" class="btn-primary">
            <i class="fas fa-plus mr-2"></i> Add Product
        </a>
        @endcan
    </div>
</div>

{{-- Category search --}}
<div class="mb-5" x-data="{ catSearch: '' }">
    <div class="relative max-w-sm">
        <input type="text" x-model="catSearch" placeholder="Search categories..."
               class="form-input pl-9 text-sm">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
        <button x-show="catSearch" @click="catSearch = ''"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xs"></i>
        </button>
    </div>

<div class="grid gap-2 mt-4" style="grid-template-columns: repeat(auto-fill, minmax(120px, 1fr))">

    @forelse($categories as $cat)
    <a href="{{ route("{$rPrefix}.products.index", ['category' => $cat->id]) }}"
       x-show="!catSearch || '{{ strtolower($cat->name) }}'.includes(catSearch.toLowerCase())"
       class="group flex flex-col hover:opacity-90 transition-opacity">

        {{-- Square image --}}
        <div class="aspect-square rounded-xl border border-gray-200 overflow-hidden shadow-sm group-hover:border-primary-400 group-hover:shadow-md transition-all">
            @if($cat->image)
            <img src="{{ Storage::url($cat->image) }}" alt="{{ $cat->name }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
            @else
            <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                <i class="fas fa-tag text-2xl text-gray-300"></i>
            </div>
            @endif
        </div>

        {{-- Text below --}}
        <div class="mt-1.5 px-0.5">
            <div class="text-xs font-semibold text-gray-800 leading-tight truncate group-hover:text-primary-700 transition-colors">{{ $cat->name }}</div>
            <div class="text-[10px] text-gray-400 mt-0.5">{{ $cat->products_count }} products</div>
        </div>
    </a>
    @empty
    <div class="col-span-full text-center py-16 text-gray-400">
        <i class="fas fa-tags text-4xl mb-3"></i>
        <p>No categories found. <a href="{{ route('admin.categories.index') }}" class="text-primary-600 hover:underline">Add categories first.</a></p>
    </div>
    @endforelse

    {{-- Uncategorized card --}}
    @if($uncategorizedCount > 0)
    <a href="{{ route("{$rPrefix}.products.index", ['category' => 'uncategorized']) }}"
       x-show="!catSearch || 'uncategorized'.includes(catSearch.toLowerCase())"
       class="group flex flex-col hover:opacity-90 transition-opacity">
        <div class="aspect-square rounded-xl border border-dashed border-gray-300 overflow-hidden bg-gray-50 group-hover:border-gray-400 group-hover:shadow-md transition-all flex items-center justify-center">
            <i class="fas fa-question-circle text-2xl text-gray-300"></i>
        </div>
        <div class="mt-1.5 px-0.5">
            <div class="text-xs font-semibold text-gray-500 leading-tight truncate">Uncategorized</div>
            <div class="text-[10px] text-gray-400 mt-0.5">{{ $uncategorizedCount }} products</div>
        </div>
    </a>
    @endif

</div>{{-- end grid --}}
</div>{{-- end x-data catSearch --}}

@elseif(isset($subcategories))
{{-- ===== SUBCATEGORY GRID VIEW ===== --}}
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route("{$rPrefix}.products.index") }}"
           class="text-gray-400 hover:text-gray-700 transition-colors text-sm flex items-center gap-1.5">
            <i class="fas fa-arrow-left text-xs"></i> Categories
        </a>
        <span class="text-gray-300">/</span>
        <div>
            <h1 class="text-xl font-bold text-gray-900">{{ $selectedCat->name }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $subcategories->count() }} subcategories</p>
        </div>
    </div>
    @can('products.manage')
    <a href="{{ route("{$rPrefix}.products.create") }}" class="btn-primary">
        <i class="fas fa-plus mr-2"></i> Add Product
    </a>
    @endcan
</div>

<div class="grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(120px, 1fr))">

    {{-- "All [Parent]" tile for products assigned directly to the parent category --}}
    @if(isset($parentDirectCount) && $parentDirectCount > 0)
    <a href="{{ route("{$rPrefix}.products.index", ['category' => $selectedCat->id, 'all' => 1]) }}"
       class="group flex flex-col hover:opacity-90 transition-opacity">
        <div class="aspect-square rounded-xl border-2 border-dashed overflow-hidden flex items-center justify-center transition-all group-hover:shadow-md"
             style="border-color: var(--app-primary, #e11d48); background: #fff5f7;">
            <i class="fas fa-layer-group text-2xl transition-colors" style="color: var(--app-primary, #e11d48);"></i>
        </div>
        <div class="mt-1.5 px-0.5">
            <div class="text-xs font-semibold leading-tight truncate transition-colors group-hover:text-primary-700"
                 style="color: var(--app-primary, #e11d48);">All {{ $selectedCat->name }}</div>
            <div class="text-[10px] text-gray-400 mt-0.5">{{ $parentDirectCount }} products</div>
        </div>
    </a>
    @endif

    @forelse($subcategories as $sub)
    <a href="{{ route("{$rPrefix}.products.index", ['category' => $sub->id]) }}"
       class="group flex flex-col hover:opacity-90 transition-opacity">
        <div class="aspect-square rounded-xl border border-gray-200 overflow-hidden shadow-sm group-hover:border-primary-400 group-hover:shadow-md transition-all">
            @if($sub->image)
            <img src="{{ Storage::url($sub->image) }}" alt="{{ $sub->name }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
            @else
            <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                <i class="fas fa-tag text-2xl text-gray-300"></i>
            </div>
            @endif
        </div>
        <div class="mt-1.5 px-0.5">
            <div class="text-xs font-semibold text-gray-800 leading-tight truncate group-hover:text-primary-700 transition-colors">{{ $sub->name }}</div>
            <div class="text-[10px] text-gray-400 mt-0.5">{{ $sub->products_count }} products</div>
        </div>
    </a>
    @empty
    <div class="col-span-full text-center py-16 text-gray-400">
        <i class="fas fa-folder-open text-4xl mb-3"></i>
        <p>No subcategories found.</p>
    </div>
    @endforelse
</div>

@else
{{-- ===== PRODUCTS TABLE VIEW (category selected OR list mode) ===== --}}

{{-- Breadcrumb + header --}}
<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div class="flex items-center gap-3">
        @if(isset($listView) && $listView)
        {{-- In list-view mode: back arrow goes to categories grid --}}
        @else
        @if(isset($parentCat) && $parentCat)
        <a href="{{ route("{$rPrefix}.products.index", ['category' => $parentCat->id]) }}"
           class="text-gray-400 hover:text-gray-700 transition-colors text-sm flex items-center gap-1.5">
            <i class="fas fa-arrow-left text-xs"></i> {{ $parentCat->name }}
        </a>
        @else
        <a href="{{ route("{$rPrefix}.products.index") }}"
           class="text-gray-400 hover:text-gray-700 transition-colors text-sm flex items-center gap-1.5">
            <i class="fas fa-arrow-left text-xs"></i> Categories
        </a>
        @endif
        <span class="text-gray-300">/</span>
        @endif
        <div>
            <h1 class="text-xl font-bold text-gray-900">
                @if(isset($listView) && $listView) All Products
                @else {{ $selectedCat?->name ?? 'All Products' }}
                @endif
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $products->total() }} products</p>
        </div>
    </div>
    <div class="flex items-center gap-3">
        @if(isset($listView) && $listView)
        {{-- View toggle (only shown in list-view mode) --}}
        <div class="inline-flex rounded-xl border border-gray-200 overflow-hidden shadow-sm">
            <a href="{{ $toggleCatUrl }}"
               onclick="localStorage.setItem('admin_products_view','categories')"
               class="flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors bg-white">
                <i class="fas fa-th-large text-xs"></i> Categories
            </a>
            <span onclick="localStorage.setItem('admin_products_view','list')"
                  class="flex items-center gap-1.5 px-4 py-2 text-sm font-semibold cursor-default text-white"
                  style="background: var(--app-gradient, #e11d48);">
                <i class="fas fa-list text-xs"></i> All Products
            </span>
        </div>
        @endif
        @can('products.manage')
        <a href="{{ route("{$rPrefix}.products.create") }}" class="btn-primary">
            <i class="fas fa-plus mr-2"></i> Add Product
        </a>
        @endcan
    </div>
</div>

{{-- Inline search / filter --}}
<div class="card p-4 mb-5">
    <form method="GET" action="{{ route("{$rPrefix}.products.index") }}" class="flex flex-wrap gap-3 items-end">
        @if(isset($listView) && $listView)
        <input type="hidden" name="view" value="list">
        @endif
        @if(request('category'))
        <input type="hidden" name="category" value="{{ request('category') }}">
        @endif
        <div class="flex-1 min-w-48">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by name, SKU, barcode..."
                   class="form-input text-sm">
        </div>
        @if(isset($listView) && $listView)
        <div>
            <select name="brand" class="form-select text-sm">
                <option value="">All Brands</option>
                @foreach($brands as $b)
                <option value="{{ $b->id }}" {{ request('brand') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div>
            <select name="status" class="form-select text-sm">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <button type="submit" class="btn-primary btn-sm">Filter</button>
        @if(request()->hasAny(['q','status','brand']))
        <a href="{{ isset($listView) && $listView ? route("{$rPrefix}.products.index").'?view=list' : route("{$rPrefix}.products.index", ['category' => request('category')]) }}"
           class="btn-outline btn-sm">Clear</a>
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
                    @if(isset($listView) && $listView)
                    <th>Category</th>
                    @endif
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
                    @if(isset($listView) && $listView)
                    <td class="text-xs text-gray-500">
                        @if($product->category)
                            <div class="font-medium text-gray-600">{{ $product->category->name }}</div>
                            @if($product->subcategory)
                                <div class="text-gray-400">{{ $product->subcategory->name }}</div>
                            @endif
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    @endif
                    <td class="text-xs text-gray-500 font-mono">
                        @if($product->sku) <div>{{ $product->sku }}</div> @endif
                        @if($product->barcode) <div>{{ $product->barcode }}</div> @endif
                    </td>
                    <td>
                        <div class="font-semibold text-gray-800">Rs. {{ number_format($product->price) }}</div>
                        @can('products.view_cost')
                        @if($product->cost_price)
                        <div class="text-xs text-gray-400">Cost: Rs. {{ number_format($product->cost_price) }}</div>
                        @endif
                        @endcan
                    </td>
                    <td>
                        @if($product->track_inventory)
                            @php $stock = $product->colors->count() > 0 ? $product->colors->sum('stock_quantity') : $product->stock_quantity; @endphp
                            @if($stock <= 0)
                                <span class="badge bg-red-100 text-red-700">Out of Stock</span>
                            @elseif($product->isLowStock())
                                <span class="badge bg-orange-100 text-orange-700">{{ $stock }} (Low)</span>
                            @else
                                <span class="text-sm font-medium text-gray-700">{{ number_format($stock) }}</span>
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
                            <a href="{{ route("{$rPrefix}.products.show", $product) }}" class="btn-outline btn-sm" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            @can('products.manage')
                            <a href="{{ route("{$rPrefix}.products.edit", $product) }}" class="btn-outline btn-sm" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            @endcan
                            @if($product->barcode)
                            <a href="{{ route("{$rPrefix}.products.print-barcode", $product) }}" target="_blank"
                               class="btn-outline btn-sm" title="Print Barcode">
                                <i class="fas fa-barcode"></i>
                            </a>
                            @endif
                            @can('products.manage')
                            <form method="POST" action="{{ route("{$rPrefix}.products.destroy", $product) }}"
                                  onsubmit="return confirm('Delete this product?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger btn-sm" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endcan
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
