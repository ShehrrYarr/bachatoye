@php
    // Pricing logic is identical to the default card — presentation only differs.
    $deal            = $product->getActiveDeal();
    $finalPrice      = $product->getDiscountedPrice();
    $serialFromPrice = false;
    if ($product->is_serialized && $finalPrice == 0) {
        $serialMin = (float) (\App\Models\SerialNumber::where('product_id', $product->id)
            ->where('status', 'in_stock')
            ->where('selling_price', '>', 0)
            ->min('selling_price') ?? 0);
        if ($serialMin > 0) {
            $finalPrice      = $serialMin;
            $serialFromPrice = true;
        }
    }
    $hasDiscount   = $deal && $deal->type !== 'buy_x_get_y' && $finalPrice < $product->price;
    $comparePrice  = $product->compare_price && $product->compare_price > $finalPrice ? $product->compare_price : null;
    $strikePrice   = $hasDiscount ? $product->price : $comparePrice;
    $offPercent    = $strikePrice && $strikePrice > 0 ? (int) round((1 - $finalPrice / $strikePrice) * 100) : 0;
    $productColors = $product->relationLoaded('colors')
        ? $product->colors->where('stock_quantity', '>', 0)->values()
        : collect();
    $hasColors  = $productColors->isNotEmpty();
    $freeShip   = $product->free_delivery || $product->category?->free_delivery;
    $inStock    = $product->isInStock();
    $waNumber   = \App\Models\Setting::get('whatsapp_number');
    $waBookUrl  = $waNumber
        ? 'https://wa.me/' . $waNumber . '?text=' . urlencode("Hi! I want to pre-book: {$product->name}. Please let me know when it's available.")
        : null;
@endphp

