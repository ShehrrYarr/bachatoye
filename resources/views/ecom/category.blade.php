@extends('layouts.ecom')
@section('title', $category->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">

    <nav class="text-sm text-gray-500 mb-6 flex items-center gap-2">
        <a href="{{ route('home') }}" class="hover:text-primary-600">Home</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <a href="{{ route('products.index') }}" class="hover:text-primary-600">Products</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-800 font-medium">{{ $category->name }}</span>
    </nav>

    {{-- Category Header --}}
    <div class="flex items-center gap-5 mb-8">
        @if($category->image)
        <img src="{{ $category->image_url }}" loading="lazy" class="w-20 h-20 object-cover rounded-2xl bg-gray-100 shrink-0">
        @else
        <div class="w-20 h-20 rounded-2xl bg-primary-50 flex items-center justify-center shrink-0">
            <i class="fas fa-box text-primary-600 text-3xl"></i>
        </div>
        @endif
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $category->name }}</h1>
            <p class="text-gray-500 text-sm mt-1">{{ $products->total() }} products</p>
            @if($category->description)
            <p class="text-gray-600 text-sm mt-1">{{ $category->description }}</p>
            @endif
        </div>
    </div>

    {{-- Sub-categories --}}
    @if($category->children->count())
    <div class="flex gap-2 mb-6 overflow-x-auto pb-1">
        @foreach($category->children as $child)
        <a href="{{ route('category.show', $child->slug) }}"
           class="shrink-0 flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:border-primary-300 hover:text-primary-600 transition-all">
            {{ $child->name }}
            <span class="text-xs text-gray-400">({{ $child->products_count }})</span>
        </a>
        @endforeach
    </div>
    @endif

    <div class="flex gap-8">
        {{-- Sidebar sort/filter --}}
        <aside class="w-48 shrink-0 hidden lg:block">
            <form method="GET" action="{{ route('category.show', $category->slug) }}" id="filterForm">
                <div class="card p-4 space-y-5">
                    <div>
                        <h3 class="font-semibold text-gray-800 mb-3 text-sm">Brand</h3>
                        <div class="space-y-1.5">
                            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                                <input type="radio" name="brand" value="" {{ !request('brand') ? 'checked' : '' }}
                                       class="text-primary-600" onchange="document.getElementById('filterForm').submit()"> All Brands
                            </label>
                            @foreach($brands as $brand)
                            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                                <input type="radio" name="brand" value="{{ $brand->id }}" {{ request('brand') == $brand->id ? 'checked' : '' }}
                                       class="text-primary-600" onchange="document.getElementById('filterForm').submit()"> {{ $brand->name }}
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 mb-3 text-sm">Price Range</h3>
                        <div class="space-y-2">
                            <input type="number" name="min_price" placeholder="Min" value="{{ request('min_price') }}" class="form-input text-xs py-1.5">
                            <input type="number" name="max_price" placeholder="Max" value="{{ request('max_price') }}" class="form-input text-xs py-1.5">
                            <button type="submit" class="btn-primary btn-sm w-full justify-center">Apply</button>
                        </div>
                    </div>
                    @if(request()->hasAny(['brand','min_price','max_price']))
                    <a href="{{ route('category.show', $category->slug) }}" class="block text-xs text-center text-red-500 hover:underline">Clear filters</a>
                    @endif
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                </div>
            </form>
        </aside>

        {{-- Products --}}
        <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between mb-5">
                <p class="text-sm text-gray-500">{{ $products->total() }} products</p>
                <select onchange="window.location=this.value" class="form-select text-sm py-1.5 w-44">
                    @foreach(['newest'=>'Newest First','price_asc'=>'Price: Low→High','price_desc'=>'Price: High→Low'] as $val=>$label)
                    <option value="{{ request()->fullUrlWithQuery(['sort'=>$val]) }}" {{ request('sort',$val==='newest'?'newest':'') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            @if($products->count())
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($products as $product)
                    @include('ecom.partials.product-card', ['product' => $product])
                @endforeach
            </div>
            <div class="mt-8">{{ $products->links() }}</div>
            @else
            <div class="text-center py-20">
                <i class="fas fa-box-open text-5xl text-gray-200 mb-4"></i>
                <p class="text-gray-500 font-medium">No products in this category.</p>
                <a href="{{ route('products.index') }}" class="btn-primary mt-4">Browse All Products</a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
