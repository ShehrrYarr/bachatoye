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
                        <div class="text-xs text-gray-400 mb-2">{{ $item['product']->category->name }}</div>
                    @endif
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <span class="text-base font-bold text-primary-700">Rs. {{ number_format($item['price']) }}</span>
                        <div class="flex items-center gap-3">
                            {{-- Qty adjuster --}}
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
                    <div class="border-t border-gray-200 pt-3 flex justify-between font-bold text-gray-900 text-base">
                        <span>Total</span>
                        <span class="text-primary-700">Rs. {{ number_format($total) }}</span>
                    </div>
                </div>

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
