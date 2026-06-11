<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS — {{ \App\Models\Setting::get('shop_name', 'MobileHub') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { overflow: hidden; height: 100vh; height: 100dvh; }
        .pos-grid { height: calc(100vh - 36px); display: grid; grid-template-columns: 1fr 380px; grid-template-rows: auto 1fr; }
        .pos-left { grid-row: 1 / 3; overflow-y: auto; }
        .pos-cart { display: flex; flex-direction: column; height: calc(100vh - 36px); }
        .pos-cart-items { flex: 1; overflow-y: auto; }

        /* ── Mobile layout (< 1024px): products full-screen, cart slides up as overlay ── */
        @media (max-width: 1023px) {
            /* 36px summary bar + 56px floating cart bar */
            .pos-grid {
                display: block;
                height: calc(100vh - 92px);
                height: calc(100dvh - 92px);
            }
            .pos-left { height: 100%; }
            .pos-cart {
                position: fixed;
                inset: 0;
                z-index: 45;
                height: 100vh;
                height: 100dvh;
                border-left: none;
                transform: translateY(100%);
                transition: transform 0.3s ease;
            }
            .pos-cart.mobile-open { transform: translateY(0); }
            /* Payment section can grow taller than small screens — let it scroll */
            .pos-cart-totals { max-height: 65vh; max-height: 65dvh; overflow-y: auto; }
            .pos-cart-items { min-height: 60px; }
        }

        @media print { .no-print { display: none !important; } }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-100 font-sans antialiased" x-data>
@yield('content')
@stack('scripts')
</body>
</html>
