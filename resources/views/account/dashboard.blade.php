@extends('layouts.ecom')
@section('title', 'My Account')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-10">
    <div class="flex flex-col lg:flex-row gap-6">

        {{-- Sidebar --}}
        <aside class="lg:w-64 shrink-0">
            @include('account._sidebar')
        </aside>

        {{-- Main --}}
        <main class="flex-1 min-w-0 space-y-6">

            {{-- Welcome header --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h1 class="text-xl font-bold text-gray-900">Welcome back, {{ explode(' ', $customer->name)[0] }}!</h1>
                <p class="text-sm text-gray-500 mt-1">Here's a summary of your account activity.</p>
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="text-2xl font-bold text-gray-900">{{ $customer->orders()->where('source', 'ecommerce')->count() }}</div>
                    <div class="text-sm text-gray-500 mt-0.5">Total Orders</div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="text-2xl font-bold text-green-600">{{ $customer->orders()->where('source', 'ecommerce')->where('status', 'delivered')->count() }}</div>
                    <div class="text-sm text-gray-500 mt-0.5">Delivered</div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 col-span-2 sm:col-span-1">
                    <div class="text-2xl font-bold text-yellow-600">{{ $customer->orders()->where('source', 'ecommerce')->whereIn('status', ['pending', 'processing', 'shipped'])->count() }}</div>
                    <div class="text-sm text-gray-500 mt-0.5">In Progress</div>
                </div>
            </div>

            {{-- Recent Orders --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-900">Recent Orders</h2>
                    <a href="{{ route('account.orders') }}" class="text-sm text-primary-600 hover:underline">View all</a>
                </div>
                @if($recentOrders->isEmpty())
                <div class="px-6 py-10 text-center text-gray-400">
                    <i class="fas fa-box-open text-3xl mb-3"></i>
                    <p class="text-sm">You haven't placed any orders yet.</p>
                    <a href="{{ route('products.index') }}" class="inline-block mt-4 text-sm text-primary-600 font-medium hover:underline">Browse products</a>
                </div>
                @else
                <div class="divide-y divide-gray-50">
                    @foreach($recentOrders as $order)
                    <div class="px-6 py-4 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <div class="font-semibold text-sm text-gray-900">{{ $order->order_number }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">{{ $order->created_at->format('d M Y') }}</div>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            @php
                                $statusColors = [
                                    'pending'    => 'bg-yellow-100 text-yellow-700',
                                    'processing' => 'bg-blue-100 text-blue-700',
                                    'shipped'    => 'bg-indigo-100 text-indigo-700',
                                    'delivered'  => 'bg-green-100 text-green-700',
                                    'cancelled'  => 'bg-red-100 text-red-700',
                                ];
                            @endphp
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst($order->status) }}
                            </span>
                            <span class="text-sm font-semibold text-gray-800">Rs. {{ number_format($order->total) }}</span>
                            <a href="{{ route('account.orders.show', $order) }}"
                               class="text-xs text-primary-600 hover:underline whitespace-nowrap">Details</a>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

        </main>
    </div>
</div>
@endsection
