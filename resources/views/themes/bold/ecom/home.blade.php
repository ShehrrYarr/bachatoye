@extends('layouts.ecom')
@section('title', \App\Models\Setting::get('shop_name', 'MobileHub'))

@section('content')

{{-- ══ Hero — banner proportions match every other view ═════════════════ --}}
@if($heroBanners->count())
@php $sliderInterval = max(2, (int) \App\Models\Setting::get('banner_slider_interval', 5)); @endphp
<section class="relative select-none" style="background: var(--t-text);"
         x-data="heroBanner({{ $heroBanners->count() }}, {{ $sliderInterval * 1000 }})"
         x-init="init()" @mouseenter="pause()" @mouseleave="resume()">

    <div class="overflow-hidden">
        <div class="flex" :style="'transform:translateX(-' + (active * 100) + '%); transition:transform 650ms cubic-bezier(.34,1.4,.64,1)'">
            @foreach($heroBanners as $banner)
            <div class="relative shrink-0 w-full min-w-full md:h-[480px]">
                <img src="{{ $banner->image_url }}" alt="{{ $banner->title ?? '' }}"
                     class="block w-full h-auto md:h-full md:object-cover">
                <div class="absolute inset-0 flex items-center"
                     style="background:linear-gradient(to right, rgba(0,0,0,.72) 0%, rgba(0,0,0,.3) 58%, transparent 100%)">
                    <div class="t-container w-full">
                        <div class="max-w-lg">
                            <span class="inline-block text-white text-[11px] font-black px-3 py-1.5 rounded-full mb-4"
                                  style="background: var(--app-gradient);">
                                <i class="fas fa-bolt mr-1"></i>LIMITED TIME
                            </span>
                            @if($banner->title)
                            <h1 class="text-3xl md:text-5xl font-black text-white leading-none mb-4"
                                style="letter-spacing:-.035em;">{{ $banner->title }}</h1>
                            @endif
                            @if($banner->subtitle)
                            <p class="text-lg mb-7 font-medium" style="color:#f5f5f4;">{{ $banner->subtitle }}</p>
                            @endif
                            @if($banner->link_url)
                            <a href="{{ $banner->link_url }}" class="t-btn t-btn-primary text-base px-8 py-4">
                                {{ $banner->button_text ?: 'GRAB THE DEAL' }} <i class="fas fa-arrow-right"></i>
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    @if($heroBanners->count() > 1)
    <div class="absolute bottom-5 left-0 right-0 flex items-center justify-center gap-2 z-10">
        @foreach($heroBanners as $i => $banner)
        <button @click="goTo({{ $i }})" aria-label="Slide {{ $i + 1 }}" class="rounded-full transition-all duration-300"
                :style="active === {{ $i }} ? 'width:32px;height:6px;background: var(--app-primary);' : 'width:6px;height:6px;background:rgba(255,255,255,.5);'"></button>
        @endforeach
    </div>
    @endif
</section>

@push('scripts')
<script>
function heroBanner(total, interval) {
    return {
        active: 0, total, interval, _timer: null, _paused: false,
        init() { if (this.total > 1) this._schedule(); },
        _schedule() {
            clearTimeout(this._timer);
            this._timer = setTimeout(() => { if (!this._paused) this.goTo((this.active + 1) % this.total); }, this.interval);
        },
        goTo(i) { this.active = i; this._schedule(); },
        prev()  { this.goTo((this.active - 1 + this.total) % this.total); },
        next()  { this.goTo((this.active + 1) % this.total); },
        pause() { this._paused = true; clearTimeout(this._timer); },
        resume(){ this._paused = false; this._schedule(); },
    };
}
</script>
@endpush

