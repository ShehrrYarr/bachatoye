<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', \App\Models\Setting::get('shop_name', 'MobileHub')) — @yield('meta-title', \App\Models\Setting::get('shop_tagline', 'Your One-Stop Mobile Store'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.color-vars')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @stack('styles')
    <style>
        [x-cloak] { display: none !important; }

        @keyframes pageIn {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        #page-main {
            animation: pageIn 0.38s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased" x-data="{ mobileMenuOpen: false, searchOpen: false }">

{{-- Top bar --}}
<div class="text-white text-xs py-2 hidden sm:block" style="background: var(--app-gradient, linear-gradient(135deg, #be123c 0%, #881337 100%))">
    <div class="max-w-7xl mx-auto px-4 flex items-center justify-between">
        <span><i class="fas fa-phone-alt mr-1"></i>{{ \App\Models\Setting::get('shop_phone', '03001234567') }}</span>
        <span>Free delivery on orders over Rs. {{ number_format(\App\Models\Setting::get('free_delivery_above', 5000)) }}</span>
        <span><i class="fas fa-map-marker-alt mr-1"></i>{{ \App\Models\Setting::get('shop_address', 'Lahore, Pakistan') }}</span>
    </div>
</div>

{{-- Main Navbar --}}
<header class="bg-white border-b border-gray-200 sticky top-0 z-40 shadow-sm">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center gap-4 h-16">

            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0">
                @if(\App\Models\Setting::get('logo'))
                    <img src="{{ asset('storage/'.\App\Models\Setting::get('logo')) }}" class="h-9 w-auto" alt="Logo">
                @else
                    <div class="w-9 h-9 bg-primary-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-mobile-alt text-white"></i>
                    </div>
                    <span class="font-bold text-gray-900 text-lg hidden sm:block">{{ \App\Models\Setting::get('shop_name', 'MobileHub') }}</span>
                @endif
            </a>

            {{-- Desktop Nav --}}
            <nav class="hidden md:flex items-center gap-1 flex-1">
                <a href="{{ route('home') }}" class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-primary-600 transition-colors">Home</a>
                <a href="{{ route('products.index') }}" class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-primary-600 transition-colors">Products</a>
                <a href="{{ route('deals.index') }}" class="px-3 py-2 text-sm font-medium text-red-600 hover:text-red-700 transition-colors font-semibold">
                    <i class="fas fa-fire mr-1"></i>Deals
                </a>
                <a href="{{ route('order.track') }}" class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-primary-600 transition-colors">Track Order</a>
            </nav>

            {{-- Search Bar --}}
            <div class="flex-1 max-w-md hidden md:block" x-data="liveSearch()">
                <div class="relative">
                    <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="open = true" @click.outside="open = false"
                           placeholder="Search phones, cases, chargers..."
                           class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-gray-50">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>

                    <div x-show="open && results.length > 0" x-transition
                         class="absolute top-full left-0 right-0 mt-1 bg-white rounded-xl shadow-xl border border-gray-200 z-50 overflow-hidden">
                        <template x-for="item in results" :key="item.slug">
                            <a :href="`/products/${item.slug}`" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors">
                                <img :src="item.image" class="w-10 h-10 object-cover rounded-lg bg-gray-100">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-800 truncate" x-text="item.name"></div>
                                    <div class="text-xs text-primary-600 font-semibold" x-text="`Rs. ${Number(item.price).toLocaleString()}`"></div>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Right icons --}}
            <div class="flex items-center gap-2 ml-auto md:ml-0">
                {{-- Mobile search --}}
                <button @click="searchOpen = !searchOpen" class="md:hidden p-2 text-gray-600 hover:text-primary-600 transition-colors">
                    <i class="fas fa-search"></i>
                </button>

                {{-- Account --}}
                @auth('customer')
                <a href="{{ route('account.dashboard') }}"
                   class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl text-sm font-medium text-gray-700 hover:text-primary-600 hover:bg-gray-100 transition-colors">
                    <i class="fas fa-user-circle text-base"></i>
                    <span class="hidden md:inline">{{ explode(' ', Auth::guard('customer')->user()->customer->name)[0] }}</span>
                </a>
                <a href="{{ route('account.dashboard') }}" class="sm:hidden p-2 text-gray-600 hover:text-primary-600 transition-colors" title="My Account">
                    <i class="fas fa-user-circle text-lg"></i>
                </a>
                @else
                <a href="{{ route('account.login') }}"
                   class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl border text-sm font-medium transition-colors border-gray-300 text-gray-700 hover:border-primary-500 hover:text-primary-600 hover:bg-primary-50">
                    <i class="fas fa-user text-sm"></i>
                    <span>Login</span>
                </a>
                <a href="{{ route('account.login') }}" class="sm:hidden p-2 text-gray-600 hover:text-primary-600 transition-colors" title="Sign In">
                    <i class="fas fa-user text-lg"></i>
                </a>
                @endauth

                {{-- Cart --}}
                @php $cartCount = array_sum(array_column(session('cart', []), 'quantity')); @endphp
                <a href="{{ route('cart.index') }}" class="relative p-2 text-gray-600 hover:text-primary-600 transition-colors">
                    <i class="fas fa-shopping-cart text-lg"></i>
                    <span id="cart-count-badge"
                          class="absolute -top-0.5 -right-0.5 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold"
                          style="{{ $cartCount > 0 ? '' : 'display:none' }}">
                        {{ $cartCount > 9 ? '9+' : $cartCount }}
                    </span>
                </a>

                {{-- Mobile menu --}}
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 text-gray-600">
                    <i class="fas fa-bars text-lg"></i>
                </button>
            </div>
        </div>

        {{-- Mobile search bar --}}
        <div x-show="searchOpen" x-transition class="pb-3 md:hidden">
            <form action="{{ route('products.search') }}" method="GET">
                <div class="relative">
                    <input type="text" name="q" placeholder="Search products..."
                           class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                </div>
            </form>
        </div>
    </div>

    {{-- Mobile nav --}}
    <div x-show="mobileMenuOpen" x-transition class="md:hidden border-t border-gray-100 bg-white">
        <nav class="px-4 py-3 space-y-1">
            <a href="{{ route('home') }}" class="block px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg">Home</a>
            <a href="{{ route('products.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg">All Products</a>
            <a href="{{ route('deals.index') }}" class="block px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg"><i class="fas fa-fire mr-1"></i>Deals</a>
            <a href="{{ route('order.track') }}" class="block px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg">Track Order</a>
            @auth('customer')
            <a href="{{ route('account.dashboard') }}" class="block px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg"><i class="fas fa-user-circle mr-2 text-primary-500"></i>My Account</a>
            @else
            <a href="{{ route('account.login') }}" class="block px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg"><i class="fas fa-user mr-2 text-gray-400"></i>Sign In / Register</a>
            @endauth
        </nav>
    </div>
