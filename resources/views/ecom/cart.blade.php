@extends('layouts.ecom')
@section('title', 'Shopping Cart')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">

    <nav class="text-sm text-gray-500 mb-6 flex items-center gap-2">
        <a href="{{ route('home') }}" class="hover:text-primary-600">Home</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-800 font-medium">Shopping Cart</span>
    </nav>

    @if(count($items) > 0)
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- Cart Items --}}
        <div class="lg:col-span-2 space-y-4">
            <h1 class="text-xl font-bold text-gray-900 mb-4">Your Cart ({{ collect($items)->sum('quantity') }} items)</h1>

            @foreach($items as $item)
            <div class="card flex items-start gap-4 p-4">
                <a href="{{ route('products.show', $item['product']->slug) }}" class="shrink-0">
                    <img src="{{ $item['product']->primary_image_url }}" alt="{{ $item['product']->name }}"
                         class="w-20 h-20 object-cover rounded-xl bg-gray-100">
                </a>
                <div class="flex-1 min-w-0">
                    <a href="{{ route('products.show', $item['product']->slug) }}"
                       class="font-semibold text-gray-800 hover:text-primary-600 text-sm leading-snug block mb-1">{{ $item['product']->name }}</a>
                    @if($item['product']->category)
                        <div class="text-xs text-gray-400 mb-1">{{ $item['product']->category->name }}</div>
                    @endif
                    @if($item['color_name'])
                        <div class="flex items-center gap-1.5 mb-1">
                            @if($item['color_hex'])
                                <span class="w-3.5 h-3.5 rounded-full border border-gray-300 shrink-0"
                                      style="background: {{ $item['color_hex'] }}"></span>
                            @endif
                            <span class="text-xs text-gray-500">{{ $item['color_name'] }}</span>
                        </div>
                    @endif
                    @if(!empty($item['attr_option']))
                        <div class="text-xs text-indigo-600 font-medium mb-1">
                            <i class="fas fa-tag text-[10px] mr-1 opacity-70"></i>{{ $item['attr_option'] }}
                        </div>
                    @endif
                    @if(!empty($item['serial_id']))
                        <div class="text-xs text-purple-600 font-medium mb-1">
                            <i class="fas fa-mobile-alt text-[10px] mr-1 opacity-70"></i>Used unit{{ !empty($item['serial_label']) ? ': '.$item['serial_label'] : '' }}
                        </div>
                    @endif
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <span class="text-base font-bold text-primary-700">Rs. {{ number_format($item['price']) }}</span>
                        <div class="flex items-center gap-3">
                            {{-- Qty adjuster — fixed at 1 for one-of-a-kind used units --}}
                            @if(!empty($item['serial_id']))
                            <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden opacity-60">
                                <span class="px-2.5 py-1.5 text-gray-300 text-sm cursor-not-allowed">–</span>
                                <span class="w-8 text-center text-sm font-semibold">1</span>
                                <span class="px-2.5 py-1.5 text-gray-300 text-sm cursor-not-allowed">+</span>
                            </div>
                            @else
                            <div class="flex items-center border border-gray-300 rounded-xl overflow-hidden">
                                <form method="POST" action="{{ route('cart.update', $item['row_id']) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="quantity" value="{{ max(0, $item['quantity'] - 1) }}">
                                    <button type="submit"
                                            class="px-2.5 py-1.5 text-gray-600 hover:bg-gray-50 transition-colors text-sm">–</button>
                                </form>
                                <span class="w-8 text-center text-sm font-semibold">{{ $item['quantity'] }}</span>
                                <form method="POST" action="{{ route('cart.update', $item['row_id']) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="quantity" value="{{ $item['quantity'] + 1 }}">
                                    <button type="submit"
                                            class="px-2.5 py-1.5 text-gray-600 hover:bg-gray-50 transition-colors text-sm">+</button>
                                </form>
                            </div>
                            @endif
                            {{-- Remove --}}
                            <form method="POST" action="{{ route('cart.remove', $item['row_id']) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-600 transition-colors p-1"
                                        title="Remove">
                                    <i class="fas fa-trash-alt text-sm"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        Subtotal: <span class="font-semibold text-gray-700">Rs. {{ number_format($item['line_total']) }}</span>
                    </div>
                </div>
            </div>
            @endforeach

            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('products.index') }}" class="btn-outline btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Continue Shopping
                </a>
                <form method="POST" action="{{ route('cart.clear') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-red-500 hover:text-red-700 hover:underline transition-colors">
                        Clear Cart
                    </button>
                </form>
            </div>
        </div>

        {{-- Order Summary --}}
        <div>
            @php
                $freeAbove = (float) \App\Models\Setting::get('free_delivery_above', 5000);
            @endphp
            <div class="card p-5 sticky top-24">
                <h2 class="font-bold text-gray-900 text-lg mb-5">Order Summary</h2>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal</span>
                        <span class="font-medium text-gray-800">Rs. {{ number_format($subtotal) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Delivery</span>
                        @if($deliveryCharge == 0)
                            <span class="text-green-600 font-semibold">Free</span>
                        @else
                            <span class="font-medium text-gray-800">Rs. {{ number_format($deliveryCharge) }}</span>
                        @endif
                    </div>
                    @if($deliveryCharge > 0)
                    <div class="text-xs text-blue-600 bg-blue-50 rounded-lg px-3 py-2">
                        Add Rs. {{ number_format($freeAbove - $subtotal) }} more for free delivery
                    </div>
                    @endif

                    @if($couponDiscount > 0)
                    <div class="flex justify-between text-green-700 font-medium bg-green-50 rounded-lg px-3 py-2">
                        <span class="flex items-center gap-1.5">
                            <i class="fas fa-ticket-alt text-xs"></i>
                            {{ $couponCode }}
                        </span>
                        <span>– Rs. {{ number_format($couponDiscount) }}</span>
                    </div>
                    @endif

                    <div class="border-t border-gray-200 pt-3 flex justify-between font-bold text-gray-900 text-base">
                        <span>Total</span>
                        <span class="text-primary-700">Rs. {{ number_format($total) }}</span>
                    </div>
                </div>

                {{-- Coupon box --}}
                <div class="mt-4 border-t border-gray-100 pt-4">
                    @if($couponCode)
                        <div class="flex items-center justify-between bg-green-50 border border-green-200 rounded-xl px-3 py-2.5">
                            <div>
                                <span class="text-xs font-bold text-green-700 uppercase tracking-widest font-mono">{{ $couponCode }}</span>
                                <span class="text-xs text-green-600 ml-1.5">applied!</span>
                            </div>
                            <form method="POST" action="{{ route('cart.coupon.remove') }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-600 text-xs font-medium transition-colors">
                                    <i class="fas fa-times mr-1"></i>Remove
                                </button>
                            </form>
                        </div>
                    @else
                        @error('coupon_code')
                            <p class="text-red-500 text-xs mb-2">
                                <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                            </p>
                        @enderror
                        <form method="POST" action="{{ route('cart.coupon.apply') }}" class="flex gap-2">
                            @csrf
                            <input type="text" name="coupon_code"
                                   value="{{ old('coupon_code') }}"
                                   placeholder="Coupon code"
                                   class="flex-1 text-sm border border-gray-300 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 font-mono tracking-widest uppercase @error('coupon_code') border-red-400 @enderror">
                            <button type="submit"
                                    class="shrink-0 bg-gray-800 hover:bg-gray-900 text-white text-sm font-semibold px-4 py-2 rounded-xl transition-colors">
                                Apply
                            </button>
                        </form>
                        <p class="text-xs text-gray-400 mt-1.5">
                            <i class="fas fa-gift mr-1"></i>Have a thank-you coupon? Enter it here.
                        </p>
                    @endif
                </div>

                {{-- Bundle Free deal banners --}}
                @if($triggeredDeals->isNotEmpty())
                <div class="mt-4 space-y-3">
                    @foreach($triggeredDeals as $deal)
                    <div class="bg-green-50 border border-green-300 rounded-xl px-3 py-3">
                        <div class="flex items-center gap-2 mb-1.5">
                            <i class="fas fa-gift text-green-600 text-base"></i>
                            <span class="text-sm font-bold text-green-800">Deal Unlocked: {{ $deal->name }}</span>
                        </div>
                        <p class="text-xs text-green-700 mb-1.5">You'll receive the following for free:</p>
                        <ul class="space-y-1">
                            @foreach($deal->freeProducts as $fp)
                            <li class="flex items-center gap-2 text-xs text-green-800">
                                <i class="fas fa-check-circle text-green-500 shrink-0"></i>
                                <span>{{ $fp->name }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endforeach
                </div>
                @endif

                <a href="{{ route('checkout.index') }}" class="btn-primary btn-lg w-full justify-center mt-5">
                    Proceed to Checkout <i class="fas fa-arrow-right ml-1"></i>
                </a>

                <div class="mt-4 space-y-2">
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <i class="fas fa-lock text-gray-400"></i>
                        <span>Secure checkout</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <i class="fas fa-undo text-gray-400"></i>
                        <span>7-day return policy</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <i class="fas fa-headset text-gray-400"></i>
                        <span>Support: {{ \App\Models\Setting::get('shop_phone') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @else
    {{-- Empty cart --}}
    <div class="text-center py-24">
        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-shopping-cart text-4xl text-gray-300"></i>
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-2">Your cart is empty</h2>
        <p class="text-gray-500 mb-8">Looks like you haven't added anything yet.</p>
        <a href="{{ route('products.index') }}" class="btn-primary btn-lg">
            <i class="fas fa-shopping-bag mr-2"></i> Start Shopping
        </a>
    </div>
    @endif
</div>
@endsection
