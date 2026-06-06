@extends('layouts.ecom')
@section('title', 'Reset Password')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-md">

        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl mb-4"
                 style="background: var(--app-gradient, linear-gradient(135deg, #be123c 0%, #881337 100%))">
                <i class="fas fa-key text-white text-xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Reset Password</h1>
            <p class="text-gray-500 text-sm mt-1">Answer your security question to reset your password</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8"
             x-data="forgotPassword()" x-cloak>

            {{-- Step 1: Email --}}
            <div x-show="step === 1">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
                    <input type="email" x-model="email" @keydown.enter.prevent="lookupQuestion()" required autofocus
                           placeholder="Your registered email"
                           class="w-full px-4 py-2.5 border border-gray-300 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <p x-show="emailError" class="text-red-500 text-xs mt-1.5" x-text="emailError"></p>
                </div>
                <button @click="lookupQuestion()" :disabled="loading"
                        class="w-full mt-5 py-3 rounded-xl text-white font-semibold text-sm transition-opacity hover:opacity-90 disabled:opacity-50"
                        style="background: var(--app-gradient, linear-gradient(135deg, #be123c 0%, #881337 100%))">
                    <i class="fas fa-spinner fa-spin mr-2" x-show="loading"></i>
                    <span x-text="loading ? 'Looking up...' : 'Continue'"></span>
                </button>
            </div>

            {{-- Step 2: Answer + New Password --}}
            <div x-show="step === 2" x-transition>
                <form method="POST" action="{{ route('account.password.reset') }}" class="space-y-5">
                    @csrf
                    <input type="hidden" name="email" :value="email">

                    <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3">
                        <p class="text-xs text-blue-600 font-medium mb-1">Security Question</p>
                        <p class="text-sm text-blue-900 font-semibold" x-text="question"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Your Answer</label>
                        <input type="text" name="security_answer" required
                               placeholder="Case-insensitive"
                               class="w-full px-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500
                                      {{ $errors->has('security_answer') ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-gray-50' }}">
                        @error('security_answer')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
                        <input type="password" name="password" required minlength="6"
                               class="w-full px-4 py-2.5 border border-gray-300 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                        @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm New Password</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full px-4 py-2.5 border border-gray-300 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>

                    <button type="submit"
                            class="w-full py-3 rounded-xl text-white font-semibold text-sm transition-opacity hover:opacity-90"
                            style="background: var(--app-gradient, linear-gradient(135deg, #be123c 0%, #881337 100%))">
                        Reset Password
                    </button>
                </form>
                <button @click="step = 1; emailError = ''" class="w-full text-center text-sm text-gray-500 hover:text-gray-700 mt-4">
                    <i class="fas fa-arrow-left mr-1 text-xs"></i> Use a different email
                </button>
            </div>

        </div>

        <p class="text-center text-sm text-gray-500 mt-5">
            Remembered your password?
            <a href="{{ route('account.login') }}" class="text-primary-600 font-medium hover:underline">Sign in</a>
        </p>

    </div>
</div>
@endsection

@push('scripts')
<script>
function forgotPassword() {
    return {
        step: {{ $errors->any() ? 2 : 1 }},
        email: '{{ old('email', '') }}',
        question: '{{ session('_security_question', '') }}',
        loading: false,
        emailError: '',

        async lookupQuestion() {
            if (!this.email) { this.emailError = 'Please enter your email.'; return; }
            this.loading = true;
            this.emailError = '';
            try {
                const res = await fetch('{{ route('account.password.get-question') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ email: this.email }),
                });
                const data = await res.json();
                if (res.ok) {
                    this.question = data.question;
                    this.step = 2;
                } else {
                    this.emailError = data.errors?.email?.[0] || data.error || 'No account found with that email.';
                }
            } catch {
                this.emailError = 'Something went wrong. Please try again.';
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endpush
