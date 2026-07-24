<!DOCTYPE html>
<html lang="en" class="scroll-smooth" data-theme="dark">
<head>
    @include('theme.head')
    <style>
        .dk-nav {
            font-size: .875rem; font-weight: 500;
            color: var(--t-muted);
            padding: .5rem 0;
            position: relative;
            white-space: nowrap;
            transition: color .2s ease;
        }
        .dk-nav:hover, .dk-nav[data-active] { color: var(--t-text); }
        .dk-nav[data-active]::after {
            content:''; position:absolute; left:0; right:0; bottom:-1px; height:2px;
            background: var(--app-gradient);
            box-shadow: 0 0 12px var(--t-accent);
            border-radius: 2px;
        }

        /* Glass surfaces + accent glow */
        .dk-glass {
            background: linear-gradient(160deg, rgba(255,255,255,.05), rgba(255,255,255,.015));
            border: 1px solid var(--t-border);
            border-radius: var(--t-radius);
            backdrop-filter: blur(12px);
        }
        .dk-glow { box-shadow: 0 0 28px -6px rgb(var(--t-accent-rgb) / .55); }
        .t-card { background: linear-gradient(160deg, rgba(255,255,255,.045), rgba(255,255,255,.012)); }
        .t-card:hover { border-color: rgb(var(--t-accent-rgb) / .45); }
        .t-price { text-shadow: 0 0 18px rgb(var(--t-accent-rgb) / .5); }

        /* Ambient light behind the page */
        body::before {
            content: '';
            position: fixed; inset: 0;
            pointer-events: none; z-index: 0;
            background:
                radial-gradient(60rem 40rem at 15% -10%, rgb(var(--t-accent-rgb) / .13), transparent 65%),
                radial-gradient(50rem 34rem at 92% 8%, rgb(var(--t-accent-rgb) / .09), transparent 62%);
        }
        body > * { position: relative; z-index: 1; }
    </style>
</head>
<body class="antialiased" x-data="{ mobileMenuOpen: false, searchOpen: false }">

@php
    $dkShop  = \App\Models\Setting::get('shop_name', 'MobileHub');
    $dkLogo  = \App\Models\Setting::get('logo');
    $dkPhone = \App\Models\Setting::get('shop_phone', '03001234567');
    $dkAddr  = \App\Models\Setting::get('shop_address', 'Lahore, Pakistan');
    $dkFree  = \App\Models\Setting::get('free_delivery_above', 5000);
    $dkCart  = array_sum(array_column(session('cart', []), 'quantity'));
    $dkRoute = request()->route()?->getName();
@endphp

<div class="text-center text-[11px] py-2 no-print" style="border-bottom:1px solid var(--t-border); color: var(--t-muted);">
    <i class="fas fa-bolt mr-1.5 t-accent"></i>
    Free delivery over Rs. {{ number_format($dkFree) }} · 100% genuine · 7-day returns
</div>

