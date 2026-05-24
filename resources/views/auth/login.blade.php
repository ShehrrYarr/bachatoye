<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — {{ \App\Models\Setting::get('shop_name', 'MobileHub') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="h-full bg-gradient-to-br from-slate-50 to-blue-50 flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        {{-- Logo / Brand --}}
        <div class="text-center mb-8">
            @if(\App\Models\Setting::get('logo'))
                <img src="{{ asset('storage/' . \App\Models\Setting::get('logo')) }}" class="h-12 mx-auto mb-3" alt="Logo">
            @else
                <div class="w-14 h-14 bg-primary-600 rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-lg shadow-primary-200">
                    <i class="fas fa-mobile-alt text-white text-2xl"></i>
                </div>
            @endif
            <h1 class="text-2xl font-bold text-gray-900">{{ \App\Models\Setting::get('shop_name', 'MobileHub') }}</h1>
            <p class="text-gray-500 text-sm mt-1">Staff Portal — Sign in to continue</p>
        </div>

        {{-- Card --}}
        <div class="card">
            <div class="card-body">

                @if(session('status'))
                    <div class="alert-success mb-4">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('auth.login.post') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label class="form-label">Email address</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                               class="form-input @error('email') border-red-400 @enderror"
                               placeholder="admin@mobilehub.com">
                        @error('email')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="form-label !mb-0">Password</label>
                            <a href="{{ route('auth.password.request') }}" class="text-xs text-primary-600 hover:underline">Forgot password?</a>
                        </div>
                        <input type="password" name="password" required
                               class="form-input @error('password') border-red-400 @enderror"
                               placeholder="••••••••">
                        @error('password')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="remember" id="remember" class="rounded border-gray-300 text-primary-600">
                        <label for="remember" class="text-sm text-gray-600">Keep me signed in</label>
                    </div>

                    <button type="submit" class="btn-primary w-full justify-center btn-lg">
                        Sign In
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">
            &copy; {{ date('Y') }} {{ \App\Models\Setting::get('shop_name', 'MobileHub') }}. Staff portal only.
        </p>
    </div>


</body>
</html>
