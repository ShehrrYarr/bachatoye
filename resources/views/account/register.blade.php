@extends('layouts.ecom')
@section('title', 'Create Account')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-lg">

        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl mb-4"
                 style="background: var(--app-gradient, linear-gradient(135deg, #be123c 0%, #881337 100%))">
                <i class="fas fa-user-plus text-white text-xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Create an account</h1>
            <p class="text-gray-500 text-sm mt-1">Track orders, manage your profile, and more</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
            <form method="POST" action="{{ route('account.register.post') }}" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="w-full px-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500
                                      {{ $errors->has('name') ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-gray-50' }}">
                        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone Number</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" required placeholder="03001234567"
                               class="w-full px-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500
                                      {{ $errors->has('phone') ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-gray-50' }}">
                        @error('phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full px-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500
                                  {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-gray-50' }}">
                    @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                        <input type="password" name="password" required minlength="6"
                               class="w-full px-4 py-2.5 border border-gray-300 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                        @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm Password</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full px-4 py-2.5 border border-gray-300 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                </div>

                {{-- Security Question --}}
                <div class="border-t border-gray-100 pt-5">
                    <p class="text-xs text-gray-500 mb-4 flex items-start gap-1.5">
                        <i class="fas fa-shield-alt text-primary-500 mt-0.5 shrink-0"></i>
                        Set a security question to recover your password if you ever forget it.
                    </p>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Security Question</label>
                            <select name="security_question" required
                                    class="w-full px-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500
                                           {{ $errors->has('security_question') ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-gray-50' }}">
                                <option value="">— Select a question —</option>
                                @foreach($questions as $q)
                                    <option value="{{ $q }}" {{ old('security_question') === $q ? 'selected' : '' }}>{{ $q }}</option>
                                @endforeach
                            </select>
                            @error('security_question')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Your Answer</label>
                            <input type="text" name="security_answer" value="{{ old('security_answer') }}" required
                                   placeholder="Answer (case-insensitive)"
                                   class="w-full px-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500
                                          {{ $errors->has('security_answer') ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-gray-50' }}">
                            @error('security_answer')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <button type="submit"
                        class="w-full py-3 rounded-xl text-white font-semibold text-sm transition-opacity hover:opacity-90"
                        style="background: var(--app-gradient, linear-gradient(135deg, #be123c 0%, #881337 100%))">
                    Create Account
                </button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-6">
                Already have an account?
                <a href="{{ route('account.login') }}" class="text-primary-600 font-medium hover:underline">Sign in</a>
            </p>
        </div>

    </div>
</div>
@endsection
