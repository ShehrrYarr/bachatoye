@extends('layouts.ecom')
@section('title', 'Products')

@section('content')
<div class="t-container py-6 md:py-8" x-data="{ filtersOpen: false }">

    @include('theme.breadcrumb', ['crumbs' => [['Products', null]]])

    {{-- Active filter chips --}}
    @php
        $activeFilters = collect([
            'category'  => optional($categories->firstWhere('id', request('category')))->name,
            'brand'     => optional($brands->firstWhere('id', request('brand')))->name,
            'min_price' => request('min_price') ? 'Min Rs. ' . number_format((float) request('min_price')) : null,
            'max_price' => request('max_price') ? 'Max Rs. ' . number_format((float) request('max_price')) : null,
            'q'         => request('q') ? '“' . request('q') . '”' : null,
        ])->filter();
    @endphp
    @if($activeFilters->isNotEmpty())
    <div class="flex flex-wrap items-center gap-2 mb-5">
        <span class="text-xs font-semibold t-muted">Filtered by</span>
        @foreach($activeFilters as $key => $label)
        <a href="{{ request()->fullUrlWithQuery([$key => null]) }}" class="t-chip t-chip-accent hover:opacity-80">
            {{ $label }} <i class="fas fa-times text-[10px]"></i>
        </a>
        @endforeach
        <a href="{{ route('products.index') }}" class="text-xs font-semibold hover:underline" style="color:#ef4444;">Clear all</a>
    </div>
    @endif

    <div class="flex gap-6 lg:gap-8">

        {{-- ── Filters ─────────────────────────────────────────────────── --}}
        <aside class="w-60 shrink-0 hidden lg:block">
            <div class="sticky" style="top: 6rem;">
                @include('theme.product-filters', [
                    'action'     => route('products.index'),
                    'formId'     => 'filterForm',
                    'categories' => $categories,
                    'brands'     => $brands,
                    'clearUrl'   => route('products.index'),
                ])
            </div>
        </aside>

        {{-- Mobile filter drawer --}}
        <div x-show="filtersOpen" x-cloak class="fixed inset-0 z-[600] lg:hidden" @keydown.escape.window="filtersOpen = false">
            <div class="absolute inset-0" style="background:rgba(0,0,0,.5);" @click="filtersOpen = false"></div>
            <div class="absolute inset-y-0 left-0 w-[85%] max-w-xs overflow-y-auto p-4"
                 style="background: var(--t-bg);"
                 x-transition:enter="transition ease-out duration-250"
                 x-transition:enter-start="-translate-x-full"
                 x-transition:enter-end="translate-x-0">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-extrabold t-heading">Filters</h2>
                    <button @click="filtersOpen = false" class="w-8 h-8 rounded-full flex items-center justify-center t-muted"
                            style="background: var(--t-surface-2);">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                @include('theme.product-filters', [
                    'action'     => route('products.index'),
                    'formId'     => 'filterFormMobile',
                    'categories' => $categories,
                    'brands'     => $brands,
                    'clearUrl'   => route('products.index'),
                ])
            </div>
        </div>

        {{-- ── Grid ────────────────────────────────────────────────────── --}}
        <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-3 mb-5">
                <p class="text-sm t-muted">
                    <span class="font-bold" style="color: var(--t-text);">{{ number_format($products->total()) }}</span>
                    product{{ $products->total() === 1 ? '' : 's' }} found
                </p>

                <div class="flex items-center gap-2">
                    <button @click="filtersOpen = true" class="t-btn t-btn-outline text-xs py-2 px-3 lg:hidden">
                        <i class="fas fa-sliders"></i> Filters
                        @if($activeFilters->isNotEmpty())
                        <span class="w-4 h-4 rounded-full text-[10px] font-bold flex items-center justify-center text-white"
                              style="background: var(--t-accent);">{{ $activeFilters->count() }}</span>
                        @endif
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
                'icon'   => 'box-open',
                'title'  => 'No products found',
                'text'   => 'Try removing a filter or widening your price range.',
                'ctaUrl' => route('products.index'),
                'ctaText'=> 'Clear filters',
            ])
            @endif
        </div>
    </div>
</div>
@endsection