</header>

@include('layouts.partials.theme-preview-bar')

{{-- Flash messages --}}
@if(session('success'))
<div class="fixed top-20 right-4 z-50 max-w-sm" x-data x-init="setTimeout(()=>$el.remove(),4000)">
    <div class="alert-success shadow-lg">
        <i class="fas fa-check-circle shrink-0"></i>
        <span>{{ session('success') }}</span>
    </div>
</div>
@endif
@if(session('error') || $errors->any())
<div class="fixed top-20 right-4 z-50 max-w-sm" x-data x-init="setTimeout(()=>$el.remove(),5000)">
    <div class="alert-error shadow-lg">
        <i class="fas fa-exclamation-circle shrink-0"></i>
        <span>{{ session('error') ?: $errors->first() }}</span>
    </div>
</div>
@endif

{{-- Page content --}}
<main id="page-main">
    @yield('content')
</main>

{{-- Footer --}}
<footer class="mt-16 text-gray-200" style="background: var(--app-gradient, linear-gradient(160deg, #881337 0%, #be123c 50%, #9f1239 100%))">
    <div class="max-w-7xl mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="md:col-span-2">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 bg-primary-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-mobile-alt text-white text-sm"></i>
                    </div>
                    <span class="font-bold text-white text-lg">{{ \App\Models\Setting::get('shop_name', 'MobileHub') }}</span>
                </div>
                <p class="text-sm text-red-100 leading-relaxed">{{ \App\Models\Setting::get('shop_tagline', 'Your One-Stop Mobile Store') }}.
                    Quality phones and accessories at the best prices.</p>
                <div class="flex items-center gap-4 mt-4">
                    <a href="tel:{{ \App\Models\Setting::get('shop_phone') }}" class="flex items-center gap-2 text-sm hover:text-white transition-colors">
                        <i class="fas fa-phone-alt text-red-200"></i>
                        {{ \App\Models\Setting::get('shop_phone', '03001234567') }}
                    </a>
                </div>
            </div>
            <div>
                <h4 class="font-semibold text-white mb-4">Quick Links</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('products.index') }}" class="hover:text-white transition-colors">All Products</a></li>
                    <li><a href="{{ route('deals.index') }}" class="hover:text-white transition-colors">Deals & Offers</a></li>
                    <li><a href="{{ route('order.track') }}" class="hover:text-white transition-colors">Track My Order</a></li>
                    <li><a href="{{ route('cart.index') }}" class="hover:text-white transition-colors">My Cart</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold text-white mb-4">Contact</h4>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-start gap-2"><i class="fas fa-map-marker-alt mt-0.5 text-red-200"></i>{{ \App\Models\Setting::get('shop_address', 'Lahore, Pakistan') }}</li>
                    <li class="flex items-start gap-2"><i class="fas fa-envelope mt-0.5 text-red-200"></i>{{ \App\Models\Setting::get('shop_email', 'info@mobilehub.com') }}</li>
                </ul>
            </div>
        </div>
        <div class="border-t border-red-800 mt-8 pt-6 text-center text-xs text-red-200">
            &copy; {{ date('Y') }} {{ \App\Models\Setting::get('shop_name', 'MobileHub') }}. All rights reserved.
        </div>
    </div>
