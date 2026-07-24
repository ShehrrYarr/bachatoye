@extends('layouts.ecom')
@section('title', 'Deals & Offers')

@section('content')
<div class="t-container py-6 md:py-8">

    @include('theme.breadcrumb', ['crumbs' => [['Deals & Offers', null]]])

    <div class="text-center mb-10">
        <span class="t-chip mb-3" style="background:#fee2e2; color:#b91c1c; border-color:#fecaca;">
            <i class="fas fa-bolt"></i> Limited Time Offers
        </span>
        <h1 class="text-2xl md:text-4xl font-extrabold t-heading">Deals &amp; Offers</h1>
        <p class="t-muted mt-2 text-sm md:text-base">Grab the best prices before they're gone</p>
    </div>

    @if($deals->count())
    <div class="space-y-10">
        @foreach($deals as $deal)
        @php
            // Same resolution as the default deals page.
            $dealProducts = $deal->products->count()
                ? $deal->products
                : \App\Models\Product::whereHas('category', fn($q) => $q->whereIn('id', $deal->categories->pluck('id')))
                    ->active()->with(['images', 'category', 'brand'])->take(8)->get();
        @endphp
        <section class="t-card overflow-hidden">
            <div class="flex flex-wrap items-center gap-4 px-4 md:px-6 py-4"
                 style="background: linear-gradient(135deg,#ef4444,#db2777);">
                <span class="flex items-center gap-2 px-3 py-1.5 rounded-full shrink-0"
                      style="background:rgba(255,255,255,.2);">
                    <i class="fas
                        @if($deal->type === 'percentage') fa-percent
                        @elseif($deal->type === 'flat') fa-tag
                        @else fa-gift @endif text-white text-sm"></i>
                    <span class="font-black text-white text-lg leading-none">{{ $deal->badge_label }}</span>
                </span>
                <div class="min-w-0">
                    <h2 class="font-extrabold text-white text-base md:text-lg leading-tight">{{ $deal->name }}</h2>
                    @if($deal->ends_at)
                    <p class="text-xs flex items-center gap-1.5 mt-0.5" style="color:rgba(255,255,255,.9);">
                        <i class="fas fa-clock"></i> Ends {{ $deal->ends_at->format('d M Y') }}
                    </p>
                    @endif
                </div>
                <span class="ml-auto text-xs font-bold px-3 py-1.5 rounded-full shrink-0"
                      style="background:rgba(255,255,255,.2); color:#fff;">
                    {{ $dealProducts->count() }} item{{ $dealProducts->count() === 1 ? '' : 's' }}
                </span>
            </div>

            <div class="p-4 md:p-5">
                @if($dealProducts->count())
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4" data-reveal-grid>
                    @foreach($dealProducts as $product)
                        @include('ecom.partials.product-card', ['product' => $product])
                    @endforeach
                </div>
                @else
                <div class="text-center py-10 t-muted" style="background: var(--t-surface-2); border-radius: var(--t-radius);">
                    <i class="fas fa-box-open text-2xl mb-2"></i>
                    <p class="text-sm">No products in this deal yet</p>
                </div>
                @endif
            </div>
        </section>
        @endforeach
    </div>

    @else
    @include('theme.empty', [
        'icon'    => 'tag',
        'title'   => 'No Active Deals',
        'text'    => 'Check back soon for exciting offers!',
        'ctaUrl'  => route('products.index'),
        'ctaText' => 'Browse All Products',
    ])
    @endif
</div>
@endsection
