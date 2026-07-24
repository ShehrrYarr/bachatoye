{{-- Shared <head> contents for every storefront view. --}}
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', \App\Models\Setting::get('shop_name', 'MobileHub')) — @yield('meta-title', \App\Models\Setting::get('shop_tagline', 'Your One-Stop Mobile Store'))</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
<link rel="preconnect" href="https://fonts.bunny.net">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@include('layouts.partials.theme-vars')
@stack('styles')
<style>
    [x-cloak] { display: none !important; }
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }

    @keyframes pageIn {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    #page-main { animation: pageIn .38s cubic-bezier(.16,1,.3,1) both; }

    .reveal {
        opacity: 0;
        transform: translateY(28px);
        transition: opacity .7s cubic-bezier(.16,1,.3,1), transform .7s cubic-bezier(.16,1,.3,1);
    }
    .reveal.is-visible { opacity: 1; transform: none; }
    @media (prefers-reduced-motion: reduce) {
        .reveal, #page-main { animation: none !important; transition: none !important; opacity: 1 !important; transform: none !important; }
    }
</style>