</footer>

<script>
// Page transition: intercept link navigation and animate out
(function () {
    var leaving = false;
    document.addEventListener('click', function (e) {
        if (leaving) return;
        var a = e.target.closest('a[href]');
        if (!a) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        var href = a.getAttribute('href');
        if (!href || href.charAt(0) === '#' || href.indexOf('javascript') === 0) return;
        if (a.target === '_blank' || a.target === '_parent') return;
        try {
            var url = new URL(href, location.href);
            if (url.hostname !== location.hostname) return;
        } catch (ex) { return; }
        e.preventDefault();
        leaving = true;
        var main = document.getElementById('page-main');
        if (main) {
            main.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
            main.style.opacity = '0';
            main.style.transform = 'translateY(-10px)';
        }
        setTimeout(function () { location.href = href; }, 210);
    });
}());

function liveSearch() {
    return {
        query: '',
        results: [],
        open: false,
        async search() {
            if (this.query.length < 2) { this.results = []; return; }
            const res = await fetch(`/api/products/search?q=${encodeURIComponent(this.query)}`);
            this.results = await res.json();
            this.open = true;
        }
    }
}
</script>
{{-- ===== COLOR PICKER MODAL ===== --}}
<div x-data="colorPickerModal()"
     @open-color-picker.window="open($event.detail)"
     x-show="isOpen"
     x-cloak
     style="display:none"
     class="fixed inset-0 z-[999] flex items-center justify-center"
     style="padding: 1rem;">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/60" @click="close()"></div>

    {{-- Modal card --}}
    <div class="relative bg-white rounded-2xl shadow-2xl z-10 w-full"
         style="max-width: 400px; margin: auto;"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.stop>

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-gray-100">
            <div>
                <h3 class="font-bold text-gray-900 text-base" x-text="productName"></h3>
                <p class="text-xs text-gray-400 mt-0.5">Pick a color to continue</p>
            </div>
            <button @click="close()"
                    class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors ml-3 shrink-0">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>

        {{-- Color options --}}
        <div class="px-5 py-4 space-y-2" style="max-height: 300px; overflow-y: auto;">
            <template x-for="color in colors" :key="color.id">
                <button type="button"
                        @click="selectedColorId = color.id"
                        class="w-full flex items-center gap-3 px-4 py-3 border-2 rounded-xl transition-all text-left"
                        :class="selectedColorId === color.id
                            ? 'border-primary-500 bg-primary-50'
                            : 'border-gray-200 hover:border-gray-300 bg-white'">
                    <span class="w-7 h-7 rounded-full border border-gray-200 shadow-sm shrink-0"
                          :style="color.hex_code ? `background:${color.hex_code}` : 'background:#e5e7eb'"></span>
                    <span class="font-semibold text-sm text-gray-800" x-text="color.name"></span>
                    <span class="ml-auto text-xs text-gray-400 shrink-0" x-text="`${color.stock_quantity} in stock`"></span>
                    <i class="fas fa-check-circle text-primary-500 text-sm shrink-0"
                       :style="selectedColorId === color.id ? '' : 'visibility:hidden'"></i>
                </button>
            </template>
        </div>

        {{-- Footer --}}
        <div class="px-5 pt-2" style="padding-bottom: 1.75rem;">
            <p x-show="error" class="flex items-center gap-1.5 text-red-500 text-xs mb-3">
                <i class="fas fa-exclamation-circle"></i>
                <span x-text="error"></span>
            </p>
            <button @click="addToCart()"
                    :disabled="!selectedColorId || loading"
                    class="w-full flex items-center justify-center gap-2 py-3 rounded-xl font-bold text-sm text-white transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                    style="background: var(--app-gradient, linear-gradient(135deg, #be123c 0%, #881337 100%))">
                <i class="fas fa-spinner fa-spin" x-show="loading"></i>
                <i class="fas fa-cart-plus" x-show="!loading"></i>
                <span x-text="loading ? 'Adding...' : 'Add to Cart'"></span>
            </button>
        </div>
    </div>
</div>

{{-- ===== WHATSAPP CHAT WIDGET ===== --}}
@php
    $waNumber   = \App\Models\Setting::get('whatsapp_number');
    $waMessage  = \App\Models\Setting::get('whatsapp_message', 'Hi, I need help with an order.');
    $waGreeting = \App\Models\Setting::get('whatsapp_greeting', 'Hello! 👋 How can we help you today? Click below to chat with us on WhatsApp.');
    $waShop     = \App\Models\Setting::get('shop_name', 'MobileHub');
    $waLink     = 'https://wa.me/' . preg_replace('/\D/', '', $waNumber) . '?text=' . urlencode($waMessage);
@endphp
@if($waNumber)
<div x-data="waWidget()" x-init="init()" class="fixed z-50 no-print" style="bottom: 1.5rem; right: 1.5rem;">

    {{-- Chat popup --}}
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-3 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-3 scale-95"
         class="mb-4 w-72 rounded-2xl shadow-2xl overflow-hidden"
         style="transform-origin: bottom right;">

        {{-- Header --}}
        <div class="px-4 py-3 flex items-center gap-3" style="background: #075e54;">
            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0" style="background: rgba(255,255,255,0.2);">
                <i class="fab fa-whatsapp text-white text-2xl"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-white font-semibold text-sm truncate">{{ $waShop }}</p>
                <p class="text-xs" style="color: #9de1b5;">Typically replies instantly</p>
            </div>
            <button @click="close()" class="text-white hover:text-white/70 text-xl leading-none ml-1 focus:outline-none">&times;</button>
        </div>

        {{-- Chat bubble --}}
        <div class="p-4" style="background: #ece5dd;">
            <div class="relative bg-white rounded-lg rounded-tl-none px-3 py-2 shadow text-sm text-gray-700 max-w-[90%]">
                <div class="absolute -left-2 top-0 w-0 h-0" style="border-right: 8px solid white; border-bottom: 8px solid transparent;"></div>
                {{ $waGreeting }}
                <span class="text-[10px] text-gray-400 ml-2 float-right mt-1">now</span>
            </div>
        </div>

        {{-- Start Chat button --}}
        <div class="px-4 pb-4" style="background: #ece5dd;">
            <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer"
               class="flex items-center justify-center gap-2 w-full py-2.5 rounded-full text-white text-sm font-semibold shadow transition-opacity hover:opacity-90"
               style="background: #25D366;">
                <i class="fab fa-whatsapp text-lg"></i>
                Start Chat
            </a>
        </div>
    </div>

    {{-- Floating button --}}
    <button @click="toggle()"
            :class="{ 'wa-ring': !open }"
            class="w-14 h-14 rounded-full shadow-2xl flex items-center justify-center focus:outline-none active:scale-95 transition-transform"
            style="background: #25D366;"
            aria-label="Chat on WhatsApp">
        <i x-show="!open" class="fab fa-whatsapp text-white text-3xl"></i>
        <i x-show="open"  class="fas fa-times text-white text-2xl"></i>
    </button>
</div>

<style>
@keyframes wa-shake {
    0%, 50%, 100% { transform: rotate(0deg); }
    10%, 30%      { transform: rotate(-14deg); }
    20%, 40%      { transform: rotate(14deg); }
}
@keyframes wa-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(37,211,102,0.55); }
    60%      { box-shadow: 0 0 0 14px rgba(37,211,102,0); }
}
.wa-ring { animation: wa-shake 2.4s ease-in-out infinite, wa-pulse 2.4s ease-in-out infinite; }
</style>

