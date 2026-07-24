@extends('layouts.ecom')
@section('title', \App\Models\Setting::get('shop_name', 'MobileHub'))

@section('content')

{{-- ══ Hero slider — banner proportions match every other view ══════════ --}}
@if($heroBanners->count())
@php $sliderInterval = max(2, (int) \App\Models\Setting::get('banner_slider_interval', 5)); @endphp
<section class="relative select-none" style="background:#0f172a;"
         x-data="heroBanner({{ $heroBanners->count() }}, {{ $sliderInterval * 1000 }})"
         x-init="init()" @mouseenter="pause()" @mouseleave="resume()">

    <div class="overflow-hidden">
        <div class="flex" :style="'transform:translateX(-' + (active * 100) + '%); transition:transform 750ms cubic-bezier(.25,.46,.45,.94)'">
            @foreach($heroBanners as $banner)
            <div class="relative shrink-0 w-full min-w-full md:h-[480px]">
                <img src="{{ $banner->image_url }}" alt="{{ $banner->title ?? '' }}"
                     class="block w-full h-auto md:h-full md:object-cover">
                <div class="absolute inset-0 flex items-center"
                     style="background:linear-gradient(to right,rgba(0,0,0,.68) 0%,rgba(0,0,0,.28) 55%,transparent 100%)">
                    <div class="t-container w-full">
                        <div class="max-w-lg">
                            @if($banner->title)
                            <h1 class="text-3xl md:text-5xl font-extrabold text-white leading-tight mb-3" style="letter-spacing:-.02em; text-shadow:0 2px 18px rgba(0,0,0,.5);">
                                {{ $banner->title }}
                            </h1>
                            @endif
                            @if($banner->subtitle)
                            <p class="text-lg mb-6" style="color:#e2e8f0; text-shadow:0 1px 10px rgba(0,0,0,.5);">{{ $banner->subtitle }}</p>
                            @endif
                            @if($banner->link_url)
                            <a href="{{ $banner->link_url }}" class="t-btn t-btn-primary text-base px-7 py-3.5">
                                {{ $banner->button_text ?: 'Shop Now' }}
                                <i class="fas fa-arrow-right"></i>
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
    <button @click="prev()" aria-label="Previous"
            class="absolute left-4 top-1/2 -translate-y-1/2 z-10 w-10 h-10 rounded-full items-center justify-center text-white transition-all hidden sm:flex"
            style="background:rgba(0,0,0,.35); backdrop-filter:blur(4px);">
        <i class="fas fa-chevron-left text-sm"></i>
    </button>
    <button @click="next()" aria-label="Next"
            class="absolute right-4 top-1/2 -translate-y-1/2 z-10 w-10 h-10 rounded-full items-center justify-center text-white transition-all hidden sm:flex"
            style="background:rgba(0,0,0,.35); backdrop-filter:blur(4px);">
        <i class="fas fa-chevron-right text-sm"></i>
    </button>

    <div class="absolute bottom-5 left-0 right-0 flex items-center justify-center gap-2 z-10">
        @foreach($heroBanners as $i => $banner)
        <button @click="goTo({{ $i }})" aria-label="Slide {{ $i + 1 }}" class="rounded-full transition-all duration-300"
                :style="active === {{ $i }} ? 'width:28px;height:6px;background:#fff;' : 'width:6px;height:6px;background:rgba(255,255,255,.45);'"></button>
        @endforeach
    </div>

    <div class="absolute bottom-0 left-0 right-0 z-10" style="height:3px; background:rgba(255,255,255,.2)">
        <div class="h-full" style="background:rgba(255,255,255,.75)"
             :style="'width:' + progress + '%; transition: width ' + (progress > 0 ? interval + 'ms' : '0ms') + ' linear'"></div>
    </div>
    @endif
</section>

@push('scripts')
<script>
function heroBanner(total, interval) {
    return {
        active: 0, total, interval, progress: 0, _timer: null, _paused: false,
        init() { if (this.total > 1) this._schedule(); },
        _schedule() {
            clearTimeout(this._timer);
            this.progress = 0;
            this.$nextTick(() => requestAnimationFrame(() => { this.progress = 100; }));
            this._timer = setTimeout(() => { if (!this._paused) this.goTo((this.active + 1) % this.total); }, this.interval);
        },
        goTo(i) { this.active = i; this._schedule(); },
        prev()  { this.goTo((this.active - 1 + this.total) % this.total); },
        next()  { this.goTo((this.active + 1) % this.total); },
        pause() { this._paused = true; clearTimeout(this._timer); this.progress = 0; },
        resume(){ this._paused = false; this._schedule(); },
    };
}
</script>
@endpush

