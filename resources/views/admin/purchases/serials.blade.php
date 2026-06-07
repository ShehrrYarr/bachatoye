@extends('layouts.admin')
@section('title', 'Register Serial Numbers')

@section('content')
@php
    $rPrefix = auth()->user()->hasRole('admin') ? 'admin' : 'salesman';

    // Build JS-friendly initial state per item
    $itemsData = $serializedItems->map(function ($item) use ($registeredSerials) {
        $existing = $registeredSerials->get($item->id, collect());
        $units = [];
        for ($i = 0; $i < $item->quantity; $i++) {
            $sn = $existing->values()->get($i);
            $attrs = $sn?->attributes ?? [];
            $units[] = [
                'serial'        => $sn?->serial_number ?? '',
                'cost_price'    => $sn?->cost_price ? (string) $sn->cost_price : '',
                'selling_price' => $sn?->selling_price ? (string) $sn->selling_price : '',
                'attributes'    => (object) ($attrs ?: []),
                'extraFields'   => [],
                'is_sold'       => $sn?->status === 'sold',
            ];
        }
        return [
            'id'           => $item->id,
            'product_name' => $item->product_name,
            'color_name'   => $item->color_name,
            'sku'          => $item->product?->sku,
            'quantity'     => $item->quantity,
            'units'        => $units,
        ];
    })->values()->all();
@endphp

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
    Scan or type the IMEI / serial number for each unit. Add cost price, selling price, and any custom fields per unit. Sold serials are locked.
</div>

