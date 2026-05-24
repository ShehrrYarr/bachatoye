@extends('layouts.admin')
@section('title', 'Order: ' . $order->order_number)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.orders.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-xl font-bold text-gray-900">{{ $order->order_number }}</h1>
        <span class="badge
            @if($order->status === 'delivered') bg-green-100 text-green-700
            @elseif($order->status === 'cancelled') bg-red-100 text-red-700
            @elseif($order->status === 'shipped') bg-purple-100 text-purple-700
            @elseif($order->status === 'processing') bg-blue-100 text-blue-700
            @else bg-yellow-100 text-yellow-700 @endif">
            {{ ucfirst($order->status) }}
        </span>
    </div>
    <div class="flex gap-2">
        @if($order->source === 'pos')
        <a href="{{ route('pos.receipt', $order) }}" target="_blank" class="btn-outline btn-sm">
            <i class="fas fa-receipt mr-1"></i> POS Receipt
        </a>
        @endif
        <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank" class="btn-outline btn-sm">
            <i class="fas fa-file-invoice mr-1"></i> Invoice
        </a>
        @can('orders.manage')
        <button onclick="document.getElementById('statusForm').classList.toggle('hidden')" class="btn-primary btn-sm">
            <i class="fas fa-edit mr-1"></i> Update Status
        </button>
        @endcan
    </div>
</div>

{{-- Status Update Form --}}
@can('orders.manage')
<div id="statusForm" class="hidden card p-4 mb-5">
    <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="flex gap-3 items-end">
        @csrf @method('PATCH')
        <div>
            <label class="form-label text-sm">Order Status</label>
            <select name="status" class="form-select text-sm">
                @foreach(['pending','processing','shipped','delivered','cancelled'] as $s)
                <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label text-sm">Payment Status</label>
            <select name="payment_status" class="form-select text-sm">
                <option value="pending" {{ $order->payment_status === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="partial" {{ $order->payment_status === 'partial' ? 'selected' : '' }}>Partial</option>
                <option value="paid" {{ $order->payment_status === 'paid' ? 'selected' : '' }}>Paid</option>
            </select>
        </div>
        <button type="submit" class="btn-primary btn-sm">Update</button>
    </form>
</div>
@endcan

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Order items --}}
    <div class="lg:col-span-2 space-y-5">
        <div class="card">
            <div class="card-header"><h2 class="font-semibold text-gray-800">Order Items</h2></div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Qty</th>
                            <th class="text-right">Unit Price</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr>
                            <td>
                                <div class="font-medium text-gray-800">{{ $item->product_name }}</div>
                                @if($item->product)
                                <div class="text-xs text-gray-400 font-mono">{{ $item->product->sku }}</div>
                                @endif
                            </td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-right">Rs. {{ number_format($item->unit_price) }}</td>
                            <td class="text-right font-semibold">Rs. {{ number_format($item->line_total) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50">
                            <td colspan="3" class="text-right font-medium text-sm">Subtotal</td>
                            <td class="text-right font-semibold">Rs. {{ number_format($order->subtotal) }}</td>
                        </tr>
                        @if($order->discount_amount > 0)
                        <tr class="bg-gray-50">
                            <td colspan="3" class="text-right text-sm text-red-500">Discount</td>
                            <td class="text-right text-red-500 font-semibold">– Rs. {{ number_format($order->discount_amount) }}</td>
                        </tr>
                        @endif
                        @if($order->delivery_charge > 0)
                        <tr class="bg-gray-50">
                            <td colspan="3" class="text-right text-sm">Delivery</td>
                            <td class="text-right font-semibold">Rs. {{ number_format($order->delivery_charge) }}</td>
                        </tr>
                        @endif
                        <tr class="bg-gray-50">
                            <td colspan="3" class="text-right font-bold text-base">Total</td>
                            <td class="text-right font-bold text-primary-700 text-base">Rs. {{ number_format($order->total) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if($order->payment_proof)
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Payment Proof</h2>
            <a href="{{ asset('storage/'.$order->payment_proof) }}" target="_blank">
                <img src="{{ asset('storage/'.$order->payment_proof) }}" class="max-h-48 rounded-xl border border-gray-200">
            </a>
        </div>
        @endif
    </div>

    {{-- Sidebar --}}
    <div class="space-y-5">
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Customer</h2>
            <dl class="space-y-2 text-sm">
                <div><dt class="text-gray-500 text-xs">Name</dt><dd class="font-medium">{{ $order->customer_name }}</dd></div>
                <div><dt class="text-gray-500 text-xs">Phone</dt><dd>{{ $order->customer_phone }}</dd></div>
                @if($order->customer_address)
                <div><dt class="text-gray-500 text-xs">Address</dt><dd>{{ $order->customer_address }}, {{ $order->customer_city }}</dd></div>
                @endif
                @if($order->customer)
                <div class="pt-2 border-t border-gray-100">
                    <a href="{{ route('admin.customers.show', $order->customer) }}" class="text-primary-600 hover:underline text-xs font-medium">
                        View Customer Profile →
                    </a>
                </div>
                @endif
            </dl>
        </div>

        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Order Info</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Source</dt><dd><span class="badge {{ $order->source === 'pos' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">{{ strtoupper($order->source) }}</span></dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Payment</dt><dd class="font-medium">{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Pay Status</dt>
                    <dd><span class="badge {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-700' : ($order->payment_status === 'partial' ? 'bg-orange-100 text-orange-700' : 'bg-yellow-100 text-yellow-700') }}">{{ ucfirst($order->payment_status) }}</span></dd>
                </div>
                @if($order->payment_method === 'partial')
                <div class="flex justify-between"><dt class="text-gray-500">Paid Now</dt><dd class="font-semibold text-green-700">Rs. {{ number_format($order->amount_paid) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">On Khata</dt><dd class="font-semibold text-red-600">Rs. {{ number_format($order->total - $order->amount_paid) }}</dd></div>
                @endif
                @if($order->servedBy)
                <div class="flex justify-between"><dt class="text-gray-500">Served By</dt><dd class="font-medium">{{ $order->servedBy->name }}</dd></div>
                @endif
                <div class="flex justify-between"><dt class="text-gray-500">Date</dt><dd class="text-xs">{{ $order->created_at->format('d M Y H:i') }}</dd></div>
            </dl>
            @if($order->notes)
            <div class="mt-3 p-3 bg-gray-50 rounded-xl text-sm text-gray-600">
                <strong>Notes:</strong> {{ $order->notes }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