<header class="sticky top-0 z-40 no-print"
        style="background: rgba(8,8,13,.82); backdrop-filter: blur(16px); border-bottom:1px solid var(--t-border);">
    <div class="t-container">
        <div class="flex items-center gap-4 md:gap-8" style="height:4.25rem;">

            <a href="{{ route('home') }}" class="flex items-center gap-2.5 shrink-0">
                @if($dkLogo)
                    <img src="{{ asset('storage/'.$dkLogo) }}" class="h-9 w-auto" alt="{{ $dkShop }}">
                @else
                    <span class="w-9 h-9 flex items-center justify-center dk-glow"
                          style="background: var(--app-gradient); border-radius: var(--t-radius-sm);">
                        <i class="fas fa-mobile-screen-button text-white"></i>
                    </span>
                    <span class="font-bold text-lg t-heading" style="letter-spacing:-.02em;">{{ $dkShop }}</span>
                @endif
            </a>

            <nav class="hidden md:flex items-center gap-7">
                <a href="{{ route('home') }}" class="dk-nav" @if($dkRoute === 'home') data-active @endif>Home</a>
                <a href="{{ route('products.index') }}" class="dk-nav" @if(str_starts_with((string)$dkRoute, 'products.')) data-active @endif>Shop</a>
                <a href="{{ route('deals.index') }}" class="dk-nav" @if(str_starts_with((string)$dkRoute, 'deals.')) data-active @endif>Deals</a>
                <a href="{{ route('order.track') }}" class="dk-nav" @if(str_starts_with((string)$dkRoute, 'order.track')) data-active @endif>Track</a>
            </nav>

            <div class="flex-1 max-w-sm hidden lg:block ml-auto" x-data="liveSearch()">
                <div class="relative">
                    <input type="text" x-model="query" @input.debounce.300ms="search()"
                           @focus="open = true" @click.outside="open = false"
                           @keydown.enter="window.location = '/search?q=' + encodeURIComponent(query)"
                           placeholder="Search…" class="w-full text-sm"
                           style="background: var(--t-surface); border:1px solid var(--t-border); border-radius:999px; padding:.55rem 1rem .55rem 2.5rem; color: var(--t-text);">
                    <i class="fas fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-xs t-muted"></i>

                    <div x-show="open && results.length > 0" x-transition x-cloak
                         class="absolute top-full left-0 right-0 mt-2 z-50 overflow-hidden dk-glass" style="background:#12121b;">
                        <template x-for="item in results" :key="item.slug">
                            <a :href="`/products/${item.slug}`" class="flex items-center gap-3 px-4 py-3" style="border-bottom:1px solid var(--t-border);">
                                <img :src="item.image" class="w-10 h-10 object-cover" style="border-radius: var(--t-radius-sm); background: var(--t-surface-2);">
                                <span class="flex-1 min-w-0">
                                    <span class="block text-sm font-medium truncate" x-text="item.name"></span>
                                    <span class="block text-xs t-price" x-text="`Rs. ${Number(item.price).toLocaleString()}`"></span>
                                </span>
                            </a>
                        </template>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-1 ml-auto lg:ml-0">
                <button @click="searchOpen = !searchOpen" class="lg:hidden p-2.5 t-muted"><i class="fas fa-magnifying-glass"></i></button>

                @auth('customer')
                <a href="{{ route('account.dashboard') }}" class="p-2.5 t-muted hover:t-accent transition-colors" title="My Account">
                    <i class="fas fa-circle-user text-lg"></i>
                </a>
                @else
                <a href="{{ route('account.login') }}" class="p-2.5 t-muted hover:t-accent transition-colors" title="Sign In">
                    <i class="fas fa-user text-lg"></i>
                </a>
                @endauth

                <a href="{{ route('cart.index') }}" class="relative flex items-center gap-2 px-3.5 py-2 ml-1 dk-glow"
                   style="background: var(--app-gradient); border-radius:999px;">
                    <i class="fas fa-cart-shopping text-white text-sm"></i>
                    <span class="hidden sm:inline text-white text-sm font-bold">Cart</span>
                    <span id="cart-count-badge" class="text-white text-xs font-bold"
                          style="{{ $dkCart > 0 ? '' : 'display:none' }}">{{ $dkCart > 9 ? '9+' : $dkCart }}</span>
                </a>

                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2.5 t-muted">
                    <i class="fas fa-bars text-lg"></i>
                </button>
            </div>
        </div>

        <div x-show="searchOpen" x-transition x-cloak class="pb-3 lg:hidden">
            <form action="{{ route('products.search') }}" method="GET">
                <div class="relative">
                    <input type="text" name="q" placeholder="Search products…" class="w-full text-sm"
                           style="background: var(--t-surface); border:1px solid var(--t-border); border-radius:999px; padding:.6rem 1rem .6rem 2.5rem; color: var(--t-text);">
                    <i class="fas fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-xs t-muted"></i>
                </div>
            </form>
        </div>
    </div>

    <div x-show="mobileMenuOpen" x-transition x-cloak class="md:hidden" style="border-top:1px solid var(--t-border); background: var(--t-surface);">
        <nav class="px-4 py-3 space-y-1">
            <a href="{{ route('home') }}" class="block px-3 py-2.5 text-sm font-semibold" style="border-radius: var(--t-radius-sm);">Home</a>
            <a href="{{ route('products.index') }}" class="block px-3 py-2.5 text-sm font-semibold" style="border-radius: var(--t-radius-sm);">Shop</a>
            <a href="{{ route('deals.index') }}" class="block px-3 py-2.5 text-sm font-semibold t-accent" style="border-radius: var(--t-radius-sm);">Deals</a>
            <a href="{{ route('order.track') }}" class="block px-3 py-2.5 text-sm font-semibold" style="border-radius: var(--t-radius-sm);">Track Order</a>
            @auth('customer')
            <a href="{{ route('account.dashboard') }}" class="block px-3 py-2.5 text-sm font-semibold" style="border-radius: var(--t-radius-sm);">My Account</a>
            @else
            <a href="{{ route('account.login') }}" class="block px-3 py-2.5 text-sm font-semibold" style="border-radius: var(--t-radius-sm);">Sign In / Register</a>
            @endauth
        </nav>
    </div>
