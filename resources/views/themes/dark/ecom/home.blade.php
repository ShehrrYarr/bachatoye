@extends('layouts.ecom')
@section('title', \App\Models\Setting::get('shop_name', 'MobileHub'))

@section('content')

{{-- ══ Hero — banner proportions match every other view ═════════════════ --}}
@if($heroBanners->count())
@php $sliderInterval = max(2, (int) \App\Models\Setting::get('banner_slider_interval', 5)); @endphp
<section class="relative select-none overflow-hidden" style="background: var(--t-surface);"
         x-data="heroBanner({{ $heroBanners->count() }}, {{ $sliderInterval * 1000 }})"
         x-init="init()" @mouseenter="pause()" @mouseleave="resume()">

    <div class="overflow-hidden">
        <div class="flex" :style="'transform:translateX(-' + (active * 100) + '%); transition:transform 750ms cubic-bezier(.25,.46,.45,.94)'">
            @foreach($heroBanners as $banner)
            <div class="relative shrink-0 w-full min-w-full md:h-[480px]">
                <img src="{{ $banner->image_url }}" alt="{{ $banner->title ?? '' }}"
                     class="block w-full h-auto md:h-full md:object-cover" style="opacity:.85;">
                <div class="absolute inset-0 flex items-center"
                     style="background:linear-gradient(to right, rgba(8,8,13,.92) 0%, rgba(8,8,13,.55) 55%, rgba(8,8,13,.15) 100%)">
                    <div class="t-container w-full">
                        <div class="max-w-lg">
                            @if($banner->title)
                            <h1 class="text-3xl md:text-5xl font-bold leading-tight mb-4 t-heading"
                                style="letter-spacing:-.025em; color: var(--t-text);">
                                {{ $banner->title }}
                            </h1>
                            @endif
                            @if($banner->subtitle)
                            <p class="text-base md:text-lg mb-7 t-muted">{{ $banner->subtitle }}</p>
                            @endif
                            @if($banner->link_url)
                            <a href="{{ $banner->link_url }}" class="t-btn t-btn-primary dk-glow text-base px-7 py-3.5">
                                {{ $banner->button_text ?: 'Shop Now' }} <i class="fas fa-arrow-right"></i>
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
            class="absolute left-4 top-1/2 -translate-y-1/2 z-10 w-10 h-10 rounded-full items-center justify-center hidden sm:flex t-muted"
            style="background: rgba(18,18,27,.8); border:1px solid var(--t-border);">
        <i class="fas fa-chevron-left text-sm"></i>
    </button>
    <button @click="next()" aria-label="Next"
            class="absolute right-4 top-1/2 -translate-y-1/2 z-10 w-10 h-10 rounded-full items-center justify-center hidden sm:flex t-muted"
            style="background: rgba(18,18,27,.8); border:1px solid var(--t-border);">
        <i class="fas fa-chevron-right text-sm"></i>
    </button>

    <div class="absolute bottom-5 left-0 right-0 flex items-center justify-center gap-2 z-10">
        @foreach($heroBanners as $i => $banner)
        <button @click="goTo({{ $i }})" aria-label="Slide {{ $i + 1 }}" class="rounded-full transition-all duration-300"
                :style="active === {{ $i }}
                    ? 'width:28px;height:5px;background: var(--app-primary); box-shadow:0 0 12px var(--app-primary);'
                    : 'width:5px;height:5px;background: rgba(255,255,255,.3);'"></button>
        @endforeach
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
<section class="text-center py-24 px-4">
    <h1 class="text-4xl md:text-6xl font-bold mb-4 t-heading">{{ \App\Models\Setting::get('shop_name', 'MobileHub') }}</h1>
    <p class="t-muted text-lg mb-9">{{ \App\Models\Setting::get('shop_tagline', 'Your One-Stop Mobile Store') }}</p>
    <a href="{{ route('products.index') }}" class="t-btn t-btn-primary dk-glow px-8 py-4">Shop Now <i class="fas fa-arrow-right"></i></a>
</section>
@endif

