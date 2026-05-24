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
            <div class="w-14 h-14 bg-green-600 rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                <i class="fas fa-key text-white text-xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Set New Password</h1>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('auth.password.update') }}" class="space-y-5">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ old('email', $email ?? '') }}" required class="form-input @error('email') border-red-400 @enderror">
                        @error('email') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" required class="form-input @error('password') border-red-400 @enderror" placeholder="Min 8 characters">
                        @error('password') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" required class="form-input">
                    </div>

                    <button type="submit" class="btn-primary w-full justify-center">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
