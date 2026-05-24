<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — {{ \App\Models\Setting::get('shop_name', 'MobileHub') }}</title>
    @vite(['resources/css/app.css'])
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
</head>
<body class="h-full bg-gradient-to-br from-slate-50 to-blue-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="w-14 h-14 bg-primary-600 rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-lg shadow-primary-200">
                <i class="fas fa-lock text-white text-xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Forgot Password?</h1>
            <p class="text-gray-500 text-sm mt-1">Enter your email to receive a reset link.</p>
        </div>

        <div class="card">
            <div class="card-body">
                @if(session('status'))
                    <div class="alert-success mb-4">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('auth.password.email') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label class="form-label">Email address</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                               class="form-input @error('email') border-red-400 @enderror">
                        @error('email')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="btn-primary w-full justify-center">Send Reset Link</button>
                </form>
                <div class="mt-4 text-center">
                    <a href="{{ route('auth.login') }}" class="text-sm text-primary-600 hover:underline">Back to login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
