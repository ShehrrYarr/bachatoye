@extends('layouts.ecom')
@section('title', 'Create Account')

@section('content')
<div class="flex items-center justify-center py-12 px-4" style="min-height:70vh;">
    <div class="w-full max-w-lg">

        <div class="text-center mb-7">
            <span class="inline-flex items-center justify-center w-14 h-14 mb-4"
                  style="background: var(--app-gradient); border-radius: var(--t-radius);">
                <i class="fas fa-user-plus text-white text-xl"></i>
            </span>
            <h1 class="text-2xl font-extrabold t-heading">Create an account</h1>
            <p class="t-muted text-sm mt-1">Track orders, manage your profile, and more</p>
        </div>

        <div class="t-card p-7 md:p-8">
            <form method="POST" action="{{ route('account.register.post') }}" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="ta-label">Full Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="t-input"
                               style="@error('name') border-color:#f87171; @enderror">
                        @error('name')<p class="ta-err">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="ta-label">Phone Number</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" required placeholder="03001234567" class="t-input"
                               style="@error('phone') border-color:#f87171; @enderror">
                        @error('phone')<p class="ta-err">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="ta-label">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="t-input"
                           style="@error('email') border-color:#f87171; @enderror">
                    @error('email')<p class="ta-err">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="ta-label">Password</label>
                        <input type="password" name="password" required minlength="6" class="t-input">
                        @error('password')<p class="ta-err">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="ta-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" required class="t-input">
                    </div>
                </div>

                <div class="pt-5" style="border-top:1px solid var(--t-border);">
                    <p class="text-xs t-muted mb-4 flex items-start gap-1.5">
                        <i class="fas fa-shield-halved t-accent mt-0.5 shrink-0"></i>
                        Set a security question to recover your password if you ever forget it.
                    </p>
                    <div class="space-y-4">
                        <div>
                            <label class="ta-label">Security Question</label>
                            <select name="security_question" required class="t-input"
                                    style="@error('security_question') border-color:#f87171; @enderror">
                                <option value="">— Select a question —</option>
                                @foreach($questions as $q)
                                <option value="{{ $q }}" {{ old('security_question') === $q ? 'selected' : '' }}>{{ $q }}</option>
                                @endforeach
                            </select>
                            @error('security_question')<p class="ta-err">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="ta-label">Your Answer</label>
                            <input type="text" name="security_answer" value="{{ old('security_answer') }}" required
                                   placeholder="Answer (case-insensitive)" class="t-input"
                                   style="@error('security_answer') border-color:#f87171; @enderror">
                            @error('security_answer')<p class="ta-err">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <button type="submit" class="t-btn t-btn-primary w-full py-3">Create Account</button>
            </form>

            <p class="text-center text-sm t-muted mt-6">
                Already have an account?
                <a href="{{ route('account.login') }}" class="font-semibold t-accent hover:underline">Sign in</a>
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
