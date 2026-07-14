@extends('layouts.ecom')
@section('title', \App\Models\Setting::get('shop_name', 'MobileHub'))

@section('content')

{{-- Hero Banner Slider --}}
@if($heroBanners->count())
@php $sliderInterval = max(2, (int)\App\Models\Setting::get('banner_slider_interval', 5)); @endphp
<section class="relative bg-gray-900 select-none"
         x-data="heroBanner({{ $heroBanners->count() }}, {{ $sliderInterval * 1000 }})"
         x-init="init()"
         @mouseenter="pause()"
         @mouseleave="resume()">

    {{-- ── Slides strip ─────────────────────────────────────────────────────── --}}
    <div class="overflow-hidden">
        <div class="flex"
             :style="'transform:translateX(-' + (active * 100) + '%); transition:transform 750ms cubic-bezier(0.25,0.46,0.45,0.94)'">
            @foreach($heroBanners as $banner)
            <div class="relative shrink-0 w-full min-w-full md:h-[480px]">
                <img src="{{ $banner->image_url }}" alt="{{ $banner->title ?? '' }}"
                     class="block w-full h-auto md:h-full md:object-cover">
                <div class="absolute inset-0 flex items-center"
                     style="background:linear-gradient(to right,rgba(0,0,0,.65) 0%,rgba(0,0,0,.25) 55%,transparent 100%)">
                    <div class="max-w-7xl mx-auto px-6 w-full">
                        <div class="max-w-lg">
                            @if($banner->title)
                            <h1 class="text-3xl md:text-5xl font-extrabold text-white leading-tight mb-3 drop-shadow-lg">
                                {{ $banner->title }}
                            </h1>
                            @endif
                            @if($banner->subtitle)
                            <p class="text-gray-200 text-lg mb-6 drop-shadow">{{ $banner->subtitle }}</p>
                            @endif
                            @if($banner->link_url)
                            <a href="{{ $banner->link_url }}"
                               class="inline-flex items-center gap-2 text-white font-semibold px-6 py-3 rounded-xl transition-colors shadow-lg"
                               style="background:#be123c">
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
    {{-- ── Prev arrow ──────────────────────────────────────────────────────── --}}
    <button @click="prev()"
            class="absolute left-4 top-1/2 -translate-y-1/2 z-10 w-10 h-10 rounded-full flex items-center justify-center text-white transition-all"
            style="background:rgba(0,0,0,.35)" onmouseover="this.style.background='rgba(0,0,0,.65)'" onmouseout="this.style.background='rgba(0,0,0,.35)'">
        <i class="fas fa-chevron-left text-sm"></i>
    </button>

    {{-- ── Next arrow ──────────────────────────────────────────────────────── --}}
    <button @click="next()"
            class="absolute right-4 top-1/2 -translate-y-1/2 z-10 w-10 h-10 rounded-full flex items-center justify-center text-white transition-all"
            style="background:rgba(0,0,0,.35)" onmouseover="this.style.background='rgba(0,0,0,.65)'" onmouseout="this.style.background='rgba(0,0,0,.35)'">
        <i class="fas fa-chevron-right text-sm"></i>
    </button>

    {{-- ── Dots + timer ring ───────────────────────────────────────────────── --}}
    <div class="absolute bottom-5 left-0 right-0 flex items-center justify-center gap-2 z-10">
        @foreach($heroBanners as $i => $banner)
        <button @click="goTo({{ $i }})"
                class="rounded-full transition-all duration-300"
                :style="active === {{ $i }}
                    ? 'width:28px; height:6px; background:white;'
                    : 'width:6px;  height:6px; background:rgba(255,255,255,0.45);'">
        </button>
        @endforeach
    </div>

    {{-- ── Progress bar ────────────────────────────────────────────────────── --}}
    <div class="absolute bottom-0 left-0 right-0 z-10" style="height:3px; background:rgba(255,255,255,.2)">
        <div class="h-full"
             style="background:rgba(255,255,255,.75)"
             :style="'width:' + progress + '%; transition: width ' + (progress > 0 ? interval + 'ms' : '0ms') + ' linear'">
        </div>
    </div>
    @endif

