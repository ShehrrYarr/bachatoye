<!DOCTYPE html>
<html lang="en" class="scroll-smooth" data-theme="bold">
<head>
    @include('theme.head')
    <style>
        .bd-nav {
            font-size: .875rem; font-weight: 700;
            color: var(--t-text);
            padding: .5rem .9rem;
            border-radius: 999px;
            white-space: nowrap;
            transition: background .18s ease, color .18s ease;
        }
        .bd-nav:hover { background: rgb(var(--t-accent-rgb) / .12); color: var(--t-accent); }
        .bd-nav[data-active] { background: var(--app-gradient); color:#fff; }

        .t-btn { border-radius: 999px; font-weight: 800; }
        .t-card { border-width: 2px; }

        /* Ticker */
        @keyframes bdTicker { from { transform: translateX(0); } to { transform: translateX(-50%); } }
        .bd-ticker { display:flex; width:max-content; animation: bdTicker 26s linear infinite; }
        @media (prefers-reduced-motion: reduce) { .bd-ticker { animation: none; } }

        @media (max-width: 767px) { body { padding-bottom: 4.25rem; } }
    </style>
</head>
<body class="antialiased" x-data="{ mobileMenuOpen: false, searchOpen: false }">

@php
    $bdShop  = \App\Models\Setting::get('shop_name', 'MobileHub');
    $bdLogo  = \App\Models\Setting::get('logo');
    $bdPhone = \App\Models\Setting::get('shop_phone', '03001234567');
    $bdAddr  = \App\Models\Setting::get('shop_address', 'Lahore, Pakistan');
    $bdFree  = \App\Models\Setting::get('free_delivery_above', 5000);
    $bdCart  = array_sum(array_column(session('cart', []), 'quantity'));
    $bdRoute = request()->route()?->getName();
    $bdTicks = [
        'FREE DELIVERY OVER RS. ' . number_format($bdFree),
        '100% GENUINE PRODUCTS',
        '7-DAY EASY RETURNS',
        'CASH ON DELIVERY AVAILABLE',
    ];
@endphp

{{-- Scrolling promo ticker --}}
<div class="overflow-hidden py-2 no-print" style="background: var(--app-gradient);">
    <div class="bd-ticker">
        @for($pass = 0; $pass < 2; $pass++)
            @foreach($bdTicks as $tick)
            <span class="text-white text-[11px] font-black px-8 whitespace-nowrap" style="letter-spacing:.1em;">
                <i class="fas fa-star text-[8px] mr-2" style="opacity:.7;"></i>{{ $tick }}
            </span>
            @endforeach
        @endfor
    </div>
</div>

<header class="sticky top-0 z-40 no-print"
        style="background: var(--t-surface); border-bottom:2px solid var(--t-border);">
    <div class="t-container">
        <div class="flex items-center gap-3 md:gap-5" style="height:4.25rem;">

            <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0">
                @if($bdLogo)
                    <img src="{{ asset('storage/'.$bdLogo) }}" class="h-10 w-auto" alt="{{ $bdShop }}">
                @else
                    <span class="w-10 h-10 flex items-center justify-center text-white"
                          style="background: var(--app-gradient); border-radius: 14px;">
                        <i class="fas fa-bolt text-lg"></i>
                    </span>
                    <span class="font-black text-xl hidden sm:block t-heading" style="letter-spacing:-.03em;">{{ $bdShop }}</span>
                @endif
            </a>

            <div class="flex-1 max-w-xl hidden md:block" x-data="liveSearch()">
                <div class="relative">
                    <input type="text" x-model="query" @input.debounce.300ms="search()"
                           @focus="open = true" @click.outside="open = false"
                           @keydown.enter="window.location = '/search?q=' + encodeURIComponent(query)"
                           placeholder="What are you hunting for today?"
                           class="w-full text-sm font-medium"
                           style="background: var(--t-surface-2); border:2px solid var(--t-border); border-radius:999px; padding:.7rem 1.25rem .7rem 3rem; color: var(--t-text);">
                    <i class="fas fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 t-accent"></i>

                    <div x-show="open && results.length > 0" x-transition x-cloak
                         class="absolute top-full left-0 right-0 mt-2 z-50 overflow-hidden t-card">
                        <template x-for="item in results" :key="item.slug">
                            <a :href="`/products/${item.slug}`" class="flex items-center gap-3 px-4 py-3" style="border-bottom:1px solid var(--t-border);">
                                <img :src="item.image" class="w-11 h-11 object-cover" style="border-radius:12px; background: var(--t-surface-2);">
                                <span class="flex-1 min-w-0">
                                    <span class="block text-sm font-bold truncate" x-text="item.name"></span>
                                    <span class="block text-xs t-price" x-text="`Rs. ${Number(item.price).toLocaleString()}`"></span>
                                </span>
                            </a>
                        </template>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-1.5 ml-auto">
                <button @click="searchOpen = !searchOpen" class="md:hidden p-2.5 t-muted">
                    <i class="fas fa-magnifying-glass"></i>
                </button>

                @auth('customer')
                <a href="{{ route('account.dashboard') }}" class="hidden sm:flex bd-nav">
                    <i class="fas fa-circle-user mr-1.5"></i>{{ explode(' ', Auth::guard('customer')->user()->customer->name)[0] }}
                </a>
                @else
                <a href="{{ route('account.login') }}" class="hidden sm:flex bd-nav">
                    <i class="fas fa-user mr-1.5"></i>Login
                </a>
                @endauth

                <a href="{{ route('cart.index') }}" class="relative flex items-center gap-2 px-4 py-2.5 text-white"
                   style="background: var(--app-gradient); border-radius:999px;">
                    <i class="fas fa-cart-shopping text-sm"></i>
                    <span class="hidden lg:inline text-sm font-black">Cart</span>
                    <span id="cart-count-badge"
                          class="absolute -top-1 -right-1 w-5 h-5 text-[10px] rounded-full flex items-center justify-center font-black"
                          style="background: var(--t-text); color: var(--t-surface); {{ $bdCart > 0 ? '' : 'display:none' }}">
                        {{ $bdCart > 9 ? '9+' : $bdCart }}
                    </span>
                </a>

                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2.5 t-muted">
                    <i class="fas fa-bars text-lg"></i>
                </button>
            </div>
        </div>

        <nav class="hidden md:flex items-center gap-1.5 pb-3">
            <a href="{{ route('home') }}" class="bd-nav" @if($bdRoute === 'home') data-active @endif>Home</a>
            <a href="{{ route('products.index') }}" class="bd-nav" @if(str_starts_with((string)$bdRoute, 'products.')) data-active @endif>Shop All</a>
            <a href="{{ route('deals.index') }}" class="bd-nav" @if(str_starts_with((string)$bdRoute, 'deals.')) data-active @endif>
                <i class="fas fa-fire mr-1"></i>Hot Deals
            </a>
            <a href="{{ route('order.track') }}" class="bd-nav" @if(str_starts_with((string)$bdRoute, 'order.track')) data-active @endif>Track Order</a>
            <span class="ml-auto text-xs font-bold t-muted whitespace-nowrap hidden lg:block">
                <i class="fas fa-headset mr-1.5 t-accent"></i>{{ $bdPhone }}
            </span>
        </nav>

        <div x-show="searchOpen" x-transition x-cloak class="pb-3 md:hidden">
            <form action="{{ route('products.search') }}" method="GET">
                <div class="relative">
                    <input type="text" name="q" placeholder="Search products…" class="w-full text-sm font-medium"
                           style="background: var(--t-surface-2); border:2px solid var(--t-border); border-radius:999px; padding:.7rem 1.25rem .7rem 3rem; color: var(--t-text);">
                    <i class="fas fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 t-accent"></i>
                </div>
            </form>
        </div>
    </div>

    <div x-show="mobileMenuOpen" x-transition x-cloak class="md:hidden" style="border-top:2px solid var(--t-border);">
        <nav class="px-4 py-3 space-y-1.5">
            <a href="{{ route('home') }}" class="block bd-nav">Home</a>
            <a href="{{ route('products.index') }}" class="block bd-nav">Shop All</a>
            <a href="{{ route('deals.index') }}" class="block bd-nav"><i class="fas fa-fire mr-1"></i>Hot Deals</a>
            <a href="{{ route('order.track') }}" class="block bd-nav">Track Order</a>
            @auth('customer')
            <a href="{{ route('account.dashboard') }}" class="block bd-nav">My Account</a>
            @else
            <a href="{{ route('account.login') }}" class="block bd-nav">Sign In / Register</a>
            @endauth
        </nav>
    </div>
</header>

@include('theme.flash')
@include('layouts.partials.theme-preview-bar')

<main id="page-main">
    @yield('content')
</main>

<footer class="mt-16 no-print" style="background: var(--t-text); color: rgba(255,255,255,.75);">
    <div class="t-container py-12 grid grid-cols-1 md:grid-cols-4 gap-10">
        <div class="md:col-span-2">
            <div class="flex items-center gap-2 mb-4">
                @if($bdLogo)
                    <img src="{{ asset('storage/'.$bdLogo) }}" class="h-10 w-auto" alt="{{ $bdShop }}">
                @else
                    <span class="w-10 h-10 flex items-center justify-center text-white" style="background: var(--app-gradient); border-radius:14px;">
                        <i class="fas fa-bolt"></i>
                    </span>
                @endif
                <span class="font-black text-xl text-white">{{ $bdShop }}</span>
            </div>
            <p class="text-sm leading-relaxed max-w-md">
                {{ \App\Models\Setting::get('shop_tagline', 'Your One-Stop Mobile Store') }}.
                Quality phones and accessories at the best prices.
            </p>
            <div class="flex items-center gap-3 mt-5">
                @foreach([['social_facebook','fab fa-facebook-f'],['social_instagram','fab fa-instagram'],['social_tiktok','fab fa-tiktok']] as [$key, $icon])
                    @if(\App\Models\Setting::get($key))
                    <a href="{{ \App\Models\Setting::get($key) }}" target="_blank" rel="noopener"
                       class="w-10 h-10 rounded-full flex items-center justify-center text-white transition-transform hover:scale-110"
                       style="background: var(--app-gradient);">
                        <i class="{{ $icon }}"></i>
                    </a>
                    @endif
                @endforeach
            </div>
        </div>

        <div>
            <h4 class="font-black text-white mb-4 text-sm uppercase tracking-wide">Shop</h4>
            <ul class="space-y-2.5 text-sm">
                <li><a href="{{ route('products.index') }}" class="hover:text-white transition-colors">All Products</a></li>
                <li><a href="{{ route('deals.index') }}" class="hover:text-white transition-colors">Hot Deals</a></li>
                <li><a href="{{ route('order.track') }}" class="hover:text-white transition-colors">Track Order</a></li>
                <li><a href="{{ route('cart.index') }}" class="hover:text-white transition-colors">My Cart</a></li>
            </ul>
        </div>

        <div>
            <h4 class="font-black text-white mb-4 text-sm uppercase tracking-wide">Contact</h4>
            <ul class="space-y-2.5 text-sm">
                <li class="flex items-start gap-2"><i class="fas fa-location-dot mt-1" style="color: var(--app-primary);"></i>{{ $bdAddr }}</li>
                <li class="flex items-start gap-2"><i class="fas fa-phone mt-1" style="color: var(--app-primary);"></i>{{ $bdPhone }}</li>
                <li class="flex items-start gap-2"><i class="fas fa-envelope mt-1" style="color: var(--app-primary);"></i>{{ \App\Models\Setting::get('shop_email', 'info@mobilehub.com') }}</li>
            </ul>
        </div>
    </div>

    <div class="text-center text-xs py-5" style="border-top:1px solid rgba(255,255,255,.12); color:rgba(255,255,255,.5);">
        &copy; {{ date('Y') }} {{ $bdShop }}. All rights reserved.
    </div>
</footer>

{{-- Mobile bottom bar --}}
<nav class="fixed bottom-0 inset-x-0 z-[450] flex md:hidden no-print px-2 py-2 gap-1"
     style="background: var(--t-surface); border-top:2px solid var(--t-border);">
    @foreach([
        ['home', 'house', 'Home', route('home')],
        ['products.', 'grip', 'Shop', route('products.index')],
        ['deals.', 'fire', 'Deals', route('deals.index')],
        ['cart.', 'cart-shopping', 'Cart', route('cart.index')],
    ] as [$prefix, $icon, $label, $url])
    @php $on = $bdRoute === $prefix || str_starts_with((string)$bdRoute, $prefix); @endphp
    <a href="{{ $url }}" class="flex-1 flex flex-col items-center gap-0.5 py-1.5 rounded-2xl text-[10px] font-black"
       style="{{ $on ? 'background: var(--app-gradient); color:#fff;' : 'color: var(--t-muted);' }}">
        <i class="fas fa-{{ $icon }}" style="font-size:1rem;"></i>{{ $label }}
    </a>
    @endforeach
    <a href="{{ auth('customer')->check() ? route('account.dashboard') : route('account.login') }}"
       class="flex-1 flex flex-col items-center gap-0.5 py-1.5 rounded-2xl text-[10px] font-black"
       style="{{ str_starts_with((string)$bdRoute, 'account.') ? 'background: var(--app-gradient); color:#fff;' : 'color: var(--t-muted);' }}">
        <i class="fas fa-circle-user" style="font-size:1rem;"></i>Account
    </a>
</nav>

@include('theme.scripts')
</body>
</html>
