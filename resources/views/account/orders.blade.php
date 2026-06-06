@extends('layouts.ecom')
@section('title', 'My Orders')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-10">
    <div class="flex flex-col lg:flex-row gap-6">

        <aside class="lg:w-64 shrink-0">
            @include('account._sidebar')
        </aside>

        <main class="flex-1 min-w-0">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h1 class="font-bold text-gray-900 text-lg">My Orders</h1>
                </div>

                @if($orders->isEmpty())
                <div class="px-6 py-14 text-center text-gray-400">
                    <i class="fas fa-box-open text-4xl mb-3"></i>
                    <p>No orders placed yet.</p>
                    <a href="{{ route('products.index') }}" class="inline-block mt-4 text-sm text-primary-600 font-medium hover:underline">Start shopping</a>
                </div>
                @else
                <div class="divide-y divide-gray-50">
                    @foreach($orders as $order)
                    @php
                        $statusColors = [
                            'pending'    => 'bg-yellow-100 text-yellow-700',
                            'processing' => 'bg-blue-100 text-blue-700',
                            'shipped'    => 'bg-indigo-100 text-indigo-700',
                            'delivered'  => 'bg-green-100 text-green-700',
                            'cancelled'  => 'bg-red-100 text-red-700',
                        ];
                        $paymentColors = [
                            'pending'  => 'text-yellow-600',
                            'paid'     => 'text-green-600',
                            'failed'   => 'text-red-600',
                        ];
                    @endphp
                    <div class="px-6 py-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold text-gray-900">{{ $order->order_number }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ $order->created_at->format('d M Y, h:i A') }}</div>
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-600' }}">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        Payment:
                                        <span class="font-medium {{ $paymentColors[$order->payment_status] ?? '' }}">
                                            {{ ucfirst($order->payment_status) }}
                                        </span>
                                    </span>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="font-bold text-gray-900">Rs. {{ number_format($order->total) }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</div>
                                <a href="{{ route('account.orders.show', $order) }}"
                                   class="inline-block mt-2 text-xs text-primary-600 hover:underline">
                                    View Details <i class="fas fa-arrow-right ml-0.5 text-[10px]"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                @if($orders->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $orders->links() }}
                </div>
                @endif
                @endif
            </div>
        </main>

    </div>
</div>
@endsection
