@extends('layouts.ecom')
@section('title', \App\Models\Setting::get('shop_name', 'MobileHub'))

@section('content')

{{-- Hero Banner Slider --}}
@if($heroBanners->count())
<section class="relative overflow-hidden bg-gray-900" x-data="{ active: 0, total: {{ $heroBanners->count() }} }" x-init="setInterval(() => active = (active + 1) % total, 5000)">
    @foreach($heroBanners as $i => $banner)
    <div x-show="active === {{ $i }}" x-transition:enter="transition-opacity duration-700" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="relative w-full" style="min-height: 420px;">
        <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="w-full object-cover" style="height: 420px;">
        <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-black/30 to-transparent flex items-center">
            <div class="max-w-7xl mx-auto px-4 w-full">
                <div class="max-w-lg">
                    @if($banner->title)
                        <h1 class="text-3xl md:text-5xl font-extrabold text-white leading-tight mb-3">{{ $banner->title }}</h1>
                    @endif
                    @if($banner->subtitle)
                        <p class="text-gray-200 text-lg mb-6">{{ $banner->subtitle }}</p>
                    @endif
                    @if($banner->link_url)
                        <a href="{{ $banner->link_url }}" class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold px-6 py-3 rounded-xl transition-colors shadow-lg">
                            {{ $banner->button_text ?: 'Shop Now' }}
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach

    {{-- Dots --}}
    @if($heroBanners->count() > 1)
    <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-2">
        @foreach($heroBanners as $i => $banner)
        <button @click="active = {{ $i }}" :class="active === {{ $i }} ? 'bg-white w-6' : 'bg-white/50 w-2'"
                class="h-2 rounded-full transition-all duration-300"></button>
        @endforeach
    </div>
    @endif
</section>
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
    <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-hide">
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
<section class="max-w-7xl mx-auto px-4 mt-10">
    <div class="flex items-center justify-between mb-5">
        <h2 class="text-xl font-bold text-gray-900">Shop by Category</h2>
        <a href="{{ route('products.index') }}" class="text-sm text-primary-600 hover:underline font-medium">All Categories</a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 lg:gap-4">
        @foreach($categories as $cat)
        <a href="{{ route('category.show', $cat->slug) }}" class="group flex flex-col items-center gap-2 p-4 bg-white rounded-2xl border border-gray-200 hover:border-primary-300 hover:shadow-md transition-all">
            @if($cat->image)
                <img src="{{ $cat->image_url }}" class="w-14 h-14 object-cover rounded-xl bg-gray-100 group-hover:scale-110 transition-transform" alt="{{ $cat->name }}">
            @else
                <div class="w-14 h-14 rounded-xl bg-primary-50 flex items-center justify-center group-hover:bg-primary-100 transition-colors">
                    <i class="fas fa-box text-primary-600 text-xl"></i>
                </div>
            @endif
            <span class="text-xs font-medium text-gray-700 text-center leading-tight">{{ $cat->name }}</span>
            <span class="text-xs text-gray-400">{{ $cat->products_count }} items</span>
        </a>
        @endforeach
    </div>
</section>
@endif

{{-- Promo Banners --}}
@if($promoBanners->count())
<section class="max-w-7xl mx-auto px-4 mt-10">
    <div class="grid grid-cols-1 md:grid-cols-{{ $promoBanners->count() > 1 ? '2' : '1' }} gap-4">
        @foreach($promoBanners->take(2) as $banner)
        <a href="{{ $banner->link_url ?: '#' }}" class="relative overflow-hidden rounded-2xl group">
            <img src="{{ $banner->image_url }}" class="w-full object-cover h-48 group-hover:scale-105 transition-transform duration-500" alt="{{ $banner->title }}">
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
    <div class="flex items-center justify-between mb-5">
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
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Featured Products</h2>
            <p class="text-sm text-gray-500">Handpicked top picks</p>
        </div>
        <a href="{{ route('products.index', ['featured' => 1]) }}" class="text-sm text-primary-600 hover:underline font-medium">View All</a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 gap-4">
        @foreach($featuredProducts as $product)
            @include('ecom.partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>
@endif

{{-- New Arrivals --}}
@if($newArrivals->count())
<section class="max-w-7xl mx-auto px-4 mt-12 mb-8">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-xl font-bold text-gray-900">New Arrivals</h2>
            <p class="text-sm text-gray-500">Just landed in store</p>
        </div>
        <a href="{{ route('products.index', ['sort' => 'newest']) }}" class="text-sm text-primary-600 hover:underline font-medium">View All</a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
        @foreach($newArrivals as $product)
            @include('ecom.partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>
@endif

{{-- Trust badges --}}
<section class="bg-white border-t border-gray-100 mt-12">
    <div class="max-w-7xl mx-auto px-4 py-10">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
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
