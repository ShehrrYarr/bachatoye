@extends('layouts.admin')
@section('title', 'Profit & Loss Report')

@section('content')

@php
/* Build flat option lists for Alpine searchable selects */
$sectionOptions = $sections->map(fn($s) => ['value' => $s->id, 'label' => $s->name])->values()->toArray();

$categoryOptions = [];
foreach ($categories as $cat) {
    $categoryOptions[] = ['value' => $cat->id, 'label' => $cat->name];
    foreach ($cat->children as $sub) {
        $categoryOptions[] = ['value' => $sub->id, 'label' => '— ' . $sub->name];
    }
}

/* Labels for active filters */
$activeSectionLabel  = $sectionId  ? $sections->firstWhere('id', $sectionId)?->name  : null;
$activeCategoryLabel = $categoryId ? collect($categoryOptions)->firstWhere('value', $categoryId)['label'] ?? null : null;
@endphp

<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Profit & Loss Report</h1>
    <button onclick="window.print()" class="btn-outline btn-sm no-print">
        <i class="fas fa-print mr-1"></i> Print
    </button>
</div>

<div class="card p-4 mb-5 no-print" x-data>
    <form method="GET" action="{{ route('admin.reports.profit_loss') }}" class="flex flex-wrap gap-3 items-end">
        {{-- Period quick-select --}}
        <div>
            <label class="form-label text-xs">Period</label>
            <select name="period" class="form-select text-sm" onchange="this.form.submit()">
                @foreach(['this_month'=>'This Month','last_month'=>'Last Month','this_year'=>'This Year','last_year'=>'Last Year'] as $v=>$l)
                <option value="{{ $v }}" {{ request('period', 'this_month') === $v ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="form-label text-xs">Shop</label>
            @include('admin.partials.shop-filter')
        </div>

        {{-- Custom date range --}}
        <div class="flex gap-2">
            <div>
                <label class="form-label text-xs">From</label>
                <input type="date" name="date_from" value="{{ request('date_from', $from) }}" class="form-input text-sm">
            </div>
            <div>
                <label class="form-label text-xs">To</label>
                <input type="date" name="date_to" value="{{ request('date_to', $to) }}" class="form-input text-sm">
            </div>
        </div>

        {{-- Section searchable dropdown --}}
        <div x-data="searchableSelect({
                options: {{ json_encode($sectionOptions) }},
                initial: {{ $sectionId ?? 'null' }},
                placeholder: 'All Sections'
            })"
             @click.outside="closeDropdown()"
             class="relative w-44">
            <label class="form-label text-xs">Section</label>
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
            <input type="hidden" name="section" :value="value">
            <div x-show="open"
                 class="absolute z-50 top-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg max-h-52 overflow-y-auto mt-1"
                 style="display:none;">
                <div @mousedown.prevent="clear()"
                     class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-50 text-gray-400 border-b border-gray-100">
                    All Sections
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

        {{-- Category searchable dropdown --}}
        <div x-data="searchableSelect({
                options: {{ json_encode($categoryOptions) }},
                initial: {{ $categoryId ?? 'null' }},
                placeholder: 'All Categories'
            })"
             @click.outside="closeDropdown()"
             class="relative w-48">
            <label class="form-label text-xs">Category</label>
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
            <input type="hidden" name="category" :value="value">
            <div x-show="open"
                 class="absolute z-50 top-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg max-h-52 overflow-y-auto mt-1"
                 style="display:none;">
                <div @mousedown.prevent="clear()"
                     class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-50 text-gray-400 border-b border-gray-100">
                    All Categories
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

        <div class="self-end">
            <button type="submit" class="btn-primary btn-sm">
                <i class="fas fa-filter mr-1"></i> Apply
            </button>
            @if($isFiltered)
            <a href="{{ route('admin.reports.profit_loss') }}?{{ http_build_query(array_filter(['period' => request('period'), 'date_from' => request('date_from'), 'date_to' => request('date_to')])) }}"
               class="btn-outline btn-sm ml-1">
                <i class="fas fa-times mr-1"></i> Clear
            </a>
            @endif
        </div>
    </form>

    @if($isFiltered)
    <div class="mt-3 flex flex-wrap gap-2">
        @if($activeSectionLabel)
        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
            <i class="fas fa-layer-group text-[10px]"></i> Section: {{ $activeSectionLabel }}
        </span>
        @endif
        @if($activeCategoryLabel)
        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">
            <i class="fas fa-tag text-[10px]"></i> Category: {{ $activeCategoryLabel }}
        </span>
        @endif
        <span class="text-xs text-gray-400 self-center">
            <i class="fas fa-info-circle mr-0.5"></i>
            Revenue, COGS &amp; Refunds are filtered to matching items. Discounts, Exchange, Delivery
            and Expenses aren't attributable per-category and are omitted here (see the unfiltered
            report for those).
        </span>
    </div>
    @endif
</div>

