@extends('layouts.admin')
@section('title', 'Create Coupon')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.coupons.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Create Coupon</h1>
</div>

<form method="POST" action="{{ route('admin.coupons.store') }}" x-data="couponForm()">
    @csrf
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Main --}}
        <div class="xl:col-span-2 space-y-5">

            {{-- Basic info --}}
            <div class="card">
                <div class="card-header"><h2 class="font-semibold text-gray-800">Coupon Details</h2></div>
                <div class="card-body space-y-4">

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Coupon Code *</label>
                            <input type="text" name="code" value="{{ old('code') }}"
                                   placeholder="e.g. THANKS10"
                                   class="form-input font-mono uppercase tracking-widest @error('code') border-red-500 @enderror"
                                   required>
                            <p class="text-xs text-gray-400 mt-1">Letters, numbers and hyphens only. Auto-uppercased.</p>
                            @error('code') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Coupon Name *</label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                   placeholder="e.g. Thank You 10% Off"
                                   class="form-input @error('name') border-red-500 @enderror" required>
                            @error('name') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Description <span class="text-gray-400 font-normal">(shown to customer when coupon is applied)</span></label>
                        <input type="text" name="description" value="{{ old('description') }}"
                               placeholder="e.g. Thank you for your order! Enjoy 10% off your next purchase."
                               class="form-input">
                    </div>

                    <div>
                        <label class="form-label">Discount Type *</label>
                        <select name="type" x-model="couponType" class="form-select" required>
                            <option value="percentage">Percentage (% off)</option>
                            <option value="flat">Fixed Amount (Rs. off)</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">
                                <span x-text="couponType === 'percentage' ? 'Discount Percentage *' : 'Discount Amount (Rs.) *'"></span>
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="number" name="value" value="{{ old('value') }}"
                                       :min="couponType === 'percentage' ? 1 : 1"
                                       :max="couponType === 'percentage' ? 100 : 999999"
                                       step="0.01"
                                       x-model="discountValue"
                                       class="form-input @error('value') border-red-500 @enderror" required>
                                <span class="text-gray-500 font-semibold shrink-0"
                                      x-text="couponType === 'percentage' ? '%' : 'Rs.'"></span>
                            </div>
                            @error('value') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Minimum Order Amount (Rs.)</label>
                            <input type="number" name="min_order_amount" value="{{ old('min_order_amount') }}"
                                   min="0" step="1" placeholder="Leave empty for no minimum"
                                   class="form-input">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Valid From</label>
                            <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Expires At</label>
                            <input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}" class="form-input">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Scope --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="font-semibold text-gray-800">Apply Coupon To</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Choose specific products or categories, or leave both empty for all products.</p>
                </div>
                <div class="card-body space-y-5">

                    {{-- Applies to radio --}}
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="applies_to" value="all" x-model="appliesTo"
                                   {{ old('applies_to', 'all') === 'all' ? 'checked' : '' }}
                                   class="w-4 h-4 text-primary-600">
                            <span class="text-sm font-medium text-gray-700">All products</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="applies_to" value="products" x-model="appliesTo"
                                   {{ old('applies_to') === 'products' ? 'checked' : '' }}
                                   class="w-4 h-4 text-primary-600">
                            <span class="text-sm font-medium text-gray-700">Specific products</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="applies_to" value="categories" x-model="appliesTo"
                                   {{ old('applies_to') === 'categories' ? 'checked' : '' }}
                                   class="w-4 h-4 text-primary-600">
                            <span class="text-sm font-medium text-gray-700">Specific categories</span>
                        </label>
                    </div>

                    {{-- Products list --}}
                    <div x-show="appliesTo === 'products'">
                        <label class="form-label">Select Products</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-64 overflow-y-auto p-2 border border-gray-200 rounded-xl">
                            @foreach($products as $product)
                            <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-gray-50 px-2 py-1 rounded-lg">
                                <input type="checkbox" name="product_ids[]" value="{{ $product->id }}"
                                       {{ in_array($product->id, old('product_ids', [])) ? 'checked' : '' }}
                                       class="w-4 h-4 text-primary-600 rounded">
                                <span class="truncate">{{ $product->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Categories list --}}
                    <div x-show="appliesTo === 'categories'">
                        <label class="form-label">Select Categories</label>
                        <div class="grid grid-cols-2 gap-2 p-2 border border-gray-200 rounded-xl">
                            @foreach($categories as $cat)
                            <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-gray-50 px-2 py-1 rounded-lg">
                                <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}"
                                       {{ in_array($cat->id, old('category_ids', [])) ? 'checked' : '' }}
                                       class="w-4 h-4 text-primary-600 rounded">
                                <span>{{ $cat->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div>
            <div class="card p-5 sticky top-6">
                <h2 class="font-semibold text-gray-800 mb-4">Publish</h2>

                <label class="flex items-center gap-2 cursor-pointer mb-5">
                    <input type="checkbox" name="is_active" value="1"
                           {{ old('is_active', true) ? 'checked' : '' }}
                           class="w-4 h-4 text-primary-600 rounded">
                    <span class="text-sm font-medium text-gray-700">Active</span>
                </label>

                {{-- Live preview --}}
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-5">
                    <p class="text-xs text-gray-500 mb-2 font-semibold uppercase tracking-wide">Preview</p>
                    <div class="font-mono font-bold tracking-widest text-primary-700 text-lg" x-text="couponCode || 'YOURCODE'"></div>
                    <div class="text-sm font-semibold text-gray-800 mt-1" x-text="badgePreview()"></div>
                </div>

                <button type="submit" class="btn-primary w-full justify-center">
                    <i class="fas fa-save mr-2"></i> Create Coupon
                </button>
                <a href="{{ route('admin.coupons.index') }}"
                   class="block text-center text-sm text-gray-500 hover:text-gray-700 mt-3">Cancel</a>
            </div>
        </div>

    </div>
</form>

@push('scripts')
<script>
function couponForm() {
    return {
        couponType:   '{{ old('type', 'percentage') }}',
        discountValue: {{ old('value', 10) }},
        couponCode:   '{{ old('code', '') }}',
        appliesTo:    '{{ old('applies_to', 'all') }}',

        badgePreview() {
            if (this.couponType === 'percentage') return (this.discountValue || 0) + '% OFF';
            return 'Rs. ' + (this.discountValue || 0) + ' OFF';
        }
    };
}
</script>
@endpush
@endsection
