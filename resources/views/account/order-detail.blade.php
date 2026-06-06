@extends('layouts.ecom')
@section('title', 'Order ' . $order->order_number)

@section('content')
<div class="max-w-7xl mx-auto px-4 py-10">
    <div class="flex flex-col lg:flex-row gap-6">

        <aside class="lg:w-64 shrink-0">
            @include('account._sidebar')
        </aside>

        <main class="flex-1 min-w-0 space-y-5">

            {{-- Back link + header --}}
            <div class="flex items-center gap-3">
                <a href="{{ route('account.orders') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
                    <i class="fas fa-arrow-left text-xs"></i> Back to Orders
                </a>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">{{ $order->order_number }}</h1>
                        <p class="text-sm text-gray-500 mt-0.5">Placed on {{ $order->created_at->format('d M Y, h:i A') }}</p>
                    </div>
                    @php
                        $statusColors = [
                            'pending'    => 'bg-yellow-100 text-yellow-700',
                            'processing' => 'bg-blue-100 text-blue-700',
                            'shipped'    => 'bg-indigo-100 text-indigo-700',
                            'delivered'  => 'bg-green-100 text-green-700',
                            'cancelled'  => 'bg-red-100 text-red-700',
                        ];
                    @endphp
                    <span class="px-3 py-1.5 rounded-full text-sm font-semibold {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>

                {{-- Status tracker --}}
                @php
                    $steps  = ['pending', 'processing', 'shipped', 'delivered'];
                    $current = array_search($order->status, $steps);
                @endphp
                @if($order->status !== 'cancelled')
                <div class="mt-6">
                    <div class="flex items-center gap-0">
                        @foreach($steps as $i => $step)
                        <div class="flex items-center {{ $i < count($steps) - 1 ? 'flex-1' : '' }}">
                            <div class="flex flex-col items-center">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                                    {{ $current !== false && $i <= $current ? 'text-white' : 'bg-gray-100 text-gray-400' }}"
                                    style="{{ $current !== false && $i <= $current ? 'background: var(--app-gradient, linear-gradient(135deg, #be123c 0%, #881337 100%))' : '' }}">
                                    @if($current !== false && $i < $current)
                                        <i class="fas fa-check text-xs"></i>
                                    @else
                                        {{ $i + 1 }}
                                    @endif
                                </div>
                                <div class="text-xs mt-1 font-medium whitespace-nowrap {{ $current !== false && $i <= $current ? 'text-primary-600' : 'text-gray-400' }}">
                                    {{ ucfirst($step) }}
                                </div>
                            </div>
                            @if($i < count($steps) - 1)
                            <div class="flex-1 h-0.5 mx-1 mb-4 {{ $current !== false && $i < $current ? 'bg-primary-500' : 'bg-gray-200' }}"></div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- Delivery info --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <h2 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                        <i class="fas fa-map-marker-alt text-primary-500 text-sm"></i>
                        Delivery Address
                    </h2>
                    <div class="text-sm text-gray-700 space-y-1">
                        <div class="font-medium">{{ $order->customer_name }}</div>
                        <div>{{ $order->customer_phone }}</div>
                        <div>{{ $order->delivery_address }}</div>
                        @if($order->city)<div>{{ $order->city }}</div>@endif
                        @if($order->delivery_notes)
                        <div class="text-gray-500 text-xs mt-2">Note: {{ $order->delivery_notes }}</div>
                        @endif
                    </div>
                </div>

                {{-- Payment info --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <h2 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                        <i class="fas fa-credit-card text-primary-500 text-sm"></i>
                        Payment
                    </h2>
                    <div class="text-sm space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Method</span>
                            <span class="font-medium text-gray-800">{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Status</span>
                            <span class="font-semibold {{ $order->payment_status === 'paid' ? 'text-green-600' : ($order->payment_status === 'failed' ? 'text-red-600' : 'text-yellow-600') }}">
                                {{ ucfirst($order->payment_status) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Items --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-900">Order Items</h2>
                </div>
                <div class="divide-y divide-gray-50">
                    @foreach($order->items as $item)
                    <div class="px-6 py-4 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <div class="font-medium text-sm text-gray-800">{{ $item->product_name }}</div>
                            @if($item->color_name)
                            <div class="text-xs text-gray-400 mt-0.5">Color: {{ $item->color_name }}</div>
                            @endif
                            <div class="text-xs text-gray-400">Qty: {{ $item->quantity }}</div>
                        </div>
                        <div class="text-sm font-semibold text-gray-900 shrink-0">
                            Rs. {{ number_format($item->line_total) }}
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="px-6 py-4 border-t border-gray-100 space-y-1.5">
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Subtotal</span><span>Rs. {{ number_format($order->subtotal) }}</span>
                    </div>
                    @if($order->delivery_charge > 0)
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Delivery</span><span>Rs. {{ number_format($order->delivery_charge) }}</span>
                    </div>
                    @endif
                    @if($order->coupon_discount > 0)
                    <div class="flex justify-between text-sm text-green-600">
                        <span>Discount</span><span>−Rs. {{ number_format($order->coupon_discount) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between font-bold text-gray-900 pt-2 border-t border-gray-100">
                        <span>Total</span><span>Rs. {{ number_format($order->total) }}</span>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>
@endsection
