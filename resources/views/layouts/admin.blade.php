<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — {{ \App\Models\Setting::get('shop_name', 'MobileHub') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @stack('styles')
</head>
<body class="h-full flex" x-data="{ sidebarOpen: true }">

    {{-- Sidebar --}}
    <aside
        class="flex flex-col bg-white border-r border-gray-200 shrink-0 overflow-hidden transition-all duration-300"
        :style="sidebarOpen ? 'width:256px; min-width:256px;' : 'width:0; min-width:0; border-right-width:0;'"
    >
        {{-- Logo --}}
        <div class="flex items-center gap-3 px-5 py-5" style="background: linear-gradient(135deg, #f43f5e 0%, #be123c 100%)">
            @if(\App\Models\Setting::get('logo'))
                <img src="{{ asset('storage/' . \App\Models\Setting::get('logo')) }}" class="h-8 w-auto" alt="Logo">
            @else
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-mobile-alt text-white text-sm"></i>
                </div>
            @endif
            <div>
                <div class="font-bold text-white text-sm leading-tight">{{ \App\Models\Setting::get('shop_name', 'MobileHub') }}</div>
                <div class="text-xs text-red-200">{{ auth()->user()->isAdmin() ? 'Admin Panel' : 'Salesman Panel' }}</div>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">
            @if(auth()->user()->isAdmin())
                @include('layouts.partials.admin-nav')
            @else
                @include('layouts.partials.salesman-nav')
            @endif
        </nav>

        {{-- User --}}
        <div class="border-t border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
                    <span class="text-primary-700 text-xs font-bold">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-gray-800 truncate">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</div>
                </div>
                <form method="POST" action="{{ route('auth.logout') }}">
                    @csrf
                    <button type="submit" title="Logout" class="text-gray-400 hover:text-red-500 transition-colors">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        {{-- Topbar --}}
        <header class="bg-white flex items-center gap-4 px-6 py-3 shrink-0" style="border-bottom: 2px solid transparent; border-image: linear-gradient(135deg, #f43f5e, #be123c) 1">
            <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-gray-800 transition-colors">
                <i class="fas fa-bars"></i>
            </button>
            <div class="flex-1">
                <h1 class="text-base font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h1>
            </div>
            <div class="flex items-center gap-3 text-sm text-gray-500">
                <span>{{ now()->format('D, d M Y') }}</span>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('pos.index') }}" class="btn-primary btn-sm">
                        <i class="fas fa-cash-register"></i> POS
                    </a>
                @endif
            </div>
        </header>

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="mx-6 mt-4">
                <div class="alert-success" x-data x-init="setTimeout(() => $el.remove(), 4000)">
                    <i class="fas fa-check-circle mt-0.5"></i>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif
        @if(session('error') || $errors->any())
            <div class="mx-6 mt-4">
                <div class="alert-error" x-data x-init="setTimeout(() => $el.remove(), 6000)">
                    <i class="fas fa-exclamation-circle mt-0.5"></i>
                    <div>
                        {{ session('error') }}
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Page content --}}
        <main class="flex-1 overflow-y-auto p-6">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
