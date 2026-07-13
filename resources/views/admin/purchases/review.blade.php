@extends('layouts.admin')
@section('title', 'Review Purchase')

@section('content')
@php
    $rPrefix = auth()->user()->panelPrefix();
    $pmLabels = [
        'cash'          => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'credit'        => 'Credit (Khata)',
        'partial'       => 'Partial Payment',
    ];
@endphp

<div class="flex items-center gap-3 mb-4">
    <a href="{{ route("{$rPrefix}.purchases.create") }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Review Purchase</h1>
</div>

<div class="mb-6 bg-orange-50 border border-orange-200 text-orange-800 rounded-xl px-4 py-3 flex items-center gap-2 text-sm">
    <i class="fas fa-exclamation-triangle shrink-0"></i>
    <span><strong>Not recorded yet.</strong> Match the items and prices below against the vendor's invoice, then press <strong>Confirm Purchase</strong>.</span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Left: items --}}
    <div class="lg:col-span-2 space-y-5">
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-gray-800">Items ({{ $lines->count() }})</h2>
                <span class="text-sm text-gray-500">Total Qty: <strong>{{ $lines->sum('quantity') }}</strong></span>
            </div>
            <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Unit Cost</th>
                        <th class="text-right">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lines as $line)
                    <tr>
                        <td>
                            <div class="font-medium text-gray-800">{{ $line['product_name'] }}</div>
                            @if($line['color_name'])
                                <div class="text-xs text-purple-600 mt-0.5">{{ $line['color_name'] }}</div>
                            @endif
                        </td>
                        <td class="text-center font-medium">{{ $line['quantity'] }}</td>
                        <td class="text-right text-sm">Rs. {{ number_format($line['unit_cost'], 2) }}</td>
                        <td class="text-right text-sm font-semibold">Rs. {{ number_format($line['line_total']) }}</td>
                    </tr>
                    @if($line['is_serialized'] && $line['serials']->isNotEmpty())
                    <tr>
                        <td colspan="4" class="!py-2 bg-gray-50/70">
                            <div class="space-y-1.5 pl-2">
                                @foreach($line['serials'] as $s)
                                <div class="flex items-center gap-3 flex-wrap text-xs">
                                    @if(!empty($s['image_path']))
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($s['image_path']) }}"
                                             class="w-8 h-8 object-cover rounded border border-gray-200 shrink-0">
                                    @endif
                                    <span class="font-mono text-gray-700">{{ $s['serial'] }}</span>
                                    @php $attrs = array_filter($s['attributes'] ?? []); @endphp
                                    @foreach($attrs as $k => $v)
                                        <span class="bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded">{{ $k }}: {{ $v }}</span>
                                    @endforeach
                                    <span class="text-gray-500 ml-auto whitespace-nowrap">
                                        Cost: <strong class="text-gray-700">Rs. {{ is_numeric($s['cost_price'] ?? null) ? number_format((float) $s['cost_price']) : '—' }}</strong>
                                        @if(is_numeric($s['selling_price'] ?? null))
                                            · Sell: <strong class="text-gray-700">Rs. {{ number_format((float) $s['selling_price']) }}</strong>
                                        @endif
                                    </span>
                                </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50">
                        <td colspan="3" class="text-right font-bold text-gray-800">Grand Total</td>
                        <td class="text-right font-extrabold text-gray-900">Rs. {{ number_format($total) }}</td>
                    </tr>
                </tfoot>
            </table>
            </div>
        </div>

        @if(!empty($draft['notes']))
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-2">Notes</h2>
            <p class="text-sm text-gray-600">{{ $draft['notes'] }}</p>
        </div>
        @endif
    </div>

    {{-- Right: vendor + payment + actions --}}
    <div class="space-y-5">

        {{-- Vendor / invoice --}}
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Vendor & Invoice</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between gap-3">
                    <span class="text-gray-500">Vendor</span>
                    <span class="font-semibold text-right">{{ $vendor?->name ?? '—' }}@if($vendor?->company) <span class="text-gray-400 font-normal">({{ $vendor->company }})</span>@endif</span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-gray-500">Invoice / Ref #</span>
                    <span class="font-mono">{{ $draft['reference'] ?: '—' }}</span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-gray-500">Purchase Date</span>
                    <span>{{ \Carbon\Carbon::parse($draft['purchase_date'])->format('d M Y') }}</span>
                </div>
            </div>
        </div>

        {{-- Payment breakdown --}}
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Payment</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Method</span>
                    <span class="font-semibold">
                        {{ $pmLabels[$payMethod] ?? $payMethod }}
                        @if($payMethod === 'partial')
                            <span class="text-gray-400 font-normal">({{ ($draft['partial_pay_via'] ?? 'cash') === 'bank' ? 'via Bank' : 'via Cash' }})</span>
                        @endif
                    </span>
                </div>
                @if($bankAccount)
                <div class="flex justify-between gap-3">
                    <span class="text-gray-500">Bank Account</span>
                    <span class="text-right">{{ $bankAccount->label }} — {{ $bankAccount->bank_name }}</span>
                </div>
                @endif
                <div class="flex justify-between border-t border-gray-100 pt-2">
                    <span class="text-gray-500">Total</span>
                    <span class="font-bold">Rs. {{ number_format($total) }}</span>
                </div>
                <div class="flex justify-between text-green-600 font-semibold">
                    <span>Paying Now</span>
                    <span>Rs. {{ number_format($amountPaid) }}</span>
                </div>
                @if($owed > 0)
                <div class="flex justify-between text-red-600 font-semibold">
                    <span>On Credit (Khata)</span>
                    <span>Rs. {{ number_format($owed) }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Khata impact --}}
        @if($owed > 0 && $vendor)
        <div class="card p-5 border-red-200 bg-red-50">
            <h2 class="font-semibold text-red-800 mb-2"><i class="fas fa-book mr-1.5"></i>Khata Impact</h2>
            <div class="space-y-1.5 text-sm">
                <div class="flex justify-between">
                    <span class="text-red-700/70">Current balance owed</span>
                    <span class="font-semibold text-red-700">Rs. {{ number_format($vendor->balance) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-red-700/70">This purchase adds</span>
                    <span class="font-semibold text-red-700">+ Rs. {{ number_format($owed) }}</span>
                </div>
                <div class="flex justify-between border-t border-red-200 pt-1.5">
                    <span class="font-bold text-red-800">New balance owed</span>
                    <span class="font-extrabold text-red-800">Rs. {{ number_format($vendor->balance + $owed) }}</span>
                </div>
            </div>
        </div>
        @endif

        {{-- Actions --}}
        <div class="card p-5 space-y-3">
            <form method="POST" action="{{ route("{$rPrefix}.purchases.review.confirm") }}"
                  x-data="{ confirming: false }" @submit="confirming = true">
                @csrf
                <button type="submit" :disabled="confirming"
                        class="btn-primary w-full justify-center btn-lg"
                        :class="confirming ? 'opacity-60 cursor-wait' : ''">
                    <i class="fas fa-spinner fa-spin mr-2" x-show="confirming" style="display:none;"></i>
                    <i class="fas fa-check-circle mr-2" x-show="!confirming"></i>
                    <span x-text="confirming ? 'Recording…' : 'Confirm Purchase'"></span>
                </button>
            </form>
            <a href="{{ route("{$rPrefix}.purchases.create") }}" class="btn-outline w-full justify-center">
                <i class="fas fa-plus mr-2"></i> Add More Items
            </a>
            <form method="POST" action="{{ route("{$rPrefix}.purchases.review.discard") }}"
                  onsubmit="return confirm('Discard this purchase draft? All entered items will be lost.')">
                @csrf
                <button type="submit" class="w-full text-center text-xs text-red-500 hover:text-red-700 py-1">
                    <i class="fas fa-trash-alt mr-1"></i> Discard draft
                </button>
            </form>
            <p class="text-xs text-gray-400 text-center">Stock, payments, and khata update only after confirming.</p>
        </div>
    </div>
</div>
@endsection
