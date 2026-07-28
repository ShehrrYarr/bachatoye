@php
    // Pricing logic matches the default card exactly — presentation only differs.
    $deal            = $product->getActiveDeal();
    $finalPrice      = $product->getDiscountedPrice();
    $serialFromPrice = false;
    if ($product->is_serialized && $finalPrice == 0) {
        $serialMin = (float) (\App\Models\SerialNumber::where('product_id', $product->id)
            ->where('status', 'in_stock')->where('selling_price', '>', 0)->min('selling_price') ?? 0);
        if ($serialMin > 0) { $finalPrice = $serialMin; $serialFromPrice = true; }
    }
    $hasDiscount   = $deal && $deal->type !== 'buy_x_get_y' && $finalPrice < $product->price;
    $comparePrice  = $product->compare_price && $product->compare_price > $finalPrice ? $product->compare_price : null;
    $strikePrice   = $hasDiscount ? $product->price : $comparePrice;
    $productColors = $product->relationLoaded('colors') ? $product->colors->where('stock_quantity', '>', 0)->values() : collect();
    $hasColors = $productColors->isNotEmpty();
    $inStock   = $product->isInStock();
    $waNumber  = \App\Models\Setting::get('whatsapp_number');
    $waBookUrl = $waNumber
        ? 'https://wa.me/' . $waNumber . '?text=' . urlencode("Hi! I want to pre-book: {$product->name}. Please let me know when it's available.")
        : null;
@endphp

<div class="bq-card group" x-data="{ hovered: false }" @mouseenter="hovered = true" @mouseleave="hovered = false">

    <div class="relative overflow-hidden" style="background: var(--t-surface-2);">
        <a href="{{ route('products.show', $product->slug) }}" class="block aspect-[4/5]">
            <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" loading="lazy"
                 class="w-full h-full object-contain p-5 transition-transform duration-700"
                 :class="hovered ? 'scale-105' : 'scale-100'">
        </a>

        @if($deal)
        <span class="absolute top-4 left-4 text-[10px] font-semibold px-2.5 py-1"
              style="background: var(--t-text); color: var(--t-surface); letter-spacing:.1em; text-transform:uppercase;">
            {{ $deal->badge_label }}
        </span>
        @endif

        @if(!$inStock)
        <div class="absolute inset-0 flex items-center justify-center" style="background: rgba(250,249,247,.8);">
            <span class="text-[10px] font-semibold px-3 py-1.5"
                  style="background: var(--t-text); color: var(--t-surface); letter-spacing:.12em; text-transform:uppercase;">
                Sold Out
            </span>
        </div>
        @endif

        {{-- Quiet hover action --}}
        @if($inStock)
        <div x-show="hovered" x-cloak style="display:none;"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="absolute bottom-4 left-4 right-4 hidden sm:block">
            @if($product->is_serialized)
            <a href="{{ route('products.show', $product->slug) }}"
               class="block w-full text-center text-[11px] font-semibold py-3"
               style="background: var(--t-surface); color: var(--t-text); letter-spacing:.12em; text-transform:uppercase; border:1px solid var(--t-text);">
                Select Variant
            </a>
            @elseif($hasColors)
            <button type="button"
                    data-product-id="{{ $product->id }}"
                    data-product-name="{{ $product->name }}"
                    data-colors="{{ json_encode($productColors->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'hex_code' => $c->hex_code, 'stock_quantity' => $c->stock_quantity])->values()) }}"
                    @click="$dispatch('open-color-picker', {
                        productId: $el.dataset.productId,
                        productName: $el.dataset.productName,
                        colors: JSON.parse($el.dataset.colors)
                    })"
                    class="w-full text-[11px] font-semibold py-3"
                    style="background: var(--t-surface); color: var(--t-text); letter-spacing:.12em; text-transform:uppercase; border:1px solid var(--t-text);">
                Select Colour
            </button>
            @else
            <form method="POST" action="{{ route('cart.add') }}">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="w-full text-[11px] font-semibold py-3"
                        style="background: var(--t-surface); color: var(--t-text); letter-spacing:.12em; text-transform:uppercase; border:1px solid var(--t-text);">
                    Add to Bag
                </button>
            </form>
            @endif
        </div>
        @elseif($waBookUrl)
        <div x-show="hovered" x-cloak style="display:none;" x-transition
             class="absolute bottom-4 left-4 right-4 hidden sm:block">
            <a href="{{ $waBookUrl }}" target="_blank" rel="noopener"
               class="block text-center text-[11px] font-semibold py-3"
               style="background: var(--t-surface); color: var(--t-text); letter-spacing:.12em; text-transform:uppercase; border:1px solid var(--t-text);">
                Enquire
            </a>
        </div>
        @endif
    </div>

    <div class="pt-4 text-center">
        @if($product->category)
        <div class="text-[10px] t-muted mb-1.5" style="letter-spacing:.14em; text-transform:uppercase;">{{ $product->category->name }}</div>
        @endif

        <a href="{{ route('products.show', $product->slug) }}"
           class="block text-sm leading-snug line-clamp-2 mb-2 transition-opacity hover:opacity-60 t-heading"
           style="min-height:2.5rem;">{{ $product->name }}</a>

        <div class="flex items-baseline justify-center gap-2">
            <span class="text-sm font-semibold">
                @if($serialFromPrice)<span class="text-[11px] t-muted">From</span> @endif Rs.&nbsp;{{ number_format($finalPrice) }}
            </span>
            @if($strikePrice)
            <span class="text-xs t-strike">Rs. {{ number_format($strikePrice) }}</span>
            @endif
        </div>

        @if($hasColors)
        <div class="flex items-center justify-center gap-1.5 mt-2.5">
            @foreach($productColors->take(5) as $c)
            <span class="w-2.5 h-2.5 rounded-full" style="background: {{ $c->hex_code ?: '#d4d4d8' }}; border:1px solid var(--t-border);"></span>
            @endforeach
        </div>
        @endif

        {{-- Touch devices get a persistent action --}}
        @if($inStock)
        <div class="sm:hidden mt-3">
            @if($product->is_serialized)
            <a href="{{ route('products.show', $product->slug) }}"
               class="block w-full text-center text-[10px] font-semibold py-2.5"
               style="border:1px solid var(--t-border); letter-spacing:.1em; text-transform:uppercase;">
                Select Variant
            </a>
            @elseif($hasColors)
            <button type="button"
                    data-product-id="{{ $product->id }}"
                    data-product-name="{{ $product->name }}"
                    data-colors="{{ json_encode($productColors->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'hex_code' => $c->hex_code, 'stock_quantity' => $c->stock_quantity])->values()) }}"
                    @click="$dispatch('open-color-picker', {
                        productId: $el.dataset.productId,
                        productName: $el.dataset.productName,
                        colors: JSON.parse($el.dataset.colors)
                    })"
                    class="w-full text-[10px] font-semibold py-2.5"
                    style="border:1px solid var(--t-border); letter-spacing:.1em; text-transform:uppercase;">
                Select Colour
            </button>
            @else
            <form method="POST" action="{{ route('cart.add') }}">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="w-full text-[10px] font-semibold py-2.5"
                        style="border:1px solid var(--t-border); letter-spacing:.1em; text-transform:uppercase;">
                    Add to Bag
                </button>
            </form>
            @endif
        </div>
        @endif
    </div>
</div>

@once
@push('styles')
<style>
    .bq-card { display:flex; flex-direction:column; }
    .bq-card img { transition: transform .7s cubic-bezier(.16,1,.3,1); }
</style>
@endpush
@endonce
