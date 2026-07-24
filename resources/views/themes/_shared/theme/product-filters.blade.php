{{--
    Shared filter panel. Field names (category, brand, min_price, max_price,
    sort) match the controllers exactly — only the presentation is themed.

    $action, $formId, $brands, $clearUrl; $categories optional.
--}}
<form method="GET" action="{{ $action }}" id="{{ $formId }}">
    <div class="t-card p-4 md:p-5 space-y-6">

        @if(!empty($categories) && $categories->count())
        <div>
            <h3 class="font-bold mb-3 text-sm t-heading">Category</h3>
            <div class="space-y-1">
                <label class="tf-opt">
                    <input type="radio" name="category" value="" {{ !request('category') ? 'checked' : '' }}
                           onchange="document.getElementById('{{ $formId }}').submit()">
                    <span>All Categories</span>
                </label>
                @foreach($categories as $cat)
                <label class="tf-opt">
                    <input type="radio" name="category" value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'checked' : '' }}
                           onchange="document.getElementById('{{ $formId }}').submit()">
                    <span>{{ $cat->name }}</span>
                </label>
                @endforeach
            </div>
        </div>
        @endif

        @if($brands->count())
        <div>
            <h3 class="font-bold mb-3 text-sm t-heading">Brand</h3>
            <div class="space-y-1" style="max-height: 15rem; overflow-y: auto;">
                <label class="tf-opt">
                    <input type="radio" name="brand" value="" {{ !request('brand') ? 'checked' : '' }}
                           onchange="document.getElementById('{{ $formId }}').submit()">
                    <span>All Brands</span>
                </label>
                @foreach($brands as $brand)
                <label class="tf-opt">
                    <input type="radio" name="brand" value="{{ $brand->id }}" {{ request('brand') == $brand->id ? 'checked' : '' }}
                           onchange="document.getElementById('{{ $formId }}').submit()">
                    <span>{{ $brand->name }}</span>
                </label>
                @endforeach
            </div>
        </div>
        @endif

        <div>
            <h3 class="font-bold mb-3 text-sm t-heading">Price Range</h3>
            <div class="flex items-center gap-2">
                <input type="number" name="min_price" placeholder="Min" value="{{ request('min_price') }}"
                       class="t-input text-xs" style="padding:.5rem .625rem;">
                <span class="t-muted text-xs">–</span>
                <input type="number" name="max_price" placeholder="Max" value="{{ request('max_price') }}"
                       class="t-input text-xs" style="padding:.5rem .625rem;">
            </div>
            <button type="submit" class="t-btn t-btn-primary w-full text-sm mt-3" style="padding:.55rem 1rem;">Apply</button>
        </div>

        @if(request()->hasAny(['category', 'brand', 'min_price', 'max_price', 'q']))
        <a href="{{ $clearUrl }}" class="block text-xs text-center font-semibold hover:underline" style="color:#ef4444;">
            Clear all filters
        </a>
        @endif

        <input type="hidden" name="sort" value="{{ request('sort') }}">
        @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
    </div>
</form>

@once
@push('styles')
<style>
    .tf-opt {
        display: flex; align-items: center; gap: .5rem;
        font-size: .8125rem;
        color: var(--t-muted);
        cursor: pointer;
        padding: .3rem .4rem;
        border-radius: var(--t-radius-sm);
        transition: background .15s ease, color .15s ease;
    }
    .tf-opt:hover { background: var(--t-surface-2); color: var(--t-text); }
    .tf-opt input { accent-color: var(--t-accent); cursor: pointer; }
    .tf-opt:has(input:checked) { color: var(--t-accent); font-weight: 600; }
</style>
@endpush
@endonce
