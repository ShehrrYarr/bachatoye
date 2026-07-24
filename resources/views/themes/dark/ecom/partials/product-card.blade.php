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
    $productColors = $product->relationLoaded('colors') ? $product->colors->where('stock_quantity', '>', 0)->values() : collect();
    $hasColors = $productColors->isNotEmpty();
    $freeShip  = $product->free_delivery || $product->category?->free_delivery;
    $inStock   = $product->isInStock();
    $waNumber  = \App\Models\Setting::get('whatsapp_number');
    $waBookUrl = $waNumber
        ? 'https://wa.me/' . $waNumber . '?text=' . urlencode("Hi! I want to pre-book: {$product->name}. Please let me know when it's available.")
        : null;
@endphp

<div class="dk-card group" x-data="{ hovered: false }" @mouseenter="hovered = true" @mouseleave="hovered = false">

    <div class="relative overflow-hidden" style="border-radius: var(--t-radius) var(--t-radius) 0 0; background: var(--t-surface-2);">
        <a href="{{ route('products.show', $product->slug) }}" class="block aspect-square">
            <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" loading="lazy"
                 class="w-full h-full object-contain p-3 transition-transform duration-500"
                 :class="hovered ? 'scale-110' : 'scale-100'">
        </a>

        @if($offPercent > 0)
        <span class="absolute top-3 left-3 text-[10px] font-black px-2.5 py-1 rounded-full"
              style="background: var(--app-gradient); color:#fff; box-shadow:0 0 16px rgb(var(--t-accent-rgb) / .7);">
            −{{ $offPercent }}%
        </span>
        @elseif($deal)
        <span class="absolute top-3 left-3 text-[10px] font-black px-2.5 py-1 rounded-full"
              style="background: var(--app-gradient); color:#fff; box-shadow:0 0 16px rgb(var(--t-accent-rgb) / .7);">
            {{ $deal->badge_label }}
        </span>
        @endif

        @if($freeShip)
        <span class="absolute top-3 right-3 text-[10px] font-bold px-2 py-0.5 rounded-full"
              style="background: rgba(34,197,94,.16); color:#4ade80; border:1px solid rgba(34,197,94,.35);">
            <i class="fas fa-truck"></i>
        </span>
        @endif

        @if(!$inStock)
        <div class="absolute inset-0 flex items-center justify-center" style="background: rgba(8,8,13,.72); backdrop-filter: blur(2px);">
            <span class="text-[11px] font-bold px-3 py-1.5 rounded-full"
                  style="background: var(--t-surface); color: var(--t-muted); border:1px solid var(--t-border);">Out of Stock</span>
        </div>
        @endif

        @if($inStock)
        <div x-show="hovered" x-cloak style="display:none;"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="absolute bottom-3 left-3 right-3 hidden sm:block">
            @if($hasColors)
            <button type="button"
                    data-product-id="{{ $product->id }}"
                    data-product-name="{{ $product->name }}"
                    data-colors="{{ json_encode($productColors->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'hex_code' => $c->hex_code, 'stock_quantity' => $c->stock_quantity])->values()) }}"
                    @click="$dispatch('open-color-picker', {
                        productId: $el.dataset.productId,
                        productName: $el.dataset.productName,
                        colors: JSON.parse($el.dataset.colors)
                    })"
                    class="w-full text-white text-xs font-bold py-2.5 flex items-center justify-center gap-1.5 dk-glow"
                    style="background: var(--app-gradient); border-radius: var(--t-radius-sm);">
                <i class="fas fa-palette"></i> Choose Colour
            </button>
            @else
            <form method="POST" action="{{ route('cart.add') }}">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="w-full text-white text-xs font-bold py-2.5 dk-glow"
                        style="background: var(--app-gradient); border-radius: var(--t-radius-sm);">
                    <i class="fas fa-cart-plus mr-1"></i> Add to Cart
                </button>
            </form>
            @endif
        </div>
        @elseif($waBookUrl)
        <div x-show="hovered" x-cloak style="display:none;" x-transition class="absolute bottom-3 left-3 right-3 hidden sm:block">
            <a href="{{ $waBookUrl }}" target="_blank" rel="noopener"
               class="w-full text-white text-xs font-bold py-2.5 flex items-center justify-center gap-1.5"
               style="background:#25D366; border-radius: var(--t-radius-sm);">
                <i class="fab fa-whatsapp"></i> Pre-book
            </a>
        </div>
        @endif
    </div>

    <div class="p-4">
        @if($product->category)
        <div class="text-[10px] t-muted mb-1.5" style="letter-spacing:.08em; text-transform:uppercase;">{{ $product->category->name }}</div>
        @endif

        <a href="{{ route('products.show', $product->slug) }}"
           class="block text-sm font-semibold leading-snug line-clamp-2 mb-2.5 transition-colors"
           style="min-height:2.4rem;">{{ $product->name }}</a>

        <div class="flex items-baseline gap-2 flex-wrap">
            <span class="text-base t-price">
                @if($serialFromPrice)<span class="text-[11px] font-medium t-muted">From</span> @endif Rs.&nbsp;{{ number_format($finalPrice) }}
            </span>
            @if($strikePrice)
            <span class="text-xs t-strike">Rs. {{ number_format($strikePrice) }}</span>
            @endif
        </div>

        <div class="flex items-center gap-2 mt-2 flex-wrap" style="min-height:1rem;">
            @if($hasColors)
            <span class="flex items-center gap-1">
                @foreach($productColors->take(4) as $c)
                <span class="w-3 h-3 rounded-full" style="background: {{ $c->hex_code ?: '#3f3f46' }}; border:1px solid var(--t-border);"></span>
                @endforeach
            </span>
            @endif
            @if($product->track_inventory && $product->stock_quantity <= 5 && $product->stock_quantity > 0)
            <span class="text-[11px] font-bold" style="color:#fb923c;">Only {{ $product->stock_quantity }} left</span>
            @endif
        </div>

        @if($inStock)
        <div class="sm:hidden mt-3">
            @if($hasColors)
            <button type="button"
                    data-product-id="{{ $product->id }}"
                    data-product-name="{{ $product->name }}"
                    data-colors="{{ json_encode($productColors->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'hex_code' => $c->hex_code, 'stock_quantity' => $c->stock_quantity])->values()) }}"
                    @click="$dispatch('open-color-picker', {
                        productId: $el.dataset.productId,
                        productName: $el.dataset.productName,
                        colors: JSON.parse($el.dataset.colors)
                    })"
                    class="w-full text-xs font-bold py-2"
                    style="background: rgb(var(--t-accent-rgb) / .18); color: var(--t-accent); border-radius: var(--t-radius-sm);">
                Choose Colour
            </button>
            @else
            <form method="POST" action="{{ route('cart.add') }}">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="w-full text-xs font-bold py-2"
                        style="background: rgb(var(--t-accent-rgb) / .18); color: var(--t-accent); border-radius: var(--t-radius-sm);">
                    Add to Cart
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
    .dk-card {
        display:flex; flex-direction:column;
        background: linear-gradient(160deg, rgba(255,255,255,.05), rgba(255,255,255,.012));
        border: 1px solid var(--t-border);
        border-radius: var(--t-radius);
        overflow: hidden;
        transition: border-color .25s ease, box-shadow .25s ease, transform .25s ease;
    }
    .dk-card:hover {
        border-color: rgb(var(--t-accent-rgb) / .5);
        box-shadow: 0 0 36px -10px rgb(var(--t-accent-rgb) / .55);
        transform: translateY(-3px);
    }
    .dk-card a:hover { color: var(--t-accent); }
</style>
@endpush
@endonce
