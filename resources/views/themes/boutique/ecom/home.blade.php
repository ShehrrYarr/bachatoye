@extends('layouts.ecom')
@section('title', \App\Models\Setting::get('shop_name', 'MobileHub'))

@section('content')

{{-- ══ Hero — banner proportions match every other view ═════════════════ --}}
@if($heroBanners->count())
@php $sliderInterval = max(2, (int) \App\Models\Setting::get('banner_slider_interval', 5)); @endphp
<section class="relative select-none" style="background: var(--t-surface-2);"
         x-data="heroBanner({{ $heroBanners->count() }}, {{ $sliderInterval * 1000 }})"
         x-init="init()" @mouseenter="pause()" @mouseleave="resume()">

    <div class="overflow-hidden">
        <div class="flex" :style="'transform:translateX(-' + (active * 100) + '%); transition:transform 900ms cubic-bezier(.16,1,.3,1)'">
            @foreach($heroBanners as $banner)
            <div class="relative shrink-0 w-full min-w-full md:h-[480px]">
                <img src="{{ $banner->image_url }}" alt="{{ $banner->title ?? '' }}"
                     class="block w-full h-auto md:h-full md:object-cover">
                <div class="absolute inset-0 flex items-center justify-center text-center"
                     style="background:linear-gradient(to bottom, rgba(0,0,0,.18), rgba(0,0,0,.34));">
                    <div class="px-6 max-w-2xl">
                        @if($banner->title)
                        <h1 class="text-3xl md:text-5xl text-white leading-tight mb-4 t-heading" style="font-weight:500; letter-spacing:-.01em;">
                            {{ $banner->title }}
                        </h1>
                        @endif
                        @if($banner->subtitle)
                        <p class="text-base md:text-lg mb-8 mx-auto max-w-lg" style="color:rgba(255,255,255,.92);">{{ $banner->subtitle }}</p>
                        @endif
                        @if($banner->link_url)
                        <a href="{{ $banner->link_url }}" class="t-btn px-9 py-3.5"
                           style="background: var(--t-surface); color: var(--t-text);">
                            {{ $banner->button_text ?: 'Shop the Collection' }}
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    @if($heroBanners->count() > 1)
    <div class="absolute bottom-6 left-0 right-0 flex items-center justify-center gap-3 z-10">
        @foreach($heroBanners as $i => $banner)
        <button @click="goTo({{ $i }})" aria-label="Slide {{ $i + 1 }}" class="transition-all duration-300"
                :style="active === {{ $i }} ? 'width:36px;height:2px;background:#fff;' : 'width:14px;height:2px;background:rgba(255,255,255,.5);'"></button>
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
<section class="text-center py-24 px-6" style="background: var(--t-surface-2);">
    <h1 class="text-4xl md:text-6xl mb-5 t-heading" style="font-weight:500;">{{ \App\Models\Setting::get('shop_name', 'MobileHub') }}</h1>
    <p class="t-muted text-lg mb-9 max-w-lg mx-auto">{{ \App\Models\Setting::get('shop_tagline', 'Your One-Stop Mobile Store') }}</p>
    <a href="{{ route('products.index') }}" class="t-btn t-btn-primary px-9 py-3.5">Shop the Collection</a>
</section>
@endif

{{-- ══ Categories — quiet editorial grid ═══════════════════════════════ --}}
@if($categories->count())
<section class="t-container py-16 md:py-20" x-data="{
        modal: false, parentName: '', parentSlug: '', subs: [],
        open(slug) {
            const d = window._catData && window._catData[slug];
            if (!d || !d.subs.length) { window.location.href = '/category/' + slug; return; }
            this.parentName = d.name; this.parentSlug = slug; this.subs = d.subs; this.modal = true;
        }
    }">
    <div class="text-center mb-12 reveal">
        <p class="text-[11px] t-muted mb-3" style="letter-spacing:.2em; text-transform:uppercase;">Browse</p>
        <h2 class="text-2xl md:text-3xl t-heading" style="font-weight:500;">Shop by Category</h2>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-10" data-reveal-grid>
        @foreach($categories as $cat)
        @php $hasSubs = $cat->children->where('is_active', true)->count() > 0; @endphp
        <{{ $hasSubs ? 'button' : 'a' }}
            @if($hasSubs) type="button" @click="open('{{ $cat->slug }}')" @else href="{{ route('category.show', $cat->slug) }}" @endif
            class="group text-center">
            <span class="block overflow-hidden mb-4" style="background: var(--t-surface-2); aspect-ratio: 1;">
                @if($cat->image)
                <img src="{{ $cat->image_url }}" alt="{{ $cat->name }}" loading="lazy"
                     class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                @else
                <span class="w-full h-full flex items-center justify-center"><i class="fas fa-box text-3xl t-muted"></i></span>
                @endif
            </span>
            <span class="block text-sm t-heading">{{ $cat->name }}</span>
            <span class="block text-[11px] t-muted mt-1" style="letter-spacing:.1em;">{{ $cat->products_count }} items</span>
        </{{ $hasSubs ? 'button' : 'a' }}>
        @endforeach
    </div>

    <template x-teleport="body">
        <div x-show="modal" x-cloak @keydown.escape.window="modal = false" class="fixed inset-0 z-[600] flex items-center justify-center p-4">
            <div class="absolute inset-0" @click="modal = false" style="background:rgba(28,25,23,.5)"></div>
            <div x-show="modal" x-transition class="relative t-card w-full max-w-md overflow-hidden z-10">
                <div class="flex items-center justify-between px-6 py-5" style="border-bottom:1px solid var(--t-border);">
                    <h3 class="t-heading" x-text="parentName"></h3>
                    <button @click="modal = false" class="t-muted"><i class="fas fa-times"></i></button>
                </div>
                <div class="p-6 space-y-2" style="max-height:70vh; overflow-y:auto;">
                    <a :href="'/category/' + parentSlug" class="flex items-center justify-between py-3 text-sm t-heading"
                       style="border-bottom:1px solid var(--t-border);">
                        <span x-text="'All ' + parentName"></span>
                        <i class="fas fa-arrow-right text-xs t-muted"></i>
                    </a>
                    <template x-for="sub in subs" :key="sub.slug">
                        <a :href="'/category/' + sub.slug" class="flex items-center justify-between py-3 text-sm"
                           style="border-bottom:1px solid var(--t-border);">
                            <span x-text="sub.name"></span>
                            <span class="text-xs t-muted" x-text="sub.count"></span>
                        </a>
                    </template>
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

