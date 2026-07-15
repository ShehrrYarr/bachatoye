@extends('layouts.admin')
@section('title', 'Inventory')

@push('styles')
<style>[x-cloak] { display: none !important; }</style>
@endpush

@section('content')
@php
    $rPrefix          = auth()->user()->panelPrefix();
    $inventorySearchUrl = route("{$rPrefix}.inventory.search");
@endphp

{{-- ── Global real-time product search ─────────────────────────────── --}}
<div x-data="inventorySearch('{{ $inventorySearchUrl }}')"
     @click.outside="open = false"
     class="relative mb-5">

    <div class="relative">
        <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
        <input type="text"
               x-model="q"
               @input="onInput()"
               @keydown.enter.prevent="goFirst()"
               @keydown.escape="open = false"
               @focus="results.length && (open = true)"
               placeholder="Search any product by name or barcode..."
               class="form-input pl-9 pr-9 w-full text-sm">
        <span x-show="loading" class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
            <i class="fas fa-spinner fa-spin text-gray-400 text-xs"></i>
        </span>
        <button x-show="q && !loading" @click="clear()"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xs"></i>
        </button>
    </div>

    {{-- Results dropdown --}}
    <div x-show="open" x-cloak
         class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">

        <template x-if="results.length">
            <div>
                <template x-for="p in results" :key="p.id">
                    <a :href="p.adjust_url"
                       class="flex items-center gap-3 px-4 py-2.5 hover:bg-indigo-50 border-b border-gray-100 last:border-0 transition-colors">
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-gray-900 text-sm truncate" x-text="p.name"></div>
                            <div class="text-xs text-gray-400 font-mono mt-0.5"
                                 x-text="[p.barcode !== '—' ? p.barcode : '', p.sku !== '—' ? p.sku : ''].filter(Boolean).join(' · ')"></div>
                        </div>
                        <div class="shrink-0" x-show="p.stock !== null">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                                  :class="p.stock <= 0
                                      ? 'bg-red-100 text-red-700'
                                      : (p.stock <= 5 ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700')"
                                  x-text="'Stock: ' + p.stock"></span>
                        </div>
                    </a>
                </template>
            </div>
        </template>

        <template x-if="!results.length && !loading">
            <div class="px-4 py-3 text-sm text-gray-400">
                No products found for "<span x-text="q"></span>"
            </div>
        </template>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     STAGE 1 — PICKER
═══════════════════════════════════════════════════════════════════ --}}
@if($stage === 'picker')

<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Inventory Management</h1>
    <div class="flex gap-2">
        <a href="{{ route("{$rPrefix}.inventory.low_stock") }}" class="btn-outline btn-sm">
            <i class="fas fa-exclamation-triangle text-orange-500 mr-1"></i> Low Stock
        </a>
        @if(auth()->user()->hasRole('admin'))
        <a href="{{ route('admin.inventory.barcode_print') }}" class="btn-outline btn-sm">
            <i class="fas fa-print mr-1"></i> Print Labels
        </a>
        @endif
    </div>
</div>

<div class="flex items-center justify-center" style="min-height: 55vh;">
    <div class="w-full max-w-xl">

        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-4"
                 style="background: #eef2ff;">
                <i class="fas fa-cubes text-2xl" style="color: #6366f1;"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">How would you like to view inventory?</h2>
            <p class="text-gray-500 mt-2 text-sm">Pick a grouping to explore your stock</p>
        </div>

        <div class="grid grid-cols-2 gap-5">
            {{-- Category Wise --}}
            <a href="{{ route("{$rPrefix}.inventory.index", ['group_by' => 'category']) }}"
               class="card p-7 flex flex-col items-center gap-4 text-center border-2 border-transparent
                      hover:shadow-lg transition-all duration-200 group cursor-pointer"
               style="--tw-border-opacity:1;"
               onmouseover="this.style.borderColor='#a5b4fc'"
               onmouseout="this.style.borderColor='transparent'">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center transition-colors"
                     style="background:#eef2ff;">
                    <i class="fas fa-folder-open text-xl" style="color:#6366f1;"></i>
                </div>
                <div>
                    <div class="font-bold text-gray-900 text-base">Category Wise</div>
                    <div class="text-xs text-gray-500 mt-1 leading-relaxed">Browse stock grouped<br>by product categories</div>
                </div>
                <div class="text-xs font-semibold flex items-center gap-1" style="color:#6366f1;">
                    View Categories <i class="fas fa-arrow-right text-xs"></i>
                </div>
            </a>

            {{-- Brand Wise --}}
            <a href="{{ route("{$rPrefix}.inventory.index", ['group_by' => 'brand']) }}"
               class="card p-7 flex flex-col items-center gap-4 text-center border-2 border-transparent
                      hover:shadow-lg transition-all duration-200 group cursor-pointer"
               onmouseover="this.style.borderColor='#d8b4fe'"
               onmouseout="this.style.borderColor='transparent'">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center transition-colors"
                     style="background:#faf5ff;">
                    <i class="fas fa-tag text-xl" style="color:#a855f7;"></i>
                </div>
                <div>
                    <div class="font-bold text-gray-900 text-base">Brand Wise</div>
                    <div class="text-xs text-gray-500 mt-1 leading-relaxed">Browse stock grouped<br>by manufacturer or brand</div>
                </div>
                <div class="text-xs font-semibold flex items-center gap-1" style="color:#a855f7;">
                    View Brands <i class="fas fa-arrow-right text-xs"></i>
                </div>
            </a>
        </div>

    </div>
