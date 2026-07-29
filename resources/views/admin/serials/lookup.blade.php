@extends('layouts.admin')
@section('title', 'Serial Number Lookup')

@section('content')
@php $rPrefix = auth()->user()->panelPrefix(); @endphp

<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">
        <i class="fas fa-search text-indigo-500 mr-2"></i>Serial Number Lookup
    </h1>
</div>

{{-- Search form --}}
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route("{$rPrefix}.serials.lookup") }}" class="flex gap-3">
            <div class="relative flex-1">
                <i class="fas fa-barcode absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="q" value="{{ $query }}" autofocus
                       placeholder="Enter IMEI or serial number…"
                       class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <button type="submit" class="btn-primary px-6">
                <i class="fas fa-search mr-2"></i>Search
            </button>
        </form>
    </div>
</div>

@if($query && !$serial)
<div class="text-center py-16 text-gray-400">
    <i class="fas fa-search text-4xl mb-3"></i>
    <p class="text-base font-medium">No serial number found matching "<span class="font-mono text-gray-600">{{ $query }}</span>"</p>
    <p class="text-sm mt-1">Make sure the serial number has been registered in a purchase.</p>
</div>
@endif

@if($serial)
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Main info --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Serial Identity Card --}}
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Serial Number Details</h2>
                @php
                    $statusClasses = match($serial->status) {
                        'in_stock' => 'bg-green-100 text-green-700',
                        'sold'     => 'bg-blue-100 text-blue-700',
                        'returned' => 'bg-orange-100 text-orange-700',
                        default    => 'bg-gray-100 text-gray-600',
                    };
                    $statusLabel = match($serial->status) {
                        'in_stock' => 'In Stock',
                        'sold'     => 'Sold',
                        'returned' => 'Returned',
                        default    => ucfirst($serial->status),
                    };
                @endphp
                <span class="badge {{ $statusClasses }} text-sm px-3 py-1">{{ $statusLabel }}</span>
            </div>
            <div class="card-body">
                <div class="flex items-start gap-4">
                    @if($serial->product)
                    <div class="w-16 h-16 rounded-xl bg-gray-100 overflow-hidden shrink-0">
                        <img src="{{ $serial->product->primary_image_url }}" loading="lazy" class="w-full h-full object-cover">
                    </div>
                    @endif
                    <div class="flex-1">
                        <div class="text-2xl font-mono font-bold text-gray-900 tracking-wider">{{ $serial->serial_number }}</div>
                        @if($serial->product)
                        <div class="text-base font-semibold text-gray-700 mt-1">{{ $serial->product->name }}</div>
                        <div class="flex items-center gap-3 mt-1 text-xs text-gray-500">
                            @if($serial->product->sku)
                            <span>SKU: <span class="font-mono">{{ $serial->product->sku }}</span></span>
                            @endif
                            @if($serial->product->barcode)
                            <span>Barcode: <span class="font-mono">{{ $serial->product->barcode }}</span></span>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Timeline --}}
        <div class="card">
            <div class="card-header"><h2 class="font-semibold text-gray-800">History Timeline</h2></div>
            <div class="card-body">
                @if($timeline->isEmpty())
                <div class="text-sm text-gray-400 italic">No history recorded for this unit yet.</div>
                @else
                <ol class="relative border-l-2 border-gray-200 ml-3 space-y-6">
                    @foreach($timeline as $event)
                    @php
                        $dotColor = match($event['type']) {
                            'purchase' => 'bg-indigo-500',
                            'sale'     => 'bg-blue-500',
                            'return'   => 'bg-orange-500',
                            'buyback'  => 'bg-purple-500',
                        };
                        $iconColor = match($event['type']) {
                            'purchase' => 'text-indigo-400',
                            'sale'     => 'text-blue-400',
                            'return'   => 'text-orange-400',
                            'buyback'  => 'text-purple-400',
                        };
                        $linkColor = match($event['type']) {
                            'purchase' => 'text-indigo-600',
                            'sale'     => 'text-blue-600',
                            'return'   => 'text-orange-600',
                            'buyback'  => 'text-purple-600',
                        };
                    @endphp
                    <li class="ml-5">
                        <div class="absolute -left-[9px] w-4 h-4 rounded-full border-2 border-white {{ $dotColor }}"></div>
                        <div class="text-xs text-gray-400 mb-0.5">Step {{ $loop->iteration }}</div>

                        @if($event['type'] === 'purchase')
                        <div class="font-semibold text-gray-800 text-sm">Purchased from Vendor</div>
                        <div class="text-xs text-gray-600 mt-1 space-y-0.5">
                            <div><i class="fas fa-store {{ $iconColor }} w-4 mr-1"></i>
                                Vendor: <strong>{{ $event['purchase']->vendor?->name ?? '—' }}</strong>
                            </div>
                            <div><i class="fas fa-calendar {{ $iconColor }} w-4 mr-1"></i>
                                Date: {{ $event['purchase']->purchase_date->format('d M Y') }}
                            </div>
                            @if($event['purchase']->reference)
                            <div><i class="fas fa-hashtag {{ $iconColor }} w-4 mr-1"></i>
                                Ref: <span class="font-mono">{{ $event['purchase']->reference }}</span>
                            </div>
                            @endif
                            <div class="mt-1">
                                <a href="{{ route("{$rPrefix}.purchases.show", $event['purchase']) }}"
                                   class="{{ $linkColor }} hover:underline text-xs">
                                    <i class="fas fa-external-link-alt mr-1"></i>View Purchase
                                </a>
                            </div>
                        </div>

                        @elseif($event['type'] === 'sale')
                        <div class="font-semibold text-gray-800 text-sm">Sold</div>
                        <div class="text-xs text-gray-600 mt-1 space-y-0.5">
                            <div><i class="fas fa-receipt {{ $iconColor }} w-4 mr-1"></i>
                                Order: <span class="font-mono font-semibold">{{ $event['order']->order_number }}</span>
                            </div>
                            <div><i class="fas fa-user {{ $iconColor }} w-4 mr-1"></i>
                                Customer: {{ $event['order']->customer_name }}
                                @if($event['order']->customer_phone && $event['order']->customer_phone !== '-')
                                    <span class="text-gray-400">({{ $event['order']->customer_phone }})</span>
                                @endif
                            </div>
                            <div><i class="fas fa-tag {{ $iconColor }} w-4 mr-1"></i>
                                Price: Rs. {{ number_format($event['orderItem']->unit_price) }}
                            </div>
                            <div><i class="fas fa-calendar {{ $iconColor }} w-4 mr-1"></i>
                                Date: {{ $event['order']->created_at->format('d M Y H:i') }}
                            </div>
                            @if($event['order']->servedBy)
                            <div><i class="fas fa-user-tie {{ $iconColor }} w-4 mr-1"></i>
                                Served by: {{ $event['order']->servedBy->name }}
                            </div>
                            @endif
                            <div class="mt-1">
                                <a href="{{ route("{$rPrefix}.orders.show", $event['order']) }}"
                                   class="{{ $linkColor }} hover:underline text-xs">
                                    <i class="fas fa-external-link-alt mr-1"></i>View Order
                                </a>
                            </div>
                        </div>

                        @elseif($event['type'] === 'return')
                        <div class="font-semibold text-gray-800 text-sm">Returned</div>
                        <div class="text-xs text-gray-600 mt-1 space-y-0.5">
                            <div><i class="fas fa-undo {{ $iconColor }} w-4 mr-1"></i>
                                Return: <span class="font-mono font-semibold">{{ $event['returnOrder']->return_number }}</span>
                            </div>
                            @if($event['returnOrder']->reason)
                            <div><i class="fas fa-comment {{ $iconColor }} w-4 mr-1"></i>
                                Reason: {{ $event['returnOrder']->reason }}
                            </div>
                            @endif
                            <div><i class="fas fa-calendar {{ $iconColor }} w-4 mr-1"></i>
                                Date: {{ $event['returnOrder']->created_at->format('d M Y H:i') }}
                            </div>
                        </div>

                        @elseif($event['type'] === 'buyback')
                        <div class="font-semibold text-gray-800 text-sm">Bought Back</div>
                        <div class="text-xs text-gray-600 mt-1 space-y-0.5">
                            <div><i class="fas fa-hand-holding-usd {{ $iconColor }} w-4 mr-1"></i>
                                Buyback: <span class="font-mono font-semibold">{{ $event['buyback']->buyback_number }}</span>
                            </div>
                            <div><i class="fas fa-user {{ $iconColor }} w-4 mr-1"></i>
                                From: {{ $event['buyback']->seller_name }}
                                @if($event['buyback']->seller_phone)
                                    <span class="text-gray-400">({{ $event['buyback']->seller_phone }})</span>
                                @endif
                            </div>
                            <div><i class="fas fa-tag {{ $iconColor }} w-4 mr-1"></i>
                                Paid: Rs. {{ number_format($event['buybackItem']->price_paid) }}
                            </div>
                            <div><i class="fas fa-calendar {{ $iconColor }} w-4 mr-1"></i>
                                Date: {{ $event['buyback']->created_at->format('d M Y H:i') }}
                            </div>
                            @if($event['originalOrderItem']?->order)
                            <div><i class="fas fa-link {{ $iconColor }} w-4 mr-1"></i>
                                Originally sold on order
                                <a href="{{ route("{$rPrefix}.orders.show", $event['originalOrderItem']->order) }}"
                                   class="font-mono font-semibold {{ $linkColor }} hover:underline">{{ $event['originalOrderItem']->order->order_number }}</a>
                                ({{ $event['originalOrderItem']->order->created_at->format('d M Y') }})
                                for Rs. {{ number_format($event['originalOrderItem']->unit_price) }}
                            </div>
                            @endif
                            <div class="mt-1">
                                <a href="{{ route('pos.buyback.receipt', $event['buyback']) }}"
                                   class="{{ $linkColor }} hover:underline text-xs">
                                    <i class="fas fa-external-link-alt mr-1"></i>View Buyback
                                </a>
                            </div>
                        </div>
                        @endif
                    </li>
                    @endforeach
                </ol>
                @endif
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="space-y-5">
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Quick Summary</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Current Status</dt>
                    <dd><span class="badge {{ $statusClasses }}">{{ $statusLabel }}</span></dd>
                </div>
                @if($serial->product)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Product</dt>
                    <dd class="font-medium text-right">
                        <a href="{{ route("{$rPrefix}.products.show", $serial->product) }}" class="text-primary-600 hover:underline">
                            {{ Str::limit($serial->product->name, 25) }}
                        </a>
                    </dd>
                </div>
                @endif
                @if($serial->cost_price)
                <div class="flex justify-between border-t border-gray-100 pt-2">
                    <dt class="text-gray-500">Cost Price</dt>
                    <dd class="font-semibold text-gray-700">Rs. {{ number_format($serial->cost_price) }}</dd>
                </div>
                @endif
                @if($serial->selling_price)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Selling Price</dt>
                    <dd class="font-semibold text-primary-700">Rs. {{ number_format($serial->selling_price) }}</dd>
                </div>
                @endif
                <div class="flex justify-between border-t border-gray-100 pt-2">
                    <dt class="text-gray-500">Registered</dt>
                    <dd class="text-xs">{{ $serial->created_at->format('d M Y') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Last Updated</dt>
                    <dd class="text-xs">{{ $serial->updated_at->format('d M Y H:i') }}</dd>
                </div>
            </dl>
        </div>

        @if(!empty($serial->attributes))
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Attributes</h2>
            <dl class="space-y-2 text-sm">
                @foreach($serial->attributes as $key => $value)
                @if($value)
                <div class="flex justify-between">
                    <dt class="text-gray-500">{{ $key }}</dt>
                    <dd class="font-semibold text-gray-800">
                        <span class="bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-full px-2.5 py-0.5 text-xs font-medium">
                            {{ $value }}
                        </span>
                    </dd>
                </div>
                @endif
                @endforeach
            </dl>
        </div>
        @endif

        @if($serial->notes)
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-2">Notes</h2>
            <p class="text-sm text-gray-600">{{ $serial->notes }}</p>
        </div>
        @endif
    </div>
</div>
@endif

@endsection
