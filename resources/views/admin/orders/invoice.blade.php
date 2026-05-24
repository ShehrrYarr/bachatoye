@extends('layouts.admin')
@section('title', 'Invoice #' . $order->order_number)
@section('page-title', 'Invoice')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="card p-8" id="invoice">

        {{-- Header --}}
        <div class="flex justify-between items-start mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ \App\Models\Setting::get('shop_name', 'MobileHub') }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ \App\Models\Setting::get('shop_address') }}</p>
                <p class="text-sm text-gray-500">{{ \App\Models\Setting::get('shop_phone') }}</p>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold text-primary-600">INVOICE</div>
                <div class="text-sm text-gray-500 mt-1 font-mono">{{ $order->order_number }}</div>
                <div class="text-sm text-gray-500">{{ $order->created_at->format('d M Y') }}</div>
            </div>
        </div>

        {{-- Billing info --}}
        <div class="grid grid-cols-2 gap-8 mb-8">
            <div>
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Bill To</h3>
                <p class="font-semibold text-gray-900">{{ $order->customer_name }}</p>
                <p class="text-sm text-gray-600">{{ $order->customer_phone }}</p>
                @if($order->customer_email)<p class="text-sm text-gray-600">{{ $order->customer_email }}</p>@endif
                @if($order->delivery_address)<p class="text-sm text-gray-600 mt-1">{{ $order->delivery_address }}</p>@endif
                @if($order->city)<p class="text-sm text-gray-600">{{ $order->city }}</p>@endif
            </div>
            <div class="text-right">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Details</h3>
                <div class="space-y-1 text-sm text-gray-600">
                    <div><span class="text-gray-400">Status:</span> <span class="font-medium">{{ ucfirst($order->status) }}</span></div>
                    <div><span class="text-gray-400">Payment:</span> <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</span></div>
                    <div><span class="text-gray-400">Payment Status:</span> <span class="font-medium">{{ ucfirst($order->payment_status) }}</span></div>
                    @if($order->servedBy)<div><span class="text-gray-400">Served by:</span> <span class="font-medium">{{ $order->servedBy->name }}</span></div>@endif
                </div>
            </div>
        </div>

        {{-- Items table --}}
        <table class="w-full mb-6">
            <thead>
                <tr class="border-b-2 border-gray-200">
                    <th class="text-left py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">#</th>
                    <th class="text-left py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Item</th>
                    <th class="text-right py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Price</th>
                    <th class="text-right py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Qty</th>
                    <th class="text-right py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($order->items as $i => $item)
                <tr>
                    <td class="py-3 text-sm text-gray-400">{{ $i + 1 }}</td>
                    <td class="py-3">
                        <div class="text-sm font-medium text-gray-900">{{ $item->product_name }}</div>
                        @if($item->product_barcode)<div class="text-xs text-gray-400 font-mono">{{ $item->product_barcode }}</div>@endif
                    </td>
                    <td class="py-3 text-sm text-right text-gray-600">Rs. {{ number_format($item->unit_price, 2) }}</td>
                    <td class="py-3 text-sm text-right text-gray-600">{{ $item->quantity }}</td>
                    <td class="py-3 text-sm text-right font-semibold text-gray-900">Rs. {{ number_format($item->line_total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t border-gray-200">
                    <td colspan="4" class="pt-3 text-sm text-right text-gray-500">Subtotal</td>
                    <td class="pt-3 text-sm text-right text-gray-700">Rs. {{ number_format($order->subtotal, 2) }}</td>
                </tr>
                @if($order->discount_amount > 0)
                <tr>
                    <td colspan="4" class="py-1 text-sm text-right text-red-500">Discount</td>
                    <td class="py-1 text-sm text-right text-red-500">– Rs. {{ number_format($order->discount_amount, 2) }}</td>
                </tr>
                @endif
                @if($order->exchange_value > 0)
                <tr>
                    <td colspan="4" class="py-1 text-sm text-right text-orange-600">Exchange ({{ $order->exchange_item_name ?? 'Item' }})</td>
                    <td class="py-1 text-sm text-right text-orange-600">– Rs. {{ number_format($order->exchange_value, 2) }}</td>
                </tr>
                @endif
                @if($order->delivery_charge > 0)
                <tr>
                    <td colspan="4" class="py-1 text-sm text-right text-gray-500">Delivery</td>
                    <td class="py-1 text-sm text-right text-gray-700">Rs. {{ number_format($order->delivery_charge, 2) }}</td>
                </tr>
                @endif
                <tr class="border-t-2 border-gray-200">
                    <td colspan="4" class="pt-3 text-base font-bold text-right text-gray-900">TOTAL</td>
                    <td class="pt-3 text-base font-bold text-right text-primary-700">Rs. {{ number_format($order->total, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        @if($order->delivery_notes)
        <div class="text-sm text-gray-500 mb-4"><span class="font-medium">Notes:</span> {{ $order->delivery_notes }}</div>
        @endif

        {{-- Footer --}}
        <div class="border-t border-gray-200 pt-4 text-center text-xs text-gray-400">
            Thank you for your business! — {{ \App\Models\Setting::get('shop_name', 'MobileHub') }}
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex gap-3 mt-4 no-print">
        <button onclick="window.print()" class="btn-primary">
            <i class="fas fa-print mr-2"></i> Print Invoice
        </button>
        <a href="{{ route('admin.orders.invoice_pdf', $order) }}" class="btn-outline">
            <i class="fas fa-file-pdf mr-2"></i> Download PDF
        </a>
        <a href="{{ route('admin.orders.show', $order) }}" class="btn-outline">
            <i class="fas fa-arrow-left mr-2"></i> Back to Order
        </a>
    </div>
</div>
@endsection

@push('styles')
<style>
@media print {
    .no-print, nav, aside, header { display: none !important; }
    body { background: white; }
    #invoice { box-shadow: none; padding: 0; }
}
</style>
@endpush
