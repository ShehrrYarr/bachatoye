@extends('layouts.ecom')
@section('title', $brand->name . ' Products')

@section('content')
<div class="t-container py-6 md:py-8" x-data="{ filtersOpen: false }">

    @include('theme.breadcrumb', ['crumbs' => [
        ['Products', route('products.index')],
        [$brand->name, null],
    ]])

    <div class="t-card p-5 md:p-6 mb-6 flex items-center gap-5">
        @if($brand->logo)
        <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}"
             class="w-20 h-20 object-contain p-2 shrink-0"
             style="border-radius: var(--t-radius); background:#fff; border:1px solid var(--t-border);">
        @else
        <span class="w-20 h-20 flex items-center justify-center shrink-0"
              style="border-radius: var(--t-radius); background: var(--t-surface-2);">
            <span class="text-2xl font-extrabold t-muted">{{ strtoupper(substr($brand->name, 0, 2)) }}</span>
        </span>
        @endif
        <div class="min-w-0">
            <h1 class="text-xl md:text-2xl font-extrabold t-heading">{{ $brand->name }}</h1>
            <p class="text-sm t-muted mt-1">{{ number_format($products->total()) }} products</p>
            @if($brand->description)
            <p class="text-sm t-muted mt-1.5">{{ $brand->description }}</p>
            @endif
        </div>
    </div>

    <div class="flex gap-6 lg:gap-8">
        <aside class="w-56 shrink-0 hidden lg:block">
            <div class="sticky" style="top: 6rem;">
                @include('theme.product-filters', [
                    'action'     => route('brand.show', $brand->slug),
                    'formId'     => 'filterForm',
                    'categories' => $categories,
                    'brands'     => collect(),
                    'clearUrl'   => route('brand.show', $brand->slug),
                ])
            </div>
        </aside>

        <div x-show="filtersOpen" x-cloak class="fixed inset-0 z-[600] lg:hidden" @keydown.escape.window="filtersOpen = false">
            <div class="absolute inset-0" style="background:rgba(0,0,0,.5);" @click="filtersOpen = false"></div>
            <div class="absolute inset-y-0 left-0 w-[85%] max-w-xs overflow-y-auto p-4" style="background: var(--t-bg);">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-extrabold t-heading">Filters</h2>
                    <button @click="filtersOpen = false" class="w-8 h-8 rounded-full flex items-center justify-center t-muted"
                            style="background: var(--t-surface-2);"><i class="fas fa-times"></i></button>
                </div>
                @include('theme.product-filters', [
                    'action'     => route('brand.show', $brand->slug),
                    'formId'     => 'filterFormMobile',
                    'categories' => $categories,
                    'brands'     => collect(),
                    'clearUrl'   => route('brand.show', $brand->slug),
                ])
            </div>
        </div>

        <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-3 mb-5">
                <p class="text-sm t-muted">
                    <span class="font-bold" style="color: var(--t-text);">{{ number_format($products->total()) }}</span> products
                </p>
                <div class="flex items-center gap-2">
                    <button @click="filtersOpen = true" class="t-btn t-btn-outline text-xs py-2 px-3 lg:hidden">
                        <i class="fas fa-sliders"></i> Filters
                    </button>
                    <select onchange="window.location = this.value" class="t-input text-xs py-2" style="width:auto;">
                        @foreach(['newest' => 'Newest First', 'price_asc' => 'Price: Low → High', 'price_desc' => 'Price: High → Low'] as $val => $label)
                        <option value="{{ request()->fullUrlWithQuery(['sort' => $val]) }}"
                                {{ request('sort', $val === 'newest' ? 'newest' : '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if($products->count())
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 md:gap-4" data-reveal-grid>
                @foreach($products as $product)
                    @include('ecom.partials.product-card', ['product' => $product])
                @endforeach
            </div>
            <div class="mt-8">{{ $products->links() }}</div>
            @else
            @include('theme.empty', [
                'icon'    => 'box-open',
                'title'   => 'No products found',
                'text'    => "Nothing from {$brand->name} is available right now.",
                'ctaUrl'  => route('products.index'),
                'ctaText' => 'Browse All Products',
            ])
            @endif
        </div>
    </div>
</div>
@endsection
