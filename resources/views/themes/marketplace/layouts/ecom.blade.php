<!DOCTYPE html>
<html lang="en" class="scroll-smooth" data-theme="marketplace">
<head>
    @include('theme.head')
    <style>
        .mk-navlink {
            position: relative;
            padding: .875rem .25rem;
            font-size: .875rem;
            font-weight: 600;
            color: var(--t-text);
            white-space: nowrap;
            transition: color .18s ease;
        }
        .mk-navlink::after {
            content: '';
            position: absolute; left: 0; right: 0; bottom: 0;
            height: 3px; border-radius: 3px 3px 0 0;
            background: var(--app-gradient);
            transform: scaleX(0);
            transition: transform .22s ease;
        }
        .mk-navlink:hover { color: var(--t-accent); }
        .mk-navlink:hover::after, .mk-navlink[data-active]::after { transform: scaleX(1); }
        .mk-navlink[data-active] { color: var(--t-accent); }

        /* Mobile bottom tab bar */
        .mk-tab {
            flex: 1;
            display: flex; flex-direction: column; align-items: center; gap: 2px;
            padding: .5rem 0;
            font-size: .625rem; font-weight: 600;
            color: var(--t-muted);
        }
        .mk-tab i { font-size: 1.05rem; }
        .mk-tab[data-active] { color: var(--t-accent); }

        @media (max-width: 767px) { body { padding-bottom: 4rem; } }
    </style>
</head>
<body class="antialiased" x-data="{ mobileMenuOpen: false, searchOpen: false }">

@php
    $mkShop  = \App\Models\Setting::get('shop_name', 'MobileHub');
    $mkLogo  = \App\Models\Setting::get('logo');
    $mkPhone = \App\Models\Setting::get('shop_phone', '03001234567');
    $mkAddr  = \App\Models\Setting::get('shop_address', 'Lahore, Pakistan');
    $mkFree  = \App\Models\Setting::get('free_delivery_above', 5000);
    $mkCart  = array_sum(array_column(session('cart', []), 'quantity'));
    $mkRoute = request()->route()?->getName();
@endphp

{{-- ── Utility bar ─────────────────────────────────────────────────────── --}}
<div class="text-white text-xs py-2 hidden sm:block no-print" style="background: var(--app-gradient);">
    <div class="t-container flex items-center justify-between gap-4">
        <a href="tel:{{ $mkPhone }}" class="inline-flex items-center gap-1.5 hover:opacity-80 transition-opacity">
            <i class="fas fa-headset"></i>{{ $mkPhone }}
        </a>
        <span class="inline-flex items-center gap-1.5 font-semibold">
            <i class="fas fa-truck-fast"></i>
            Free delivery on orders over Rs. {{ number_format($mkFree) }}
        </span>
        <span class="inline-flex items-center gap-1.5" style="opacity:.9;">
            <i class="fas fa-location-dot"></i>{{ $mkAddr }}
        </span>
    </div>
</div>