@else
<section class="text-white py-20 px-4 text-center" style="background: var(--app-gradient);">
    <h1 class="text-4xl md:text-6xl font-extrabold mb-4">{{ \App\Models\Setting::get('shop_name', 'MobileHub') }}</h1>
    <p class="text-xl mb-8" style="opacity:.9;">{{ \App\Models\Setting::get('shop_tagline', 'Your One-Stop Mobile Store') }}</p>
    <a href="{{ route('products.index') }}" class="t-btn text-base px-8 py-4" style="background:#fff; color: var(--app-primary);">
        Shop Now <i class="fas fa-arrow-right"></i>
    </a>
</section>
@endif

{{-- ══ Category rail ═══════════════════════════════════════════════════ --}}
@if($categories->count())
<section class="t-container mt-6" x-data="{
        modal: false, parentName: '', parentSlug: '', subs: [],
        open(slug) {
            const d = window._catData && window._catData[slug];
            if (!d || !d.subs.length) { window.location.href = '/category/' + slug; return; }
            this.parentName = d.name; this.parentSlug = slug; this.subs = d.subs; this.modal = true;
        }
    }">

    <div class="t-card p-4 md:p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base md:text-lg font-extrabold t-heading">
                <i class="fas fa-grip-vertical mr-2 t-accent"></i>Shop by Category
            </h2>
            <a href="{{ route('products.index') }}" class="text-xs font-bold t-accent hover:underline whitespace-nowrap">
                All Categories <i class="fas fa-chevron-right text-[10px] ml-0.5"></i>
            </a>
        </div>

        <div class="flex md:grid gap-3 md:gap-2 overflow-x-auto scrollbar-hide pb-1"
             style="grid-template-columns: repeat(8, minmax(0,1fr));">
            @foreach($categories as $cat)
            @php $hasSubs = $cat->children->where('is_active', true)->count() > 0; @endphp
            <{{ $hasSubs ? 'button' : 'a' }}
                @if($hasSubs) type="button" @click="open('{{ $cat->slug }}')" @else href="{{ route('category.show', $cat->slug) }}" @endif
                class="mk-cat group shrink-0">
                <span class="mk-cat-img">
                    @if($cat->image)
                        <img src="{{ $cat->image_url }}" alt="{{ $cat->name }}" loading="lazy"
                             class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110">
                    @else
                        <i class="fas fa-box text-xl t-accent"></i>
                    @endif
                    @if($hasSubs)
                    <span class="absolute -top-1 -right-1 text-[9px] font-bold px-1.5 py-0.5 rounded-full"
                          style="background: var(--app-gradient); color:#fff;">{{ $cat->children->where('is_active', true)->count() }}</span>
                    @endif
                </span>
                <span class="text-[11px] font-semibold text-center leading-tight line-clamp-2">{{ $cat->name }}</span>
                <span class="text-[10px] t-muted">{{ $cat->products_count }} items</span>
            </{{ $hasSubs ? 'button' : 'a' }}>
            @endforeach
        </div>
    </div>

    {{-- Sub-category modal --}}
    <template x-teleport="body">
        <div x-show="modal" x-cloak @keydown.escape.window="modal = false"
             class="fixed inset-0 z-[600] flex items-center justify-center p-4">
            <div class="absolute inset-0" @click="modal = false" style="background:rgba(0,0,0,.55)"></div>
            <div x-show="modal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 class="relative t-card w-full max-w-md overflow-hidden z-10" style="box-shadow: var(--t-shadow-lg);">

                <div class="flex items-center justify-between px-5 py-4" style="border-bottom:1px solid var(--t-border);">
                    <div>
                        <h3 class="font-extrabold text-base t-heading" x-text="parentName"></h3>
                        <p class="text-xs t-muted mt-0.5">Select a sub-category to browse</p>
                    </div>
                    <button @click="modal = false" class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 t-muted"
                            style="background: var(--t-surface-2);">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                <div class="p-5 space-y-3" style="max-height:70vh; overflow-y:auto;">
                    <a :href="'/category/' + parentSlug" class="flex items-center gap-3 p-3 transition-all"
                       style="border:2px solid rgb(var(--t-accent-rgb) / .35); border-radius: var(--t-radius-sm); background: rgb(var(--t-accent-rgb) / .08);">
                        <span class="w-10 h-10 flex items-center justify-center shrink-0"
                              style="background: var(--app-gradient); border-radius: var(--t-radius-sm);">
                            <i class="fas fa-grip text-white text-sm"></i>
                        </span>
                        <span class="flex-1 min-w-0">
                            <span class="block font-bold text-sm t-accent" x-text="'All in ' + parentName"></span>
                            <span class="block text-xs t-muted">View every product in this category</span>
                        </span>
                        <i class="fas fa-chevron-right text-xs t-accent"></i>
                    </a>

                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="sub in subs" :key="sub.slug">
                            <a :href="'/category/' + sub.slug" class="flex items-center gap-2 p-3 transition-all"
                               style="border:1px solid var(--t-border); border-radius: var(--t-radius-sm);">
                                <img :src="sub.image_url" :alt="sub.name" class="w-9 h-9 object-cover shrink-0"
                                     style="border-radius: var(--t-radius-sm); background: var(--t-surface-2);"
                                     onerror="this.src='/images/category-placeholder.png'">
                                <span class="min-w-0">
                                    <span class="block text-xs font-bold leading-tight truncate" x-text="sub.name"></span>
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
                'name'      => $c->name,
                'slug'      => $c->slug,
                'image_url' => $c->image_url,
                'count'     => $c->products_count ?? 0,
            ])->values()->all(),
        ],
    ])
);
</script>
@endpush
@endif