</div>


{{-- ═══════════════════════════════════════════════════════════════════
     STAGE 2 — GROUP CARDS
═══════════════════════════════════════════════════════════════════ --}}
@elseif($stage === 'groups')

@php
    $isCategory  = $groupBy === 'category';
    $groupLabel  = $isCategory ? 'Category Wise' : 'Brand Wise';
    $iconClass   = $isCategory ? 'fa-folder-open' : 'fa-tag';
    $accentColor = $isCategory ? '#6366f1' : '#a855f7';
    $accentBg    = $isCategory ? '#eef2ff'  : '#faf5ff';
    $cardIcon    = $isCategory ? 'fa-folder' : 'fa-tag';
@endphp

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route("{$rPrefix}.inventory.index") }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition-colors">
            <i class="fas fa-arrow-left text-xs"></i> Inventory
        </a>
        <span class="text-gray-300">/</span>
        <h1 class="text-xl font-bold text-gray-900">{{ $groupLabel }}</h1>
    </div>
    <div class="flex gap-2">
        <a href="{{ route("{$rPrefix}.inventory.low_stock") }}" class="btn-outline btn-sm">
            <i class="fas fa-exclamation-triangle text-orange-500 mr-1"></i> Low Stock
        </a>
        @if(auth()->user()->hasRole('admin'))
        <a href="{{ route('admin.inventory.barcode_print') }}" class="btn-outline btn-sm">
            <i class="fas fa-print mr-1"></i> Print Labels
        </a>
        @endif
    </div>
</div>

{{-- Summary bar --}}
<div class="card p-4 mb-6 flex items-center justify-between flex-wrap gap-3">
    <div class="flex items-center gap-2 text-sm text-gray-600">
        <i class="fas {{ $iconClass }} text-gray-400"></i>
        <span>
            <strong class="text-gray-900">{{ $groups->count() }}</strong>
            {{ $isCategory ? 'categories' : 'brands' }} with stock
        </span>
    </div>
    <div class="text-sm text-gray-600">
        Total inventory value:
        <strong class="text-gray-900">Rs. {{ number_format($grandTotal) }}</strong>
    </div>
</div>

{{-- Group cards --}}
@if($groups->isNotEmpty())
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($groups as $group)
    <a href="{{ route("{$rPrefix}.inventory.index", ['group_by' => $groupBy, 'group_id' => $group->id]) }}"
       class="card p-5 border border-gray-200 hover:shadow-md transition-all duration-200 group block"
       onmouseover="this.style.borderColor='{{ $accentColor }}33'"
       onmouseout="this.style.borderColor='#e5e7eb'">

        <div class="flex items-start justify-between mb-4">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                 style="background:{{ $accentBg }};">
                <i class="fas {{ $cardIcon }}" style="color:{{ $accentColor }};"></i>
            </div>
            <span class="text-xs text-gray-400 group-hover:text-gray-700 transition-colors flex items-center gap-1">
                View <i class="fas fa-arrow-right text-xs"></i>
            </span>
        </div>

        <div class="font-semibold text-gray-900 text-base mb-4 leading-tight">{{ $group->name }}</div>

        <div class="grid grid-cols-2 gap-2 mb-3">
            <div class="rounded-lg p-2.5 text-center" style="background:#f9fafb;">
                <div class="text-lg font-bold text-gray-800">{{ number_format($group->item_count) }}</div>
                <div class="text-xs text-gray-500 mt-0.5">{{ $group->item_count == 1 ? 'product' : 'products' }}</div>
            </div>
            <div class="rounded-lg p-2.5 text-center" style="background:#f9fafb;">
                <div class="text-lg font-bold text-gray-800">{{ number_format($group->total_stock) }}</div>
                <div class="text-xs text-gray-500 mt-0.5">total units</div>
            </div>
        </div>

        <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
            <span class="text-xs text-gray-500">Stock value</span>
            <span class="text-sm font-bold text-gray-800">Rs. {{ number_format($group->total_cost) }}</span>
        </div>

    </a>
    @endforeach
