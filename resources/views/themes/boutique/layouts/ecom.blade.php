<!DOCTYPE html>
<html lang="en" class="scroll-smooth" data-theme="boutique">
<head>
    @include('theme.head')
    <style>
        .bq-nav {
            font-size: .78rem;
            font-weight: 500;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--t-text);
            padding: .25rem 0;
            position: relative;
            white-space: nowrap;
        }
        .bq-nav::after {
            content:''; position:absolute; left:0; right:0; bottom:-2px;
            height:1px; background: var(--t-text);
            transform: scaleX(0); transform-origin: left;
            transition: transform .3s cubic-bezier(.16,1,.3,1);
        }
        .bq-nav:hover::after, .bq-nav[data-active]::after { transform: scaleX(1); }

        .bq-wordmark {
            font-family: var(--t-font-heading);
            font-weight: 600;
            letter-spacing: .04em;
        }
        /* Boutique keeps corners crisp and shadows absent */
        .t-card { box-shadow: none; }
        .t-card:hover { box-shadow: none; border-color: var(--t-text); }
        .t-btn { letter-spacing: .06em; text-transform: uppercase; font-size: .8rem; }
    </style>
</head>
<body class="antialiased" x-data="{ mobileMenuOpen: false, searchOpen: false }">

@php
    $bqShop  = \App\Models\Setting::get('shop_name', 'MobileHub');
    $bqLogo  = \App\Models\Setting::get('logo');
    $bqPhone = \App\Models\Setting::get('shop_phone', '03001234567');
    $bqAddr  = \App\Models\Setting::get('shop_address', 'Lahore, Pakistan');
    $bqFree  = \App\Models\Setting::get('free_delivery_above', 5000);
    $bqCart  = array_sum(array_column(session('cart', []), 'quantity'));
    $bqRoute = request()->route()?->getName();
@endphp

{{-- Announcement --}}
<div class="text-center text-[11px] py-2.5 no-print"
     style="background: var(--t-text); color: var(--t-surface); letter-spacing:.1em; text-transform:uppercase;">
    Complimentary delivery on orders over Rs. {{ number_format($bqFree) }}
</div>

<header class="sticky top-0 z-40 no-print" style="background: var(--t-surface); border-bottom:1px solid var(--t-border);">
    <div class="t-container">
        {{-- Row 1: utilities left, wordmark centre, actions right --}}
        <div class="flex items-center justify-between" style="height:4.5rem;">
            <div class="flex items-center gap-4 flex-1">
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden t-muted p-1">
                    <i class="fas fa-bars"></i>
                </button>
                <button @click="searchOpen = !searchOpen" class="hidden md:inline-flex items-center gap-2 bq-nav">
                    <i class="fas fa-magnifying-glass text-xs"></i> Search
                </button>
            </div>

            <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0">
                @if($bqLogo)
                    <img src="{{ asset('storage/'.$bqLogo) }}" class="h-10 w-auto" alt="{{ $bqShop }}">
                @else
                    <span class="bq-wordmark text-xl md:text-2xl">{{ $bqShop }}</span>
                @endif
            </a>

            <div class="flex items-center justify-end gap-4 flex-1">
                <button @click="searchOpen = !searchOpen" class="md:hidden t-muted p-1">
                    <i class="fas fa-magnifying-glass"></i>
                </button>
                @auth('customer')
                <a href="{{ route('account.dashboard') }}" class="bq-nav hidden md:inline-block">Account</a>
                <a href="{{ route('account.dashboard') }}" class="md:hidden t-muted p-1"><i class="fas fa-user"></i></a>
                @else
                <a href="{{ route('account.login') }}" class="bq-nav hidden md:inline-block">Sign In</a>
                <a href="{{ route('account.login') }}" class="md:hidden t-muted p-1"><i class="fas fa-user"></i></a>
                @endauth
                <a href="{{ route('cart.index') }}" class="relative bq-nav">
                    <span class="hidden md:inline">Bag</span>
                    <i class="fas fa-bag-shopping md:hidden"></i>
                    <span id="cart-count-badge" class="ml-1 text-[10px] font-bold"
                          style="{{ $bqCart > 0 ? '' : 'display:none' }}">({{ $bqCart }})</span>
                </a>
            </div>
        </div>

        {{-- Row 2: primary nav, centred --}}
        <nav class="hidden md:flex items-center justify-center gap-9 pb-4">
            <a href="{{ route('home') }}" class="bq-nav" @if($bqRoute === 'home') data-active @endif>Home</a>
            <a href="{{ route('products.index') }}" class="bq-nav" @if(str_starts_with((string)$bqRoute, 'products.')) data-active @endif>Shop All</a>
            <a href="{{ route('deals.index') }}" class="bq-nav" @if(str_starts_with((string)$bqRoute, 'deals.')) data-active @endif>Offers</a>
            <a href="{{ route('order.track') }}" class="bq-nav" @if(str_starts_with((string)$bqRoute, 'order.track')) data-active @endif>Track Order</a>
        </nav>

        {{-- Search --}}
        <div x-show="searchOpen" x-transition x-cloak class="pb-5" x-data="liveSearch()">
            <div class="relative max-w-xl mx-auto">
                <input type="text" x-model="query" @input.debounce.300ms="search()"
                       @keydown.enter="window.location = '/search?q=' + encodeURIComponent(query)"
                       placeholder="What are you looking for?"
                       class="w-full text-center text-sm"
                       style="background:transparent; border:0; border-bottom:1px solid var(--t-border); padding:.75rem 0; color: var(--t-text); outline:none;">
                <div x-show="results.length > 0" x-transition x-cloak class="absolute top-full left-0 right-0 mt-2 z-50 t-card overflow-hidden">
                    <template x-for="item in results" :key="item.slug">
                        <a :href="`/products/${item.slug}`" class="flex items-center gap-3 px-4 py-3 text-left" style="border-bottom:1px solid var(--t-border);">
                            <img :src="item.image" class="w-11 h-11 object-cover" style="background: var(--t-surface-2);">
                            <span class="flex-1 min-w-0">
                                <span class="block text-sm truncate" x-text="item.name"></span>
                                <span class="block text-xs t-muted" x-text="`Rs. ${Number(item.price).toLocaleString()}`"></span>
                            </span>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <div x-show="mobileMenuOpen" x-transition x-cloak class="md:hidden" style="border-top:1px solid var(--t-border);">
        <nav class="px-6 py-5 space-y-4">
            <a href="{{ route('home') }}" class="block bq-nav">Home</a>
            <a href="{{ route('products.index') }}" class="block bq-nav">Shop All</a>
            <a href="{{ route('deals.index') }}" class="block bq-nav">Offers</a>
            <a href="{{ route('order.track') }}" class="block bq-nav">Track Order</a>
            @auth('customer')
            <a href="{{ route('account.dashboard') }}" class="block bq-nav">My Account</a>
            @else
            <a href="{{ route('account.login') }}" class="block bq-nav">Sign In / Register</a>
            @endauth
        </nav>
    </div>
