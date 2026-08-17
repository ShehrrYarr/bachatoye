@extends('layouts.superadmin')
@section('title', 'My Account')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('superadmin.dashboard') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">My Account</h1>
</div>

<div class="max-w-lg">
    <form method="POST" action="{{ route('superadmin.account.update') }}">
        @csrf @method('PUT')
        <div class="card p-6 space-y-4">
            <div>
                <label class="form-label">Name *</label>
                <input type="text" name="name" value="{{ old('name', $account->name) }}" class="form-input" required>
            </div>
            <div>
                <label class="form-label">Email *</label>
                <input type="email" name="email" value="{{ old('email', $account->email) }}" class="form-input" required>
            </div>
            <div class="border-t border-gray-100 pt-4">
                <p class="text-xs text-gray-500 mb-3">Leave blank to keep your current password.</p>
                <label class="form-label">New Password</label>
                <input type="text" name="password" class="form-input" minlength="8" autocomplete="off">
            </div>
            <div>
                <label class="form-label">Confirm New Password</label>
                <input type="text" name="password_confirmation" class="form-input" minlength="8" autocomplete="off">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </div>
    </form>
</div>
@endsection