</div>

@else
<div class="card p-14 text-center">
    <i class="fas fa-box-open text-4xl text-gray-300 mb-3 block"></i>
    <p class="text-gray-400">No {{ $isCategory ? 'categories' : 'brands' }} with products found.</p>
</div>
@endif


{{-- ═══════════════════════════════════════════════════════════════════
     STAGE 3 — PRODUCTS IN GROUP
═══════════════════════════════════════════════════════════════════ --}}
@else

@php $isCategory = $groupBy === 'category'; @endphp

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3 flex-wrap">
        <a href="{{ route("{$rPrefix}.inventory.index", ['group_by' => $groupBy]) }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition-colors">
            <i class="fas fa-arrow-left text-xs"></i>
            {{ $isCategory ? 'Category Wise' : 'Brand Wise' }}
        </a>
        <span class="text-gray-300">/</span>
        <h1 class="text-xl font-bold text-gray-900">{{ $group->name }}</h1>
    </div>
    <div class="flex gap-2">
        <a href="{{ route("{$rPrefix}.inventory.low_stock") }}" class="btn-outline btn-sm">
            <i class="fas fa-exclamation-triangle text-orange-500 mr-1"></i> Low Stock
        </a>
        @if(auth()->user()->hasRole('admin'))
        <a href="{{ route('admin.inventory.barcode_print') }}" class="btn-outline btn-sm">
            <i class="fas fa-print mr-1"></i> Print Labels
        </a>
        @endif
    </div>
</div>

{{-- Stats bar --}}
<div class="card p-4 mb-5">
    <div class="grid grid-cols-3 divide-x divide-gray-100 text-center">
        <div class="px-4">
            <div class="text-xl font-bold text-gray-800">{{ number_format($totalItems) }}</div>
            <div class="text-xs text-gray-500 mt-0.5">{{ $totalItems == 1 ? 'product' : 'products' }}</div>
        </div>
        <div class="px-4">
            <div class="text-xl font-bold text-gray-800">{{ number_format($totalStock) }}</div>
            <div class="text-xs text-gray-500 mt-0.5">total units</div>
        </div>
        <div class="px-4">
            <div class="text-xl font-bold text-gray-800">Rs. {{ number_format($totalValue) }}</div>
            <div class="text-xs text-gray-500 mt-0.5">inventory value</div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card p-4 mb-5">
    <form method="GET" action="{{ route("{$rPrefix}.inventory.index") }}" class="flex flex-wrap gap-3 items-end">
        <input type="hidden" name="group_by" value="{{ $groupBy }}">
        <input type="hidden" name="group_id" value="{{ $groupId }}">
        <div class="flex-1 min-w-48">
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="Search name, SKU, barcode..."
                   class="form-input text-sm">
        </div>
        <div>
            <select name="stock_status" class="form-select text-sm">
                <option value="">All Stock</option>
                <option value="in_stock"     {{ request('stock_status') === 'in_stock'     ? 'selected' : '' }}>In Stock</option>
                <option value="low_stock"    {{ request('stock_status') === 'low_stock'    ? 'selected' : '' }}>Low Stock</option>
                <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
            </select>
        </div>
        <button type="submit" class="btn-primary btn-sm">Filter</button>
        @if(request()->hasAny(['q', 'stock_status']))
        <a href="{{ route("{$rPrefix}.inventory.index", ['group_by' => $groupBy, 'group_id' => $groupId]) }}"
           class="btn-outline btn-sm">Clear</a>
        @endif
    </form>
</div>

