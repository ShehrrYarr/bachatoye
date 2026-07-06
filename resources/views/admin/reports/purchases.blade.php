@extends('layouts.admin')
@section('title', 'Purchase Report')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Purchase Report</h1>
    <a href="{{ route('admin.purchases.create') }}" class="btn-primary btn-sm">
        <i class="fas fa-plus mr-1"></i> Record Purchase
    </a>
</div>

{{-- Filters --}}
@php
    $productOptions     = $products->map(fn($p) => ['value' => $p->id, 'label' => $p->name])->values()->toArray();
    $activeProductLabel = $productId ? $products->firstWhere('id', $productId)?->name : null;
@endphp
<form method="GET" class="flex flex-wrap gap-3 mb-6 items-start">
    <select name="vendor" class="form-select w-44">
        <option value="">All Vendors</option>
        @foreach($vendors as $v)
        <option value="{{ $v->id }}" {{ request('vendor') == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
        @endforeach
    </select>
    <select name="status" class="form-select w-36">
        <option value="">All Status</option>
        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
        <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Partial</option>
        <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
    </select>

    {{-- Searchable product filter --}}
    <div x-data="searchableSelect({
            options: {{ json_encode($productOptions) }},
            initial: {{ $productId ?? 'null' }},
            placeholder: 'All Products'
        })"
         @click.outside="closeDropdown()"
         class="relative w-52">
        <div class="relative">
            <input type="text" x-model="search"
                   @click="openDropdown()"
                   @input="open = true"
                   @keydown.escape="closeDropdown()"
                   class="form-input text-sm w-full pr-7"
                   :placeholder="selectedLabel || placeholder"
                   autocomplete="off">
            <span class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                <i class="fas fa-chevron-down text-xs"></i>
            </span>
        </div>
        <input type="hidden" name="product" :value="value">
        <div x-show="open"
             class="absolute z-50 top-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg max-h-52 overflow-y-auto mt-1"
             style="display:none;">
            <div @mousedown.prevent="clear()"
                 class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-50 text-gray-400 border-b border-gray-100">
                All Products
            </div>
            <template x-for="opt in filtered" :key="opt.value">
                <div @mousedown.prevent="pick(opt)"
                     class="px-3 py-2 text-sm cursor-pointer hover:bg-primary-50 hover:text-primary-700"
                     :class="{ 'bg-primary-50 text-primary-700 font-medium': String(opt.value) === String(value) }"
                     x-text="opt.label"></div>
            </template>
            <div x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-400">No results</div>
        </div>
    </div>

    <input type="date" name="from" value="{{ request('from', now()->startOfMonth()->toDateString()) }}" class="form-input w-40">
    <input type="date" name="to" value="{{ request('to', now()->toDateString()) }}" class="form-input w-40">
    <button type="submit" class="btn-primary btn-sm">Generate Report</button>
    <a href="{{ route('admin.reports.purchases') }}" class="btn-outline btn-sm">Reset</a>
</form>

@if($activeProductLabel)
<div class="mb-5 flex flex-wrap items-center gap-2">
    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">
        <i class="fas fa-box text-[10px]"></i> Product: {{ $activeProductLabel }}
    </span>
    <span class="text-xs text-gray-400">
        <i class="fas fa-info-circle mr-0.5"></i>
        Showing purchases containing this product. Purchase totals may include other items from the same purchase.
    </span>
</div>
@endif