{{-- ══ Deal chips ══════════════════════════════════════════════════════ --}}
@if($activeDeals->count())
<section class="t-container mt-6">
    <div class="flex gap-3 overflow-x-auto scrollbar-hide pb-1">
        @foreach($activeDeals as $deal)
        <a href="{{ route('deals.index') }}" class="flex items-center gap-3 px-5 py-3 shrink-0 transition-transform hover:-translate-y-0.5"
           style="background: linear-gradient(135deg,#ef4444,#db2777); color:#fff; border-radius: var(--t-radius);">
            <span class="text-2xl font-black leading-none">{{ $deal->badge_label }}</span>
            <span class="text-xs leading-tight max-w-[130px]" style="opacity:.92;">{{ $deal->name }}</span>
        </a>
        @endforeach
        <a href="{{ route('deals.index') }}" class="flex items-center gap-2 px-5 py-3 shrink-0 text-sm font-semibold transition-colors t-muted"
           style="border:2px dashed var(--t-border); border-radius: var(--t-radius);">
            View All Deals <i class="fas fa-chevron-right text-xs"></i>
        </a>
    </div>
</section>
@endif

{{-- ══ Hot deals ═══════════════════════════════════════════════════════ --}}
@if($dealProductChunks->count())
@php $totalSlides = $dealProductChunks->count(); @endphp
<section class="t-container mt-8" x-data="{ slide: 0, total: {{ $totalSlides }} }">
    <div class="overflow-hidden t-card" style="border-color: rgba(239,68,68,.3);">
        <div class="flex items-center justify-between px-4 md:px-5 py-3.5"
             style="background: linear-gradient(135deg,#ef4444,#db2777);">
            <div class="flex items-center gap-2.5 min-w-0">
                <i class="fas fa-bolt text-white text-lg"></i>
                <div class="min-w-0">
                    <h2 class="text-white font-extrabold text-base md:text-lg leading-tight t-heading">Hot Deals</h2>
                    <p class="text-[11px]" style="color:rgba(255,255,255,.85);">Best prices, limited time</p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if($totalSlides > 1)
                <button @click="slide = (slide - 1 + total) % total" aria-label="Previous"
                        class="w-8 h-8 rounded-full flex items-center justify-center text-white" style="background:rgba(255,255,255,.22);">
                    <i class="fas fa-chevron-left text-xs"></i>
                </button>
                <button @click="slide = (slide + 1) % total" aria-label="Next"
                        class="w-8 h-8 rounded-full flex items-center justify-center text-white" style="background:rgba(255,255,255,.22);">
                    <i class="fas fa-chevron-right text-xs"></i>
                </button>
                @endif
                <a href="{{ route('deals.index') }}" class="text-xs font-bold text-white hover:underline whitespace-nowrap ml-1">View All</a>
            </div>
        </div>

        <div class="relative overflow-hidden p-3 md:p-4">
            @foreach($dealProductChunks as $slideIndex => $chunk)
            <div x-show="slide === {{ $slideIndex }}"
                 x-transition:enter="transition-all duration-300 ease-out"
                 x-transition:enter-start="opacity-0 translate-x-6"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                @foreach($chunk as $product)
                    @include('ecom.partials.product-card', ['product' => $product])
                @endforeach
            </div>
            @endforeach
        </div>

        @if($totalSlides > 1)
        <div class="flex justify-center gap-1.5 pb-4">
            @for($d = 0; $d < $totalSlides; $d++)
            <button @click="slide = {{ $d }}" aria-label="Slide {{ $d + 1 }}"
                    class="h-1.5 rounded-full transition-all duration-300"
                    :class="slide === {{ $d }} ? 'w-6' : 'w-1.5'"
                    :style="slide === {{ $d }} ? 'background:#ef4444' : 'background: var(--t-border)'"></button>
            @endfor
        </div>
        @endif
    </div>
