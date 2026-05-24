<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', \App\Models\Setting::get('shop_name', 'MobileHub')) — @yield('meta-title', \App\Models\Setting::get('shop_tagline', 'Your One-Stop Mobile Store'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
<div class="text-white text-xs py-2 hidden sm:block" style="background: linear-gradient(135deg, #be123c 0%, #881337 100%)">
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

                {{-- Cart --}}
                <a href="{{ route('cart.index') }}" class="relative p-2 text-gray-600 hover:text-primary-600 transition-colors">
                    <i class="fas fa-shopping-cart text-lg"></i>
                    @php $cartCount = array_sum(array_column(session('cart', []), 'quantity')); @endphp
                    @if($cartCount > 0)
                        <span class="absolute -top-0.5 -right-0.5 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold">
                            {{ $cartCount > 9 ? '9+' : $cartCount }}
                        </span>
                    @endif
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
        </nav>
    </div>
</header>

{{-- Flash messages --}}
@if(session('success'))
<div class="fixed top-20 right-4 z-50 max-w-sm" x-data x-init="setTimeout(()=>$el.remove(),4000)">
    <div class="alert-success shadow-lg">
        <i class="fas fa-check-circle shrink-0"></i>
        <span>{{ session('success') }}</span>
    </div>
</div>
@endif
@if(session('error'))
<div class="fixed top-20 right-4 z-50 max-w-sm" x-data x-init="setTimeout(()=>$el.remove(),5000)">
    <div class="alert-error shadow-lg">
        <i class="fas fa-exclamation-circle shrink-0"></i>
        <span>{{ session('error') }}</span>
    </div>
</div>
@endif

{{-- Page content --}}
<main id="page-main">
    @yield('content')
</main>

{{-- Footer --}}
<footer class="mt-16 text-gray-200" style="background: linear-gradient(160deg, #881337 0%, #be123c 50%, #9f1239 100%)">
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
@stack('scripts')
</body>
</html>
