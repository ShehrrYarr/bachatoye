@extends('layouts.admin')
@section('title', 'Print Stickers — ' . $product->name)

@push('styles')
<style>
/* ── Print-only rules ──────────────────────────────────────────────── */
@media print {
    /* Isolate print area — hide everything, then reveal only stickers */
    body * { visibility: hidden !important; }
    #sticker-print-area,
    #sticker-print-area * { visibility: visible !important; }
    #sticker-print-area {
        position: fixed !important;
        top: 0; left: 0;
        width: 100%;
        padding: 6mm;
        background: white;
    }

    #print-header { display: block !important; }

    .sticker-grid {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 4mm !important;
    }

    .sticker {
        border: 1px solid #555 !important;
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }
}

/* ── Screen ─────────────────────────────────────────────────────────── */
@media screen {
    .sticker-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 12px;
    }
}

/* ── Sticker card (both screen + print) ─────────────────────────────── */
.sticker {
    border: 1.5px dashed #aaa;
    border-radius: 6px;
    padding: 10px 12px;
    background: white;
    font-family: sans-serif;
    min-height: 100px;
}

.sticker-name {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #111;
    line-height: 1.3;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 5px;
    margin-bottom: 6px;
}

.sticker-serial {
    font-size: 13px;
    font-family: monospace;
    font-weight: 700;
    color: #1e293b;
    word-break: break-all;
    margin-bottom: 5px;
}

.sticker-attrs {
    display: flex;
    flex-wrap: wrap;
    gap: 3px 8px;
    margin-bottom: 4px;
}

.sticker-attr-item {
    font-size: 9px;
    color: #374151;
    white-space: nowrap;
}

.sticker-attr-key {
    color: #6b7280;
}

.sticker-price {
    font-size: 11px;
    font-weight: 700;
    color: #dc2626;
    margin-top: 5px;
    border-top: 1px solid #e5e7eb;
    padding-top: 4px;
}
</style>
@endpush

