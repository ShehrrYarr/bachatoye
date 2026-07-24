@extends('layouts.ecom')
@section('title', 'Track Order')

@section('content')
<div class="t-container py-8 md:py-12" style="max-width: 44rem;">

    <div class="text-center mb-8">
        <span class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4"
              style="background: rgb(var(--t-accent-rgb) / .12);">
            <i class="fas fa-location-crosshairs text-xl t-accent"></i>
        </span>
        <h1 class="text-2xl md:text-3xl font-extrabold t-heading">Track Your Order</h1>
        <p class="t-muted mt-2 text-sm">Enter your order number and phone number to see where it is</p>
    </div>

    <div class="t-card p-5 md:p-6 mb-8">
        <form method="GET" action="{{ route('order.track') }}" class="flex flex-col sm:flex-row gap-3">
            <input type="text" name="order" value="{{ request('order') }}"
                   placeholder="Order number (e.g. ORD-20240101-0001)" class="t-input flex-1">
            <input type="tel" name="phone" value="{{ request('phone') }}"
                   placeholder="Phone number" class="t-input flex-1">
            <button type="submit" class="t-btn t-btn-primary shrink-0 px-6">
                <i class="fas fa-magnifying-glass"></i> Track
            </button>
        </form>
    </div>

    @if(isset($order))
    @php
        $statuses     = ['pending', 'processing', 'shipped', 'delivered'];
        $currentIndex = array_search($order->status, $statuses);
        if ($order->status === 'cancelled') $currentIndex = -1;
        $statusStyles = [
            'delivered'  => ['#dcfce7', '#15803d'],
            'cancelled'  => ['#fee2e2', '#b91c1c'],
            'processing' => ['#dbeafe', '#1d4ed8'],
            'shipped'    => ['#f3e8ff', '#7e22ce'],
        ];
        [$badgeBg, $badgeFg] = $statusStyles[$order->status] ?? ['#fef3c7', '#a16207'];
    @endphp

    <div class="t-card overflow-hidden">
        <div class="px-5 py-4 flex items-center justify-between gap-3" style="border-bottom:1px solid var(--t-border);">
            <div class="min-w-0">
                <h2 class="font-extrabold t-heading" style="font-family:ui-monospace,monospace;">{{ $order->order_number }}</h2>
                <p class="text-xs t-muted mt-0.5">Placed on {{ $order->created_at->format('d M Y, H:i') }}</p>
            </div>
            <span class="text-xs font-bold px-3 py-1.5 rounded-full shrink-0" style="background: {{ $badgeBg }}; color: {{ $badgeFg }};">
                {{ ucfirst($order->status) }}
            </span>
        </div>

        <div class="p-5 space-y-6">

            {{-- Timeline --}}
            @if($order->status !== 'cancelled')
            <div class="flex justify-between">
                @foreach($statuses as $i => $status)
                @php $reached = $i <= $currentIndex; @endphp
                <div class="flex flex-col items-center flex-1 relative">
                    @if($i < count($statuses) - 1)
                    <span class="absolute h-0.5" style="top:1rem; left:50%; width:100%; background: {{ $i < $currentIndex ? 'var(--app-primary)' : 'var(--t-border)' }};"></span>
                    @endif
                    <span class="w-8 h-8 rounded-full flex items-center justify-center relative" style="z-index:1;
                          {{ $reached ? 'background: var(--app-gradient); color:#fff;' : 'background: var(--t-surface-2); color: var(--t-muted); border:1px solid var(--t-border);' }}">
                        @if($status === 'pending')        <i class="fas fa-clock text-xs"></i>
                        @elseif($status === 'processing') <i class="fas fa-box text-xs"></i>
                        @elseif($status === 'shipped')    <i class="fas fa-truck text-xs"></i>
                        @else                             <i class="fas fa-check text-xs"></i>
                        @endif
                    </span>
                    <span class="text-[11px] font-bold mt-2 text-center {{ $reached ? 't-accent' : 't-muted' }}">
                        {{ ucfirst($status) }}
                    </span>
                </div>
                @endforeach
            </div>
            @else
            <div class="flex items-center gap-3 p-4" style="background:#fef2f2; border:1px solid #fecaca; border-radius: var(--t-radius-sm);">
                <i class="fas fa-circle-xmark text-lg shrink-0" style="color:#ef4444;"></i>
                <div>
                    <div class="font-bold" style="color:#b91c1c;">Order Cancelled</div>
                    <div class="text-sm" style="color:#dc2626;">This order has been cancelled. Contact us if you have questions.</div>
                </div>
            </div>
            @endif

            {{-- Details --}}
            <div class="grid grid-cols-2 gap-4 text-sm p-4" style="background: var(--t-surface-2); border-radius: var(--t-radius-sm);">
                <div>
                    <div class="text-xs t-muted mb-1">Customer Name</div>
                    <div class="font-semibold">{{ $order->customer_name }}</div>
                </div>
                <div>
                    <div class="text-xs t-muted mb-1">Phone</div>
                    <div class="font-semibold">{{ $order->customer_phone }}</div>
                </div>
                <div class="col-span-2">
                    <div class="text-xs t-muted mb-1">Delivery Address</div>
                    <div class="font-semibold">{{ $order->customer_address }}, {{ $order->customer_city }}</div>
                </div>
                <div>
                    <div class="text-xs t-muted mb-1">Payment</div>
                    <div class="font-semibold">{{ $order->payment_method === 'cash' ? 'Cash on Delivery' : 'Bank Transfer' }}</div>
                </div>
                <div>
                    <div class="text-xs t-muted mb-1">Payment Status</div>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full"
                          style="{{ $order->payment_status === 'paid' ? 'background:#dcfce7; color:#15803d;' : 'background:#fef3c7; color:#a16207;' }}">
                        {{ ucfirst($order->payment_status) }}
                    </span>
                </div>
            </div>

            {{-- Items --}}
            <div>
                <h3 class="font-extrabold text-sm mb-3 t-heading">Items</h3>
                <div class="space-y-2.5">
                    @foreach($order->items as $item)
                    <div class="flex items-center gap-3">
                        @if($item->product && $item->product->primary_image_url)
                        <img src="{{ $item->product->primary_image_url }}" loading="lazy" alt=""
                             class="w-11 h-11 object-contain shrink-0 p-0.5"
                             style="border-radius: var(--t-radius-sm); background:#fff; border:1px solid var(--t-border);">
                        @else
                        <span class="w-11 h-11 flex items-center justify-center shrink-0"
                              style="border-radius: var(--t-radius-sm); background: var(--t-surface-2);">
                            <i class="fas fa-box t-muted text-sm"></i>
                        </span>
                        @endif
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold truncate">{{ $item->product_name }}</div>
                            <div class="text-xs t-muted">Qty: {{ $item->quantity }} × Rs. {{ number_format($item->unit_price) }}</div>
                        </div>
                        <div class="text-sm font-bold shrink-0">Rs. {{ number_format($item->line_total) }}</div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-4 pt-3 space-y-1.5" style="border-top:1px solid var(--t-border);">
                    <div class="flex justify-between text-sm">
                        <span class="t-muted">Subtotal</span>
                        <span class="font-semibold">Rs. {{ number_format($order->subtotal) }}</span>
                    </div>
                    @if($order->delivery_charge > 0)
                    <div class="flex justify-between text-sm">
                        <span class="t-muted">Delivery</span>
                        <span class="font-semibold">Rs. {{ number_format($order->delivery_charge) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between font-extrabold text-base pt-1">
                        <span>Total</span>
                        <span class="t-price">Rs. {{ number_format($order->total) }}</span>
                    </div>
                </div>
            </div>

            @if($order->notes)
            <div class="p-3 text-sm t-muted" style="background: var(--t-surface-2); border-radius: var(--t-radius-sm);">
                <span class="font-semibold" style="color: var(--t-text);">Notes:</span> {{ $order->notes }}
            </div>
            @endif
        </div>
    </div>

    @elseif(request('order') || request('phone'))
    @include('theme.empty', [
        'icon'  => 'magnifying-glass',
        'title' => 'Order Not Found',
        'text'  => 'No order matches these details. Please check your order number and phone number.',
    ])
    @endif

    <p class="text-center mt-8 text-sm t-muted">
        Need help?
        <a href="tel:{{ \App\Models\Setting::get('shop_phone') }}" class="font-semibold t-accent hover:underline ml-1">
            <i class="fas fa-phone mr-1"></i>{{ \App\Models\Setting::get('shop_phone') }}
        </a>
    </p>
</div>
@endsection