{{-- ══ Categories ══════════════════════════════════════════════════════ --}}
@if($categories->count())
<section class="t-container mt-12" x-data="{
        modal: false, parentName: '', parentSlug: '', subs: [],
        open(slug) {
            const d = window._catData && window._catData[slug];
            if (!d || !d.subs.length) { window.location.href = '/category/' + slug; return; }
            this.parentName = d.name; this.parentSlug = slug; this.subs = d.subs; this.modal = true;
        }
    }">
    <div class="flex items-end justify-between mb-6 reveal">
        <div>
            <p class="text-[11px] t-accent mb-1.5" style="letter-spacing:.16em; text-transform:uppercase;">Browse</p>
            <h2 class="text-xl md:text-2xl font-bold t-heading">Shop by Category</h2>
        </div>
        <a href="{{ route('products.index') }}" class="text-xs font-bold t-accent hover:underline whitespace-nowrap">
            All <i class="fas fa-arrow-right text-[10px]"></i>
        </a>
    </div>

    <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-8 gap-3" data-reveal-grid>
        @foreach($categories as $cat)
        @php $hasSubs = $cat->children->where('is_active', true)->count() > 0; @endphp
        <{{ $hasSubs ? 'button' : 'a' }}
            @if($hasSubs) type="button" @click="open('{{ $cat->slug }}')" @else href="{{ route('category.show', $cat->slug) }}" @endif
            class="dk-cat group">
            <span class="dk-cat-img">
                @if($cat->image)
                <img src="{{ $cat->image_url }}" alt="{{ $cat->name }}" loading="lazy"
                     class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110" style="border-radius:50%;">
                @else
                <i class="fas fa-box text-lg t-accent"></i>
                @endif
            </span>
            <span class="block text-[11px] font-semibold text-center leading-tight mt-2 line-clamp-2">{{ $cat->name }}</span>
            <span class="block text-[10px] t-muted text-center">{{ $cat->products_count }}</span>
        </{{ $hasSubs ? 'button' : 'a' }}>
        @endforeach
    </div>

    <template x-teleport="body">
        <div x-show="modal" x-cloak @keydown.escape.window="modal = false" class="fixed inset-0 z-[600] flex items-center justify-center p-4">
            <div class="absolute inset-0" @click="modal = false" style="background:rgba(0,0,0,.7); backdrop-filter:blur(3px);"></div>
            <div x-show="modal" x-transition class="relative dk-glass w-full max-w-md overflow-hidden z-10" style="background:#12121b;">
                <div class="flex items-center justify-between px-5 py-4" style="border-bottom:1px solid var(--t-border);">
                    <h3 class="font-bold t-heading" x-text="parentName"></h3>
                    <button @click="modal = false" class="t-muted"><i class="fas fa-times"></i></button>
                </div>
                <div class="p-5 space-y-2" style="max-height:70vh; overflow-y:auto;">
                    <a :href="'/category/' + parentSlug" class="flex items-center justify-between px-4 py-3"
                       style="background: rgb(var(--t-accent-rgb) / .12); border-radius: var(--t-radius-sm);">
                        <span class="text-sm font-bold t-accent" x-text="'All in ' + parentName"></span>
                        <i class="fas fa-arrow-right text-xs t-accent"></i>
                    </a>
                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="sub in subs" :key="sub.slug">
                            <a :href="'/category/' + sub.slug" class="flex items-center gap-2 p-3"
                               style="border:1px solid var(--t-border); border-radius: var(--t-radius-sm);">
                                <img :src="sub.image_url" class="w-9 h-9 object-cover shrink-0"
                                     style="border-radius: var(--t-radius-sm); background: var(--t-surface-2);"
                                     onerror="this.src='/images/category-placeholder.png'">
                                <span class="min-w-0">
                                    <span class="block text-xs font-semibold truncate" x-text="sub.name"></span>
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
<section class="t-container mt-14" x-data="{ slide: 0, total: {{ $totalSlides }} }">
    <div class="flex items-end justify-between mb-6 reveal">
        <div>
            <p class="text-[11px] mb-1.5" style="letter-spacing:.16em; text-transform:uppercase; color:#f87171;">Limited Time</p>
            <h2 class="text-xl md:text-2xl font-bold t-heading">
                <i class="fas fa-bolt mr-1.5" style="color:#f87171;"></i>Hot Deals
            </h2>
        </div>
        <div class="flex items-center gap-2">
            @if($totalSlides > 1)
            <button @click="slide = (slide - 1 + total) % total" aria-label="Previous"
                    class="w-9 h-9 rounded-full flex items-center justify-center t-muted"
                    style="border:1px solid var(--t-border);"><i class="fas fa-chevron-left text-xs"></i></button>
            <button @click="slide = (slide + 1) % total" aria-label="Next"
                    class="w-9 h-9 rounded-full flex items-center justify-center t-muted"
                    style="border:1px solid var(--t-border);"><i class="fas fa-chevron-right text-xs"></i></button>
            @endif
            <a href="{{ route('deals.index') }}" class="text-xs font-bold t-accent hover:underline ml-1 whitespace-nowrap">View All</a>
        </div>
    </div>

    <div class="relative overflow-hidden">
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
</section>
@endif