@section('content')
<div x-data="stickerMgr()" x-init="init()">

    {{-- ── Header ── --}}
    <div class="flex items-center gap-3 mb-5 no-print">
        <a href="{{ route('admin.products.show', $product) }}" class="btn-outline btn-sm">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-900">Print Stickers</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $product->name }}</p>
        </div>
    </div>

    @if($serials->isEmpty())
    <div class="card p-8 text-center no-print">
        <i class="fas fa-box-open text-4xl text-gray-300 mb-3"></i>
        <p class="text-gray-500 font-medium">No in-stock serial numbers found for this product.</p>
        <p class="text-sm text-gray-400 mt-1">Add serial numbers via a purchase to print stickers.</p>
        <a href="{{ route('admin.products.show', $product) }}" class="btn-outline btn-sm mt-4 inline-flex">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>
    @else

    {{-- ── Controls ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6 no-print">

        {{-- Attribute field selection --}}
        <div class="card p-4 lg:col-span-2">
            <h2 class="font-semibold text-gray-800 mb-3 text-sm">
                <i class="fas fa-tags text-indigo-500 mr-1.5"></i>Fields to Print on Each Sticker
            </h2>
            <div class="space-y-1.5">
                {{-- Always-on: product name --}}
                <label class="flex items-center gap-2.5 opacity-50 cursor-not-allowed">
                    <input type="checkbox" checked disabled class="w-4 h-4 rounded border-gray-300 text-indigo-600">
                    <span class="text-sm text-gray-700">Product Name</span>
                    <span class="text-xs text-gray-400">(always shown)</span>
                </label>
                {{-- Always-on: serial/IMEI --}}
                <label class="flex items-center gap-2.5 opacity-50 cursor-not-allowed">
                    <input type="checkbox" checked disabled class="w-4 h-4 rounded border-gray-300 text-indigo-600">
                    <span class="text-sm text-gray-700">IMEI / Serial Number</span>
                    <span class="text-xs text-gray-400">(always shown)</span>
                </label>

                {{-- Dynamic attribute keys --}}
                @foreach($allAttrKeys as $key)
                <label class="flex items-center gap-2.5 cursor-pointer hover:bg-gray-50 rounded px-1 py-0.5">
                    <input type="checkbox"
                           x-model="selectedAttrs"
                           value="{{ $key }}"
                           class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700">{{ $key }}</span>
                </label>
                @endforeach

                {{-- Price toggle --}}
                <label class="flex items-center gap-2.5 cursor-pointer hover:bg-gray-50 rounded px-1 py-0.5 border-t border-gray-100 mt-2 pt-2.5">
                    <input type="checkbox"
                           x-model="showPrice"
                           class="w-4 h-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <span class="text-sm text-gray-700">Selling Price</span>
                </label>
            </div>
        </div>

        {{-- Serial number selection + print action --}}
        <div class="card p-4 flex flex-col gap-3">
            <h2 class="font-semibold text-gray-800 mb-1 text-sm">
                <i class="fas fa-barcode text-green-600 mr-1.5"></i>Serial Numbers
                <span class="text-xs font-normal text-gray-400 ml-1">
                    (<span x-text="selectedSerials.length"></span> of {{ $serials->count() }} selected)
                </span>
            </h2>
            <div class="flex gap-2">
                <button @click="selectAll()" type="button"
                        class="flex-1 btn-outline btn-sm text-xs">
                    <i class="fas fa-check-double mr-1"></i> All
                </button>
                <button @click="deselectAll()" type="button"
                        class="flex-1 btn-outline btn-sm text-xs">
                    <i class="fas fa-times mr-1"></i> None
                </button>
            </div>

            <div class="overflow-y-auto max-h-48 space-y-1 pr-1">
                @foreach($serials as $serial)
                <label class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 rounded px-1 py-0.5">
                    <input type="checkbox"
                           x-model="selectedSerials"
                           value="{{ $serial->id }}"
                           class="w-3.5 h-3.5 rounded border-gray-300 text-green-600 shrink-0">
                    <span class="text-xs font-mono text-gray-700 truncate">{{ $serial->serial_number }}</span>
                </label>
                @endforeach
            </div>

            <button @click="printStickers()" type="button"
                    :disabled="selectedSerials.length === 0"
                    :class="selectedSerials.length === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                    class="btn-primary w-full mt-auto">
                <i class="fas fa-print mr-2"></i>
                Print <span x-text="selectedSerials.length"></span> Sticker(s)
            </button>
        </div>
    </div>

    {{-- ── Preview / Print Area ── --}}
    <div class="print-area" id="sticker-print-area">

        {{-- Print header (only visible on print) --}}
        <div class="hidden print:block mb-4" style="display:none;" id="print-header">
            <p style="font-size:10px; color:#666; margin:0 0 6px 0;">
                {{ $product->name }}
                @if($product->sku) · SKU: {{ $product->sku }} @endif
                · Printed {{ now()->format('d M Y H:i') }}
            </p>
        </div>

        <div class="sticker-grid" id="stickerGrid">
            @foreach($serials as $serial)
            <div class="sticker"
                 data-serial-id="{{ $serial->id }}"
                 data-selling-price="{{ $serial->selling_price ? 'Rs. '.number_format($serial->selling_price, 0) : '' }}"
                 data-attributes='@json($serial->attributes ?? [])'
                 x-show="selectedSerials.includes('{{ $serial->id }}')"
                 x-cloak>

                {{-- Product name --}}
                <div class="sticker-name">{{ $product->name }}</div>

                {{-- Serial / IMEI --}}
                <div class="sticker-serial">{{ $serial->serial_number }}</div>

                {{-- Dynamic attribute values --}}
                @if(!empty($serial->attributes))
                <div class="sticker-attrs">
                    @foreach($serial->attributes as $attrKey => $attrVal)
                    <span class="sticker-attr-item attr-field" data-attr-key="{{ $attrKey }}"
                          x-show="selectedAttrs.includes('{{ $attrKey }}')">
                        <span class="sticker-attr-key">{{ $attrKey }}:</span> {{ $attrVal }}
                    </span>
                    @endforeach
                </div>
                @endif

                {{-- Optional price --}}
                @if($serial->selling_price)
                <div class="sticker-price" x-show="showPrice">
                    Rs. {{ number_format($serial->selling_price, 0) }}
                </div>
                @endif

            </div>
            @endforeach
        </div>

    </div>

    @endif
</div>
@endsection

@push('scripts')
<script>
function stickerMgr() {
    const allIds   = @json($serials->pluck('id')->map(fn($id) => (string)$id));
    const allAttrs = @json($allAttrKeys);

    return {
        selectedSerials: [...allIds],
        selectedAttrs:   [...allAttrs],
        showPrice:       true,

        init() {},

        selectAll()   { this.selectedSerials = [...allIds]; },
        deselectAll() { this.selectedSerials = []; },

        printStickers() {
            if (this.selectedSerials.length === 0) return;
            window.print();
        },
    };
}
</script>
@endpush