{{-- ══ Featured ════════════════════════════════════════════════════════ --}}
@if($featuredProducts->count())
<section class="t-container pb-16 md:pb-20">
    <div class="text-center mb-12 reveal">
        <p class="text-[11px] t-muted mb-3" style="letter-spacing:.2em; text-transform:uppercase;">Curated</p>
        <h2 class="text-2xl md:text-3xl t-heading" style="font-weight:500;">Featured Pieces</h2>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-12" data-reveal-grid>
        @foreach($featuredProducts->take(8) as $product)
            @include('ecom.partials.product-card', ['product' => $product])
        @endforeach
    </div>
    <div class="text-center mt-12">
        <a href="{{ route('products.index', ['featured' => 1]) }}" class="t-btn t-btn-outline px-8">View All</a>
    </div>
</section>
@endif

{{-- ══ Promo banners — full-bleed editorial ════════════════════════════ --}}
@if($promoBanners->count())
<section class="t-container pb-16 md:pb-20">
    <div class="grid grid-cols-1 md:grid-cols-{{ $promoBanners->count() > 1 ? '2' : '1' }} gap-6" data-reveal-grid>
        @foreach($promoBanners->take(2) as $banner)
        <a href="{{ $banner->link_url ?: '#' }}" class="relative overflow-hidden group block">
            <img src="{{ $banner->image_url }}" loading="lazy" alt="{{ $banner->title }}"
                 class="w-full object-cover h-64 transition-transform duration-700 group-hover:scale-105">
            @if($banner->title)
            <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-6"
                 style="background:rgba(28,25,23,.32);">
                <div class="text-white text-xl md:text-2xl t-heading" style="font-weight:500;">{{ $banner->title }}</div>
                @if($banner->button_text)
                <div class="mt-3 text-[11px] text-white" style="letter-spacing:.16em; text-transform:uppercase; border-bottom:1px solid rgba(255,255,255,.6); padding-bottom:2px;">
                    {{ $banner->button_text }}
                </div>
                @endif
            </div>
            @endif
        </a>
        @endforeach
    </div>
</section>
@endif

{{-- ══ Offers ══════════════════════════════════════════════════════════ --}}
@if($dealProductChunks->count())
<section class="py-16 md:py-20" style="background: var(--t-surface-2);">
    <div class="t-container">
        <div class="text-center mb-12 reveal">
            <p class="text-[11px] t-muted mb-3" style="letter-spacing:.2em; text-transform:uppercase;">Limited Time</p>
            <h2 class="text-2xl md:text-3xl t-heading" style="font-weight:500;">Current Offers</h2>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-12" data-reveal-grid>
            @foreach($dealProductChunks->flatten()->take(8) as $product)
                @include('ecom.partials.product-card', ['product' => $product])
            @endforeach
        </div>
        <div class="text-center mt-12">
            <a href="{{ route('deals.index') }}" class="t-btn t-btn-outline px-8">All Offers</a>
        </div>
    </div>
</section>
@endif

{{-- ══ New arrivals per section ════════════════════════════════════════ --}}
@foreach($sectionNewArrivals as $data)
<section class="t-container py-16 md:py-20">
    <div class="text-center mb-12 reveal">
        <p class="text-[11px] t-muted mb-3" style="letter-spacing:.2em; text-transform:uppercase;">New In</p>
        <h2 class="text-2xl md:text-3xl t-heading" style="font-weight:500;">{{ $data['section']->name }}</h2>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-12" data-reveal-grid>
        @foreach($data['products']->take(8) as $product)
            @include('ecom.partials.product-card', ['product' => $product])
        @endforeach
    </div>
    <div class="text-center mt-12">
        <a href="{{ route('products.index', ['section' => $data['section']->slug, 'sort' => 'newest']) }}"
           class="t-btn t-btn-outline px-8">View All</a>
    </div>
</section>
@endforeach

{{-- ══ Assurances ══════════════════════════════════════════════════════ --}}
<section class="t-container pb-20">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center py-12" style="border-top:1px solid var(--t-border);">
        @foreach([
            ['truck', 'Fast Delivery', 'Same-day in Lahore'],
            ['shield-halved', 'Genuine Products', '100% authentic'],
            ['rotate-left', 'Easy Returns', '7-day policy'],
            ['headset', 'Support', 'Always here to help'],
        ] as [$icon, $title, $sub])
        <div>
            <i class="fas fa-{{ $icon }} text-lg mb-3 t-muted"></i>
            <div class="text-sm t-heading">{{ $title }}</div>
            <div class="text-xs t-muted mt-1">{{ $sub }}</div>
        </div>
        @endforeach
    </div>
</section>

@endsection
