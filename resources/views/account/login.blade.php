@extends('layouts.ecom')
@section('title', 'Login')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-md">

        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl mb-4"
                 style="background: var(--app-gradient, linear-gradient(135deg, #be123c 0%, #881337 100%))">
                <i class="fas fa-user text-white text-xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Welcome back</h1>
            <p class="text-gray-500 text-sm mt-1">Sign in to your account</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">

            @if(session('error'))
            <div class="flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl mb-6">
                <i class="fas fa-exclamation-circle shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
            @endif

            <form method="POST" action="{{ route('account.login.post') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full px-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent
                                  {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-gray-50' }}">
                    @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <a href="{{ route('account.password.forgot') }}" class="text-xs text-primary-600 hover:underline">Forgot password?</a>
                    </div>
                    <input type="password" name="password" required
                           class="w-full px-4 py-2.5 border border-gray-300 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" id="remember" name="remember" class="w-4 h-4 text-primary-600 rounded">
                    <label for="remember" class="text-sm text-gray-600">Keep me logged in</label>
                </div>

                <button type="submit"
                        class="w-full py-3 rounded-xl text-white font-semibold text-sm transition-opacity hover:opacity-90"
                        style="background: var(--app-gradient, linear-gradient(135deg, #be123c 0%, #881337 100%))">
                    Sign In
                </button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-6">
                Don't have an account?
                <a href="{{ route('account.register') }}" class="text-primary-600 font-medium hover:underline">Create one</a>
            </p>
        </div>

    </div>
</div>
@endsection