{{-- ══ Promo banners ═══════════════════════════════════════════════════ --}}
@if($promoBanners->count())
<section class="t-container mt-14">
    <div class="grid grid-cols-1 md:grid-cols-{{ $promoBanners->count() > 1 ? '2' : '1' }} gap-4" data-reveal-grid>
        @foreach($promoBanners->take(2) as $banner)
        <a href="{{ $banner->link_url ?: '#' }}" class="relative overflow-hidden group"
           style="border-radius: var(--t-radius); border:1px solid var(--t-border);">
            <img src="{{ $banner->image_url }}" loading="lazy" alt="{{ $banner->title }}"
                 class="w-full object-cover h-52 transition-transform duration-500 group-hover:scale-105" style="opacity:.9;">
            @if($banner->title)
            <div class="absolute inset-0 flex items-end p-6" style="background:linear-gradient(to top, rgba(8,8,13,.9), transparent);">
                <div>
                    <div class="text-xl font-bold t-heading" style="color: var(--t-text);">{{ $banner->title }}</div>
                    @if($banner->button_text)
                    <div class="mt-1.5 text-xs font-bold t-accent">{{ $banner->button_text }} →</div>
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
<section class="t-container mt-14">
    <div class="flex items-end justify-between mb-6 reveal">
        <div>
            <p class="text-[11px] t-accent mb-1.5" style="letter-spacing:.16em; text-transform:uppercase;">Curated</p>
            <h2 class="text-xl md:text-2xl font-bold t-heading">Featured Products</h2>
        </div>
        <a href="{{ route('products.index', ['featured' => 1]) }}" class="text-xs font-bold t-accent hover:underline whitespace-nowrap">
            View All <i class="fas fa-arrow-right text-[10px]"></i>
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
<section class="t-container mt-14 {{ $loop->last ? 'mb-12' : '' }}">
    <div class="flex items-end justify-between mb-6 reveal">
        <div>
            <p class="text-[11px] t-accent mb-1.5" style="letter-spacing:.16em; text-transform:uppercase;">New In</p>
            <h2 class="text-xl md:text-2xl font-bold t-heading">{{ $data['section']->name }}</h2>
        </div>
        <a href="{{ route('products.index', ['section' => $data['section']->slug, 'sort' => 'newest']) }}"
           class="text-xs font-bold t-accent hover:underline whitespace-nowrap">
            View All <i class="fas fa-arrow-right text-[10px]"></i>
        </a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 md:gap-4" data-reveal-grid>
        @foreach($data['products'] as $product)
            @include('ecom.partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>
@endforeach

{{-- ══ Assurances ══════════════════════════════════════════════════════ --}}
<section class="t-container mt-14 mb-4">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach([
            ['truck-fast', 'Fast Delivery', 'Same-day in Lahore'],
            ['shield-halved', 'Genuine Products', '100% authentic'],
            ['rotate-left', 'Easy Returns', '7-day policy'],
            ['headset', '24/7 Support', 'Always here to help'],
        ] as [$icon, $title, $sub])
        <div class="dk-glass p-4 flex items-center gap-3">
            <span class="w-10 h-10 rounded-full flex items-center justify-center shrink-0"
                  style="background: rgb(var(--t-accent-rgb) / .14);">
                <i class="fas fa-{{ $icon }} t-accent"></i>
            </span>
            <div class="min-w-0">
                <div class="text-sm font-bold truncate">{{ $title }}</div>
                <div class="text-xs t-muted truncate">{{ $sub }}</div>
            </div>
        </div>
        @endforeach
    </div>
</section>

@endsection

@push('styles')
<style>
    .dk-cat {
        display: flex; flex-direction: column; align-items: center;
        padding: .75rem .5rem;
        border-radius: var(--t-radius);
        border: 1px solid transparent;
        transition: background .2s ease, border-color .2s ease;
    }
    .dk-cat:hover { background: var(--t-surface); border-color: rgb(var(--t-accent-rgb) / .35); }
    .dk-cat-img {
        width: 48px; height: 48px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: rgb(var(--t-accent-rgb) / .12);
        overflow: hidden;
    }
</style>
@endpush