{{-- ── Main header ─────────────────────────────────────────────────────── --}}
<header class="sticky top-0 z-40 no-print"
        style="background: var(--t-surface); border-bottom: 1px solid var(--t-border); box-shadow: var(--t-shadow);">
    <div class="t-container">
        <div class="flex items-center gap-3 md:gap-5" style="height:4rem;">

            <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0">
                @if($mkLogo)
                    <img src="{{ asset('storage/'.$mkLogo) }}" class="h-9 w-auto" alt="{{ $mkShop }}">
                @else
                    <span class="w-10 h-10 flex items-center justify-center text-white"
                          style="background: var(--app-gradient); border-radius: var(--t-radius-sm);">
                        <i class="fas fa-mobile-screen-button text-lg"></i>
                    </span>
                    <span class="font-extrabold text-lg hidden sm:block t-heading" style="letter-spacing:-.02em;">{{ $mkShop }}</span>
                @endif
            </a>

            {{-- Search — the centrepiece of a marketplace header --}}
            <div class="flex-1 max-w-2xl hidden md:block" x-data="liveSearch()">
                <div class="relative">
                    <input type="text" x-model="query" @input.debounce.300ms="search()"
                           @focus="open = true" @click.outside="open = false"
                           @keydown.enter="window.location = '/search?q=' + encodeURIComponent(query)"
                           placeholder="Search phones, cases, chargers…"
                           class="w-full text-sm"
                           style="background: var(--t-surface-2); border:2px solid var(--t-border); border-radius:999px; padding:.65rem 6.5rem .65rem 2.75rem; color: var(--t-text);">
                    <i class="fas fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-sm t-muted"></i>
                    <button type="button" @click="window.location = '/search?q=' + encodeURIComponent(query)"
                            class="absolute right-1.5 top-1/2 -translate-y-1/2 text-white text-xs font-bold px-5 py-2"
                            style="background: var(--app-gradient); border-radius:999px;">
                        Search
                    </button>

                    <div x-show="open && results.length > 0" x-transition x-cloak
                         class="absolute top-full left-0 right-0 mt-2 z-50 overflow-hidden t-card">
                        <template x-for="item in results" :key="item.slug">
                            <a :href="`/products/${item.slug}`" class="flex items-center gap-3 px-4 py-3 transition-colors"
                               style="border-bottom:1px solid var(--t-border);">
                                <img :src="item.image" class="w-11 h-11 object-cover" style="border-radius: var(--t-radius-sm); background: var(--t-surface-2);">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-semibold truncate" x-text="item.name"></div>
                                    <div class="text-xs t-price" x-text="`Rs. ${Number(item.price).toLocaleString()}`"></div>
                                </div>
                                <i class="fas fa-arrow-right text-xs t-muted"></i>
                            </a>
                        </template>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-1 ml-auto">
                <button @click="searchOpen = !searchOpen" class="md:hidden p-2.5 t-muted">
                    <i class="fas fa-magnifying-glass"></i>
                </button>

                <a href="{{ route('order.track') }}"
                   class="hidden lg:flex items-center gap-2 px-3 py-2 text-sm font-semibold transition-colors t-muted hover:t-accent">
                    <i class="fas fa-box"></i> Track
                </a>

                @auth('customer')
                <a href="{{ route('account.dashboard') }}"
                   class="hidden sm:flex items-center gap-2 px-3 py-2 text-sm font-semibold transition-colors t-muted hover:t-accent">
                    <i class="fas fa-circle-user text-base"></i>
                    <span class="hidden lg:inline">{{ explode(' ', Auth::guard('customer')->user()->customer->name)[0] }}</span>
                </a>
                @else
                <a href="{{ route('account.login') }}"
                   class="hidden sm:flex items-center gap-2 px-3 py-2 text-sm font-semibold transition-colors t-muted hover:t-accent">
                    <i class="fas fa-user"></i> <span class="hidden lg:inline">Login</span>
                </a>
                @endauth

                <a href="{{ route('cart.index') }}" class="relative flex items-center gap-2 px-3 py-2 ml-1"
                   style="background: rgb(var(--t-accent-rgb) / .10); border-radius: var(--t-radius-sm);">
                    <i class="fas fa-cart-shopping" style="color: var(--t-accent);"></i>
                    <span class="hidden lg:inline text-sm font-bold" style="color: var(--t-accent);">Cart</span>
                    <span id="cart-count-badge"
                          class="absolute -top-1.5 -right-1.5 w-5 h-5 text-white text-[10px] rounded-full flex items-center justify-center font-bold"
                          style="background:#ef4444; {{ $mkCart > 0 ? '' : 'display:none' }}">
                        {{ $mkCart > 9 ? '9+' : $mkCart }}
                    </span>
                </a>

                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2.5 t-muted">
                    <i class="fas fa-bars text-lg"></i>
                </button>
            </div>
        </div>

        {{-- Mobile search --}}
        <div x-show="searchOpen" x-transition x-cloak class="pb-3 md:hidden">
            <form action="{{ route('products.search') }}" method="GET">
                <div class="relative">
                    <input type="text" name="q" placeholder="Search products…"
                           class="w-full text-sm"
                           style="background: var(--t-surface-2); border:2px solid var(--t-border); border-radius:999px; padding:.65rem 1rem .65rem 2.75rem; color: var(--t-text);">
                    <i class="fas fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-sm t-muted"></i>
                </div>
            </form>
        </div>
    </div>

    {{-- Secondary nav strip --}}
    <div class="hidden md:block" style="border-top:1px solid var(--t-border); background: var(--t-surface);">
        <div class="t-container flex items-center gap-6 overflow-x-auto scrollbar-hide">
            <a href="{{ route('home') }}" class="mk-navlink" @if($mkRoute === 'home') data-active @endif>
                <i class="fas fa-house mr-1.5"></i>Home
            </a>
            <a href="{{ route('products.index') }}" class="mk-navlink" @if(str_starts_with((string)$mkRoute, 'products.')) data-active @endif>
                All Products
            </a>
            <a href="{{ route('deals.index') }}" class="mk-navlink" @if(str_starts_with((string)$mkRoute, 'deals.')) data-active @endif>
                <i class="fas fa-bolt mr-1.5" style="color:#f59e0b;"></i>Deals
            </a>
            <a href="{{ route('order.track') }}" class="mk-navlink" @if(str_starts_with((string)$mkRoute, 'order.track')) data-active @endif>
                Track Order
            </a>
            <span class="ml-auto text-xs t-muted whitespace-nowrap py-3 hidden lg:block">
                <i class="fas fa-shield-halved mr-1.5" style="color:#16a34a;"></i>100% genuine · 7-day returns
            </span>
        </div>
    </div>

    {{-- Mobile drawer --}}
    <div x-show="mobileMenuOpen" x-transition x-cloak class="md:hidden"
         style="border-top:1px solid var(--t-border); background: var(--t-surface);">
        <nav class="px-4 py-3 space-y-1">
            <a href="{{ route('home') }}" class="block px-3 py-2.5 text-sm font-semibold" style="border-radius: var(--t-radius-sm);">Home</a>
            <a href="{{ route('products.index') }}" class="block px-3 py-2.5 text-sm font-semibold" style="border-radius: var(--t-radius-sm);">All Products</a>
            <a href="{{ route('deals.index') }}" class="block px-3 py-2.5 text-sm font-semibold" style="border-radius: var(--t-radius-sm); color:#f59e0b;">
                <i class="fas fa-bolt mr-1.5"></i>Deals
            </a>
            <a href="{{ route('order.track') }}" class="block px-3 py-2.5 text-sm font-semibold" style="border-radius: var(--t-radius-sm);">Track Order</a>
            @auth('customer')
            <a href="{{ route('account.dashboard') }}" class="block px-3 py-2.5 text-sm font-semibold" style="border-radius: var(--t-radius-sm);">
                <i class="fas fa-circle-user mr-2 t-accent"></i>My Account
            </a>
            @else
            <a href="{{ route('account.login') }}" class="block px-3 py-2.5 text-sm font-semibold" style="border-radius: var(--t-radius-sm);">
                <i class="fas fa-user mr-2 t-muted"></i>Sign In / Register
            </a>
            @endauth
        </nav>
    </div>