<div class="mk-card group" x-data="{ hovered: false }" @mouseenter="hovered = true" @mouseleave="hovered = false">

    <div class="relative overflow-hidden" style="background:#fff; border-radius: var(--t-radius) var(--t-radius) 0 0;">
        <a href="{{ route('products.show', $product->slug) }}" class="block aspect-square">
            <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" loading="lazy"
                 class="w-full h-full object-contain p-2 transition-transform duration-500"
                 :class="hovered ? 'scale-105' : 'scale-100'">
        </a>

        {{-- Discount flag — the marketplace signature --}}
        @if($offPercent > 0)
        <span class="absolute top-0 left-0 text-white text-[11px] font-extrabold px-2.5 py-1"
              style="background:#ef4444; border-radius: var(--t-radius) 0 var(--t-radius-sm) 0;">
            {{ $offPercent }}% OFF
        </span>
        @elseif($deal)
        <span class="absolute top-0 left-0 text-white text-[11px] font-extrabold px-2.5 py-1"
              style="background:#ef4444; border-radius: var(--t-radius) 0 var(--t-radius-sm) 0;">
            {{ $deal->badge_label }}
        </span>
        @endif

        @if($freeShip)
        <span class="absolute top-2 right-2 inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full"
              style="background:#dcfce7; color:#15803d;">
            <i class="fas fa-truck"></i> Free
        </span>
        @endif

        @if(!$inStock)
        <div class="absolute inset-0 flex items-center justify-center" style="background:rgba(255,255,255,.72);">
            <span class="text-xs font-bold px-3 py-1.5 rounded-full" style="background:#374151; color:#fff;">Out of Stock</span>
        </div>
        @endif

        {{-- Hover action --}}
        @if($inStock)
        <div x-show="hovered" x-cloak style="display:none;"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-2"
             class="absolute bottom-0 left-0 right-0 hidden sm:block">
            @if($hasColors)
                <button type="button"
                        data-product-id="{{ $product->id }}"
                        data-product-name="{{ $product->name }}"
                        data-colors="{{ json_encode($productColors->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'hex_code' => $c->hex_code, 'stock_quantity' => $c->stock_quantity])->values()) }}"
                        @click="$dispatch('open-color-picker', {
                            productId:   $el.dataset.productId,
                            productName: $el.dataset.productName,
                            colors:      JSON.parse($el.dataset.colors)
                        })"
                        class="w-full text-white text-sm font-bold py-2.5 flex items-center justify-center gap-1.5"
                        style="background: var(--app-gradient);">
                    <i class="fas fa-palette"></i> Choose Colour
                </button>
            @else
                <form method="POST" action="{{ route('cart.add') }}">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="w-full text-white text-sm font-bold py-2.5"
                            style="background: var(--app-gradient);">
                        <i class="fas fa-cart-plus mr-1"></i> Add to Cart
                    </button>
                </form>
            @endif
        </div>
        @elseif($waBookUrl)
        <div x-show="hovered" x-cloak style="display:none;"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="absolute bottom-0 left-0 right-0 hidden sm:block">
            <a href="{{ $waBookUrl }}" target="_blank" rel="noopener"
               class="w-full text-white text-sm font-bold py-2.5 flex items-center justify-center gap-1.5"
               style="background:#25D366;">
                <i class="fab fa-whatsapp text-base"></i> Pre-book
            </a>
        </div>
        @endif
    </div>

    <div class="p-3">
        <a href="{{ route('products.show', $product->slug) }}"
           class="block text-[13px] font-semibold leading-snug line-clamp-2 mb-2 transition-colors group-hover:t-accent"
           style="min-height:2.4rem;">{{ $product->name }}</a>

        <div class="flex items-baseline gap-1.5 flex-wrap">
            <span class="text-[15px] t-price">
                @if($serialFromPrice)<span class="text-[11px] font-semibold t-muted">From</span> @endif Rs.&nbsp;{{ number_format($finalPrice) }}
            </span>
            @if($strikePrice)
            <span class="text-[11px] t-strike">Rs. {{ number_format($strikePrice) }}</span>
            @endif
        </div>

        <div class="flex items-center gap-2 mt-1.5 flex-wrap" style="min-height:1.1rem;">
            @if($hasColors)
            <span class="flex items-center gap-1">
                @foreach($productColors->take(4) as $c)
                <span class="w-3 h-3 rounded-full" style="background: {{ $c->hex_code ?: '#d4d4d8' }}; border:1px solid var(--t-border);"></span>
                @endforeach
                @if($productColors->count() > 4)
                <span class="text-[10px] t-muted">+{{ $productColors->count() - 4 }}</span>
                @endif
            </span>
            @endif

            @if($product->track_inventory && $product->stock_quantity <= 5 && $product->stock_quantity > 0)
            <span class="text-[11px] font-bold" style="color:#ea580c;">Only {{ $product->stock_quantity }} left</span>
            @elseif($product->category)
            <span class="text-[11px] t-muted truncate">{{ $product->category->name }}</span>
            @endif
        </div>

        {{-- Mobile: always-visible action (no hover on touch) --}}
        @if($inStock)
        <div class="sm:hidden mt-2.5">
            @if($hasColors)
            <button type="button"
                    data-product-id="{{ $product->id }}"
                    data-product-name="{{ $product->name }}"
                    data-colors="{{ json_encode($productColors->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'hex_code' => $c->hex_code, 'stock_quantity' => $c->stock_quantity])->values()) }}"
                    @click="$dispatch('open-color-picker', {
                        productId:   $el.dataset.productId,
                        productName: $el.dataset.productName,
                        colors:      JSON.parse($el.dataset.colors)
                    })"
                    class="w-full text-xs font-bold py-2"
                    style="background: rgb(var(--t-accent-rgb) / .12); color: var(--t-accent); border-radius: var(--t-radius-sm);">
                <i class="fas fa-palette mr-1"></i> Choose Colour
            </button>
            @else
            <form method="POST" action="{{ route('cart.add') }}">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="w-full text-xs font-bold py-2"
                        style="background: rgb(var(--t-accent-rgb) / .12); color: var(--t-accent); border-radius: var(--t-radius-sm);">
                    <i class="fas fa-cart-plus mr-1"></i> Add to Cart
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
    .mk-card {
        background: var(--t-surface);
        border: 1px solid var(--t-border);
        border-radius: var(--t-radius);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
    }
    .mk-card:hover {
        border-color: rgb(var(--t-accent-rgb) / .45);
        box-shadow: var(--t-shadow-lg);
        transform: translateY(-2px);
    }
    .mk-card .group-hover\:t-accent { transition: color .18s ease; }
    .mk-card:hover .group-hover\:t-accent { color: var(--t-accent); }
</style>
@endpush
@endonce