</section>

@push('scripts')
<script>
function heroBanner(total, interval) {
    return {
        active:   0,
        total,
        interval,
        progress: 0,
        _timer:   null,
        _paused:  false,

        init() {
            if (this.total > 1) this._schedule();
        },

        _schedule() {
            clearTimeout(this._timer);
            // Instantly reset progress bar, then animate to 100 %
            this.progress = 0;
            this.$nextTick(() => requestAnimationFrame(() => { this.progress = 100; }));
            this._timer = setTimeout(() => {
                if (!this._paused) this.goTo((this.active + 1) % this.total);
            }, this.interval);
        },

        goTo(i) { this.active = i; this._schedule(); },
        prev()   { this.goTo((this.active - 1 + this.total) % this.total); },
        next()   { this.goTo((this.active + 1) % this.total); },

        pause()  { this._paused = true;  clearTimeout(this._timer); this.progress = 0; },
        resume() { this._paused = false; this._schedule(); },
    };
}
</script>
@endpush

@else
{{-- Fallback hero if no banners --}}
<section class="bg-gradient-to-br from-primary-600 to-primary-800 text-white py-20 px-4 text-center">
    <h1 class="text-4xl md:text-6xl font-extrabold mb-4">{{ \App\Models\Setting::get('shop_name', 'MobileHub') }}</h1>
    <p class="text-primary-100 text-xl mb-8">{{ \App\Models\Setting::get('shop_tagline', 'Your One-Stop Mobile Store') }}</p>
    <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 bg-white text-primary-700 font-bold px-8 py-4 rounded-xl shadow-lg hover:shadow-xl transition-all">
        Shop Now <i class="fas fa-arrow-right"></i>
    </a>
</section>
@endif

{{-- Active Deals Strip --}}
@if($activeDeals->count())
<section class="max-w-7xl mx-auto px-4 mt-8">
    <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-hide reveal">
        @foreach($activeDeals as $deal)
        <a href="{{ route('deals.index') }}" class="flex items-center gap-3 bg-gradient-to-r from-red-500 to-pink-600 text-white rounded-2xl px-5 py-3 shrink-0 hover:shadow-lg transition-all">
            <div class="text-2xl font-black">{{ $deal->badge_label }}</div>
            <div class="text-sm opacity-90 max-w-[120px] leading-tight">{{ $deal->name }}</div>
        </a>
        @endforeach
        <a href="{{ route('deals.index') }}" class="flex items-center gap-2 border-2 border-dashed border-gray-300 text-gray-500 rounded-2xl px-5 py-3 shrink-0 hover:border-primary-400 hover:text-primary-600 transition-all text-sm font-medium">
            View All Deals <i class="fas fa-chevron-right"></i>
        </a>
    </div>
</section>
@endif

