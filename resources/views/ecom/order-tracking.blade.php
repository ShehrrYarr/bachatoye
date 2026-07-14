@extends('layouts.ecom')
@section('title', 'Track Order')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-12">

    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Track Your Order</h1>
        <p class="text-gray-500">Enter your order number and phone number to track your order</p>
    </div>

    {{-- Tracking form --}}
    <div class="card p-6 mb-8">
        <form method="GET" action="{{ route('order.track') }}" class="flex flex-col sm:flex-row gap-3">
            <input type="text" name="order" value="{{ request('order') }}"
                   placeholder="Order number (e.g. ORD-20240101-0001)"
                   class="form-input flex-1">
            <input type="tel" name="phone" value="{{ request('phone') }}"
                   placeholder="Phone number"
                   class="form-input flex-1">
            <button type="submit" class="btn-primary px-6">
                <i class="fas fa-search mr-1"></i> Track
            </button>
        </form>
    </div>

    @if(isset($order))
    {{-- Order found --}}
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <div>
                <h2 class="font-bold text-gray-900">{{ $order->order_number }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">Placed on {{ $order->created_at->format('d M Y, h:i A') }}</p>
            </div>
            <span class="badge
                @if($order->status === 'delivered') bg-green-100 text-green-700
                @elseif($order->status === 'cancelled') bg-red-100 text-red-700
                @elseif($order->status === 'processing') bg-blue-100 text-blue-700
                @elseif($order->status === 'shipped') bg-purple-100 text-purple-700
                @else bg-yellow-100 text-yellow-700 @endif">
                {{ ucfirst($order->status) }}
            </span>
        </div>
        <div class="card-body space-y-6">

            {{-- Status timeline --}}
            @php
                $statuses = ['pending', 'processing', 'shipped', 'delivered'];
                $currentIndex = array_search($order->status, $statuses);
                if ($order->status === 'cancelled') $currentIndex = -1;
            @endphp

            @if($order->status !== 'cancelled')
            <div class="relative">
                <div class="flex justify-between">
                    @foreach($statuses as $i => $status)
                    <div class="flex flex-col items-center flex-1 {{ $i < count($statuses) - 1 ? 'relative' : '' }}">
                        @if($i < count($statuses) - 1)
                        <div class="absolute top-4 left-1/2 w-full h-0.5 {{ $i < $currentIndex ? 'bg-primary-500' : 'bg-gray-200' }}"></div>
                        @endif
                        <div class="w-8 h-8 rounded-full flex items-center justify-center z-10 relative
                            {{ $i <= $currentIndex ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-400' }}">
                            @if($status === 'pending') <i class="fas fa-clock text-xs"></i>
                            @elseif($status === 'processing') <i class="fas fa-box text-xs"></i>
                            @elseif($status === 'shipped') <i class="fas fa-truck text-xs"></i>
                            @else <i class="fas fa-check text-xs"></i>
                            @endif
                        </div>
                        <div class="text-xs font-medium mt-2 text-center {{ $i <= $currentIndex ? 'text-primary-700' : 'text-gray-400' }}">
                            {{ ucfirst($status) }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @else
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 flex items-center gap-3">
                <i class="fas fa-times-circle text-red-500 text-lg"></i>
                <div>
                    <div class="font-semibold text-red-700">Order Cancelled</div>
                    <div class="text-sm text-red-600">This order has been cancelled. Contact us if you have questions.</div>
                </div>
            </div>
            @endif

            {{-- Order Details --}}
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <div class="text-gray-500 text-xs mb-1">Customer Name</div>
                    <div class="font-medium text-gray-800">{{ $order->customer_name }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs mb-1">Phone</div>
                    <div class="font-medium text-gray-800">{{ $order->customer_phone }}</div>
                </div>
                <div class="col-span-2">
                    <div class="text-gray-500 text-xs mb-1">Delivery Address</div>
                    <div class="font-medium text-gray-800">{{ $order->customer_address }}, {{ $order->customer_city }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs mb-1">Payment</div>
                    <div class="font-medium text-gray-800">
                        {{ $order->payment_method === 'cash' ? 'Cash on Delivery' : 'Bank Transfer' }}
                    </div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs mb-1">Payment Status</div>
                    <span class="badge {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ ucfirst($order->payment_status) }}
                    </span>
                </div>
            </div>

            {{-- Items --}}
            <div>
                <h3 class="font-semibold text-gray-800 text-sm mb-3">Items</h3>
                <div class="space-y-2">
                    @foreach($order->items as $item)
                    <div class="flex items-center gap-3">
                        @if($item->product && $item->product->primary_image_url)
                        <img src="{{ $item->product->primary_image_url }}" loading="lazy" class="w-10 h-10 object-cover rounded-lg bg-gray-100 shrink-0">
                        @else
                        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center shrink-0">
                            <i class="fas fa-box text-gray-400 text-sm"></i>
                        </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-800 truncate">{{ $item->product_name }}</div>
                            <div class="text-xs text-gray-500">Qty: {{ $item->quantity }} × Rs. {{ number_format($item->unit_price) }}</div>
                        </div>
                        <div class="text-sm font-semibold text-gray-800 shrink-0">
                            Rs. {{ number_format($item->line_total) }}
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="border-t border-gray-200 mt-4 pt-3 space-y-1">
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Subtotal</span>
                        <span>Rs. {{ number_format($order->subtotal) }}</span>
                    </div>
                    @if($order->delivery_charge > 0)
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Delivery</span>
                        <span>Rs. {{ number_format($order->delivery_charge) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between font-bold text-gray-900 text-base pt-1">
                        <span>Total</span>
                        <span class="text-primary-700">Rs. {{ number_format($order->total) }}</span>
                    </div>
                </div>
            </div>

            @if($order->notes)
            <div class="bg-gray-50 rounded-xl p-3 text-sm text-gray-600">
                <span class="font-medium text-gray-700">Notes:</span> {{ $order->notes }}
            </div>
            @endif
        </div>
    </div>

    @elseif(request('order') || request('phone'))
    {{-- No order found --}}
    <div class="card p-8 text-center">
        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-search text-red-400 text-2xl"></i>
        </div>
        <h3 class="font-bold text-gray-800 mb-2">Order Not Found</h3>
        <p class="text-gray-500 text-sm">No order found matching these details. Please check your order number and phone number.</p>
    </div>
    @endif

    <div class="text-center mt-8 text-sm text-gray-500">
        Need help?
        <a href="tel:{{ \App\Models\Setting::get('shop_phone') }}" class="text-primary-600 hover:underline font-medium ml-1">
            <i class="fas fa-phone-alt mr-1"></i>{{ \App\Models\Setting::get('shop_phone') }}
        </a>
    </div>
</div>
@endsection