</header>

@include('theme.flash')
@include('layouts.partials.theme-preview-bar')

<main id="page-main">
    @yield('content')
</main>

<footer class="mt-20 no-print" style="border-top:1px solid var(--t-border); background: var(--t-surface);">
    <div class="t-container py-14">
        <div class="text-center mb-12">
            @if($bqLogo)
            <img src="{{ asset('storage/'.$bqLogo) }}" class="h-10 w-auto mx-auto mb-4" alt="{{ $bqShop }}">
            @else
            <div class="bq-wordmark text-2xl mb-3">{{ $bqShop }}</div>
            @endif
            <p class="text-sm t-muted max-w-md mx-auto leading-relaxed">
                {{ \App\Models\Setting::get('shop_tagline', 'Your One-Stop Mobile Store') }}
            </p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center md:text-left">
            <div>
                <h4 class="text-[11px] font-semibold mb-4" style="letter-spacing:.14em; text-transform:uppercase;">Shop</h4>
                <ul class="space-y-2.5 text-sm t-muted">
                    <li><a href="{{ route('products.index') }}" class="hover:t-accent transition-colors">All Products</a></li>
                    <li><a href="{{ route('deals.index') }}" class="hover:t-accent transition-colors">Offers</a></li>
                    <li><a href="{{ route('cart.index') }}" class="hover:t-accent transition-colors">Your Bag</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-[11px] font-semibold mb-4" style="letter-spacing:.14em; text-transform:uppercase;">Help</h4>
                <ul class="space-y-2.5 text-sm t-muted">
                    <li><a href="{{ route('order.track') }}" class="hover:t-accent transition-colors">Track Order</a></li>
                    <li><span>7-day returns</span></li>
                    <li><span>Genuine products</span></li>
                </ul>
            </div>
            <div>
                <h4 class="text-[11px] font-semibold mb-4" style="letter-spacing:.14em; text-transform:uppercase;">Visit</h4>
                <ul class="space-y-2.5 text-sm t-muted">
                    <li>{{ $bqAddr }}</li>
                    <li><a href="tel:{{ $bqPhone }}" class="hover:t-accent transition-colors">{{ $bqPhone }}</a></li>
                    <li>{{ \App\Models\Setting::get('shop_email', 'info@mobilehub.com') }}</li>
                </ul>
            </div>
            <div>
                <h4 class="text-[11px] font-semibold mb-4" style="letter-spacing:.14em; text-transform:uppercase;">Follow</h4>
                <div class="flex items-center gap-4 justify-center md:justify-start t-muted">
                    @foreach([['social_facebook','fab fa-facebook-f'],['social_instagram','fab fa-instagram'],['social_tiktok','fab fa-tiktok']] as [$key, $icon])
                        @if(\App\Models\Setting::get($key))
                        <a href="{{ \App\Models\Setting::get($key) }}" target="_blank" rel="noopener" class="hover:t-accent transition-colors">
                            <i class="{{ $icon }}"></i>
                        </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="text-center text-xs t-muted py-6" style="border-top:1px solid var(--t-border);">
        &copy; {{ date('Y') }} {{ $bqShop }}
    </div>
</footer>

@include('theme.scripts')
</body>
</html>