</header>

@include('theme.flash')
@include('layouts.partials.theme-preview-bar')

<main id="page-main">
    @yield('content')
</main>

{{-- ── Footer ──────────────────────────────────────────────────────────── --}}
<footer class="mt-14 no-print" style="background:#0f172a; color:#cbd5e1;">
    {{-- Trust strip --}}
    <div style="border-bottom:1px solid #1e293b;">
        <div class="t-container py-6 grid grid-cols-2 md:grid-cols-4 gap-5">
            @foreach([
                ['truck-fast', 'Fast Delivery', 'Same-day in Lahore'],
                ['shield-halved', 'Genuine Products', '100% authentic'],
                ['rotate-left', 'Easy Returns', '7-day return policy'],
                ['headset', '24/7 Support', 'Always here to help'],
            ] as [$icon, $title, $sub])
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background:#1e293b;">
                    <i class="fas fa-{{ $icon }}" style="color: var(--app-primary);"></i>
                </span>
                <div class="min-w-0">
                    <div class="font-bold text-sm text-white truncate">{{ $title }}</div>
                    <div class="text-xs truncate" style="color:#94a3b8;">{{ $sub }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="t-container py-10 grid grid-cols-1 md:grid-cols-4 gap-8">
        <div class="md:col-span-2">
            <div class="flex items-center gap-2 mb-3">
                @if($mkLogo)
                    <img src="{{ asset('storage/'.$mkLogo) }}" class="h-9 w-auto" alt="{{ $mkShop }}">
                @else
                    <span class="w-9 h-9 rounded-xl flex items-center justify-center text-white" style="background: var(--app-gradient);">
                        <i class="fas fa-mobile-screen-button"></i>
                    </span>
                @endif
                <span class="font-extrabold text-white text-lg">{{ $mkShop }}</span>
            </div>
            <p class="text-sm leading-relaxed max-w-md" style="color:#94a3b8;">
                {{ \App\Models\Setting::get('shop_tagline', 'Your One-Stop Mobile Store') }}.
                Quality phones and accessories at the best prices.
            </p>
            <div class="flex items-center gap-3 mt-5">
                @foreach([
                    ['social_facebook', 'fab fa-facebook-f'],
                    ['social_instagram', 'fab fa-instagram'],
                    ['social_tiktok', 'fab fa-tiktok'],
                ] as [$key, $icon])
                    @if(\App\Models\Setting::get($key))
                    <a href="{{ \App\Models\Setting::get($key) }}" target="_blank" rel="noopener"
                       class="w-9 h-9 rounded-lg flex items-center justify-center transition-colors"
                       style="background:#1e293b; color:#cbd5e1;">
                        <i class="{{ $icon }}"></i>
                    </a>
                    @endif
                @endforeach
            </div>
        </div>

        <div>
            <h4 class="font-bold text-white mb-4 text-sm uppercase tracking-wide">Quick Links</h4>
            <ul class="space-y-2.5 text-sm">
                <li><a href="{{ route('products.index') }}" class="transition-colors hover:text-white">All Products</a></li>
                <li><a href="{{ route('deals.index') }}" class="transition-colors hover:text-white">Deals &amp; Offers</a></li>
                <li><a href="{{ route('order.track') }}" class="transition-colors hover:text-white">Track My Order</a></li>
                <li><a href="{{ route('cart.index') }}" class="transition-colors hover:text-white">My Cart</a></li>
            </ul>
        </div>

        <div>
            <h4 class="font-bold text-white mb-4 text-sm uppercase tracking-wide">Contact</h4>
            <ul class="space-y-2.5 text-sm">
                <li class="flex items-start gap-2"><i class="fas fa-location-dot mt-1" style="color: var(--app-primary);"></i>{{ $mkAddr }}</li>
                <li class="flex items-start gap-2"><i class="fas fa-phone mt-1" style="color: var(--app-primary);"></i>{{ $mkPhone }}</li>
                <li class="flex items-start gap-2"><i class="fas fa-envelope mt-1" style="color: var(--app-primary);"></i>{{ \App\Models\Setting::get('shop_email', 'info@mobilehub.com') }}</li>
            </ul>
        </div>
    </div>

    <div class="text-center text-xs py-5" style="border-top:1px solid #1e293b; color:#64748b;">
        &copy; {{ date('Y') }} {{ $mkShop }}. All rights reserved.
    </div>
</footer>

{{-- ── Mobile bottom tab bar ───────────────────────────────────────────── --}}
<nav class="fixed bottom-0 inset-x-0 z-[450] flex md:hidden no-print"
     style="background: var(--t-surface); border-top:1px solid var(--t-border); box-shadow:0 -2px 12px rgba(15,23,42,.08);">
    <a href="{{ route('home') }}" class="mk-tab" @if($mkRoute === 'home') data-active @endif>
        <i class="fas fa-house"></i><span>Home</span>
    </a>
    <a href="{{ route('products.index') }}" class="mk-tab" @if(str_starts_with((string)$mkRoute, 'products.')) data-active @endif>
        <i class="fas fa-grip"></i><span>Shop</span>
    </a>
    <a href="{{ route('deals.index') }}" class="mk-tab" @if(str_starts_with((string)$mkRoute, 'deals.')) data-active @endif>
        <i class="fas fa-bolt"></i><span>Deals</span>
    </a>
    <a href="{{ route('cart.index') }}" class="mk-tab relative" @if(str_starts_with((string)$mkRoute, 'cart.')) data-active @endif>
        <i class="fas fa-cart-shopping"></i><span>Cart</span>
        @if($mkCart > 0)
        <span class="absolute top-1 right-1/2 translate-x-4 w-4 h-4 text-white text-[9px] rounded-full flex items-center justify-center font-bold"
              style="background:#ef4444;">{{ $mkCart > 9 ? '9+' : $mkCart }}</span>
        @endif
    </a>
    <a href="{{ auth('customer')->check() ? route('account.dashboard') : route('account.login') }}"
       class="mk-tab" @if(str_starts_with((string)$mkRoute, 'account.')) data-active @endif>
        <i class="fas fa-circle-user"></i><span>Account</span>
    </a>
</nav>

@include('theme.scripts')
</body>
</html>