{{-- Barcode scanner quick lookup --}}
<div class="card p-4 mb-5" x-data="{
    code: '', result: null, error: '',
    async lookup() {
        if (!this.code.trim()) return;
        this.error = ''; this.result = null;
        try {
            const res = await fetch(`/admin/inventory/barcode-scan?code=${encodeURIComponent(this.code)}`);
            const data = await res.json();
            if (data && data.id) { this.result = data; } else { this.error = 'Product not found.'; }
        } catch(e) { this.error = 'Lookup failed.'; }
    }
}">
    <h3 class="text-sm font-semibold text-gray-700 mb-3">Quick Barcode Lookup</h3>
    <div class="flex gap-3">
        <input type="text" x-model="code" @keydown.enter="lookup()"
               placeholder="Scan or enter barcode..."
               class="form-input flex-1 font-mono text-sm">
        <button @click="lookup()" class="btn-primary btn-sm px-5">
            <i class="fas fa-search mr-1"></i> Lookup
        </button>
    </div>
    <div x-show="result" class="mt-3 p-3 bg-green-50 border border-green-200 rounded-xl flex items-center justify-between">
        <div>
            <div class="font-semibold text-gray-800 text-sm" x-text="result?.name"></div>
            <div class="text-xs text-gray-500 mt-0.5">
                Stock: <span class="font-semibold" x-text="result?.stock_quantity"></span> |
                Price: <span x-text="`Rs. ${Number(result?.price || 0).toLocaleString()}`"></span>
            </div>
        </div>
        <a :href="`/admin/inventory/${result?.id}/adjust`" class="btn-primary btn-sm">Adjust</a>
    </div>
    <div x-show="error" class="mt-3 text-red-600 text-sm" x-text="error"></div>
</div>

{{-- Products table --}}
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Barcode</th>
                    <th>{{ $isCategory ? 'Brand' : 'Category' }}</th>
                    <th class="text-center">Stock</th>
                    <th class="text-center">Threshold</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <img src="{{ $product->primary_image_url }}"
                                 loading="lazy"
                                 class="w-9 h-9 object-cover rounded-lg bg-gray-100 shrink-0">
                            <div class="font-medium text-gray-800 text-sm">{{ $product->name }}</div>
                        </div>
                    </td>
                    <td class="text-xs font-mono text-gray-500">{{ $product->sku ?? '—' }}</td>
                    <td class="text-xs font-mono text-gray-500">{{ $product->barcode ?? '—' }}</td>
                    <td class="text-sm text-gray-600">
                        {{ $isCategory ? ($product->brand?->name ?? '—') : ($product->category?->name ?? '—') }}
                    </td>
                    <td class="text-center">
                        @if($product->track_inventory)
                            @if($product->stock_quantity <= 0)
                                <span class="badge bg-red-100 text-red-700">0</span>
                            @elseif($product->isLowStock())
                                <span class="badge bg-orange-100 text-orange-700">{{ $product->stock_quantity }}</span>
                            @else
                                <span class="font-semibold text-gray-700">{{ number_format($product->stock_quantity) }}</span>
                            @endif
                        @else
                            <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="text-center text-sm text-gray-500">{{ $product->low_stock_threshold ?? '—' }}</td>
                    <td class="text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route("{$rPrefix}.inventory.adjust.form", $product) }}" class="btn-primary btn-sm">
                                <i class="fas fa-edit mr-1"></i> Adjust
                            </a>
                            <a href="{{ route("{$rPrefix}.inventory.history", $product) }}" class="btn-outline btn-sm">
                                History
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-12 text-gray-400">No products found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-gray-200">
        {{ $products->withQueryString()->links() }}
    </div>
</div>

@endif

@endsection

@push('scripts')
<script>
function inventorySearch(searchUrl) {
    return {
        q:       '',
        results: [],
        loading: false,
        open:    false,
        timer:   null,

        onInput() {
            clearTimeout(this.timer);
            if (this.q.length < 2) {
                this.results = [];
                this.open    = false;
                this.loading = false;
                return;
            }
            this.loading = true;
            this.timer   = setTimeout(() => this.doSearch(), 300);
        },

        async doSearch() {
            try {
                const res    = await fetch(searchUrl + '?q=' + encodeURIComponent(this.q));
                this.results = await res.json();
                this.open    = true;
            } catch (e) {
                this.results = [];
            }
            this.loading = false;
        },

        goFirst() {
            if (this.results.length) window.location.href = this.results[0].adjust_url;
        },

        clear() {
            this.q = ''; this.results = []; this.open = false;
        },
    };
}
</script>
@endpush
