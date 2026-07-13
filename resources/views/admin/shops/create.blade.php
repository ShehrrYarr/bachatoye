@extends('layouts.admin')
@section('title', 'Add Sub Shop')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.shops.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Add Sub Shop</h1>
</div>

<div class="max-w-2xl">
    <form method="POST" action="{{ route('admin.shops.store') }}">
        @csrf
        <div class="space-y-5">
            <div class="card p-6 space-y-4">
                <h2 class="font-semibold text-gray-800">Shop Details</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Shop Name *</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-input @error('name') border-red-500 @enderror" required>
                        @error('name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Code</label>
                        <input type="text" name="code" value="{{ old('code') }}" placeholder="Auto (e.g. SHP1)" class="form-input @error('code') border-red-500 @enderror">
                        @error('code') <p class="form-error">{{ $message }}</p> @enderror
                        <p class="text-xs text-gray-400 mt-1">Short unique code used on slips & receipts. Left empty = auto-generated.</p>
                    </div>
                    <div>
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Opening Cash Balance</label>
                        <input type="number" name="cash_opening_balance" value="{{ old('cash_opening_balance', 0) }}" min="0" step="0.01" class="form-input">
                    </div>
                    <div class="col-span-2">
                        <label class="form-label">Address</label>
                        <textarea name="address" rows="2" class="form-input">{{ old('address') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card p-6 space-y-4">
                <h2 class="font-semibold text-gray-800">Shop Login <span class="text-xs font-normal text-gray-400">(this shop's only account)</span></h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="form-label">Display Name *</label>
                        <input type="text" name="login_name" value="{{ old('login_name') }}" class="form-input @error('login_name') border-red-500 @enderror" required>
                        @error('login_name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Login Email *</label>
                        <input type="email" name="login_email" value="{{ old('login_email') }}" class="form-input @error('login_email') border-red-500 @enderror" required>
                        @error('login_email') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Password * <span class="text-xs text-gray-400">(min 8 chars)</span></label>
                        <input type="text" name="login_password" value="{{ old('login_password') }}" class="form-input @error('login_password') border-red-500 @enderror" required>
                        @error('login_password') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="card p-6 space-y-4">
                <h2 class="font-semibold text-gray-800">Receipt <span class="text-xs font-normal text-gray-400">(printed on this shop's POS receipts)</span></h2>
                <div>
                    <label class="form-label">Receipt Header</label>
                    <input type="text" name="receipt_header" value="{{ old('receipt_header') }}" placeholder="Defaults to main shop's header" class="form-input">
                </div>
                <div>
                    <label class="form-label">Receipt Footer</label>
                    <input type="text" name="receipt_footer" value="{{ old('receipt_footer') }}" placeholder="Defaults to main shop's footer" class="form-input">
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary"><i class="fas fa-check mr-2"></i> Create Shop</button>
                <a href="{{ route('admin.shops.index') }}" class="btn-outline">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
