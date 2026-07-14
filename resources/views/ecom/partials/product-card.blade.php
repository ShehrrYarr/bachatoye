@php
    $deal           = $product->getActiveDeal();
    $finalPrice     = $product->getDiscountedPrice();
    // For serialized products with no product-level price, derive from min serial selling_price
    $serialFromPrice = false;
    if ($product->is_serialized && $finalPrice == 0) {
        $serialMin = (float) (\App\Models\SerialNumber::where('product_id', $product->id)
            ->where('status', 'in_stock')
            ->where('selling_price', '>', 0)
            ->min('selling_price') ?? 0);
        if ($serialMin > 0) {
            $finalPrice      = $serialMin;
            $serialFromPrice = true; // flag to show "From" prefix
        }
    }
    $hasDiscount    = $deal && $deal->type !== 'buy_x_get_y' && $finalPrice < $product->price;
    $comparePrice   = $product->compare_price && $product->compare_price > $finalPrice ? $product->compare_price : null;
    $strikePrice    = $hasDiscount ? $product->price : $comparePrice;
    $productColors  = $product->relationLoaded('colors')
        ? $product->colors->where('stock_quantity', '>', 0)->values()
        : collect();
    $hasColors = $productColors->isNotEmpty();
    $waNumber  = \App\Models\Setting::get('whatsapp_number');
    $waBookUrl = $waNumber
        ? 'https://wa.me/' . $waNumber . '?text=' . urlencode("Hi! I want to pre-book: {$product->name}. Please let me know when it's available.")
        : null;
@endphp
<div class="ecom-product-card" x-data="{ hovered: false }" @mouseenter="hovered = true" @mouseleave="hovered = false">
    {{-- Image --}}
    <div class="relative overflow-hidden bg-white aspect-square">
        <a href="{{ route('products.show', $product->slug) }}">
            <img src="{{ $product->primary_image_url }}"
                 alt="{{ $product->name }}"
                 loading="lazy"
                 class="w-full h-full object-contain transition-transform duration-500 p-1"
                 :class="hovered ? 'scale-105' : 'scale-100'">
        </a>

        @if($deal || $product->free_delivery || $product->category?->free_delivery)
            <div class="absolute top-2 left-2 flex flex-col gap-1">
                @if($deal)
                    <span class="deal-badge">{{ $deal->badge_label }}</span>
                @endif
                @if($product->free_delivery || $product->category?->free_delivery)
                    <span class="inline-flex items-center gap-0.5 rounded-full px-2 py-0.5 text-[10px] font-semibold bg-green-500 text-white leading-tight shadow">
                        <i class="fas fa-truck"></i> Free Delivery
                    </span>
                @endif
            </div>
        @endif

        @if(!$product->isInStock())
            <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                <span class="bg-black/70 text-white text-xs font-semibold px-3 py-1 rounded-full">Out of Stock</span>
            </div>
        @endif

        {{-- Quick add overlay --}}
        @if($product->isInStock())
        <div x-show="hovered"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-2"
             class="absolute bottom-0 left-0 right-0"
             style="display:none;">
            @if($hasColors)
                {{-- Product has colors: open color picker modal --}}
                <button type="button"
                        data-product-id="{{ $product->id }}"
                        data-product-name="{{ $product->name }}"
                        data-colors="{{ json_encode($productColors->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'hex_code' => $c->hex_code, 'stock_quantity' => $c->stock_quantity])->values()) }}"
                        @click="$dispatch('open-color-picker', {
                            productId:   $el.dataset.productId,
                            productName: $el.dataset.productName,
                            colors:      JSON.parse($el.dataset.colors)
                        })"
                        class="w-full bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold py-3 transition-colors flex items-center justify-center gap-1.5">
                    <i class="fas fa-palette"></i> Choose Color
                </button>
            @else
                {{-- No colors: direct add to cart --}}
                <form method="POST" action="{{ route('cart.add') }}">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold py-3 transition-colors">
                        <i class="fas fa-cart-plus mr-1"></i> Add to Cart
                    </button>
                </form>
            @endif
        </div>
        @elseif($waBookUrl)
        {{-- Out of stock: WhatsApp pre-book button on hover --}}
        <div x-show="hovered"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-2"
             class="absolute bottom-0 left-0 right-0"
             style="display:none;">
            <a href="{{ $waBookUrl }}" target="_blank" rel="noopener"
               class="w-full bg-green-500 hover:bg-green-600 text-white text-sm font-semibold py-3 transition-colors flex items-center justify-center gap-1.5">
                <i class="fab fa-whatsapp text-base"></i> Pre-book on WhatsApp
            </a>
        </div>
        @endif
    </div>

    {{-- Info --}}
    <div class="p-3">
        @if($product->category)
            <div class="text-xs text-gray-400 mb-1">{{ $product->category->name }}</div>
        @endif
        <a href="{{ route('products.show', $product->slug) }}" class="block text-sm font-semibold text-gray-800 hover:text-primary-600 leading-snug line-clamp-2 mb-2">{{ $product->name }}</a>

        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-base font-bold text-primary-700">
                @if($serialFromPrice)From @endif Rs. {{ number_format($finalPrice) }}
            </span>
            @if($strikePrice)
                <span class="text-xs text-gray-400 line-through">Rs. {{ number_format($strikePrice) }}</span>
            @endif
        </div>

        @if($product->track_inventory && $product->stock_quantity <= 5 && $product->stock_quantity > 0)
            <p class="text-xs text-orange-500 mt-1 font-medium">Only {{ $product->stock_quantity }} left!</p>
        @endif
    </div>
</div>
