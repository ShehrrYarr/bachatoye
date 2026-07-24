@extends('layouts.ecom')
@section('title', 'Order Placed Successfully')

@section('content')
<div class="t-container py-10 md:py-16" style="max-width: 42rem;">

    <div class="t-card overflow-hidden">
        {{-- Success banner --}}
        <div class="px-6 py-10 text-center" style="background: linear-gradient(160deg, #16a34a 0%, #15803d 100%);">
            <span class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-5"
                  style="background: rgba(255,255,255,.18);">
                <i class="fas fa-circle-check text-white" style="font-size:2.5rem;"></i>
            </span>
            <h1 class="text-2xl md:text-3xl font-extrabold text-white t-heading">Order Placed Successfully!</h1>
            <p class="mt-2" style="color:rgba(255,255,255,.9);">Thank you for your order. We'll confirm it shortly.</p>
            <div class="inline-block mt-5 px-5 py-2.5 rounded-full" style="background: rgba(255,255,255,.18);">
                <span class="text-xs uppercase tracking-widest block" style="color:rgba(255,255,255,.8);">Order Number</span>
                <span class="text-lg font-black text-white" style="font-family:ui-monospace,monospace;">{{ $order->order_number }}</span>
            </div>
        </div>

        <div class="p-6 space-y-6">
            {{-- Details --}}
            <div class="p-5 space-y-3" style="background: var(--t-surface-2); border-radius: var(--t-radius-sm);">
                @foreach([
                    ['Customer Name', $order->customer_name],
                    ['Phone', $order->customer_phone],
                    ['Payment Method', $order->payment_method === 'cash' ? 'Cash on Delivery' : 'Bank Transfer'],
                ] as [$label, $value])
                <div class="flex justify-between text-sm gap-4">
                    <span class="t-muted shrink-0">{{ $label }}</span>
                    <span class="font-semibold text-right">{{ $value }}</span>
                </div>
                @endforeach
                <div class="flex justify-between text-sm pt-3" style="border-top:1px solid var(--t-border);">
                    <span class="t-muted">Total Amount</span>
                    <span class="t-price text-base">Rs. {{ number_format($order->total) }}</span>
                </div>
            </div>

            {{-- Items --}}
            <div>
                <h3 class="text-sm font-extrabold mb-3 t-heading">Items Ordered</h3>
                <div class="space-y-2">
                    @foreach($order->items as $item)
                    <div class="flex justify-between text-sm gap-4">
                        <span>{{ $item->product_name }} <span class="t-muted">× {{ $item->quantity }}</span></span>
                        <span class="font-semibold shrink-0">Rs. {{ number_format($item->line_total) }}</span>
                    </div>
                    @endforeach
                    @if($order->delivery_charge > 0)
                    <div class="flex justify-between text-sm pt-2" style="border-top:1px solid var(--t-border);">
                        <span class="t-muted">Delivery</span>
                        <span class="font-semibold">Rs. {{ number_format($order->delivery_charge) }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- What happens next --}}
            @if($order->payment_method === 'bank_transfer')
            <div class="flex items-start gap-3 p-4" style="background:#fffbeb; border:1px solid #fcd34d; border-radius: var(--t-radius-sm);">
                <i class="fas fa-circle-info mt-0.5 shrink-0" style="color:#f59e0b;"></i>
                <div class="text-sm" style="color:#92400e;">
                    <strong>Bank Transfer Order:</strong> Your order will be confirmed after payment verification.
                    We'll contact you at {{ $order->customer_phone }}.
                </div>
            </div>
            @else
            <div class="flex items-start gap-3 p-4" style="background:#eff6ff; border:1px solid #bfdbfe; border-radius: var(--t-radius-sm);">
                <i class="fas fa-truck mt-0.5 shrink-0" style="color:#3b82f6;"></i>
                <div class="text-sm" style="color:#1e40af;">
                    Your order has been received and will be dispatched soon.
                    We'll call you at <strong>{{ $order->customer_phone }}</strong> to confirm delivery details.
                </div>
            </div>
            @endif

            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('order.track') }}?order={{ $order->order_number }}&phone={{ $order->customer_phone }}"
                   class="t-btn t-btn-primary flex-1 py-3">
                    <i class="fas fa-location-dot"></i> Track Order
                </a>
                <a href="{{ route('products.index') }}" class="t-btn t-btn-outline flex-1 py-3">
                    <i class="fas fa-bag-shopping"></i> Continue Shopping
                </a>
            </div>

            <p class="text-center text-sm t-muted">
                Need help? Call us at
                <a href="tel:{{ \App\Models\Setting::get('shop_phone') }}" class="font-semibold t-accent hover:underline">
                    {{ \App\Models\Setting::get('shop_phone') }}
                </a>
            </p>
        </div>
    </div>
</div>
@endsection
