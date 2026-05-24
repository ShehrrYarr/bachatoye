@extends('layouts.ecom')
@section('title', 'Products')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">

    {{-- Breadcrumb --}}
    <nav class="text-sm text-gray-500 mb-6 flex items-center gap-2">
        <a href="{{ route('home') }}" class="hover:text-primary-600">Home</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-800 font-medium">Products</span>
    </nav>

    <div class="flex gap-8">
        {{-- Sidebar filters --}}
        <aside class="w-56 shrink-0 hidden lg:block">
            <form method="GET" action="{{ route('products.index') }}" id="filterForm">
                <div class="card p-5 space-y-6">
                    <div>
                        <h3 class="font-semibold text-gray-800 mb-3 text-sm">Category</h3>
                        <div class="space-y-1.5">
                            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                                <input type="radio" name="category" value="" {{ !request('category') ? 'checked' : '' }} class="text-primary-600" onchange="document.getElementById('filterForm').submit()"> All
                            </label>
                            @foreach($categories as $cat)
                            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                                <input type="radio" name="category" value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'checked' : '' }} class="text-primary-600" onchange="document.getElementById('filterForm').submit()"> {{ $cat->name }}
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 mb-3 text-sm">Brand</h3>
                        <div class="space-y-1.5">
                            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                                <input type="radio" name="brand" value="" {{ !request('brand') ? 'checked' : '' }} class="text-primary-600" onchange="document.getElementById('filterForm').submit()"> All Brands
                            </label>
                            @foreach($brands as $brand)
                            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                                <input type="radio" name="brand" value="{{ $brand->id }}" {{ request('brand') == $brand->id ? 'checked' : '' }} class="text-primary-600" onchange="document.getElementById('filterForm').submit()"> {{ $brand->name }}
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 mb-3 text-sm">Price Range</h3>
                        <div class="flex items-center gap-2">
                            <input type="number" name="min_price" placeholder="Min" value="{{ request('min_price') }}" class="form-input text-xs py-1.5">
                            <span class="text-gray-400">–</span>
                            <input type="number" name="max_price" placeholder="Max" value="{{ request('max_price') }}" class="form-input text-xs py-1.5">
                        </div>
                        <button type="submit" class="btn-primary btn-sm mt-2 w-full justify-center">Apply</button>
                    </div>
                    @if(request()->hasAny(['category','brand','min_price','max_price','q']))
                    <a href="{{ route('products.index') }}" class="block text-xs text-center text-red-500 hover:underline">Clear all filters</a>
                    @endif
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                </div>
            </form>
        </aside>

        {{-- Product grid --}}
        <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between mb-5">
                <p class="text-sm text-gray-500">{{ $products->total() }} products found</p>
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
                <p class="text-gray-500 font-medium">No products found.</p>
                <a href="{{ route('products.index') }}" class="btn-primary mt-4">Clear filters</a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
