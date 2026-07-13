@extends('layouts.admin')
@section('title', 'Edit Sub Shop')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.shops.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Edit — {{ $shop->name }}</h1>
</div>

<div class="max-w-2xl">
    <form method="POST" action="{{ route('admin.shops.update', $shop) }}">
        @csrf @method('PUT')
        <div class="space-y-5">
            <div class="card p-6 space-y-4">
                <h2 class="font-semibold text-gray-800">Shop Details</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Shop Name *</label>
                        <input type="text" name="name" value="{{ old('name', $shop->name) }}" class="form-input @error('name') border-red-500 @enderror" required>
                        @error('name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Code *</label>
                        <input type="text" name="code" value="{{ old('code', $shop->code) }}" class="form-input @error('code') border-red-500 @enderror" required>
                        @error('code') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" value="{{ old('phone', $shop->phone) }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Opening Cash Balance</label>
                        <input type="number" name="cash_opening_balance" value="{{ old('cash_opening_balance', $shop->cash_opening_balance) }}" min="0" step="0.01" class="form-input">
                    </div>
                    <div class="col-span-2">
                        <label class="form-label">Address</label>
                        <textarea name="address" rows="2" class="form-input">{{ old('address', $shop->address) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card p-6 space-y-4">
                <h2 class="font-semibold text-gray-800">Shop Login</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="form-label">Display Name *</label>
                        <input type="text" name="login_name" value="{{ old('login_name', $shop->loginUser?->name) }}" class="form-input @error('login_name') border-red-500 @enderror" required>
                        @error('login_name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Login Email *</label>
                        <input type="email" name="login_email" value="{{ old('login_email', $shop->loginUser?->email) }}" class="form-input @error('login_email') border-red-500 @enderror" required>
                        @error('login_email') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">New Password <span class="text-xs text-gray-400">(leave empty to keep current)</span></label>
                        <input type="text" name="login_password" value="{{ old('login_password') }}" class="form-input @error('login_password') border-red-500 @enderror">
                        @error('login_password') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="card p-6 space-y-4">
                <h2 class="font-semibold text-gray-800">Receipt</h2>
                <div>
                    <label class="form-label">Receipt Header</label>
                    <input type="text" name="receipt_header" value="{{ old('receipt_header', $shop->receipt_header) }}" placeholder="Defaults to main shop's header" class="form-input">
                </div>
                <div>
                    <label class="form-label">Receipt Footer</label>
                    <input type="text" name="receipt_footer" value="{{ old('receipt_footer', $shop->receipt_footer) }}" placeholder="Defaults to main shop's footer" class="form-input">
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary"><i class="fas fa-check mr-2"></i> Save Changes</button>
                <a href="{{ route('admin.shops.index') }}" class="btn-outline">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
