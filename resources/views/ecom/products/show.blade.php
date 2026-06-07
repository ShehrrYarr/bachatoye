@extends('layouts.ecom')
@section('title', $product->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">

    {{-- Breadcrumb --}}
    <nav class="text-sm text-gray-500 mb-6 flex items-center gap-2">
        <a href="{{ route('home') }}" class="hover:text-primary-600">Home</a>
        <i class="fas fa-chevron-right text-xs"></i>
        @if($product->category)
        <a href="{{ route('category.show', $product->category->slug) }}" class="hover:text-primary-600">{{ $product->category->name }}</a>
        <i class="fas fa-chevron-right text-xs"></i>
        @endif
        <span class="text-gray-800 font-medium truncate">{{ $product->name }}</span>
    </nav>

@php $imgInterval = max(2, (int)\App\Models\Setting::get('product_image_interval', 3)) * 1000; @endphp
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10"
         x-data="productGallery({{ $product->images->count() ?: 1 }}, {{ $imgInterval }})"
         x-init="init()">

        {{-- ===== LEFT: Media (Images first, then Videos) ===== --}}
        <div class="space-y-3">

            {{-- Image gallery: vertical thumbnails + main view --}}
            @if($product->images->count())
            <div class="flex gap-3 items-start">

                {{-- Vertical thumbnail list --}}
                @if($product->images->count() > 1)
                <div class="flex flex-col gap-2 shrink-0" style="width:72px; max-height:480px; overflow-y:auto;">
                    @foreach($product->images as $i => $img)
                    <button @click="goTo({{ $i }})"
                            :class="activeImg === {{ $i }}
                                ? 'ring-2 ring-primary-500 ring-offset-1 opacity-100'
                                : 'ring-1 ring-gray-200 opacity-60 hover:opacity-100 hover:ring-gray-400'"
                            class="rounded-xl overflow-hidden bg-gray-100 transition-all duration-150 focus:outline-none"
                            style="width:68px; height:68px; flex-shrink:0;">
                        <img src="{{ $img->url }}" alt="Image {{ $i + 1 }}" class="w-full h-full object-cover">
                    </button>
                    @endforeach
                </div>
                @endif

                {{-- Main image — padding-bottom trick keeps 1:1 ratio reliably --}}
                <div class="flex-1 min-w-0">
                    <div class="relative rounded-2xl bg-gray-50 border border-gray-100 overflow-hidden w-full"
                         style="padding-bottom:100%;"
                         @mouseenter="zooming = true; paused = true"
                         @mouseleave="zooming = false; paused = false">

                        @foreach($product->images as $i => $img)
                        <img src="{{ $img->url }}"
                             alt="{{ $product->name }}"
                             x-show="activeImg === {{ $i }}"
                             x-transition:enter="transition ease-out duration-400"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             @if($i > 0) style="display:none" @endif
                             :class="zooming ? 'scale-[1.35] cursor-zoom-out' : 'scale-100 cursor-zoom-in'"
                             class="absolute inset-0 w-full h-full object-contain p-4 transition-transform duration-500 ease-out select-none">
                        @endforeach

                        {{-- Prev / Next arrows --}}
                        @if($product->images->count() > 1)
                        <button @click.prevent="goTo((activeImg - 1 + total) % total)"
                                class="absolute left-2 top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-white/80 backdrop-blur shadow hover:bg-white flex items-center justify-center text-gray-700 transition-all">
                            <i class="fas fa-chevron-left text-xs"></i>
                        </button>
                        <button @click.prevent="goTo((activeImg + 1) % total)"
                                class="absolute right-2 top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-white/80 backdrop-blur shadow hover:bg-white flex items-center justify-center text-gray-700 transition-all">
                            <i class="fas fa-chevron-right text-xs"></i>
                        </button>

                        {{-- Dot indicators --}}
                        @if($product->images->count() > 1)
                        <div class="absolute bottom-3 left-0 right-0 flex items-center justify-center gap-1.5 z-10">
                            @foreach($product->images as $i => $img)
                            <button @click.prevent="goTo({{ $i }})"
                                    :style="activeImg === {{ $i }}
                                        ? 'width:20px; height:5px; background:rgba(255,255,255,0.95);'
                                        : 'width:5px;  height:5px; background:rgba(255,255,255,0.45);'"
                                    class="rounded-full transition-all duration-300">
                            </button>
                            @endforeach
                        </div>
                        @endif
                        @endif

                        {{-- Deal badge --}}
                        @if($deal)
                        <div class="absolute top-3 left-3 z-10">
                            <span class="bg-red-500 text-white text-xs font-bold px-2.5 py-1 rounded-full shadow">{{ $deal->badge_label }}</span>
                        </div>
                        @endif
                    </div>
                </div>

            </div>
            @else
            {{-- No images: placeholder --}}
            <div class="relative rounded-2xl bg-gray-50 border border-gray-100 overflow-hidden w-full"
                 style="padding-bottom:100%;">
                <img src="{{ asset('images/product-placeholder.png') }}"
                     alt="{{ $product->name }}"
                     class="absolute inset-0 w-full h-full object-contain p-8 opacity-40">
            </div>
            @endif

            {{-- Videos (shown below images) --}}
            @if($product->videos->count())
            <div class="space-y-3">
                @foreach($product->videos as $video)
                <div class="rounded-2xl overflow-hidden border border-gray-200 bg-black">
                    @if($video->type === 'upload')
                        <video controls preload="metadata" class="w-full"
                               @if($video->thumbnail) poster="{{ asset('storage/'.$video->thumbnail) }}" @endif>
                            <source src="{{ asset('storage/'.$video->path) }}">
                        </video>
                    @else
                        <div class="relative w-full" style="aspect-ratio: 16/9;">
                            <iframe src="{{ $video->embed_url }}"
                                    class="absolute inset-0 w-full h-full"
                                    allowfullscreen
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    loading="lazy"></iframe>
                        </div>
                    @endif
                    @if($video->title)
                    <div class="px-3 py-2 text-xs text-gray-300 bg-gray-900">{{ $video->title }}</div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

        </div>

        {{-- ===== RIGHT: Product Info ===== --}}
        @php
            $finalPrice             = $product->getDiscountedPrice();
            $hasDealDisc            = $deal && $deal->type !== 'buy_x_get_y' && $finalPrice < $product->price;
            $comparePrice           = $product->compare_price && $product->compare_price > $finalPrice ? $product->compare_price : null;
            $strikePrice            = $hasDealDisc ? $product->price : $comparePrice;
            $firstInStockAttrOption = $attrOptions->firstWhere('in_stock', true);
            $initialDisplayPrice    = $firstInStockAttrOption ? $firstInStockAttrOption['price'] : $finalPrice;
            $productColors          = $product->colors;
            $inStockColors          = $productColors->where('stock_quantity', '>', 0)->values();
            $preSelected            = $inStockColors->count() === 1 ? $inStockColors->first() : null;
        @endphp

        {{-- Single x-data scope so price display and attribute chips share state directly --}}
        <div x-data="{
                displayPrice:       {{ $initialDisplayPrice }},
                selectedColorId:    {{ $preSelected ? $preSelected->id : 'null' }},
                selectedColorName:  '{{ $preSelected ? addslashes($preSelected->name) : '' }}',
                selectedColorStock: {{ $preSelected ? $preSelected->stock_quantity : 0 }},
                hasColors: {{ $inStockColors->count() > 0 ? 'true' : 'false' }},
                hasAttr:   {{ $attrOptions->isNotEmpty() ? 'true' : 'false' }},
                selectedAttrOption: {{ $firstInStockAttrOption ? "'".addslashes($firstInStockAttrOption['value'])."'" : 'null' }},
                selectedAttrPrice:  {{ $initialDisplayPrice }},
                selectAttr(value, price) {
                    this.selectedAttrOption = value;
                    this.selectedAttrPrice  = price;
                    this.displayPrice       = price;
                },
                canAdd() {
                    if (this.hasColors && !this.selectedColorId) return false;
                    if (this.hasAttr && !this.selectedAttrOption) return false;
                    return true;
                }
             }">

            @if($product->brand)
                <div class="text-sm text-primary-600 font-semibold mb-1 uppercase tracking-wide">{{ $product->brand->name }}</div>
            @endif
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 leading-snug mb-4">{{ $product->name }}</h1>

            {{-- Price — reads displayPrice directly from shared scope, updates instantly --}}
            <div class="flex items-baseline gap-3 flex-wrap mb-5">
                @if($attrOptions->isNotEmpty())
                <span class="text-3xl font-extrabold text-primary-700"
                      x-text="'Rs. ' + Number(displayPrice).toLocaleString('en-PK', { maximumFractionDigits: 0 })">
                    Rs. {{ number_format($initialDisplayPrice, 0) }}
                </span>
                @else
                <span class="text-3xl font-extrabold text-primary-700">Rs. {{ number_format($finalPrice, 0) }}</span>
                @endif
                @if($strikePrice)
                    <span class="text-lg text-gray-400 line-through">Rs. {{ number_format($strikePrice, 0) }}</span>
                @endif
                @if($hasDealDisc)
                    <span class="bg-red-100 text-red-600 text-xs font-bold px-2 py-0.5 rounded-lg">{{ $deal->badge_label }}</span>
                @elseif($comparePrice)
                    @php $savePct = round((($comparePrice - $finalPrice) / $comparePrice) * 100); @endphp
                    <span class="bg-green-100 text-green-700 text-xs font-bold px-2 py-0.5 rounded-lg">{{ $savePct }}% OFF</span>
                @endif
            </div>

            {{-- Urgency countdown badge --}}
            @if($product->isInStock())
            @php
                $discountLabel = $hasDealDisc
                    ? $deal->badge_label
                    : ($comparePrice ? round((($comparePrice - $finalPrice) / $comparePrice) * 100).'% OFF' : null);
            @endphp
            <div x-data="countdownTimer({{ $product->id }})" class="mb-4">
                <div class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-white text-sm font-bold select-none"
                     style="background:#1c1c1c;">
                    @if($discountLabel)
                        <span>{{ $discountLabel }}</span>
                        <span class="opacity-40 font-normal">|</span>
                    @endif
                    <span class="opacity-80 font-normal">Discount Ending In</span>
                    <span class="font-mono tracking-wide" x-text="display"></span>
                </div>
            </div>
            @endif

            @if($deal && $deal->type === 'buy_x_get_y')
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-5 flex items-center gap-2 text-amber-800 text-sm font-medium">
                <i class="fas fa-gift text-amber-500"></i> {{ $deal->badge_label }} — add {{ $deal->buy_quantity }} to cart!
            </div>
            @endif

            {{-- Short description --}}
            @if($product->short_description)
                <p class="text-gray-600 text-sm leading-relaxed mb-5">{{ $product->short_description }}</p>
            @endif

            {{-- Stock status --}}
            <div class="flex items-center flex-wrap gap-2 mb-5">
                @if($product->isInStock())
                    <span class="inline-flex items-center gap-1.5 text-green-700 bg-green-50 border border-green-200 px-3 py-1.5 rounded-lg text-sm font-medium">
                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span> In Stock
                        @if($product->track_inventory)
                            ({{ number_format($product->stock_quantity) }} left)
                        @endif
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 text-red-700 bg-red-50 border border-red-200 px-3 py-1.5 rounded-lg text-sm font-medium">
                        <span class="w-2 h-2 bg-red-500 rounded-full"></span> Out of Stock
                    </span>
                @endif
                @if($product->free_delivery || $product->category?->free_delivery)
                    <span class="inline-flex items-center gap-1.5 text-white bg-green-500 px-3 py-1.5 rounded-lg text-sm font-semibold shadow-sm">
                        <i class="fas fa-truck text-xs"></i> Free Delivery
                    </span>
                @endif
            </div>

            {{-- Add to cart (no own x-data — inherits shared scope above) --}}
            @if($product->isInStock())
            <form method="POST" action="{{ route('cart.add') }}" class="mb-5">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="color_id"             :value="selectedColorId">
                <input type="hidden" name="selected_attr_option"  :value="selectedAttrOption">
                <input type="hidden" name="selected_attr_price"   :value="selectedAttrPrice">

                {{-- Admin hint when no store attribute is configured --}}
                @if($product->is_serialized && !$primaryAttr && auth()->check() && auth()->user()->hasRole('admin'))
                <div class="mb-4 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-xs text-amber-800 flex items-start gap-2">
                    <i class="fas fa-info-circle mt-0.5 shrink-0 text-amber-500"></i>
                    <span>
                        <strong>Admin only:</strong> No store attribute selected for this product.
                        <a href="{{ route('admin.products.show', $product) }}" class="underline font-semibold">Open product settings →</a>
                        and pick a Store Attribute (e.g. Memory, Storage).
                    </span>
                </div>
                @endif

                {{-- Attribute chips (Memory: 6GB / 8GB …) --}}
                @if($attrOptions->isNotEmpty())
                <div class="mb-5">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-sm font-semibold text-gray-700">{{ $primaryAttr->name }}:</span>
                        <span class="text-sm text-gray-500" x-text="selectedAttrOption ?? 'Select an option'"></span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($attrOptions as $opt)
                        @if($opt['in_stock'])
                        <button type="button"
                                @click="selectAttr('{{ addslashes($opt['value']) }}', {{ $opt['price'] }})"
                                :class="selectedAttrOption === '{{ addslashes($opt['value']) }}'
                                    ? 'ring-2 ring-primary-500 ring-offset-2 border-primary-400 bg-primary-50 text-primary-700'
                                    : 'ring-1 ring-gray-300 hover:ring-gray-400 bg-white text-gray-700'"
                                class="flex flex-col items-center px-5 py-2.5 rounded-xl text-sm font-semibold transition-all border">
                            <span>{{ $opt['value'] }}</span>
                            <span class="text-xs font-bold mt-0.5"
                                  :class="selectedAttrOption === '{{ addslashes($opt['value']) }}' ? 'text-primary-600' : 'text-gray-500'">
                                Rs. {{ number_format($opt['price'], 0) }}
                            </span>
                        </button>
                        @else
                        <div class="flex flex-col items-center px-5 py-2.5 rounded-xl text-sm font-medium border border-gray-200 bg-gray-50 text-gray-400 cursor-not-allowed">
                            <span class="line-through">{{ $opt['value'] }}</span>
                            <span class="text-xs mt-0.5">Out of Stock</span>
                        </div>
                        @endif
                        @endforeach
                    </div>
                    <p x-show="hasAttr && !selectedAttrOption"
                       class="text-xs text-red-500 mt-2 font-medium">
                        Please select {{ $primaryAttr->name }} to continue.
                    </p>
                </div>
                @endif

                {{-- Color selector --}}
                @if($productColors->count())
                <div class="mb-5">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-sm font-semibold text-gray-700">Color:</span>
                        <span class="text-sm text-gray-500" x-text="selectedColorName || 'Select a color'"></span>
                        <span x-show="selectedColorStock > 0" class="text-xs text-gray-400"
                              x-text="`(${selectedColorStock} left)`"></span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($productColors as $color)
                        @if($color->stock_quantity > 0)
                        <button type="button"
                                @click="selectedColorId = {{ $color->id }}; selectedColorName = '{{ $color->name }}'; selectedColorStock = {{ $color->stock_quantity }}"
                                :class="selectedColorId === {{ $color->id }}
                                    ? 'ring-2 ring-primary-500 ring-offset-2 border-primary-400'
                                    : 'ring-1 ring-gray-300 hover:ring-gray-400'"
                                class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all bg-white border">
                            @if($color->hex_code)
                            <span class="inline-block w-4 h-4 rounded-full border border-gray-200 shrink-0"
                                  style="background: {{ $color->hex_code }}"></span>
                            @endif
                            {{ $color->name }}
                        </button>
                        @else
                        <div class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium border border-gray-200 bg-gray-50 text-gray-400 line-through cursor-not-allowed">
                            @if($color->hex_code)
                            <span class="inline-block w-4 h-4 rounded-full border border-gray-200 opacity-40 shrink-0"
                                  style="background: {{ $color->hex_code }}"></span>
                            @endif
                            {{ $color->name }}
                        </div>
                        @endif
                        @endforeach
                    </div>
                    <p x-show="hasColors && !selectedColorId"
                       class="text-xs text-red-500 mt-2 font-medium">
                        Please select a color to continue.
                    </p>
                </div>
                @endif

                <div class="flex items-center gap-3 mb-4">
                    <label class="text-sm font-medium text-gray-700">Qty:</label>
                    <div class="flex items-center border border-gray-300 rounded-xl overflow-hidden">
                        <button type="button" onclick="adjustQty(-1)" class="px-3 py-2 text-gray-600 hover:bg-gray-50 transition-colors">–</button>
                        <input type="number" name="quantity" id="qty" value="1" min="1"
                               max="{{ $product->track_inventory ? $product->stock_quantity : 999 }}"
                               class="w-14 text-center text-sm font-semibold border-0 focus:outline-none py-2">
                        <button type="button" onclick="adjustQty(1)" class="px-3 py-2 text-gray-600 hover:bg-gray-50 transition-colors">+</button>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="submit" :disabled="!canAdd()"
                            class="flex-1 btn-primary btn-lg justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-cart-plus mr-2"></i> Add to Cart
                    </button>
                    <button type="submit" name="buy_now" value="1" :disabled="!canAdd()"
                            class="flex-1 btn-lg justify-center font-semibold text-white disabled:opacity-50 disabled:cursor-not-allowed"
                            style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); border-radius: 0.75rem; display:inline-flex; align-items:center; gap:0.4rem;">
                        <i class="fas fa-bolt"></i> Buy Now
                    </button>
                </div>
            </form>
            @else
            <div class="flex items-center justify-center gap-2 w-full py-3 px-5 rounded-xl bg-gray-100 text-gray-400 font-semibold mb-5 cursor-not-allowed">
                <i class="fas fa-times-circle"></i> Out of Stock
            </div>
            @endif

            {{-- Delivery info --}}
            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-5 space-y-2 text-sm">
                <div class="flex items-center gap-2 text-blue-800">
                    <i class="fas fa-truck text-blue-500 w-4 shrink-0"></i>
                    @if($product->free_delivery || $product->category?->free_delivery)
                        <span><strong>Delivery:</strong> <span class="text-green-700 font-semibold">FREE</span> — this product ships free!</span>
                    @else
                        <span><strong>Delivery:</strong> Rs. {{ number_format(\App\Models\Setting::get('delivery_charge', 150)) }}
                            — Free above Rs. {{ number_format(\App\Models\Setting::get('free_delivery_above', 5000)) }}</span>
                    @endif
                </div>
                <div class="flex items-center gap-2 text-blue-800">
                    <i class="fas fa-undo text-blue-500 w-4 shrink-0"></i>
                    <span>7-day easy return policy</span>
                </div>
                <div class="flex items-center gap-2 text-blue-800">
                    <i class="fas fa-phone-alt text-blue-500 w-4 shrink-0"></i>
                    <span>Call us: <strong>{{ \App\Models\Setting::get('shop_phone') }}</strong></span>
                </div>
            </div>

            {{-- Social links --}}
            @if($product->socialLinks->count())
            <div class="mb-5">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Find this product on</div>
                <div class="flex flex-wrap gap-2">
                    @foreach($product->socialLinks as $link)
                    @php
                        $colors = [
                            'facebook'  => 'bg-blue-600 hover:bg-blue-700',
                            'tiktok'    => 'bg-gray-900 hover:bg-black',
                            'instagram' => 'bg-gradient-to-r from-purple-500 via-pink-500 to-orange-400 hover:opacity-90',
                            'youtube'   => 'bg-red-600 hover:bg-red-700',
                        ];
                        $labels = [
                            'facebook'  => 'Facebook',
                            'tiktok'    => 'TikTok',
                            'instagram' => 'Instagram',
                            'youtube'   => 'YouTube',
                        ];
                        $color = $colors[$link->platform] ?? 'bg-gray-600 hover:bg-gray-700';
                        $label = $labels[$link->platform] ?? ucfirst($link->platform);
                    @endphp
                    <a href="{{ $link->url }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 text-white text-xs font-semibold px-4 py-2 rounded-full transition-all {{ $color }}">
                        <i class="{{ $link->icon }}"></i> {{ $label }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    </div>

    {{-- Full Description --}}
    @if($product->description)
    <div class="mt-12 card">
        <div class="card-header"><h2 class="font-semibold text-gray-800">Product Description</h2></div>
        <div class="card-body prose prose-sm max-w-none text-gray-700 leading-relaxed">
            {!! nl2br(e($product->description)) !!}
        </div>
    </div>
    @endif

    {{-- Related products --}}
    @if($related->count())
    <section class="mt-12">
        <h2 class="text-xl font-bold text-gray-900 mb-5">You may also like</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            @foreach($related as $rel)
                @include('ecom.partials.product-card', ['product' => $rel])
            @endforeach
        </div>
    </section>
    @endif
</div>

@push('scripts')
<script>
function productGallery(total, intervalMs) {
    return {
        activeImg: 0,
        total,
        zooming: false,
        paused: false,
        _timer: null,
        _resumeTimer: null,

        init() {
            if (this.total > 1) {
                this._timer = setInterval(() => {
                    if (!this.paused) {
                        this.activeImg = (this.activeImg + 1) % this.total;
                    }
                }, intervalMs);
            }
        },

        destroy() {
            clearInterval(this._timer);
            clearTimeout(this._resumeTimer);
        },

        // Manual navigation — pause briefly then resume auto-slide
        goTo(i) {
            this.activeImg = i;
            this.paused = true;
            clearTimeout(this._resumeTimer);
            this._resumeTimer = setTimeout(() => { this.paused = false; }, 4000);
        },
    };
}

function adjustQty(delta) {
    const input = document.getElementById('qty');
    const max = parseInt(input.max) || 999;
    input.value = Math.min(max, Math.max(1, parseInt(input.value) + delta));
}

function countdownTimer(productId) {
    return {
        seconds: 0,
        _interval: null,

        init() {
            // Seed from product ID + current hour so each product
            // shows a different time and stays consistent within the hour
            const hour = new Date().getHours();
            const seed = ((productId * 17) + (hour * 31)) % 45;
            this.seconds = (seed + 15) * 60; // 15–59 minutes

            this._interval = setInterval(() => {
                this.seconds--;
                if (this.seconds <= 0) {
                    // Reset to a new value (20–40 min) to keep urgency alive
                    this.seconds = (Math.floor(Math.random() * 21) + 20) * 60;
                }
            }, 1000);
        },

        destroy() {
            clearInterval(this._interval);
        },

        get display() {
            const h = Math.floor(this.seconds / 3600);
            const m = Math.floor((this.seconds % 3600) / 60);
            const s = this.seconds % 60;
            const pad = n => String(n).padStart(2, '0');
            return `${pad(h)}h ${pad(m)}m ${pad(s)}s`;
        },
    };
}
</script>
@endpush
@endsection
