@extends('layouts.admin')
@section('title', 'Purchase Details')

@section('content')
@php $rPrefix = auth()->user()->hasRole('admin') ? 'admin' : 'salesman'; @endphp

@if($errors->has('delete'))
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm flex items-start gap-2">
    <i class="fas fa-exclamation-circle mt-0.5 shrink-0"></i>
    <span>{{ $errors->first('delete') }}</span>
</div>
@endif

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route("{$rPrefix}.purchases.index") }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-xl font-bold text-gray-900">Purchase</h1>
        @if($purchase->reference)
            <span class="text-gray-400 font-mono text-sm">{{ $purchase->reference }}</span>
        @endif
        <span class="badge
            @if($purchase->payment_status === 'paid') bg-green-100 text-green-700
            @elseif($purchase->payment_status === 'partial') bg-orange-100 text-orange-700
            @else bg-red-100 text-red-700 @endif">
            {{ ucfirst($purchase->payment_status) }}
        </span>
    </div>
    @if(auth()->user()->hasRole('admin'))
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.purchases.edit', $purchase) }}" class="btn-outline btn-sm">
            <i class="fas fa-edit mr-1"></i> Edit
        </a>
        <form method="POST" action="{{ route('admin.purchases.destroy', $purchase) }}"
              onsubmit="return confirm('Delete this purchase? Stock and vendor ledger will be reversed. This cannot be undone.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn-danger btn-sm">
                <i class="fas fa-trash mr-1"></i> Delete
            </button>
        </form>
    </div>
    @endif
    <div class="text-sm text-gray-500">{{ $purchase->purchase_date->format('d M Y') }}</div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Items --}}
    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-header"><h2 class="font-semibold text-gray-800">Items Purchased</h2></div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Qty</th>
                            <th class="text-right">Unit Cost</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchase->items as $item)
                        <tr>
                            <td>
                                <div class="font-medium text-gray-800">{{ $item->product_name }}</div>
                                @if($item->color_name)
                                    <div class="text-xs text-purple-600 font-medium mt-0.5">
                                        <i class="fas fa-circle text-[8px] mr-1"></i>{{ $item->color_name }}
                                    </div>
                                @endif
                                @if($item->product)
                                    <div class="text-xs text-gray-400 font-mono">{{ $item->product->sku }}</div>
                                @endif
                            </td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-right">Rs. {{ number_format($item->unit_cost) }}</td>
                            <td class="text-right font-semibold">Rs. {{ number_format($item->line_total) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50 font-bold">
                            <td colspan="3" class="text-right">Total</td>
                            <td class="text-right text-primary-700 text-base">Rs. {{ number_format($purchase->total) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if($purchase->notes)
        <div class="card p-5 mt-5">
            <div class="text-sm text-gray-600"><strong>Notes:</strong> {{ $purchase->notes }}</div>
        </div>
        @endif

        {{-- Serial Numbers Section --}}
        @php
            $serializedItems = $purchase->items->filter(fn($i) => $i->product?->is_serialized);
        @endphp
        @if($serializedItems->isNotEmpty())
        @php
            $purchaseSerials = \App\Models\SerialNumber::where('purchase_id', $purchase->id)
                ->with('product')
                ->get()
                ->groupBy('purchase_item_id');
        @endphp
        <div class="card mt-5">
            <div class="card-header flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">
                    <i class="fas fa-barcode text-indigo-500 mr-2"></i>Serial Numbers
                </h2>
                <a href="{{ route("{$rPrefix}.purchases.serials", $purchase) }}" class="btn-outline btn-sm">
                    <i class="fas fa-edit mr-1"></i>Manage Serials
                </a>
            </div>
            <div class="card-body space-y-4">
                @foreach($serializedItems as $item)
                @php $itemSerials = $purchaseSerials->get($item->id, collect()); @endphp
                <div>
                    <div class="text-sm font-semibold text-gray-700 mb-2">
                        {{ $item->product_name }}
                        <span class="text-gray-400 font-normal">({{ $item->quantity }} units)</span>
                    </div>
                    @if($itemSerials->isEmpty())
                    <p class="text-xs text-orange-600 italic">
                        <i class="fas fa-exclamation-circle mr-1"></i>No serials registered yet.
                        <a href="{{ route("{$rPrefix}.purchases.serials", $purchase) }}" class="underline font-semibold">Register now</a>
                    </p>
                    @else
                    <div class="flex flex-wrap gap-2">
                        @foreach($itemSerials as $sn)
                        @php
                            $snColor = match($sn->status) {
                                'in_stock' => 'bg-green-50 text-green-700 border-green-200',
                                'sold'     => 'bg-blue-50 text-blue-700 border-blue-200',
                                'returned' => 'bg-orange-50 text-orange-700 border-orange-200',
                                default    => 'bg-gray-50 text-gray-600 border-gray-200',
                            };
                            $snStatusLabel = match($sn->status) {
                                'in_stock' => 'In Stock',
                                'sold'     => 'Sold',
                                'returned' => 'Returned',
                                default    => ucfirst($sn->status),
                            };
                        @endphp
                        <a href="{{ route('admin.serials.lookup') }}?q={{ urlencode($sn->serial_number) }}"
                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border text-xs font-mono font-semibold transition-colors hover:opacity-80 {{ $snColor }}"
                           title="{{ $snStatusLabel }}">
                            <i class="fas fa-barcode text-[10px]"></i>
                            {{ $sn->serial_number }}
                            <span class="text-[10px] font-sans opacity-70">({{ $snStatusLabel }})</span>
                        </a>
                        @endforeach
                        @if($itemSerials->count() < $item->quantity)
                        <span class="text-xs text-orange-500 italic self-center">
                            {{ $item->quantity - $itemSerials->count() }} unit(s) without serial
                        </span>
                        @endif
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Sidebar --}}
    <div class="space-y-5">
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Purchase Info</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Vendor</dt>
                    <dd class="font-medium">
                        @if($purchase->vendor)
                            <a href="{{ route('admin.vendors.show', $purchase->vendor) }}" class="text-primary-600 hover:underline">
                                {{ $purchase->vendor->name }}
                            </a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Date</dt>
                    <dd>{{ $purchase->purchase_date->format('d M Y') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Payment</dt>
                    <dd class="font-medium">{{ ucfirst($purchase->payment_method) }}</dd>
                </div>
                <div class="flex justify-between border-t border-gray-100 pt-2">
                    <dt class="text-gray-500">Total</dt>
                    <dd class="font-bold text-primary-700">Rs. {{ number_format($purchase->total) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Amount Paid</dt>
                    <dd class="font-semibold text-green-600">Rs. {{ number_format($purchase->amount_paid) }}</dd>
                </div>
                @if($purchase->balance_due > 0)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Balance Due</dt>
                    <dd class="font-semibold text-red-600">Rs. {{ number_format($purchase->balance_due) }}</dd>
                </div>
                @endif
                @if($purchase->createdBy)
                <div class="flex justify-between border-t border-gray-100 pt-2">
                    <dt class="text-gray-500">Recorded by</dt>
                    <dd class="text-xs">{{ $purchase->createdBy->name }}</dd>
                </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-gray-500">Recorded on</dt>
                    <dd class="text-xs">{{ $purchase->created_at->format('d M Y H:i') }}</dd>
                </div>
            </dl>
        </div>

        @if($purchase->vendor && $purchase->balance_due > 0)
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Record Payment</h2>
            <form method="POST" action="{{ route('admin.vendors.ledger.add', $purchase->vendor) }}" class="space-y-3">
                @csrf
                <input type="hidden" name="type" value="debit">
                <div>
                    <label class="form-label text-sm">Amount (Rs.)</label>
                    <input type="number" name="amount" min="0.01" step="0.01"
                           max="{{ $purchase->balance_due }}" class="form-input"
                           placeholder="{{ number_format($purchase->balance_due) }}" required>
                </div>
                <input type="hidden" name="description" value="Payment for purchase{{ $purchase->reference ? ' ref: '.$purchase->reference : '' }}">
                <button type="submit" class="btn-primary w-full justify-center">
                    <i class="fas fa-check mr-2"></i> Record Payment
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