{{-- Summary cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="card p-5">
        <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Total Purchases</div>
        <div class="text-2xl font-bold text-gray-900">{{ $summary['count'] }}</div>
    </div>
    <div class="card p-5">
        <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Total Spent</div>
        <div class="text-2xl font-bold text-primary-700">Rs. {{ number_format($summary['total_spent']) }}</div>
    </div>
    <div class="card p-5">
        <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Total Paid</div>
        <div class="text-2xl font-bold text-green-600">Rs. {{ number_format($summary['total_paid']) }}</div>
    </div>
    <div class="card p-5">
        <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Outstanding</div>
        <div class="text-2xl font-bold text-red-600">Rs. {{ number_format($summary['total_unpaid']) }}</div>
    </div>
</div>

{{-- Breakdown by vendor --}}
@php
    $byVendor = $purchases->groupBy(fn($p) => $p->vendor?->name ?? 'No Vendor');
@endphp

@if($byVendor->count() > 1)
<div class="card mb-6">
    <div class="card-header"><h2 class="font-semibold text-gray-800">By Vendor</h2></div>
    <div class="overflow-x-auto">
        <table class="data-table text-sm">
            <thead>
                <tr>
                    <th>Vendor</th>
                    <th class="text-center">Purchases</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Paid</th>
                    <th class="text-right">Outstanding</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byVendor as $vendorName => $group)
                <tr>
                    <td class="font-medium">{{ $vendorName }}</td>
                    <td class="text-center">{{ $group->count() }}</td>
                    <td class="text-right font-semibold">Rs. {{ number_format($group->sum('total')) }}</td>
                    <td class="text-right">Rs. {{ number_format($group->sum('amount_paid')) }}</td>
                    <td class="text-right {{ $group->sum(fn($p) => $p->balance_due) > 0 ? 'text-red-600 font-semibold' : 'text-gray-400' }}">
                        Rs. {{ number_format($group->sum(fn($p) => $p->balance_due)) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Breakdown by product --}}
@if($byProduct->isNotEmpty())
<div class="card mb-6">
    <div class="card-header">
        <h2 class="font-semibold text-gray-800">By Product ({{ $byProduct->count() }})</h2>
        @if($activeProductLabel)<span class="text-xs text-purple-600 font-semibold">{{ $activeProductLabel }}</span>@endif
    </div>
    <div class="overflow-x-auto">
        <table class="data-table text-sm">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-center">Qty Purchased</th>
                    <th class="text-right">Total Spent</th>
                    <th class="text-right">Avg Unit Cost</th>
                    <th class="text-right">Last Unit Cost</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byProduct as $row)
                <tr>
                    <td class="font-medium text-gray-800">{{ $row['name'] }}</td>
                    <td class="text-center">{{ number_format($row['qty']) }}</td>
                    <td class="text-right font-semibold">Rs. {{ number_format($row['spent']) }}</td>
                    <td class="text-right">Rs. {{ number_format($row['avg_cost']) }}</td>
                    <td class="text-right">Rs. {{ number_format($row['last_cost']) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 font-bold">
                <tr>
                    <td class="text-right">Total</td>
                    <td class="text-center">{{ number_format($byProduct->sum('qty')) }}</td>
                    <td class="text-right">Rs. {{ number_format($byProduct->sum('spent')) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- All purchases --}}
<div class="card">
    <div class="card-header">
        <h2 class="font-semibold text-gray-800">All Purchases ({{ $purchases->count() }})</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table text-sm">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Vendor</th>
                    <th>Reference</th>
                    <th class="text-center">Items</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Paid</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                <tr>
                    <td>{{ $purchase->purchase_date->format('d M Y') }}</td>
                    <td class="font-medium">{{ $purchase->vendor?->name ?? '—' }}</td>
                    <td class="font-mono text-xs text-gray-500">{{ $purchase->reference ?? '—' }}</td>
                    <td class="text-center">{{ $purchase->items->count() ?? '?' }}</td>
                    <td class="text-right font-semibold">Rs. {{ number_format($purchase->total) }}</td>
                    <td class="text-right">Rs. {{ number_format($purchase->amount_paid) }}</td>
                    <td>
                        <span class="badge
                            @if($purchase->payment_status === 'paid') bg-green-100 text-green-700
                            @elseif($purchase->payment_status === 'partial') bg-orange-100 text-orange-700
                            @else bg-red-100 text-red-700 @endif">
                            {{ ucfirst($purchase->payment_status) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('admin.purchases.show', $purchase) }}" class="text-primary-600 hover:underline text-xs">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-gray-400 py-10">No purchases for this period.</td>
                </tr>
                @endforelse
            </tbody>
            @if($purchases->count() > 0)
            <tfoot class="bg-gray-50 font-bold">
                <tr>
                    <td colspan="4" class="text-right">Total</td>
                    <td class="text-right">Rs. {{ number_format($summary['total_spent']) }}</td>
                    <td class="text-right text-green-600">Rs. {{ number_format($summary['total_paid']) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@push('scripts')
<script>
function searchableSelect({ options, initial, placeholder }) {
    return {
        options,
        value:  initial != null ? String(initial) : '',
        search: '',
        open:   false,
        placeholder,

        get selectedLabel() {
            const opt = this.options.find(o => String(o.value) === this.value);
            return opt ? opt.label : '';
        },
        get filtered() {
            const q = this.search.toLowerCase().trim();
            return q ? this.options.filter(o => o.label.toLowerCase().includes(q)) : this.options;
        },
        openDropdown() {
            this.search = '';
            this.open   = true;
        },
        closeDropdown() {
            this.open   = false;
            this.search = this.selectedLabel;
        },
        pick(opt) {
            this.value  = String(opt.value);
            this.search = opt.label;
            this.open   = false;
        },
        clear() {
            this.value  = '';
            this.search = '';
            this.open   = false;
        },
        init() {
            this.search = this.selectedLabel;
        },
    };
}
</script>
@endpush
@endsection
