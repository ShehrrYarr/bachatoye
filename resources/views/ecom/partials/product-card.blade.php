@php
    $deal           = $product->getActiveDeal();
    $finalPrice     = $product->getDiscountedPrice();
    $hasDiscount    = $deal && $deal->type !== 'buy_x_get_y' && $finalPrice < $product->price;
    $comparePrice   = $product->compare_price && $product->compare_price > $finalPrice ? $product->compare_price : null;
    $strikePrice    = $hasDiscount ? $product->price : $comparePrice;
@endphp
<div class="ecom-product-card group">
    {{-- Image --}}
    <div class="relative overflow-hidden bg-gray-100 aspect-square">
        <a href="{{ route('products.show', $product->slug) }}">
            <img src="{{ $product->primary_image_url }}"
                 alt="{{ $product->name }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                 loading="lazy">
        </a>

        @if($deal)
            <span class="deal-badge">{{ $deal->badge_label }}</span>
        @endif

        @if(!$product->isInStock())
            <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                <span class="bg-black/70 text-white text-xs font-semibold px-3 py-1 rounded-full">Out of Stock</span>
            </div>
        @endif

        {{-- Quick add overlay --}}
        @if($product->isInStock())
        <div class="absolute bottom-0 left-0 right-0 translate-y-full group-hover:translate-y-0 transition-transform duration-300">
            <form method="POST" action="{{ route('cart.add') }}">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold py-3 transition-colors">
                    <i class="fas fa-cart-plus mr-1"></i> Add to Cart
                </button>
            </form>
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
            <span class="text-base font-bold text-primary-700">Rs. {{ number_format($finalPrice) }}</span>
            @if($strikePrice)
                <span class="text-xs text-gray-400 line-through">Rs. {{ number_format($strikePrice) }}</span>
            @endif
        </div>

        @if($product->track_inventory && $product->stock_quantity <= 5 && $product->stock_quantity > 0)
            <p class="text-xs text-orange-500 mt-1 font-medium">Only {{ $product->stock_quantity }} left!</p>
        @endif
    </div>
</div>