<form method="POST" action="{{ route("{$rPrefix}.purchases.serials.store", $purchase) }}"
      x-data="serialMgr()"
      @submit.prevent="submitForm()">
    @csrf

    {{-- Hidden container for injected fields --}}
    <div id="serialFieldsContainer"></div>

    <div class="space-y-6" x-ref="itemsWrap">
        <template x-for="(item, ii) in items" :key="item.id">
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-gray-800" x-text="item.product_name"></h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Qty: <strong x-text="item.quantity"></strong>
                            <template x-if="item.color_name">
                                <span> · <span class="text-purple-600" x-text="item.color_name"></span></span>
                            </template>
                            <template x-if="item.sku">
                                <span> · SKU: <span class="font-mono" x-text="item.sku"></span></span>
                            </template>
                        </p>
                    </div>
                    <span class="badge"
                          :class="filledCount(item) === item.quantity
                                  ? 'bg-green-100 text-green-700'
                                  : (filledCount(item) > 0 ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-500')">
                        <i class="fas fa-barcode mr-1"></i>
                        <span x-text="`${filledCount(item)}/${item.quantity} entered`"></span>
                    </span>
                </div>
                <div class="card-body">
                    <div class="space-y-3">
                        <template x-for="(unit, ui) in item.units" :key="ui">
                            <div class="border rounded-xl p-3 space-y-2"
                                 :class="unit.is_sold
                                         ? 'border-blue-200 bg-blue-50'
                                         : (unit.serial && unit.serial.trim() ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50')">

                                {{-- Unit header --}}
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-gray-500" x-text="`Unit ${ui + 1}`"></span>
                                    <template x-if="unit.is_sold">
                                        <span class="inline-flex items-center gap-1 text-xs text-blue-600 font-semibold">
                                            <i class="fas fa-lock text-[10px]"></i> Sold — locked
                                        </span>
                                    </template>
                                </div>

                                {{-- IMEI / Serial --}}
                                <div class="relative">
                                    <i class="fas fa-barcode absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                    <input type="text"
                                           x-model="unit.serial"
                                           :placeholder="`IMEI / Serial #${ui + 1}`"
                                           :readonly="unit.is_sold"
                                           @keydown.enter.prevent="focusNext($event)"
                                           data-serial-input
                                           autocomplete="off"
                                           class="w-full pl-8 pr-3 py-2 text-sm font-mono border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400 transition-colors"
                                           :class="unit.is_sold
                                                   ? 'border-blue-200 bg-blue-50 text-blue-700 cursor-not-allowed'
                                                   : (unit.serial && unit.serial.trim() ? 'border-green-400 bg-white' : 'border-gray-300 bg-white')">
                                </div>

                                {{-- Cost + Selling price (hidden for sold) --}}
                                <template x-if="!unit.is_sold">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide">Cost Price (Rs.)</label>
                                            <input type="number" x-model="unit.cost_price"
                                                   min="0" step="1" placeholder="0"
                                                   class="w-full border border-gray-300 bg-white rounded-lg px-2 py-1.5 text-sm text-right focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                        </div>
                                        <div>
                                            <label class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide">Selling Price (Rs.)</label>
                                            <input type="number" x-model="unit.selling_price"
                                                   min="0" step="1" placeholder="0"
                                                   class="w-full border border-gray-300 bg-white rounded-lg px-2 py-1.5 text-sm text-right focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                        </div>
                                    </div>
                                </template>

                                {{-- Attribute dropdowns (rendered server-side, shown only when not sold) --}}
                                @if(count($serialAttributeDefs) > 0)
                                <template x-if="!unit.is_sold">
                                    <div class="grid grid-cols-2 gap-2">
                                        @foreach($serialAttributeDefs as $attrDef)
                                        <div>
                                            <label class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide">{{ $attrDef->name }}</label>
                                            <select x-model="unit.attributes['{{ $attrDef->name }}']"
                                                    class="w-full border border-gray-300 bg-white rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                                <option value="">— Select —</option>
                                                @foreach($attrDef->options as $opt)
                                                <option value="{{ $opt }}">{{ $opt }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @endforeach
                                    </div>
                                </template>
                                @endif

                                {{-- Extra freeform fields --}}
                                <template x-if="!unit.is_sold">
                                    <div class="space-y-1.5">
                                        <template x-for="(ef, efi) in unit.extraFields" :key="efi">
                                            <div class="flex items-center gap-2">
                                                <input type="text" x-model="ef.key"
                                                       placeholder="Field name"
                                                       class="w-32 shrink-0 border border-gray-300 bg-white rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                                <span class="text-gray-300 text-sm shrink-0">:</span>
                                                <input type="text" x-model="ef.value"
                                                       placeholder="Value"
                                                       class="flex-1 border border-gray-300 bg-white rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                                <button type="button" @click="unit.extraFields.splice(efi, 1)"
                                                        class="text-red-400 hover:text-red-600 transition-colors shrink-0">
                                                    <i class="fas fa-times text-xs"></i>
                                                </button>
                                            </div>
                                        </template>

                                        {{-- + Add field button --}}
                                        <button type="button"
                                                @click="unit.extraFields.push({ key: '', value: '' })"
                                                class="w-full text-xs text-indigo-600 hover:text-indigo-800 border border-dashed border-indigo-300 hover:border-indigo-500 rounded-lg py-1.5 transition-colors flex items-center justify-center gap-1.5">
                                            <i class="fas fa-plus text-[10px]"></i> Add field
                                        </button>
                                    </div>
                                </template>

                                {{-- Read-only summary for sold serials --}}
                                <template x-if="unit.is_sold">
                                    <div class="text-xs text-blue-600 space-y-0.5 pl-1">
                                        <template x-if="unit.selling_price">
                                            <div>Sold at Rs. <span class="font-semibold" x-text="Number(unit.selling_price).toLocaleString()"></span></div>
                                        </template>
                                        <template x-if="Object.keys(unit.attributes || {}).length > 0">
                                            <div x-text="Object.entries(unit.attributes).filter(([k,v]) => v).map(([k,v]) => `${k}: ${v}`).join(' · ')"></div>
                                        </template>
                                    </div>
                                </template>

                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
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
function serialMgr() {
    const _items = {!! json_encode($itemsData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!};

    return {
        items: _items,

        filledCount(item) {
            return item.units.filter(u => u.serial && u.serial.trim()).length;
        },

        focusNext(event) {
            const inputs = Array.from(document.querySelectorAll('[data-serial-input]:not([readonly])'));
            const idx    = inputs.indexOf(event.target);
            if (idx >= 0 && idx < inputs.length - 1) {
                inputs[idx + 1].focus();
                inputs[idx + 1].select();
            }
        },

        submitForm() {
            const container = document.getElementById('serialFieldsContainer');
            container.innerHTML = '';

            this.items.forEach(item => {
                item.units.forEach((unit, ui) => {
                    if (unit.is_sold) return; // don't re-submit locked serials

                    const sn = (unit.serial || '').trim();
                    if (!sn) return; // skip blank

                    const base = `serials[${item.id}][${ui}]`;

                    const mkHidden = (name, value) => {
                        const inp = document.createElement('input');
                        inp.type  = 'hidden';
                        inp.name  = name;
                        inp.value = value ?? '';
                        container.appendChild(inp);
                    };

                    mkHidden(`${base}[serial]`,        sn);
                    mkHidden(`${base}[cost_price]`,    unit.cost_price    || '');
                    mkHidden(`${base}[selling_price]`, unit.selling_price || '');

                    // Merge attribute dropdowns + extra freeform fields
                    const allAttrs = { ...(unit.attributes || {}) };
                    (unit.extraFields || []).forEach(ef => {
                        const k = (ef.key || '').trim();
                        if (k) allAttrs[k] = ef.value || '';
                    });
                    Object.entries(allAttrs).forEach(([k, v]) => {
                        if (k && v) mkHidden(`${base}[attributes][${k}]`, v);
                    });
                });
            });

            this.$el.submit();
        },
    };
}
</script>
@endpush
