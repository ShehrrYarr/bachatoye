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
                    <p>No orders yet.</p>
                    @if($customer->source === 'online')
                    <a href="{{ route('products.index') }}" class="inline-block mt-4 text-sm text-primary-600 font-medium hover:underline">Start shopping</a>
                    @endif
                </div>
                @else
                <div class="divide-y divide-gray-50">
                    @foreach($orders as $order)
                    @php
                        $pmLabels = ['cash'=>'Cash','bank_transfer'=>'Bank Transfer','khata'=>'Khata','partial'=>'Partial','split'=>'Split'];
                        $statusColors = [
                            'pending'    => 'bg-yellow-100 text-yellow-700',
                            'processing' => 'bg-blue-100 text-blue-700',
                            'shipped'    => 'bg-indigo-100 text-indigo-700',
                            'delivered'  => 'bg-green-100 text-green-700',
                            'cancelled'  => 'bg-red-100 text-red-700',
                        ];
                    @endphp
                    <a href="{{ route('account.orders.show', $order) }}"
                       class="block px-5 sm:px-6 py-4 hover:bg-gray-50 transition-colors">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-semibold text-sm text-gray-900 font-mono">{{ $order->order_number }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ $order->created_at->format('d M Y, h:i A') }}</div>
                                <div class="flex flex-wrap items-center gap-2 mt-2">
                                    <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-600' }}">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                    @if($customer->source === 'pos')
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 font-medium">
                                        {{ $pmLabels[$order->payment_method] ?? $order->payment_method }}
                                    </span>
                                    @else
                                    <span class="text-xs text-gray-500">Payment: <span class="font-medium">{{ ucfirst($order->payment_status ?? '') }}</span></span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="font-bold text-sm text-gray-900">Rs. {{ number_format($order->total) }}</div>
                                @if($customer->source === 'pos' && $order->payment_method === 'partial')
                                <div class="text-xs text-gray-400 mt-0.5">Paid: Rs. {{ number_format($order->amount_paid) }}</div>
                                @endif
                                <span class="text-xs text-primary-600 mt-1 inline-block">Details <i class="fas fa-arrow-right text-[10px]"></i></span>
                            </div>
                        </div>
                    </a>
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
