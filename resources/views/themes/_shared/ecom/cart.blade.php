@extends('layouts.ecom')
@section('title', 'Shopping Cart')

@section('content')
<div class="t-container py-6 md:py-8">

    @include('theme.breadcrumb', ['crumbs' => [['Shopping Cart', null]]])

    @if(count($items) > 0)
    @php $freeAbove = (float) \App\Models\Setting::get('free_delivery_above', 5000); @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">

        {{-- ── Items ───────────────────────────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-3">
            <div class="flex items-center justify-between mb-1">
                <h1 class="text-xl md:text-2xl font-extrabold t-heading">Your Cart</h1>
                <span class="t-chip">{{ collect($items)->sum('quantity') }} item{{ collect($items)->sum('quantity') === 1 ? '' : 's' }}</span>
            </div>

            @foreach($items as $item)
            <div class="t-card flex items-start gap-4 p-4">
                <a href="{{ route('products.show', $item['product']->slug) }}" class="shrink-0">
                    <img src="{{ $item['product']->primary_image_url }}" alt="{{ $item['product']->name }}" loading="lazy"
                         class="w-20 h-20 md:w-24 md:h-24 object-contain p-1"
                         style="border-radius: var(--t-radius-sm); background:#fff; border:1px solid var(--t-border);">
                </a>

                <div class="flex-1 min-w-0">
                    <a href="{{ route('products.show', $item['product']->slug) }}"
                       class="font-bold text-sm leading-snug block mb-1 hover:t-accent transition-colors">{{ $item['product']->name }}</a>

                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mb-2">
                        @if($item['product']->category)
                        <span class="text-xs t-muted">{{ $item['product']->category->name }}</span>
                        @endif
                        @if($item['color_name'])
                        <span class="flex items-center gap-1.5 text-xs t-muted">
                            @if($item['color_hex'])
                            <span class="w-3.5 h-3.5 rounded-full shrink-0" style="background: {{ $item['color_hex'] }}; border:1px solid var(--t-border);"></span>
                            @endif
                            {{ $item['color_name'] }}
                        </span>
                        @endif
                        @if(!empty($item['attr_option']))
                        <span class="text-xs font-semibold" style="color:#6366f1;">
                            <i class="fas fa-tag text-[10px] mr-1" style="opacity:.7;"></i>{{ $item['attr_option'] }}
                        </span>
                        @endif
                        @if(!empty($item['serial_id']))
                        <span class="text-xs font-semibold" style="color:#9333ea;">
                            <i class="fas fa-mobile-screen text-[10px] mr-1" style="opacity:.7;"></i>Used unit{{ !empty($item['serial_label']) ? ': '.$item['serial_label'] : '' }}
                        </span>
                        @endif
                    </div>

                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <span class="text-base t-price">Rs. {{ number_format($item['price']) }}</span>

                        <div class="flex items-center gap-3">
                            @if(!empty($item['serial_id']))
                            {{-- One-of-a-kind unit: quantity is locked at 1 --}}
                            <div class="flex items-center overflow-hidden" style="border:1px solid var(--t-border); border-radius: var(--t-radius-sm); opacity:.55;">
                                <span class="px-2.5 py-1.5 text-sm t-muted cursor-not-allowed">–</span>
                                <span class="w-8 text-center text-sm font-bold">1</span>
                                <span class="px-2.5 py-1.5 text-sm t-muted cursor-not-allowed">+</span>
                            </div>
                            @else
                            <div class="flex items-center overflow-hidden" style="border:1px solid var(--t-border); border-radius: var(--t-radius-sm);">
                                <form method="POST" action="{{ route('cart.update', $item['row_id']) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="quantity" value="{{ max(0, $item['quantity'] - 1) }}">
                                    <button type="submit" class="px-3 py-1.5 text-sm transition-colors t-muted hover:t-accent">–</button>
                                </form>
                                <span class="w-8 text-center text-sm font-bold">{{ $item['quantity'] }}</span>
                                <form method="POST" action="{{ route('cart.update', $item['row_id']) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="quantity" value="{{ $item['quantity'] + 1 }}">
                                    <button type="submit" class="px-3 py-1.5 text-sm transition-colors t-muted hover:t-accent">+</button>
                                </form>
                            </div>
                            @endif

                            <form method="POST" action="{{ route('cart.remove', $item['row_id']) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1.5 transition-colors" title="Remove" style="color:#f87171;">
                                    <i class="fas fa-trash-can text-sm"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="text-xs t-muted mt-1.5">
                        Subtotal: <span class="font-bold" style="color: var(--t-text);">Rs. {{ number_format($item['line_total']) }}</span>
                    </div>
                </div>
            </div>
            @endforeach

            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('products.index') }}" class="t-btn t-btn-outline text-sm">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
                <form method="POST" action="{{ route('cart.clear') }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-sm font-semibold hover:underline transition-colors" style="color:#ef4444;">
                        Clear Cart
                    </button>
                </form>
            </div>
        </div>

        {{-- ── Summary ─────────────────────────────────────────────────── --}}
        <div>
            <div class="t-card p-5 sticky" style="top: 6rem;">
                <h2 class="font-extrabold text-lg mb-5 t-heading">Order Summary</h2>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="t-muted">Subtotal</span>
                        <span class="font-bold">Rs. {{ number_format($subtotal) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="t-muted">Delivery</span>
                        @if($deliveryCharge == 0)
                        <span class="font-bold" style="color:#16a34a;">Free</span>
                        @else
                        <span class="font-bold">Rs. {{ number_format($deliveryCharge) }}</span>
                        @endif
                    </div>

                    @if($deliveryCharge > 0 && $freeAbove > $subtotal)
                    <div class="text-xs px-3 py-2.5" style="background: rgb(var(--t-accent-rgb) / .10); color: var(--t-accent); border-radius: var(--t-radius-sm);">
                        <i class="fas fa-truck-fast mr-1.5"></i>
                        Add Rs. {{ number_format($freeAbove - $subtotal) }} more for free delivery
                        <div class="mt-2 h-1.5 rounded-full overflow-hidden" style="background: rgb(var(--t-accent-rgb) / .18);">
                            <div class="h-full rounded-full" style="width: {{ min(100, $freeAbove > 0 ? ($subtotal / $freeAbove) * 100 : 0) }}%; background: var(--app-gradient);"></div>
                        </div>
                    </div>
                    @endif

                    @if($couponDiscount > 0)
                    <div class="flex justify-between font-bold px-3 py-2" style="background:#f0fdf4; color:#15803d; border-radius: var(--t-radius-sm);">
                        <span class="flex items-center gap-1.5"><i class="fas fa-ticket text-xs"></i>{{ $couponCode }}</span>
                        <span>– Rs. {{ number_format($couponDiscount) }}</span>
                    </div>
                    @endif

                    <div class="flex justify-between font-extrabold text-base pt-3" style="border-top:1px solid var(--t-border);">
                        <span>Total</span>
                        <span class="t-price text-lg">Rs. {{ number_format($total) }}</span>
                    </div>
                </div>

                {{-- Coupon --}}
                <div class="mt-4 pt-4" style="border-top:1px solid var(--t-border);">
                    @if($couponCode)
                    <div class="flex items-center justify-between px-3 py-2.5"
                         style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius: var(--t-radius-sm);">
                        <div>
                            <span class="text-xs font-black uppercase tracking-widest" style="color:#15803d; font-family:ui-monospace,monospace;">{{ $couponCode }}</span>
                            <span class="text-xs ml-1.5" style="color:#16a34a;">applied!</span>
                        </div>
                        <form method="POST" action="{{ route('cart.coupon.remove') }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs font-semibold transition-colors" style="color:#ef4444;">
                                <i class="fas fa-times mr-1"></i>Remove
                            </button>
                        </form>
                    </div>
                    @else
                    @error('coupon_code')
                    <p class="text-xs mb-2" style="color:#ef4444;"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                    <form method="POST" action="{{ route('cart.coupon.apply') }}" class="flex gap-2">
                        @csrf
                        <input type="text" name="coupon_code" value="{{ old('coupon_code') }}" placeholder="Coupon code"
                               class="t-input flex-1 uppercase tracking-widest"
                               style="font-family:ui-monospace,monospace; @error('coupon_code') border-color:#f87171; @enderror">
                        <button type="submit" class="t-btn shrink-0 text-sm" style="background: var(--t-text); color: var(--t-surface); padding:.625rem 1rem;">
                            Apply
                        </button>
                    </form>
                    <p class="text-xs t-muted mt-1.5"><i class="fas fa-gift mr-1"></i>Have a thank-you coupon? Enter it here.</p>
                    @endif
                </div>

                {{-- Unlocked bundle deals --}}
                @if($triggeredDeals->isNotEmpty())
                <div class="mt-4 space-y-3">
                    @foreach($triggeredDeals as $deal)
                    <div class="px-3 py-3" style="background:#f0fdf4; border:1px solid #86efac; border-radius: var(--t-radius-sm);">
                        <div class="flex items-center gap-2 mb-1.5">
                            <i class="fas fa-gift text-base" style="color:#16a34a;"></i>
                            <span class="text-sm font-extrabold" style="color:#166534;">Deal Unlocked: {{ $deal->name }}</span>
                        </div>
                        <p class="text-xs mb-1.5" style="color:#15803d;">You'll receive the following for free:</p>
                        <ul class="space-y-1">
                            @foreach($deal->freeProducts as $fp)
                            <li class="flex items-center gap-2 text-xs" style="color:#166534;">
                                <i class="fas fa-circle-check shrink-0" style="color:#22c55e;"></i>{{ $fp->name }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endforeach
                </div>
                @endif

                <a href="{{ route('checkout.index') }}" class="t-btn t-btn-primary w-full mt-5 text-base py-3.5">
                    Proceed to Checkout <i class="fas fa-arrow-right"></i>
                </a>

                <div class="mt-4 space-y-2">
                    @foreach([
                        ['lock', 'Secure checkout'],
                        ['rotate-left', '7-day return policy'],
                        ['headset', 'Support: ' . \App\Models\Setting::get('shop_phone')],
                    ] as [$icon, $label])
                    <div class="flex items-center gap-2 text-xs t-muted">
                        <i class="fas fa-{{ $icon }}" style="width:1rem;"></i><span>{{ $label }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @else
    @include('theme.empty', [
        'icon'    => 'cart-shopping',
        'title'   => 'Your cart is empty',
        'text'    => "Looks like you haven't added anything yet.",
        'ctaUrl'  => route('products.index'),
        'ctaText' => 'Start Shopping',
    ])
    @endif
</div>
@endsection
