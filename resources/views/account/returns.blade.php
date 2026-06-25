@extends('layouts.ecom')
@section('title', 'My Returns')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col lg:flex-row gap-6">

        <aside class="lg:w-64 shrink-0">
            @include('account._sidebar')
        </aside>

        <main class="flex-1 min-w-0">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 sm:px-6 py-4 border-b border-gray-100">
                    <h1 class="font-bold text-gray-900 text-sm sm:text-base">My Returns</h1>
                </div>

                @if($returns->isEmpty())
                <div class="py-14 text-center text-gray-400">
                    <i class="fas fa-undo text-4xl mb-3"></i>
                    <p class="text-sm">No returns on record.</p>
                </div>
                @else
                <div class="divide-y divide-gray-50">
                    @foreach($returns as $return)
                    @php
                        $refundLabels = ['cash'=>'Cash Refund','bank_transfer'=>'Bank Refund','khata_credit'=>'Khata Credit','exchange'=>'Exchange'];
                    @endphp
                    <div class="px-5 sm:px-6 py-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-semibold text-sm text-gray-900 font-mono">{{ $return->return_number }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ $return->created_at->format('d M Y, h:i A') }}</div>
                                @if($return->order)
                                <div class="text-xs text-gray-500 mt-1">
                                    Order: <span class="font-mono font-medium">{{ $return->order->order_number }}</span>
                                </div>
                                @endif
                                @if($return->reason)
                                <div class="text-xs text-gray-500 mt-1 italic">{{ $return->reason }}</div>
                                @endif
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    @foreach($return->items as $item)
                                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                                        {{ $item->quantity }}× {{ $item->product_name }}
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="font-bold text-sm text-orange-600">Rs. {{ number_format($return->refund_amount) }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ $refundLabels[$return->refund_method] ?? ucfirst($return->refund_method) }}</div>
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium mt-1 inline-block
                                    {{ $return->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                    {{ ucfirst($return->status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                @if($returns->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $returns->links() }}
                </div>
                @endif
                @endif
            </div>
        </main>

    </div>
</div>
@endsection