@else
<section class="text-center py-24 px-4" style="background: var(--app-gradient);">
    <h1 class="text-4xl md:text-6xl font-black text-white mb-4" style="letter-spacing:-.035em;">{{ \App\Models\Setting::get('shop_name', 'MobileHub') }}</h1>
    <p class="text-xl mb-9" style="color:rgba(255,255,255,.92);">{{ \App\Models\Setting::get('shop_tagline', 'Your One-Stop Mobile Store') }}</p>
    <a href="{{ route('products.index') }}" class="t-btn px-9 py-4 text-base" style="background:#fff; color: var(--app-primary);">
        START SHOPPING <i class="fas fa-arrow-right"></i>
    </a>
</section>
@endif

{{-- ══ Flash-sale deal strip ═══════════════════════════════════════════ --}}
@if($activeDeals->count())
<section class="t-container -mt-6 md:-mt-8 relative z-10">
    <div class="t-card p-4 md:p-5" style="border-color: var(--t-accent);">
        <div class="flex items-center gap-3 mb-3.5">
            <span class="w-9 h-9 rounded-full flex items-center justify-center text-white shrink-0" style="background: var(--app-gradient);">
                <i class="fas fa-fire"></i>
            </span>
            <div class="min-w-0">
                <h2 class="font-black text-base md:text-lg t-heading leading-tight">Live Offers</h2>
                <p class="text-xs t-muted">Ending soon — don't miss out</p>
            </div>
            <a href="{{ route('deals.index') }}" class="ml-auto text-xs font-black t-accent hover:underline whitespace-nowrap">
                SEE ALL <i class="fas fa-chevron-right text-[10px]"></i>
            </a>
        </div>
        <div class="flex gap-3 overflow-x-auto scrollbar-hide pb-1">
            @foreach($activeDeals as $deal)
            <a href="{{ route('deals.index') }}" class="flex items-center gap-3 px-5 py-3 shrink-0 transition-transform hover:-translate-y-1"
               style="background: var(--app-gradient); color:#fff; border-radius:16px;">
                <span class="text-2xl font-black leading-none">{{ $deal->badge_label }}</span>
                <span class="text-xs font-bold leading-tight max-w-[130px]">{{ $deal->name }}</span>
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ══ Categories ══════════════════════════════════════════════════════ --}}
@if($categories->count())
<section class="t-container mt-10" x-data="{
        modal: false, parentName: '', parentSlug: '', subs: [],
        open(slug) {
            const d = window._catData && window._catData[slug];
            if (!d || !d.subs.length) { window.location.href = '/category/' + slug; return; }
            this.parentName = d.name; this.parentSlug = slug; this.subs = d.subs; this.modal = true;
        }
    }">
    <div class="flex items-center justify-between mb-5 reveal">
        <h2 class="text-xl md:text-2xl font-black t-heading">Shop by Category</h2>
        <a href="{{ route('products.index') }}" class="text-xs font-black t-accent hover:underline whitespace-nowrap">
            ALL <i class="fas fa-chevron-right text-[10px]"></i>
        </a>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4" data-reveal-grid>
        @foreach($categories as $cat)
        @php
            $hasSubs = $cat->children->where('is_active', true)->count() > 0;
            $tints   = [['#fff7ed','#c2410c'], ['#eff6ff','#1d4ed8'], ['#f0fdf4','#15803d'], ['#fdf4ff','#a21caf']];
            [$tintBg, $tintFg] = $tints[$loop->index % 4];
        @endphp
        <{{ $hasSubs ? 'button' : 'a' }}
            @if($hasSubs) type="button" @click="open('{{ $cat->slug }}')" @else href="{{ route('category.show', $cat->slug) }}" @endif
            class="bd-cat group text-left" style="background: {{ $tintBg }};">
            <span class="flex items-center gap-3.5">
                <span class="w-16 h-16 rounded-2xl overflow-hidden flex items-center justify-center shrink-0" style="background:#fff;">
                    @if($cat->image)
                    <img src="{{ $cat->image_url }}" alt="{{ $cat->name }}" loading="lazy"
                         class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110">
                    @else
                    <i class="fas fa-box text-xl" style="color: {{ $tintFg }};"></i>
                    @endif
                </span>
                <span class="min-w-0">
                    <span class="block font-black text-sm leading-tight" style="color: {{ $tintFg }};">{{ $cat->name }}</span>
                    <span class="block text-xs font-bold mt-1" style="color: {{ $tintFg }}; opacity:.7;">{{ $cat->products_count }} items</span>
                    @if($hasSubs)
                    <span class="inline-block mt-1.5 text-[10px] font-black px-2 py-0.5 rounded-full" style="background:#fff; color: {{ $tintFg }};">
                        {{ $cat->children->where('is_active', true)->count() }} sub
                    </span>
                    @endif
                </span>
            </span>
        </{{ $hasSubs ? 'button' : 'a' }}>
        @endforeach
    </div>

    <template x-teleport="body">
        <div x-show="modal" x-cloak @keydown.escape.window="modal = false" class="fixed inset-0 z-[600] flex items-center justify-center p-4">
            <div class="absolute inset-0" @click="modal = false" style="background:rgba(0,0,0,.55)"></div>
            <div x-show="modal" x-transition class="relative t-card w-full max-w-md overflow-hidden z-10">
                <div class="flex items-center justify-between px-5 py-4" style="background: var(--app-gradient);">
                    <h3 class="font-black text-white" x-text="parentName"></h3>
                    <button @click="modal = false" class="text-white"><i class="fas fa-times"></i></button>
                </div>
                <div class="p-5 space-y-2" style="max-height:70vh; overflow-y:auto;">
                    <a :href="'/category/' + parentSlug" class="flex items-center justify-between px-4 py-3 rounded-2xl"
                       style="background: rgb(var(--t-accent-rgb) / .12);">
                        <span class="text-sm font-black t-accent" x-text="'All in ' + parentName"></span>
                        <i class="fas fa-arrow-right text-xs t-accent"></i>
                    </a>
                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="sub in subs" :key="sub.slug">
                            <a :href="'/category/' + sub.slug" class="flex items-center gap-2 p-3 rounded-2xl"
                               style="border:2px solid var(--t-border);">
                                <img :src="sub.image_url" class="w-9 h-9 object-cover rounded-xl shrink-0" style="background: var(--t-surface-2);"
                                     onerror="this.src='/images/category-placeholder.png'">
                                <span class="min-w-0">
                                    <span class="block text-xs font-black truncate" x-text="sub.name"></span>
                                    <span class="block text-[10px] t-muted" x-text="sub.count + ' items'"></span>
                                </span>
                            </a>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </template>
