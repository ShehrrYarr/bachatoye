@extends('layouts.ecom')
@section('title', 'Login')

@section('content')
<div class="flex items-center justify-center py-12 px-4" style="min-height:70vh;">
    <div class="w-full max-w-md">

        <div class="text-center mb-7">
            <span class="inline-flex items-center justify-center w-14 h-14 mb-4"
                  style="background: var(--app-gradient); border-radius: var(--t-radius);">
                <i class="fas fa-user text-white text-xl"></i>
            </span>
            <h1 class="text-2xl font-extrabold t-heading">Welcome back</h1>
            <p class="t-muted text-sm mt-1">Sign in to your account</p>
        </div>

        <div class="t-card p-7 md:p-8">
            @if(session('error'))
            <div class="flex items-center gap-2 text-sm px-4 py-3 mb-6"
                 style="background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; border-radius: var(--t-radius-sm);">
                <i class="fas fa-exclamation-circle shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
            @endif

            <form method="POST" action="{{ route('account.login.post') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="ta-label">Email or Mobile Number</label>
                    <input type="text" name="identifier" value="{{ old('identifier') }}" required autofocus
                           placeholder="Email or 03XX-XXXXXXX" class="t-input"
                           style="@error('identifier') border-color:#f87171; @enderror">
                    @error('identifier')<p class="ta-err">{{ $message }}</p>@enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="ta-label" style="margin:0;">Password</label>
                        <a href="{{ route('account.password.forgot') }}" class="text-xs t-accent hover:underline">Forgot password?</a>
                    </div>
                    <input type="password" name="password" required class="t-input"
                           style="@error('password') border-color:#f87171; @enderror">
                    @error('password')<p class="ta-err">{{ $message }}</p>@enderror
                </div>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="remember" name="remember" class="w-4 h-4" style="accent-color: var(--t-accent);">
                    <span class="text-sm t-muted">Keep me logged in</span>
                </label>

                <button type="submit" class="t-btn t-btn-primary w-full py-3">Sign In</button>
            </form>

            <p class="text-center text-sm t-muted mt-6">
                Don't have an account?
                <a href="{{ route('account.register') }}" class="font-semibold t-accent hover:underline">Create one</a>
            </p>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .ta-label { display:block; font-size:.8125rem; font-weight:600; margin-bottom:.375rem; color: var(--t-text); }
    .ta-err   { margin-top:.375rem; font-size:.75rem; color:#ef4444; }
</style>
@endpush
