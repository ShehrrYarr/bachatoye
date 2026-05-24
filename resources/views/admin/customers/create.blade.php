@extends('layouts.admin')
@section('title', 'Add Customer')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.customers.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Add Customer</h1>
</div>

<div class="max-w-xl">
    <form method="POST" action="{{ route('admin.customers.store') }}">
        @csrf
        <div class="card p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-input @error('name') border-red-500 @enderror" required>
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Phone *</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" class="form-input @error('phone') border-red-500 @enderror" required>
                    @error('phone') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-input">
                </div>
                <div class="col-span-2">
                    <label class="form-label">Address</label>
                    <textarea name="address" rows="2" class="form-textarea">{{ old('address') }}</textarea>
                </div>
                <div>
                    <label class="form-label">City</label>
                    <input type="text" name="city" value="{{ old('city') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Opening Balance (Rs.)</label>
                    <input type="number" name="credit_balance" value="{{ old('credit_balance', 0) }}" step="0.01" class="form-input">
                    <p class="form-hint">Negative = customer owes money</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="w-4 h-4 text-primary-600 rounded">
                <label for="is_active" class="text-sm font-medium text-gray-700 cursor-pointer">Active</label>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">Save Customer</button>
                <a href="{{ route('admin.customers.index') }}" class="btn-outline">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