</section>

@push('scripts')
<script>
window._catData = @js(
    $categories->mapWithKeys(fn($cat) => [
        $cat->slug => [
            'name' => $cat->name,
            'subs' => $cat->children->where('is_active', true)->values()->map(fn($c) => [
                'name' => $c->name, 'slug' => $c->slug,
                'image_url' => $c->image_url, 'count' => $c->products_count ?? 0,
            ])->values()->all(),
        ],
    ])
);
</script>
@endpush
@endif

{{-- ══ Hot deals ═══════════════════════════════════════════════════════ --}}
@if($dealProductChunks->count())
@php $totalSlides = $dealProductChunks->count(); @endphp
<section class="t-container mt-12" x-data="{ slide: 0, total: {{ $totalSlides }} }">
    <div class="overflow-hidden" style="border-radius:24px; background: var(--app-gradient); padding:3px;">
        <div style="background: var(--t-bg); border-radius:21px;">
            <div class="flex items-center justify-between px-4 md:px-6 py-4">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="w-11 h-11 rounded-full flex items-center justify-center text-white shrink-0" style="background: var(--app-gradient);">
                        <i class="fas fa-fire text-lg"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="font-black text-lg md:text-xl t-heading leading-tight">HOT DEALS</h2>
                        <p class="text-xs font-bold t-muted">Lowest prices of the season</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    @if($totalSlides > 1)
                    <button @click="slide = (slide - 1 + total) % total" aria-label="Previous"
                            class="w-9 h-9 rounded-full flex items-center justify-center t-accent" style="background: rgb(var(--t-accent-rgb) / .12);">
                        <i class="fas fa-chevron-left text-xs"></i>
                    </button>
                    <button @click="slide = (slide + 1) % total" aria-label="Next"
                            class="w-9 h-9 rounded-full flex items-center justify-center t-accent" style="background: rgb(var(--t-accent-rgb) / .12);">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </button>
                    @endif
                </div>
            </div>

            <div class="relative overflow-hidden px-4 md:px-6 pb-6">
                @foreach($dealProductChunks as $slideIndex => $chunk)
                <div x-show="slide === {{ $slideIndex }}"
                     x-transition:enter="transition-all duration-300 ease-out"
                     x-transition:enter-start="opacity-0 translate-x-6"
                     x-transition:enter-end="opacity-100 translate-x-0"
                     class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 md:gap-4">
                    @foreach($chunk as $product)
                        @include('ecom.partials.product-card', ['product' => $product])
                    @endforeach
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

