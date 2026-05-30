@extends('layouts.admin')
@section('title', 'Edit Customer')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.customers.show', $customer) }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Edit: {{ $customer->name }}</h1>
</div>

<div class="max-w-xl">
    <form method="POST" action="{{ route('admin.customers.update', $customer) }}">
        @csrf @method('PUT')
        <div class="card p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name', $customer->name) }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Phone *</label>
                    <input type="tel" name="phone" value="{{ old('phone', $customer->phone) }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="form-input">
                </div>
                <div class="col-span-2">
                    <label class="form-label">Address</label>
                    <textarea name="address" rows="2" class="form-textarea">{{ old('address', $customer->address) }}</textarea>
                </div>
                <div>
                    <label class="form-label">City</label>
                    <input type="text" name="city" value="{{ old('city', $customer->city) }}" class="form-input">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $customer->is_active) ? 'checked' : '' }} class="w-4 h-4 text-primary-600 rounded">
                <label for="is_active" class="text-sm font-medium text-gray-700 cursor-pointer">Active</label>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">Update Customer</button>
                <a href="{{ route('admin.customers.show', $customer) }}" class="btn-outline">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
