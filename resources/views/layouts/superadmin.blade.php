<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Super Admin') — Owner Panel</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.color-vars')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="h-full bg-gray-50">
    <header class="bg-gray-900 text-white">
        <div class="max-w-6xl mx-auto px-4 md:px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center">
                    <i class="fas fa-user-shield text-sm"></i>
                </div>
                <div>
                    <div class="font-bold text-sm leading-tight">Owner Panel</div>
                    <div class="text-xs text-white/50">Super Admin — {{ auth()->user()->name }}</div>
                </div>
            </div>
            <nav class="flex items-center gap-4 text-sm">
                <a href="{{ route('superadmin.dashboard') }}" class="text-white/80 hover:text-white transition-colors">
                    <i class="fas fa-list mr-1"></i> Staff & Logins
                </a>
                <a href="{{ route('superadmin.account.edit') }}" class="text-white/80 hover:text-white transition-colors">
                    <i class="fas fa-user-cog mr-1"></i> My Account
                </a>
                <form method="POST" action="{{ route('auth.logout') }}">
                    @csrf
                    <button type="submit" class="text-white/60 hover:text-red-400 transition-colors" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </form>
            </nav>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 md:px-6 py-6">
        @if(session('success'))
            <div class="alert-success mb-4" x-data x-init="setTimeout(() => $el.remove(), 4000)">
                <i class="fas fa-check-circle mt-0.5"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error') || $errors->any())
            <div class="alert-error mb-4" x-data x-init="setTimeout(() => $el.remove(), 6000)">
                <i class="fas fa-exclamation-circle mt-0.5"></i>
                <div>
                    {{ session('error') }}
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
