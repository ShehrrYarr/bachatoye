@extends('layouts.admin')
@section('title', 'Register Serial Numbers')

@section('content')
@php $rPrefix = auth()->user()->hasRole('admin') ? 'admin' : 'salesman'; @endphp

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route("{$rPrefix}.purchases.show", $purchase) }}" class="btn-outline btn-sm">
        <i class="fas fa-arrow-left"></i>
    </a>
    <div>
        <h1 class="text-xl font-bold text-gray-900">Register Serial Numbers</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            Purchase
            @if($purchase->reference)
                <span class="font-mono">{{ $purchase->reference }}</span> ·
            @endif
            {{ $purchase->purchase_date->format('d M Y') }}
            @if($purchase->vendor)
                · <strong>{{ $purchase->vendor->name }}</strong>
            @endif
        </p>
    </div>
</div>

@if(session('success'))
<div class="mb-5 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 flex items-center gap-2 text-sm">
    <i class="fas fa-check-circle shrink-0"></i> {{ session('success') }}
</div>
@endif

@if($errors->has('serials'))
<div class="mb-5 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
    <div class="font-semibold mb-1"><i class="fas fa-exclamation-circle mr-1"></i>Please fix the following errors:</div>
    <ul class="list-disc list-inside space-y-0.5">
        @foreach($errors->get('serials') as $msgs)
            @foreach((array) $msgs as $msg)
                <li>{{ $msg }}</li>
            @endforeach
        @endforeach
    </ul>
</div>
@endif

<div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-sm text-blue-800 mb-6">
    <i class="fas fa-info-circle mr-2"></i>
    Scan or type the IMEI / serial number for each unit. Each field = one physical unit.
    You can leave a field blank if you don't want to register that unit yet.
</div>

<form method="POST" action="{{ route("{$rPrefix}.purchases.serials.store", $purchase) }}"
      x-data="serialForm()" @keydown.enter.prevent="focusNext($event)">
    @csrf

    <div class="space-y-6">
        @foreach($serializedItems as $item)
        @php
            $existingForItem = $registeredSerials->get($item->id, collect());
        @endphp
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-gray-800">{{ $item->product_name }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Qty: <strong>{{ $item->quantity }}</strong>
                        @if($item->color_name)
                            · <span class="text-purple-600">{{ $item->color_name }}</span>
                        @endif
                        @if($item->product?->sku)
                            · SKU: <span class="font-mono">{{ $item->product->sku }}</span>
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    @php $alreadyCount = $existingForItem->count(); @endphp
                    @if($alreadyCount > 0)
                    <span class="badge bg-green-100 text-green-700">
                        <i class="fas fa-check-circle mr-1"></i>{{ $alreadyCount }}/{{ $item->quantity }} registered
                    </span>
                    @else
                    <span class="badge bg-orange-100 text-orange-700">
                        <i class="fas fa-exclamation-circle mr-1"></i>Not registered yet
                    </span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @for($i = 0; $i < $item->quantity; $i++)
                    @php
                        $existingSn = $existingForItem->values()->get($i)?->serial_number ?? '';
                        $existingStatus = $existingForItem->values()->get($i)?->status ?? null;
                        $isSold = $existingStatus === 'sold';
                    @endphp
                    <div class="relative">
                        <label class="block text-xs font-medium text-gray-500 mb-1">
                            Unit {{ $i + 1 }}
                            @if($isSold)
                                <span class="text-blue-600 font-semibold ml-1">— Sold</span>
                            @endif
                        </label>
                        <div class="flex items-center gap-1">
                            <div class="relative flex-1">
                                <i class="fas fa-barcode absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                <input type="text"
                                       name="serials[{{ $item->id }}][]"
                                       value="{{ old("serials.{$item->id}.{$i}", $existingSn) }}"
                                       placeholder="Scan or type serial…"
                                       {{ $isSold ? 'readonly' : '' }}
                                       class="w-full pl-7 pr-3 py-2 text-sm font-mono border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400
                                              {{ $isSold ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-gray-300 bg-white' }}
                                              serial-input"
                                       autocomplete="off">
                            </div>
                            @if($isSold)
                            <span class="text-xs text-blue-500 shrink-0" title="Sold — cannot change">
                                <i class="fas fa-lock"></i>
                            </span>
                            @endif
                        </div>
                    </div>
                    @endfor
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200">
        <a href="{{ route("{$rPrefix}.purchases.show", $purchase) }}" class="btn-outline">
            <i class="fas fa-times mr-2"></i>Skip for Now
        </a>
        <button type="submit" class="btn-primary">
            <i class="fas fa-save mr-2"></i>Save Serial Numbers
        </button>
    </div>
</form>

@endsection

@push('scripts')
<script>
function serialForm() {
    return {
        focusNext(event) {
            const inputs = Array.from(document.querySelectorAll('.serial-input:not([readonly])'));
            const idx    = inputs.indexOf(event.target);
            if (idx >= 0 && idx < inputs.length - 1) {
                inputs[idx + 1].focus();
                inputs[idx + 1].select();
            }
        }
    };
}
</script>
@endpush