{{-- ══ Promo banners ═══════════════════════════════════════════════════ --}}
@if($promoBanners->count())
<section class="t-container mt-12">
    <div class="grid grid-cols-1 md:grid-cols-{{ $promoBanners->count() > 1 ? '2' : '1' }} gap-4" data-reveal-grid>
        @foreach($promoBanners->take(2) as $banner)
        <a href="{{ $banner->link_url ?: '#' }}" class="relative overflow-hidden group" style="border-radius:20px;">
            <img src="{{ $banner->image_url }}" loading="lazy" alt="{{ $banner->title }}"
                 class="w-full object-cover h-52 transition-transform duration-500 group-hover:scale-105">
            @if($banner->title)
            <div class="absolute inset-0 flex items-end p-6" style="background:linear-gradient(to top, rgba(0,0,0,.68), transparent);">
                <div>
                    <div class="text-white font-black text-xl leading-tight">{{ $banner->title }}</div>
                    @if($banner->button_text)
                    <span class="inline-block mt-2.5 text-[11px] font-black text-white px-4 py-2 rounded-full" style="background: var(--app-gradient);">
                        {{ $banner->button_text }}
                    </span>
                    @endif
                </div>
            </div>
            @endif
        </a>
        @endforeach
    </div>
</section>
@endif

{{-- ══ Featured ════════════════════════════════════════════════════════ --}}
@if($featuredProducts->count())
<section class="t-container mt-12">
    <div class="flex items-center justify-between mb-5 reveal">
        <h2 class="text-xl md:text-2xl font-black t-heading">⭐ Featured Picks</h2>
        <a href="{{ route('products.index', ['featured' => 1]) }}" class="text-xs font-black t-accent hover:underline whitespace-nowrap">
            VIEW ALL <i class="fas fa-chevron-right text-[10px]"></i>
        </a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 md:gap-4" data-reveal-grid>
        @foreach($featuredProducts as $product)
            @include('ecom.partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>
@endif

{{-- ══ New arrivals per section ════════════════════════════════════════ --}}
@foreach($sectionNewArrivals as $data)
<section class="t-container mt-12 {{ $loop->last ? 'mb-10' : '' }}">
    <div class="flex items-center justify-between mb-5 reveal">
        <h2 class="text-xl md:text-2xl font-black t-heading">🆕 New in {{ $data['section']->name }}</h2>
        <a href="{{ route('products.index', ['section' => $data['section']->slug, 'sort' => 'newest']) }}"
           class="text-xs font-black t-accent hover:underline whitespace-nowrap">
            VIEW ALL <i class="fas fa-chevron-right text-[10px]"></i>
        </a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 md:gap-4" data-reveal-grid>
        @foreach($data['products'] as $product)
            @include('ecom.partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>
@endforeach

@endsection

@push('styles')
<style>
    .bd-cat {
        display:block;
        padding: 1rem;
        border-radius: 20px;
        border: 2px solid transparent;
        transition: transform .2s ease, border-color .2s ease;
    }
    .bd-cat:hover { transform: translateY(-4px); border-color: var(--t-accent); }
</style>
@endpush
