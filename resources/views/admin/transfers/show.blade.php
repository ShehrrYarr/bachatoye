@extends('layouts.admin')
@section('title', $transfer->transfer_number)

@section('content')
@php $rPrefix = auth()->user()->panelPrefix(); @endphp
<div class="flex items-center gap-3 mb-6 flex-wrap">
    <a href="{{ route("{$rPrefix}.transfers.index") }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">{{ $transfer->transfer_number }}</h1>
    <span class="badge bg-green-100 text-green-700"><i class="fas fa-check mr-1"></i>Completed</span>
    <div class="ml-auto">
        <a href="{{ route("{$rPrefix}.transfers.slip", $transfer) }}" target="_blank" class="btn-primary btn-sm">
            <i class="fas fa-print mr-1"></i> Print Slip
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-gray-800">Items ({{ $transfer->items->count() }})</h2>
                <span class="text-sm text-gray-500">Total Qty: <strong>{{ $transfer->total_qty }}</strong></span>
            </div>
            <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Serial / Color</th>
                        <th class="text-center">Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transfer->items as $item)
                    <tr>
                        <td class="font-medium text-gray-800">{{ $item->product_name }}</td>
                        <td>
                            @if($item->serial_code)
                                <span class="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded">{{ $item->serial_code }}</span>
                            @elseif($item->color_name)
                                <span class="text-xs text-purple-600">{{ $item->color_name }}</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="text-center font-semibold">{{ $item->quantity }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <div class="space-y-5">
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Transfer Info</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between gap-3"><span class="text-gray-500">From</span><span class="font-semibold">{{ $transfer->from_label }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-gray-500">To</span><span class="font-semibold">{{ $transfer->to_label }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-gray-500">By</span><span>{{ $transfer->creator?->name ?? '—' }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-gray-500">Date</span><span>{{ $transfer->created_at->format('d M Y h:i A') }}</span></div>
            </div>
        </div>
        @if($transfer->note)
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-2">Note</h2>
            <p class="text-sm text-gray-600">{{ $transfer->note }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
