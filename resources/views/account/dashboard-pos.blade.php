@extends('layouts.ecom')
@section('title', 'My Account')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col lg:flex-row gap-6">

        {{-- Sidebar --}}
        <aside class="lg:w-64 shrink-0">
            @include('account._sidebar')
        </aside>

        {{-- Main --}}
        <main class="flex-1 min-w-0 space-y-5">

            {{-- Welcome --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6">
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Welcome, {{ explode(' ', $customer->name)[0] }}!</h1>
                <p class="text-sm text-gray-500 mt-1">Here's your account summary.</p>
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5">
                    <div class="text-xl sm:text-2xl font-bold text-gray-900">{{ $totalOrders }}</div>
                    <div class="text-xs sm:text-sm text-gray-500 mt-0.5">Total Orders</div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5">
                    <div class="text-xl sm:text-2xl font-bold text-green-600">Rs. {{ number_format($totalSpent) }}</div>
                    <div class="text-xs sm:text-sm text-gray-500 mt-0.5">Total Purchased</div>
                </div>
                <div class="bg-white rounded-2xl border {{ $balance < 0 ? 'border-red-200 bg-red-50' : ($balance > 0 ? 'border-green-200 bg-green-50' : 'border-gray-100') }} shadow-sm p-4 sm:p-5 col-span-2 sm:col-span-1">
                    <div class="text-xl sm:text-2xl font-bold {{ $balance < 0 ? 'text-red-600' : ($balance > 0 ? 'text-green-600' : 'text-gray-500') }}">
                        Rs. {{ number_format(abs($balance)) }}
                    </div>
                    <div class="text-xs sm:text-sm text-gray-500 mt-0.5">
                        @if($balance < 0) Amount Owed
                        @elseif($balance > 0) Credit Balance
                        @else Settled
                        @endif
                    </div>
                </div>
            </div>

            {{-- Recent Orders --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-900 text-sm sm:text-base">Recent Orders</h2>
                    <a href="{{ route('account.orders') }}" class="text-xs sm:text-sm text-primary-600 hover:underline">View all</a>
                </div>
                @if($recentOrders->isEmpty())
                <div class="px-6 py-10 text-center text-gray-400">
                    <i class="fas fa-box-open text-3xl mb-3"></i>
                    <p class="text-sm">No orders yet.</p>
                </div>
                @else
                <div class="divide-y divide-gray-50">
                    @foreach($recentOrders as $order)
                    <a href="{{ route('account.orders.show', $order) }}"
                       class="flex items-center justify-between gap-3 px-5 sm:px-6 py-3.5 hover:bg-gray-50 transition-colors">
                        <div class="min-w-0">
                            <div class="font-semibold text-xs sm:text-sm text-gray-900 font-mono">{{ $order->order_number }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">{{ $order->created_at->format('d M Y') }} · {{ $order->items->count() }} item(s)</div>
                        </div>
                        <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                            @php
                                $pmLabels = ['cash'=>'Cash','bank_transfer'=>'Bank','khata'=>'Khata','partial'=>'Partial','split'=>'Split'];
                            @endphp
                            <span class="hidden sm:inline text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-medium">
                                {{ $pmLabels[$order->payment_method] ?? $order->payment_method }}
                            </span>
                            <span class="text-xs sm:text-sm font-bold text-gray-800">Rs. {{ number_format($order->total) }}</span>
                            <i class="fas fa-chevron-right text-gray-300 text-xs"></i>
                        </div>
                    </a>
                    @endforeach
                </div>
                @endif
            </div>

        </main>
    </div>
</div>
@endsection
