@extends('layouts.admin')
@section('title', 'Return: ' . $return->return_number)

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.returns.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">{{ $return->return_number }}</h1>
    <a href="{{ route('pos.return.receipt', $return) }}" target="_blank" class="btn-outline btn-sm">
        <i class="fas fa-print mr-1"></i> Print Receipt
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-header"><h2 class="font-semibold text-gray-800">Returned Items</h2></div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Refund</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($return->items as $item)
                    <tr>
                        <td class="font-medium text-gray-800">{{ $item->product_name }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">Rs. {{ number_format($item->unit_price) }}</td>
                        <td class="text-right font-semibold">Rs. {{ number_format($item->line_total) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50">
                        <td colspan="3" class="text-right font-bold">Total Refund</td>
                        <td class="text-right font-bold text-green-700">Rs. {{ number_format($return->refund_amount) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="space-y-5">
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Return Details</h2>
            <dl class="space-y-3 text-sm">
                <div><dt class="text-gray-500 text-xs">Original Order</dt>
                    <dd><a href="{{ route('admin.orders.show', $return->order) }}" class="text-primary-600 hover:underline font-medium">{{ $return->order->order_number }}</a></dd>
                </div>
                <div><dt class="text-gray-500 text-xs">Customer</dt><dd class="font-medium">{{ $return->order->customer_name }}</dd></div>
                <div><dt class="text-gray-500 text-xs">Refund Method</dt>
                    <dd>
                        <span class="badge {{ $return->refund_method === 'cash' ? 'bg-green-100 text-green-700' : ($return->refund_method === 'khata_credit' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') }}">
                            {{ ucfirst(str_replace('_', ' ', $return->refund_method)) }}
                        </span>
                    </dd>
                </div>
                <div><dt class="text-gray-500 text-xs">Reason</dt><dd>{{ ucfirst(str_replace('_', ' ', $return->reason)) }}</dd></div>
                <div><dt class="text-gray-500 text-xs">Items Restocked</dt><dd>{{ $return->restock ? 'Yes' : 'No' }}</dd></div>
                @if($return->notes)
                <div><dt class="text-gray-500 text-xs">Notes</dt><dd class="text-gray-600">{{ $return->notes }}</dd></div>
                @endif
                <div><dt class="text-gray-500 text-xs">Processed By</dt><dd>{{ $return->processedBy?->name ?? 'N/A' }}</dd></div>
                <div><dt class="text-gray-500 text-xs">Date</dt><dd class="text-xs">{{ $return->created_at->format('d M Y H:i') }}</dd></div>
            </dl>
        </div>
    </div>
</div>
@endsection