</header>

@include('theme.flash')
@include('layouts.partials.theme-preview-bar')

<main id="page-main">
    @yield('content')
</main>

<footer class="mt-20 no-print" style="border-top:1px solid var(--t-border);">
    <div class="t-container py-12 grid grid-cols-1 md:grid-cols-4 gap-10">
        <div class="md:col-span-2">
            <div class="flex items-center gap-2.5 mb-4">
                @if($dkLogo)
                    <img src="{{ asset('storage/'.$dkLogo) }}" class="h-9 w-auto" alt="{{ $dkShop }}">
                @else
                    <span class="w-9 h-9 flex items-center justify-center dk-glow"
                          style="background: var(--app-gradient); border-radius: var(--t-radius-sm);">
                        <i class="fas fa-mobile-screen-button text-white"></i>
                    </span>
                @endif
                <span class="font-bold text-lg t-heading">{{ $dkShop }}</span>
            </div>
            <p class="text-sm t-muted leading-relaxed max-w-md">
                {{ \App\Models\Setting::get('shop_tagline', 'Your One-Stop Mobile Store') }}.
                Quality phones and accessories at the best prices.
            </p>
            <div class="flex items-center gap-3 mt-5">
                @foreach([['social_facebook','fab fa-facebook-f'],['social_instagram','fab fa-instagram'],['social_tiktok','fab fa-tiktok']] as [$key, $icon])
                    @if(\App\Models\Setting::get($key))
                    <a href="{{ \App\Models\Setting::get($key) }}" target="_blank" rel="noopener"
                       class="w-9 h-9 flex items-center justify-center transition-colors t-muted hover:t-accent"
                       style="background: var(--t-surface); border:1px solid var(--t-border); border-radius: var(--t-radius-sm);">
                        <i class="{{ $icon }}"></i>
                    </a>
                    @endif
                @endforeach
            </div>
        </div>

        <div>
            <h4 class="font-bold mb-4 text-sm t-heading">Shop</h4>
            <ul class="space-y-2.5 text-sm t-muted">
                <li><a href="{{ route('products.index') }}" class="hover:t-accent transition-colors">All Products</a></li>
                <li><a href="{{ route('deals.index') }}" class="hover:t-accent transition-colors">Deals</a></li>
                <li><a href="{{ route('order.track') }}" class="hover:t-accent transition-colors">Track Order</a></li>
                <li><a href="{{ route('cart.index') }}" class="hover:t-accent transition-colors">Cart</a></li>
            </ul>
        </div>

        <div>
            <h4 class="font-bold mb-4 text-sm t-heading">Contact</h4>
            <ul class="space-y-2.5 text-sm t-muted">
                <li class="flex items-start gap-2"><i class="fas fa-location-dot mt-1 t-accent"></i>{{ $dkAddr }}</li>
                <li class="flex items-start gap-2"><i class="fas fa-phone mt-1 t-accent"></i>{{ $dkPhone }}</li>
                <li class="flex items-start gap-2"><i class="fas fa-envelope mt-1 t-accent"></i>{{ \App\Models\Setting::get('shop_email', 'info@mobilehub.com') }}</li>
            </ul>
        </div>
    </div>

    <div class="text-center text-xs t-muted py-5" style="border-top:1px solid var(--t-border);">
        &copy; {{ date('Y') }} {{ $dkShop }}. All rights reserved.
    </div>
</footer>

@include('theme.scripts')
</body>
</html>