{{-- Categories --}}
@if($categories->count())
<section class="max-w-7xl mx-auto px-4 mt-10"
         x-data="{
             modal: false,
             parentName: '',
             parentSlug: '',
             subs: [],
             open(slug) {
                 const d = window._catData && window._catData[slug];
                 if (!d || !d.subs.length) { window.location.href = '/category/' + slug; return; }
                 this.parentName = d.name;
                 this.parentSlug = slug;
                 this.subs       = d.subs;
                 this.modal      = true;
             }
         }">

    <div class="flex items-center justify-between mb-5 reveal">
        <h2 class="text-xl font-bold text-gray-900">Shop by Category</h2>
        <a href="{{ route('products.index') }}" class="text-sm text-primary-600 hover:underline font-medium">All Categories</a>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 lg:gap-4" data-reveal-grid>
        @foreach($categories as $cat)
        @php $hasSubs = $cat->children->where('is_active', true)->count() > 0; @endphp

        @if($hasSubs)
        {{-- Has subcategories → open modal --}}
        <button type="button" @click="open('{{ $cat->slug }}')"
                class="group relative flex flex-col items-center gap-2 p-4 bg-white rounded-2xl border border-gray-200 hover:border-primary-300 hover:shadow-md transition-all w-full text-left">
            {{-- Sub-badge --}}
            <span class="absolute top-2 right-2 text-xs font-bold px-1.5 py-0.5 rounded-full leading-none"
                  style="background:#ede9fe; color:#7c3aed; font-size:10px;">
                {{ $cat->children->where('is_active', true)->count() }} sub
            </span>
            @if($cat->image)
                <img src="{{ $cat->image_url }}" alt="{{ $cat->name }}"
                     loading="lazy"
                     class="w-14 h-14 object-cover rounded-xl bg-gray-100 group-hover:scale-110 transition-transform">
            @else
                <div class="w-14 h-14 rounded-xl bg-primary-50 flex items-center justify-center group-hover:bg-primary-100 transition-colors">
                    <i class="fas fa-box text-primary-600 text-xl"></i>
                </div>
            @endif
            <span class="text-xs font-medium text-gray-700 text-center leading-tight">{{ $cat->name }}</span>
            <span class="text-xs text-gray-400">{{ $cat->products_count }} items</span>
        </button>

        @else
        {{-- No subcategories → direct link --}}
        <a href="{{ route('category.show', $cat->slug) }}"
           class="group flex flex-col items-center gap-2 p-4 bg-white rounded-2xl border border-gray-200 hover:border-primary-300 hover:shadow-md transition-all">
            @if($cat->image)
                <img src="{{ $cat->image_url }}" alt="{{ $cat->name }}"
                     loading="lazy"
                     class="w-14 h-14 object-cover rounded-xl bg-gray-100 group-hover:scale-110 transition-transform">
            @else
                <div class="w-14 h-14 rounded-xl bg-primary-50 flex items-center justify-center group-hover:bg-primary-100 transition-colors">
                    <i class="fas fa-box text-primary-600 text-xl"></i>
                </div>
            @endif
            <span class="text-xs font-medium text-gray-700 text-center leading-tight">{{ $cat->name }}</span>
            <span class="text-xs text-gray-400">{{ $cat->products_count }} items</span>
        </a>
        @endif

        @endforeach
    </div>

    {{-- ── Subcategory Modal (teleported to <body> so position:fixed works correctly) --}}
    <template x-teleport="body">
        <div x-show="modal"
             x-cloak
             @keydown.escape.window="modal = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4">

            {{-- Backdrop --}}
            <div class="absolute inset-0" @click="modal = false"
                 style="background:rgba(0,0,0,0.55)"></div>

            {{-- Card --}}
            <div x-show="modal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden z-10">

                {{-- Header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <div>
                        <h3 class="font-bold text-gray-900 text-base" x-text="parentName"></h3>
                        <p class="text-xs text-gray-500 mt-0.5">Select a sub-category to browse</p>
                    </div>
                    <button @click="modal = false"
                            class="w-8 h-8 rounded-full flex items-center justify-center transition-colors shrink-0"
                            style="background:#f3f4f6">
                        <i class="fas fa-times text-gray-500 text-sm"></i>
                    </button>
                </div>

                <div class="p-5 space-y-3 max-h-[70vh] overflow-y-auto">

                    {{-- "All in [Category]" row --}}
                    <a :href="'/category/' + parentSlug"
                       class="flex items-center gap-3 p-3 rounded-xl border-2 transition-all"
                       style="border-color:#c4b5fd; background:#f5f3ff">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0"
                             style="background:#7c3aed">
                            <i class="fas fa-th-large text-white text-sm"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm" style="color:#6d28d9"
                                 x-text="'All in ' + parentName"></div>
                            <div class="text-xs" style="color:#8b5cf6">View all products in this category</div>
                        </div>
                        <i class="fas fa-chevron-right text-xs" style="color:#a78bfa"></i>
                    </a>

                    {{-- Sub-category grid --}}
                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="sub in subs" :key="sub.slug">
                            <a :href="'/category/' + sub.slug"
                               class="flex items-center gap-2 p-3 rounded-xl border border-gray-200 hover:border-primary-300 hover:bg-primary-50 transition-all">
                                <img :src="sub.image_url" :alt="sub.name"
                                     class="w-9 h-9 object-cover rounded-lg bg-gray-100 shrink-0"
                                     onerror="this.src='/images/category-placeholder.png'">
                                <div class="min-w-0">
                                    <div class="text-xs font-semibold text-gray-800 leading-tight truncate"
                                         x-text="sub.name"></div>
                                    <div class="text-xs text-gray-400"
                                         x-text="sub.count + ' items'"></div>
                                </div>
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

{{-- Promo Banners --}}
@if($promoBanners->count())
<section class="max-w-7xl mx-auto px-4 mt-10">
    <div class="grid grid-cols-1 md:grid-cols-{{ $promoBanners->count() > 1 ? '2' : '1' }} gap-4" data-reveal-grid>
        @foreach($promoBanners->take(2) as $banner)
        <a href="{{ $banner->link_url ?: '#' }}" class="relative overflow-hidden rounded-2xl group">
            <img src="{{ $banner->image_url }}" loading="lazy" class="w-full object-cover h-48 group-hover:scale-105 transition-transform duration-500" alt="{{ $banner->title }}">
            @if($banner->title)
            <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent flex items-end p-5">
                <div>
                    <div class="text-white font-bold text-lg">{{ $banner->title }}</div>
                    @if($banner->button_text)
                    <div class="mt-1 text-xs text-primary-300 font-medium">{{ $banner->button_text }} →</div>
                    @endif
                </div>
            </div>
            @endif
        </a>
        @endforeach
    </div>
</section>
@endif

{{-- Hot Deals Slider --}}
@if($dealProductChunks->count())
@php $totalSlides = $dealProductChunks->count(); @endphp
<section class="max-w-7xl mx-auto px-4 mt-12"
         x-data="{ slide: 0, total: {{ $totalSlides }} }">

    {{-- Section header --}}
    <div class="flex items-center justify-between mb-5 reveal">
        <div class="flex items-center gap-3">
            <div class="w-1 h-7 bg-gradient-to-b from-red-500 to-pink-600 rounded-full"></div>
            <div>
                <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-fire text-red-500"></i> Hot Deals
                </h2>
                <p class="text-sm text-gray-500">Best prices, limited time</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            {{-- Prev / Next --}}
            @if($totalSlides > 1)
            <div class="flex gap-2">
                <button @click="slide = (slide - 1 + total) % total"
                        class="w-9 h-9 rounded-full border border-gray-300 hover:border-red-400 hover:bg-red-50 flex items-center justify-center text-gray-600 hover:text-red-600 transition-all">
                    <i class="fas fa-chevron-left text-xs"></i>
                </button>
                <button @click="slide = (slide + 1) % total"
                        class="w-9 h-9 rounded-full border border-gray-300 hover:border-red-400 hover:bg-red-50 flex items-center justify-center text-gray-600 hover:text-red-600 transition-all">
                    <i class="fas fa-chevron-right text-xs"></i>
                </button>
            </div>
            @endif
            <a href="{{ route('deals.index') }}" class="text-sm text-primary-600 hover:underline font-medium">View All</a>
        </div>
    </div>

    {{-- Slides --}}
    <div class="relative overflow-hidden">
        @foreach($dealProductChunks as $slideIndex => $chunk)
        <div x-show="slide === {{ $slideIndex }}"
             x-transition:enter="transition-all duration-400 ease-out"
             x-transition:enter-start="opacity-0 translate-x-8"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition-all duration-200 ease-in"
             x-transition:leave-start="opacity-100 translate-x-0"
             x-transition:leave-end="opacity-0 -translate-x-8"
             class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            @foreach($chunk as $product)
                @include('ecom.partials.product-card', ['product' => $product])
            @endforeach
        </div>
        @endforeach
    </div>

    {{-- Dot indicators --}}
    @if($totalSlides > 1)
    <div class="flex justify-center gap-2 mt-5">
        @for($d = 0; $d < $totalSlides; $d++)
        <button @click="slide = {{ $d }}"
                :class="slide === {{ $d }} ? 'bg-red-500 w-6' : 'bg-gray-300 hover:bg-gray-400 w-2'"
                class="h-2 rounded-full transition-all duration-300"></button>
        @endfor
    </div>
    @endif

</section>
@endif

{{-- Featured Products --}}
@if($featuredProducts->count())
<section class="max-w-7xl mx-auto px-4 mt-12">
    <div class="flex items-center justify-between mb-5 reveal">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Featured Products</h2>
            <p class="text-sm text-gray-500">Handpicked top picks</p>
        </div>
        <a href="{{ route('products.index', ['featured' => 1]) }}" class="text-sm text-primary-600 hover:underline font-medium">View All</a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 gap-4" data-reveal-grid>
        @foreach($featuredProducts as $product)
            @include('ecom.partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>
@endif

{{-- New Arrivals — one row per section --}}
@php
    $sectionAccents = [
        'from-primary-500 to-primary-700',
        'from-emerald-500 to-teal-600',
        'from-amber-500 to-orange-600',
        'from-rose-500 to-pink-600',
        'from-violet-500 to-purple-700',
    ];
@endphp

@foreach($sectionNewArrivals as $i => $data)
<section class="max-w-7xl mx-auto px-4 mt-12 {{ $loop->last ? 'mb-8' : '' }}">
    <div class="flex items-center justify-between mb-5 reveal">
        <div class="flex items-center gap-3">
            {{-- Accent bar — cycles through palette --}}
            <div class="w-1 h-8 bg-gradient-to-b {{ $sectionAccents[$i % count($sectionAccents)] }} rounded-full"></div>
            <div>
                <h2 class="text-xl font-bold text-gray-900">
                    New in {{ $data['section']->name }}
                </h2>
                <p class="text-sm text-gray-500">Latest arrivals — {{ $data['section']->name }}</p>
            </div>
        </div>
        <a href="{{ route('products.index', ['section' => $data['section']->slug, 'sort' => 'newest']) }}"
           class="text-sm text-primary-600 hover:underline font-medium">
            View All <i class="fas fa-arrow-right text-xs ml-1"></i>
        </a>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4" data-reveal-grid>
        @foreach($data['products'] as $product)
            @include('ecom.partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>
@endforeach

{{-- Trust badges --}}
<section class="bg-white border-t border-gray-100 mt-12">
    <div class="max-w-7xl mx-auto px-4 py-10">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center" data-reveal-grid>
            <div class="flex flex-col items-center gap-2">
                <div class="w-12 h-12 bg-primary-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-shipping-fast text-primary-600 text-xl"></i>
                </div>
                <div class="font-semibold text-gray-800 text-sm">Fast Delivery</div>
                <div class="text-xs text-gray-500">Same-day in Lahore</div>
            </div>
            <div class="flex flex-col items-center gap-2">
                <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-shield-alt text-green-600 text-xl"></i>
                </div>
                <div class="font-semibold text-gray-800 text-sm">Genuine Products</div>
                <div class="text-xs text-gray-500">100% authentic</div>
            </div>
            <div class="flex flex-col items-center gap-2">
                <div class="w-12 h-12 bg-yellow-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-undo-alt text-yellow-600 text-xl"></i>
                </div>
                <div class="font-semibold text-gray-800 text-sm">Easy Returns</div>
                <div class="text-xs text-gray-500">7-day return policy</div>
            </div>
            <div class="flex flex-col items-center gap-2">
                <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-headset text-purple-600 text-xl"></i>
                </div>
                <div class="font-semibold text-gray-800 text-sm">24/7 Support</div>
                <div class="text-xs text-gray-500">Always here to help</div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('styles')
<style>
.reveal {
    opacity: 0;
    transform: translateY(32px);
    transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1),
                transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
}
.reveal.is-visible {
    opacity: 1;
    transform: none;
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    // Stagger children of every [data-reveal-grid] container
    document.querySelectorAll('[data-reveal-grid]').forEach(function (grid) {
        Array.from(grid.children).forEach(function (child, i) {
            child.classList.add('reveal');
            child.style.transitionDelay = Math.min(i * 0.08, 0.48) + 's';
        });
    });

    // Observe all .reveal elements
    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -48px 0px' });

    document.querySelectorAll('.reveal').forEach(function (el) {
        observer.observe(el);
    });
}());
</script>
@endpush