{{-- P&L Summary --}}
<div class="card mb-6">
    <div class="card-header">
        <h2 class="font-semibold text-gray-800">
            Summary: {{ $periodLabel }}
            @if($activeSectionLabel)  <span class="text-sm font-normal text-blue-600 ml-1">— {{ $activeSectionLabel }}</span>@endif
            @if($activeCategoryLabel) <span class="text-sm font-normal text-purple-600 ml-1">— {{ $activeCategoryLabel }}</span>@endif
        </h2>
    </div>
    <div class="card-body">
        <div class="space-y-3">
            <div class="flex justify-between text-sm py-2 border-b border-gray-100">
                <span class="text-gray-600">Gross Revenue</span>
                <span class="font-semibold text-gray-900">Rs. {{ number_format($grossRevenue) }}</span>
            </div>
            <div class="flex justify-between text-sm py-2 border-b border-gray-100">
                <span class="text-gray-600">
                    Discounts Given
                    @if($isFiltered)<span class="text-xs text-gray-400 font-normal ml-1">(store-wide)</span>@endif
                </span>
                <span class="font-semibold text-red-600">– Rs. {{ number_format($totalDiscounts) }}</span>
            </div>
            @if(!$isFiltered)
            <div class="flex justify-between text-sm py-2 border-b border-gray-100">
                <span class="text-gray-600">Exchange Value Deducted</span>
                <span class="font-semibold text-red-600">– Rs. {{ number_format($exchangeValue) }}</span>
            </div>
            <div class="flex justify-between text-sm py-2 border-b border-gray-100">
                <span class="text-gray-600">Delivery Income</span>
                <span class="font-semibold text-green-600">+ Rs. {{ number_format($deliveryIncome) }}</span>
            </div>
            @endif
            <div class="flex justify-between text-sm py-2 border-b border-gray-100">
                <span class="text-gray-600">
                    Refunds (Returns)
                    @if($isFiltered)<span class="text-xs text-gray-400 font-normal ml-1">(matching items)</span>@endif
                </span>
                <span class="font-semibold text-red-600">– Rs. {{ number_format($totalRefunds) }}</span>
            </div>
            <div class="flex justify-between text-sm py-2 border-b border-gray-200 font-semibold">
                <span>Net Revenue</span>
                <span class="text-gray-900">Rs. {{ number_format($netRevenue) }}</span>
            </div>
            <div class="flex justify-between text-sm py-2 border-b border-gray-100">
                <span class="text-gray-600">Cost of Goods Sold (COGS)</span>
                <span class="font-semibold text-red-600">– Rs. {{ number_format($totalCogs) }}</span>
            </div>
            <div class="flex justify-between text-sm py-2 border-b border-gray-200 font-semibold">
                <span>Gross Profit</span>
                <span class="{{ $grossProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">Rs. {{ number_format($grossProfit) }}</span>
            </div>
            <div class="flex justify-between text-sm py-2 border-b border-gray-100">
                <span class="text-gray-600">
                    Total Expenses
                    @if($isFiltered)<span class="text-xs text-gray-400 font-normal ml-1">(store-wide)</span>@endif
                </span>
                <span class="font-semibold text-red-600">– Rs. {{ number_format($totalExpenses) }}</span>
            </div>
            <div class="flex justify-between py-3 border-t-2 border-gray-300">
                <span class="font-bold text-base">Net Profit / Loss</span>
                <span class="font-extrabold text-xl {{ $netProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    Rs. {{ number_format($netProfit) }}
                </span>
            </div>
        </div>

        {{-- Margin --}}
        @if($netRevenue > 0)
        <div class="mt-4 p-3 bg-gray-50 rounded-xl flex items-center gap-4">
            <div class="text-sm text-gray-600">Net Margin:</div>
            <div class="font-bold text-lg {{ $netProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                {{ round(($netProfit / $netRevenue) * 100, 1) }}%
            </div>
            <div class="flex-1 h-3 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-3 {{ $netProfit >= 0 ? 'bg-green-500' : 'bg-red-500' }} rounded-full"
                     style="width: {{ min(100, abs(round(($netProfit / $netRevenue) * 100, 1))) }}%"></div>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Expenses breakdown --}}
    <div class="card">
        <div class="card-header">
            <h2 class="font-semibold text-gray-800">Expenses by Category</h2>
            @if($isFiltered)<span class="text-xs text-gray-400">store-wide</span>@endif
        </div>
        <table class="data-table">
            <thead><tr><th>Category</th><th class="text-center">Count</th><th class="text-right">Total</th></tr></thead>
            <tbody>
                @forelse($expensesByCategory as $cat)
                <tr>
                    <td class="font-medium text-gray-800">{{ $cat->category_name ?? 'Uncategorized' }}</td>
                    <td class="text-center text-sm">{{ $cat->count }}</td>
                    <td class="text-right font-semibold text-sm">Rs. {{ number_format($cat->total) }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center py-6 text-gray-400">No expenses</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Monthly trend --}}
    <div class="card">
        <div class="card-header"><h2 class="font-semibold text-gray-800">Monthly Revenue vs COGS</h2></div>
        <div class="card-body">
            <div class="space-y-3">
                @foreach($monthlyData as $month)
                <div>
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>{{ \Carbon\Carbon::createFromDate(null, $month['month'], 1)->format('F') }}</span>
                        <span>Rev: Rs.{{ number_format($month['revenue']/1000, 1) }}k | COGS: Rs.{{ number_format($month['cogs']/1000, 1) }}k</span>
                    </div>
                    <div class="flex gap-1 h-4">
                        @php $maxM = collect($monthlyData)->max('revenue') ?: 1; @endphp
                        <div class="bg-primary-400 rounded h-full" style="width: {{ ($month['revenue'] / $maxM) * 100 }}%"></div>
                    </div>
                </div>
                @endforeach
                @if(empty($monthlyData))
                <p class="text-gray-400 text-sm text-center py-4">No monthly data available.</p>
                @endif
            </div>
        </div>
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
            this.search = '';   // clear so all options show when user clicks to change
            this.open   = true;
        },
        closeDropdown() {
            this.open   = false;
            this.search = this.selectedLabel; // restore display to chosen value
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
