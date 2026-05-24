@extends('layouts.ecom')
@section('title', 'Order Placed Successfully')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-16">
    <div class="card p-8 text-center">

        {{-- Success icon --}}
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-check-circle text-green-500 text-4xl"></i>
        </div>

        <h1 class="text-2xl font-bold text-gray-900 mb-2">Order Placed Successfully!</h1>
        <p class="text-gray-600 mb-6">Thank you for your order. We'll confirm it shortly.</p>

        {{-- Order info box --}}
        <div class="bg-gray-50 rounded-xl p-5 mb-6 text-left space-y-3">
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Order Number</span>
                <span class="font-bold text-gray-900">{{ $order->order_number }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Customer Name</span>
                <span class="font-medium text-gray-800">{{ $order->customer_name }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Phone</span>
                <span class="font-medium text-gray-800">{{ $order->customer_phone }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Payment Method</span>
                <span class="font-medium text-gray-800">
                    {{ $order->payment_method === 'cash' ? 'Cash on Delivery' : 'Bank Transfer' }}
                </span>
            </div>
            <div class="flex justify-between text-sm border-t border-gray-200 pt-3">
                <span class="text-gray-500">Total Amount</span>
                <span class="font-bold text-primary-700 text-base">Rs. {{ number_format($order->total) }}</span>
            </div>
        </div>

        {{-- Order items --}}
        <div class="mb-6 text-left">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Items Ordered</h3>
            <div class="space-y-2">
                @foreach($order->items as $item)
                <div class="flex justify-between text-sm text-gray-600">
                    <span>{{ $item->product_name }} <span class="text-gray-400">× {{ $item->quantity }}</span></span>
                    <span class="font-medium text-gray-800">Rs. {{ number_format($item->line_total) }}</span>
                </div>
                @endforeach
                @if($order->delivery_charge > 0)
                <div class="flex justify-between text-sm text-gray-600 border-t border-gray-100 pt-2">
                    <span>Delivery</span>
                    <span>Rs. {{ number_format($order->delivery_charge) }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Status info --}}
        @if($order->payment_method === 'bank_transfer')
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 text-left">
            <div class="flex items-start gap-2">
                <i class="fas fa-info-circle text-amber-500 mt-0.5 shrink-0"></i>
                <div class="text-sm text-amber-800">
                    <strong>Bank Transfer Order:</strong> Your order will be confirmed after payment verification.
                    We'll contact you at {{ $order->customer_phone }}.
                </div>
            </div>
        </div>
        @else
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 text-left">
            <div class="flex items-start gap-2">
                <i class="fas fa-truck text-blue-500 mt-0.5 shrink-0"></i>
                <div class="text-sm text-blue-800">
                    Your order has been received and will be dispatched soon.
                    We'll call you at <strong>{{ $order->customer_phone }}</strong> to confirm delivery details.
                </div>
            </div>
        </div>
        @endif

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('order.track') }}?order={{ $order->order_number }}&phone={{ $order->customer_phone }}"
               class="btn-primary btn-lg">
                <i class="fas fa-map-marker-alt mr-2"></i> Track Order
            </a>
            <a href="{{ route('products.index') }}" class="btn-outline btn-lg">
                <i class="fas fa-shopping-bag mr-2"></i> Continue Shopping
            </a>
        </div>

        <div class="mt-6 text-sm text-gray-500">
            Need help? Call us at
            <a href="tel:{{ \App\Models\Setting::get('shop_phone') }}" class="text-primary-600 hover:underline font-medium">
                {{ \App\Models\Setting::get('shop_phone') }}
            </a>
        </div>
    </div>
</div>
@endsection