</section>
@endif

{{-- ══ Promo banners ═══════════════════════════════════════════════════ --}}
@if($promoBanners->count())
<section class="t-container mt-8">
    <div class="grid grid-cols-1 md:grid-cols-{{ $promoBanners->count() > 1 ? '2' : '1' }} gap-4" data-reveal-grid>
        @foreach($promoBanners->take(2) as $banner)
        <a href="{{ $banner->link_url ?: '#' }}" class="relative overflow-hidden group" style="border-radius: var(--t-radius);">
            <img src="{{ $banner->image_url }}" loading="lazy" alt="{{ $banner->title }}"
                 class="w-full object-cover h-48 transition-transform duration-500 group-hover:scale-105">
            @if($banner->title)
            <div class="absolute inset-0 flex items-end p-5" style="background:linear-gradient(to top,rgba(0,0,0,.6),transparent);">
                <div>
                    <div class="text-white font-extrabold text-lg">{{ $banner->title }}</div>
                    @if($banner->button_text)
                    <div class="mt-1 text-xs font-bold" style="color:#fff; opacity:.85;">{{ $banner->button_text }} →</div>
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
<section class="t-container mt-10">
    <div class="flex items-center justify-between mb-4 reveal">
        <div class="flex items-center gap-3">
            <span style="width:4px; height:30px; border-radius:2px; background: var(--app-gradient); display:block;"></span>
            <div>
                <h2 class="text-lg font-extrabold t-heading">Featured Products</h2>
                <p class="text-xs t-muted">Handpicked top picks</p>
            </div>
        </div>
        <a href="{{ route('products.index', ['featured' => 1]) }}" class="text-xs font-bold t-accent hover:underline whitespace-nowrap">
            View All <i class="fas fa-chevron-right text-[10px]"></i>
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
@foreach($sectionNewArrivals as $i => $data)
<section class="t-container mt-10 {{ $loop->last ? 'mb-10' : '' }}">
    <div class="flex items-center justify-between mb-4 reveal">
        <div class="flex items-center gap-3">
            <span style="width:4px; height:30px; border-radius:2px; background: var(--app-gradient); display:block;"></span>
            <div>
                <h2 class="text-lg font-extrabold t-heading">New in {{ $data['section']->name }}</h2>
                <p class="text-xs t-muted">Latest arrivals — {{ $data['section']->name }}</p>
            </div>
        </div>
        <a href="{{ route('products.index', ['section' => $data['section']->slug, 'sort' => 'newest']) }}"
           class="text-xs font-bold t-accent hover:underline whitespace-nowrap">
            View All <i class="fas fa-chevron-right text-[10px]"></i>
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
    .mk-cat {
        display: flex; flex-direction: column; align-items: center; gap: .4rem;
        padding: .5rem .25rem;
        border-radius: var(--t-radius-sm);
        width: 88px;
        transition: background .18s ease;
    }
    @media (min-width: 768px) { .mk-cat { width: auto; } }
    .mk-cat:hover { background: var(--t-surface-2); }
    .mk-cat-img {
        position: relative;
        width: 56px; height: 56px;
        border-radius: 50%;
        overflow: visible;
        display: flex; align-items: center; justify-content: center;
        background: rgb(var(--t-accent-rgb) / .10);
    }
    .mk-cat-img img { border-radius: 50%; }
</style>
@endpush
