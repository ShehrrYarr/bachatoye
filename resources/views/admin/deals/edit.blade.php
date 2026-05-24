@extends('layouts.admin')
@section('title', 'Edit Deal')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.deals.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Edit Deal: {{ $deal->name }}</h1>
</div>

<form method="POST" action="{{ route('admin.deals.update', $deal) }}" x-data="dealForm()">
    @csrf @method('PUT')
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <div class="xl:col-span-2 space-y-5">
            <div class="card">
                <div class="card-header"><h2 class="font-semibold text-gray-800">Deal Details</h2></div>
                <div class="card-body space-y-4">
                    <div>
                        <label class="form-label">Deal Name *</label>
                        <input type="text" name="name" value="{{ old('name', $deal->name) }}" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Deal Type *</label>
                        <select name="type" x-model="dealType" class="form-select" required>
                            <option value="percentage">Percentage Discount</option>
                            <option value="flat">Flat Discount</option>
                            <option value="buy_x_get_y">Buy X Get Y Free</option>
                        </select>
                    </div>
                    <div x-show="dealType === 'percentage'">
                        <label class="form-label">Discount Percentage *</label>
                        <div class="flex items-center gap-2">
                            <input type="number" name="discount_value" value="{{ old('discount_value', $deal->type === 'percentage' ? $deal->value : '') }}"
                                   x-model="discountValue" min="1" max="100" class="form-input w-32">
                            <span class="text-gray-500 font-semibold">%</span>
                        </div>
                    </div>
                    <div x-show="dealType === 'flat'">
                        <label class="form-label">Flat Discount (Rs.) *</label>
                        <input type="number" name="flat_discount" value="{{ old('flat_discount', $deal->type === 'flat' ? $deal->value : '') }}"
                               x-model="flatDiscount" min="1" class="form-input w-40">
                    </div>
                    <div x-show="dealType === 'buy_x_get_y'" class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Buy Quantity (X) *</label>
                            <input type="number" name="buy_quantity" value="{{ old('buy_quantity', $deal->buy_quantity) }}"
                                   x-model="buyQty" min="1" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Get Quantity (Y) *</label>
                            <input type="number" name="get_quantity" value="{{ old('get_quantity', $deal->get_quantity) }}"
                                   x-model="getQty" min="1" class="form-input">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Start Date</label>
                            <input type="datetime-local" name="starts_at"
                                   value="{{ old('starts_at', $deal->starts_at?->format('Y-m-d\TH:i')) }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">End Date</label>
                            <input type="datetime-local" name="ends_at"
                                   value="{{ old('ends_at', $deal->ends_at?->format('Y-m-d\TH:i')) }}" class="form-input">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="font-semibold text-gray-800">Apply To</h2></div>
                <div class="card-body space-y-4">
                    <div>
                        <label class="form-label">Specific Products</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-60 overflow-y-auto p-2 border border-gray-200 rounded-xl">
                            @foreach($products as $product)
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" name="product_ids[]" value="{{ $product->id }}"
                                       {{ $deal->products->contains($product->id) ? 'checked' : '' }}
                                       class="w-4 h-4 text-primary-600 rounded">
                                <span class="truncate">{{ $product->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Or Entire Categories</label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($categories as $cat)
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}"
                                       {{ $deal->categories->contains($cat->id) ? 'checked' : '' }}
                                       class="w-4 h-4 text-primary-600 rounded">
                                <span>{{ $cat->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="card p-5 sticky top-6">
                <h2 class="font-semibold text-gray-800 mb-4">Publish</h2>
                <div class="space-y-3 mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $deal->is_active) ? 'checked' : '' }} class="w-4 h-4 text-primary-600 rounded">
                        <span class="text-sm font-medium text-gray-700">Active</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="show_badge" value="1" {{ old('show_badge', $deal->show_badge) ? 'checked' : '' }} class="w-4 h-4 text-primary-600 rounded">
                        <span class="text-sm font-medium text-gray-700">Show badge on products</span>
                    </label>
                </div>
                <div class="bg-gray-50 rounded-xl p-3 mb-4">
                    <p class="text-xs text-gray-500 mb-2">Badge preview:</p>
                    <span class="bg-red-500 text-white text-sm font-bold px-3 py-1 rounded-full" x-text="badgePreview()"></span>
                </div>
                <button type="submit" class="btn-primary w-full justify-center">
                    <i class="fas fa-save mr-2"></i> Update Deal
                </button>
                <a href="{{ route('admin.deals.index') }}" class="block text-center text-sm text-gray-500 hover:text-gray-700 mt-3">Cancel</a>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
function dealForm() {
    return {
        dealType: '{{ old("type", $deal->type) }}',
        discountValue: {{ old('discount_value', $deal->type === 'percentage' ? ($deal->value ?? 10) : 10) }},
        flatDiscount: {{ old('flat_discount', $deal->type === 'flat' ? ($deal->value ?? 100) : 100) }},
        buyQty: {{ old('buy_quantity', $deal->buy_quantity ?? 2) }},
        getQty: {{ old('get_quantity', $deal->get_quantity ?? 1) }},
        badgePreview() {
            if (this.dealType === 'percentage') return this.discountValue + '% OFF';
            if (this.dealType === 'flat') return 'Rs.' + this.flatDiscount + ' OFF';
            return 'Buy ' + this.buyQty + ' Get ' + this.getQty + ' Free';
        }
    };
}
</script>
@endpush
@endsection
