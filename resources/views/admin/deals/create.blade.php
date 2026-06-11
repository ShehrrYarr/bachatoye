@extends('layouts.admin')
@section('title', 'Create Deal')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.deals.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Create Deal</h1>
</div>

<form method="POST" action="{{ route('admin.deals.store') }}" x-data="dealForm()">
    @csrf
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <div class="xl:col-span-2 space-y-5">

            {{-- Basic --}}
            <div class="card">
                <div class="card-header"><h2 class="font-semibold text-gray-800">Deal Details</h2></div>
                <div class="card-body space-y-4">
                    <div>
                        <label class="form-label">Deal Name *</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-input @error('name') border-red-500 @enderror" required>
                        @error('name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Deal Type *</label>
                        <select name="type" x-model="dealType" class="form-select" required>
                            <option value="percentage">Percentage Discount (% off)</option>
                            <option value="flat">Flat Discount (Rs. off)</option>
                            <option value="buy_x_get_y">Buy X Get Y Free (same product)</option>
                            <option value="bundle_free">Bundle Free — Buy different products, get others free (Online Store only)</option>
                        </select>
                    </div>

                    {{-- Percentage --}}
                    <div x-show="dealType === 'percentage'">
                        <label class="form-label">Discount Percentage *</label>
                        <div class="flex items-center gap-2">
                            <input type="number" name="discount_value" value="{{ old('discount_value') }}"
                                   min="1" max="100" step="0.1" class="form-input w-32">
                            <span class="text-gray-500 font-semibold">%</span>
                        </div>
                    </div>

                    {{-- Flat --}}
                    <div x-show="dealType === 'flat'">
                        <label class="form-label">Flat Discount (Rs.) *</label>
                        <input type="number" name="flat_discount" value="{{ old('flat_discount') }}"
                               min="1" step="1" class="form-input w-40">
                    </div>

                    {{-- Buy X Get Y --}}
                    <div x-show="dealType === 'buy_x_get_y'" class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Buy Quantity (X) *</label>
                            <input type="number" name="buy_quantity" value="{{ old('buy_quantity', 2) }}" min="1" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Get Quantity (Y) *</label>
                            <input type="number" name="get_quantity" value="{{ old('get_quantity', 1) }}" min="1" class="form-input">
                        </div>
                    </div>

                    {{-- Bundle Free hint --}}
                    <div x-show="dealType === 'bundle_free'"
                         class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-sm text-green-800">
                        <i class="fas fa-gift mr-2"></i>
                        Customer must have <strong>all required products</strong> in their cart to unlock the free products.
                        Only applies on the <strong>online store</strong>.
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Start Date</label>
                            <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">End Date</label>
                            <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" class="form-input">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Product / Category scope (not used for bundle_free) --}}
            <div class="card" x-show="dealType !== 'bundle_free'">
                <div class="card-header"><h2 class="font-semibold text-gray-800">Apply To</h2></div>
                <div class="card-body space-y-4">
                    <p class="text-sm text-gray-500">Leave both empty to apply to all products.</p>

                    <div>
                        <label class="form-label">Specific Products</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-60 overflow-y-auto p-2 border border-gray-200 rounded-xl">
                            @foreach($products as $product)
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" name="product_ids[]" value="{{ $product->id }}"
                                       {{ in_array($product->id, old('product_ids', [])) ? 'checked' : '' }}
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
                                       {{ in_array($cat->id, old('category_ids', [])) ? 'checked' : '' }}
                                       class="w-4 h-4 text-primary-600 rounded">
                                <span>{{ $cat->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bundle Free product selectors --}}
            <div class="card" x-show="dealType === 'bundle_free'" x-cloak>
                <div class="card-header"><h2 class="font-semibold text-gray-800">Bundle Products</h2></div>
                <div class="card-body space-y-5">
                    <div>
                        <label class="form-label text-amber-700">Required Products <span class="font-normal text-gray-500">(customer must have ALL of these in cart)</span></label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-60 overflow-y-auto p-2 border border-amber-200 rounded-xl bg-amber-50">
                            @foreach($products as $product)
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" name="buy_product_ids[]" value="{{ $product->id }}"
                                       {{ in_array($product->id, old('buy_product_ids', [])) ? 'checked' : '' }}
                                       class="w-4 h-4 text-amber-600 rounded">
                                <span class="truncate">{{ $product->name }}</span>
                            </label>
                            @endforeach
                        </div>
                        @error('buy_product_ids') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label text-green-700">Free Products <span class="font-normal text-gray-500">(customer gets these at Rs. 0)</span></label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-60 overflow-y-auto p-2 border border-green-200 rounded-xl bg-green-50">
                            @foreach($products as $product)
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" name="free_product_ids[]" value="{{ $product->id }}"
                                       {{ in_array($product->id, old('free_product_ids', [])) ? 'checked' : '' }}
                                       class="w-4 h-4 text-green-600 rounded">
                                <span class="truncate">{{ $product->name }}</span>
                            </label>
                            @endforeach
                        </div>
                        @error('free_product_ids') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div>
            <div class="card p-5 sticky top-6">
                <h2 class="font-semibold text-gray-800 mb-4">Publish</h2>
                <div class="space-y-3 mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="w-4 h-4 text-primary-600 rounded">
                        <span class="text-sm font-medium text-gray-700">Active</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="show_badge" value="1" {{ old('show_badge', true) ? 'checked' : '' }} class="w-4 h-4 text-primary-600 rounded">
                        <span class="text-sm font-medium text-gray-700">Show badge on products</span>
                    </label>
                </div>

                {{-- Preview badge --}}
                <div class="bg-gray-50 rounded-xl p-3 mb-4">
                    <p class="text-xs text-gray-500 mb-2">Badge preview:</p>
                    <span class="bg-red-500 text-white text-sm font-bold px-3 py-1 rounded-full" x-text="badgePreview()"></span>
                </div>

                <button type="submit" class="btn-primary w-full justify-center">
                    <i class="fas fa-save mr-2"></i> Create Deal
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
        dealType: '{{ old("type", "percentage") }}',
        discountValue: {{ old('discount_value', 10) }},
        flatDiscount: {{ old('flat_discount', 100) }},
        buyQty: {{ old('buy_quantity', 2) }},
        getQty: {{ old('get_quantity', 1) }},

        badgePreview() {
            if (this.dealType === 'percentage') return this.discountValue + '% OFF';
            if (this.dealType === 'flat') return 'Rs.' + this.flatDiscount + ' OFF';
            if (this.dealType === 'bundle_free') return 'Bundle Deal';
            return 'Buy ' + this.buyQty + ' Get ' + this.getQty + ' Free';
        }
    };
}
</script>
@endpush
@endsection
