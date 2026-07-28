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
    $offPercent    = $strikePrice && $strikePrice > 0 ? (int) round((1 - $finalPrice / $strikePrice) * 100) : 0;
    $saveAmount    = $strikePrice ? (int) ($strikePrice - $finalPrice) : 0;
    $productColors = $product->relationLoaded('colors') ? $product->colors->where('stock_quantity', '>', 0)->values() : collect();
    $hasColors = $productColors->isNotEmpty();
    $freeShip  = $product->free_delivery || $product->category?->free_delivery;
    $inStock   = $product->isInStock();
    $waNumber  = \App\Models\Setting::get('whatsapp_number');
    $waBookUrl = $waNumber
        ? 'https://wa.me/' . $waNumber . '?text=' . urlencode("Hi! I want to pre-book: {$product->name}. Please let me know when it's available.")
        : null;
@endphp

<div class="bd-card group" x-data="{ hovered: false }" @mouseenter="hovered = true" @mouseleave="hovered = false">

    <div class="relative overflow-hidden" style="background:#fff; border-radius: 18px 18px 0 0;">
        <a href="{{ route('products.show', $product->slug) }}" class="block aspect-square">
            <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" loading="lazy"
                 class="w-full h-full object-contain p-3 transition-transform duration-500"
                 :class="hovered ? 'scale-110 -rotate-2' : 'scale-100'">
        </a>

        {{-- Chunky discount ribbon --}}
        @if($offPercent > 0)
        <span class="absolute top-3 -left-1 text-white text-xs font-black px-3 py-1.5"
              style="background: linear-gradient(135deg,#dc2626,#b91c1c); border-radius:0 999px 999px 0; box-shadow:0 4px 12px rgba(220,38,38,.4);">
            {{ $offPercent }}% OFF
        </span>
        @elseif($deal)
        <span class="absolute top-3 -left-1 text-white text-xs font-black px-3 py-1.5"
              style="background: linear-gradient(135deg,#dc2626,#b91c1c); border-radius:0 999px 999px 0; box-shadow:0 4px 12px rgba(220,38,38,.4);">
            {{ $deal->badge_label }}
        </span>
        @endif

        @if($freeShip)
        <span class="absolute top-3 right-3 inline-flex items-center gap-1 text-[10px] font-black px-2.5 py-1 rounded-full"
              style="background:#16a34a; color:#fff;">
            <i class="fas fa-truck"></i> FREE
        </span>
        @endif

        @if(!$inStock)
        <div class="absolute inset-0 flex items-center justify-center" style="background:rgba(255,255,255,.78);">
            <span class="text-xs font-black px-4 py-2 rounded-full" style="background: var(--t-text); color:#fff;">SOLD OUT</span>
        </div>
        @endif

        @if($inStock)
        <div x-show="hovered" x-cloak style="display:none;"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="absolute bottom-3 left-3 right-3 hidden sm:block">
            @if($product->is_serialized)
            <a href="{{ route('products.show', $product->slug) }}"
               class="w-full text-white text-xs font-black py-3 rounded-full flex items-center justify-center gap-1.5"
               style="background: var(--app-gradient); box-shadow:0 6px 18px -4px rgb(var(--t-accent-rgb) / .6);">
                <i class="fas fa-sliders-h"></i> SELECT VARIANT
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
                    class="w-full text-white text-xs font-black py-3 rounded-full flex items-center justify-center gap-1.5"
                    style="background: var(--app-gradient); box-shadow:0 6px 18px -4px rgb(var(--t-accent-rgb) / .6);">
                <i class="fas fa-palette"></i> PICK COLOUR
            </button>
            @else
            <form method="POST" action="{{ route('cart.add') }}">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="w-full text-white text-xs font-black py-3 rounded-full"
                        style="background: var(--app-gradient); box-shadow:0 6px 18px -4px rgb(var(--t-accent-rgb) / .6);">
                    <i class="fas fa-cart-plus mr-1"></i> ADD TO CART
                </button>
            </form>
            @endif
        </div>
        @elseif($waBookUrl)
        <div x-show="hovered" x-cloak style="display:none;" x-transition class="absolute bottom-3 left-3 right-3 hidden sm:block">
            <a href="{{ $waBookUrl }}" target="_blank" rel="noopener"
               class="w-full text-white text-xs font-black py-3 rounded-full flex items-center justify-center gap-1.5"
               style="background:#25D366;">
                <i class="fab fa-whatsapp text-base"></i> PRE-BOOK
            </a>
        </div>
        @endif
    </div>

    <div class="p-3.5">
        <a href="{{ route('products.show', $product->slug) }}"
           class="block text-[13px] font-bold leading-snug line-clamp-2 mb-2 transition-colors"
           style="min-height:2.4rem;">{{ $product->name }}</a>

        <div class="flex items-baseline gap-2 flex-wrap">
            <span class="text-lg t-price" style="letter-spacing:-.02em;">
                @if($serialFromPrice)<span class="text-[11px] font-bold t-muted">From</span> @endif Rs.&nbsp;{{ number_format($finalPrice) }}
            </span>
            @if($strikePrice)
            <span class="text-xs t-strike">Rs. {{ number_format($strikePrice) }}</span>
            @endif
        </div>

        @if($saveAmount > 0)
        <div class="inline-block mt-1.5 text-[10px] font-black px-2 py-0.5 rounded-full"
             style="background:#dcfce7; color:#15803d;">
            SAVE RS. {{ number_format($saveAmount) }}
        </div>
        @elseif($product->track_inventory && $product->stock_quantity <= 5 && $product->stock_quantity > 0)
        <div class="inline-block mt-1.5 text-[10px] font-black px-2 py-0.5 rounded-full"
             style="background:#ffedd5; color:#c2410c;">
            ONLY {{ $product->stock_quantity }} LEFT
        </div>
        @endif

        @if($hasColors)
        <div class="flex items-center gap-1.5 mt-2">
            @foreach($productColors->take(5) as $c)
            <span class="w-3.5 h-3.5 rounded-full" style="background: {{ $c->hex_code ?: '#d4d4d8' }}; border:2px solid var(--t-surface); box-shadow:0 0 0 1px var(--t-border);"></span>
            @endforeach
        </div>
        @endif

        @if($inStock)
        <div class="sm:hidden mt-3">
            @if($product->is_serialized)
            <a href="{{ route('products.show', $product->slug) }}"
               class="w-full text-white text-[11px] font-black py-2.5 rounded-full flex items-center justify-center"
               style="background: var(--app-gradient);">
                SELECT VARIANT
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
                    class="w-full text-white text-[11px] font-black py-2.5 rounded-full"
                    style="background: var(--app-gradient);">
                PICK COLOUR
            </button>
            @else
            <form method="POST" action="{{ route('cart.add') }}">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="w-full text-white text-[11px] font-black py-2.5 rounded-full"
                        style="background: var(--app-gradient);">
                    ADD TO CART
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
    .bd-card {
        display:flex; flex-direction:column;
        background: var(--t-surface);
        border: 2px solid var(--t-border);
        border-radius: 20px;
        overflow: hidden;
        transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
    }
    .bd-card:hover {
        border-color: var(--t-accent);
        box-shadow: 0 14px 34px -10px rgb(var(--t-accent-rgb) / .45);
        transform: translateY(-4px);
    }
    .bd-card a:hover { color: var(--t-accent); }
</style>
@endpush
@endonce