<script>
function waWidget() {
    return {
        open: false,
        init() {
            if (!sessionStorage.getItem('wa_closed')) {
                setTimeout(() => { this.open = true; }, 5000);
            }
        },
        toggle() {
            this.open = !this.open;
            if (!this.open) sessionStorage.setItem('wa_closed', '1');
        },
        close() {
            this.open = false;
            sessionStorage.setItem('wa_closed', '1');
        }
    }
}
</script>
@endif

{{-- ===== CART TOAST ===== --}}
<div x-data="{ show: false, message: '' }"
     @cart-toast.window="message = $event.detail.message; show = true; setTimeout(() => show = false, 3500)"
     x-show="show"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-4"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-4"
     class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[1000] bg-gray-900 text-white text-sm font-semibold px-5 py-3 rounded-full shadow-2xl flex items-center gap-2 whitespace-nowrap pointer-events-none">
    <i class="fas fa-check-circle text-green-400"></i>
    <span x-text="message"></span>
</div>

<script>
function colorPickerModal() {
    return {
        isOpen:          false,
        productId:       null,
        productName:     '',
        colors:          [],
        selectedColorId: null,
        loading:         false,
        error:           '',

        open(detail) {
            this.productId       = detail.productId;
            this.productName     = detail.productName;
            this.colors          = detail.colors;
            this.selectedColorId = detail.colors.length === 1 ? detail.colors[0].id : null;
            this.loading         = false;
            this.error           = '';
            this.isOpen          = true;
            document.body.style.overflow = 'hidden';
        },

        close() {
            this.isOpen = false;
            document.body.style.overflow = '';
        },

        async addToCart() {
            if (!this.selectedColorId || this.loading) return;
            this.loading = true;
            this.error   = '';

            const form = new FormData();
            form.append('product_id', this.productId);
            form.append('color_id',   this.selectedColorId);
            form.append('quantity',   1);
            form.append('_token',     document.querySelector('meta[name="csrf-token"]').content);

            try {
                const res  = await fetch('{{ route("cart.add") }}', {
                    method:  'POST',
                    headers: { 'Accept': 'application/json' },
                    body:    form,
                });
                const data = await res.json();

                if (res.ok) {
                    // Update cart badge
                    const badge = document.getElementById('cart-count-badge');
                    if (badge) {
                        badge.textContent    = data.count > 9 ? '9+' : data.count;
                        badge.style.display  = data.count > 0 ? '' : 'none';
                    }
                    window.dispatchEvent(new CustomEvent('cart-toast', { detail: { message: this.productName + ' added to cart!' } }));
                    this.close();
                } else {
                    this.error = (data.errors && data.errors.color && data.errors.color[0])
                        || data.message
                        || 'Could not add to cart.';
                }
            } catch (e) {
                this.error = 'Something went wrong. Please try again.';
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>

@stack('scripts')

{{-- ===== ANNOUNCEMENT POPUP ===== --}}
@php
    $announcementEnabled = \App\Models\Setting::get('announcement_enabled', '0') == '1';
    $announcementTitle   = \App\Models\Setting::get('announcement_title', '');
    $announcementMessage = \App\Models\Setting::get('announcement_message', '');
    $logoPath            = \App\Models\Setting::get('logo');
    $shopName            = \App\Models\Setting::get('shop_name', 'MobileHub');
@endphp
@if($announcementEnabled && $announcementMessage)
<div x-data="announcementPopup()"
     x-init="init()"
     x-show="visible"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-[200] flex items-center justify-center p-4"
     style="background:rgba(0,0,0,0.55); backdrop-filter:blur(2px);"
     @keydown.escape.window="close()">

    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-auto relative"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100"
         @click.outside="close()">

        {{-- Close button --}}
        <button @click="close()"
                class="absolute top-3 right-3 w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors z-10">
            <i class="fas fa-times text-base"></i>
        </button>

        <div class="px-7 py-8 text-center">
            {{-- Logo --}}
            <div class="mb-4">
                @if($logoPath)
                    <img src="{{ asset('storage/'.$logoPath) }}" alt="{{ $shopName }}" class="h-12 mx-auto object-contain">
                @else
                    <div class="text-lg font-extrabold text-primary-700">{{ $shopName }}</div>
                @endif
            </div>

            {{-- Title --}}
            @if($announcementTitle)
            <h2 class="text-xl font-extrabold text-gray-900 mb-3">{{ $announcementTitle }}</h2>
            @endif

            {{-- Message --}}
            <p class="text-gray-600 text-sm leading-relaxed">{{ $announcementMessage }}</p>

            {{-- Dismiss button --}}
            <button @click="close()"
                    class="mt-6 btn-primary px-8 py-2.5 text-sm mx-auto">
                Got it!
            </button>
        </div>
    </div>
</div>

<script>
function announcementPopup() {
    return {
        visible: false,
        key: 'announcement_seen_{{ md5($announcementTitle . $announcementMessage) }}',

        init() {
            // Show only if not dismissed yet in this session
            if (!sessionStorage.getItem(this.key)) {
                // Small delay so the page loads first
                setTimeout(() => { this.visible = true; }, 600);
            }
        },

        close() {
            this.visible = false;
            sessionStorage.setItem(this.key, '1');
        },
    };
}
</script>
@endif
</body>
</html>
