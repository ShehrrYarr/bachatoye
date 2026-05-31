@extends('layouts.admin')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
        <a href="{{ route('admin.reports.sales', ['period' => 'today', 'source' => 'pos']) }}"
           class="bg-white rounded-xl border border-gray-200 shadow-sm p-3 md:p-6 flex items-start gap-3 hover:border-blue-300 hover:shadow-md transition-all">
            <div class="stat-icon bg-blue-50 shrink-0">
                <i class="fas fa-shopping-bag text-blue-600 text-xl"></i>
            </div>
            <div class="min-w-0">
                <div class="text-lg md:text-2xl font-bold text-gray-900 truncate">{{ number_format($stats['today_sales']) }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Today Sale (Rs.)</div>
                <div class="text-xs text-gray-400 mt-1">{{ $stats['today_orders'] }} orders</div>
            </div>
        </a>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-3 md:p-6 flex items-start gap-3">
            <div class="stat-icon bg-yellow-50 shrink-0">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
            <div class="min-w-0">
                <div class="text-lg md:text-2xl font-bold text-gray-900">{{ $stats['pending_orders'] }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Pending Orders</div>
                <div class="text-xs">
                    <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="text-blue-600 hover:underline">View all</a>
                </div>
            </div>
        </div>

<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-3 md:p-6 flex items-start gap-3">
            <div class="stat-icon bg-orange-50 shrink-0">
                <i class="fas fa-hand-holding-usd text-orange-600 text-xl"></i>
            </div>
            <div class="min-w-0">
                <div class="text-lg md:text-2xl font-bold text-gray-900 truncate">{{ number_format($stats['outstanding_khata']) }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Outstanding Khata (Rs.)</div>
                <div class="text-xs">
                    <a href="{{ route('admin.reports.accounts') }}" class="text-orange-600 hover:underline">View all</a>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-3 md:p-6 flex items-start gap-3">
            <div class="stat-icon bg-pink-50 shrink-0">
                <i class="fas fa-receipt text-pink-600 text-xl"></i>
            </div>
            <div class="min-w-0">
                <div class="text-lg md:text-2xl font-bold text-gray-900 truncate">{{ number_format($stats['today_expenses']) }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Today's Expenses (Rs.)</div>
            </div>
        </div>
    </div>

    {{-- Sales Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- POS Sales Chart --}}
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-gray-800">POS Sales <span class="text-xs font-normal text-gray-400">(Last 7 Days)</span></h3>
                <span class="text-xs font-semibold text-purple-600">
                    Rs. {{ number_format($posChart->sum('total')) }}
                </span>
            </div>
            <div class="card-body">
                <div class="flex items-end gap-2 h-36">
                    @php $posMax = $posChart->max('total') ?: 1; @endphp
                    @foreach($posChart as $day)
                        <div class="flex-1 flex flex-col items-center gap-1">
                            @if($day['total'] > 0)
                            <div class="text-gray-500" style="font-size:9px;">{{ number_format($day['total'] / 1000, 1) }}k</div>
                            @endif
                            <div class="w-full rounded-t-sm transition-all"
                                 style="height:{{ max(4, ($day['total'] / $posMax) * 100) }}px; background:#7c3aed;"
                                 title="Rs. {{ number_format($day['total']) }}"></div>
                            <div class="text-gray-400" style="font-size:9px;">{{ $day['date'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Ecommerce Sales Chart --}}
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-gray-800">Ecommerce Sales <span class="text-xs font-normal text-gray-400">(Last 7 Days)</span></h3>
                <span class="text-xs font-semibold text-primary-600">
                    Rs. {{ number_format($ecomChart->sum('total')) }}
                </span>
            </div>
            <div class="card-body">
                <div class="flex items-end gap-2 h-36">
                    @php $ecomMax = $ecomChart->max('total') ?: 1; @endphp
                    @foreach($ecomChart as $day)
                        <div class="flex-1 flex flex-col items-center gap-1">
                            @if($day['total'] > 0)
                            <div class="text-gray-500" style="font-size:9px;">{{ number_format($day['total'] / 1000, 1) }}k</div>
                            @endif
                            <div class="w-full rounded-t-sm transition-all"
                                 style="height:{{ max(4, ($day['total'] / $ecomMax) * 100) }}px; background:#e11d48;"
                                 title="Rs. {{ number_format($day['total']) }}"></div>
                            <div class="text-gray-400" style="font-size:9px;">{{ $day['date'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Low Stock + Today's Report --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        {{-- Low Stock Alerts --}}
        <div class="card lg:col-span-2">
            <div class="card-header">
                <h3 class="font-semibold text-gray-800">Low Stock</h3>
                <a href="{{ route('admin.inventory.low_stock') }}" class="text-xs text-primary-600 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($lowStockItems as $item)
                    <div class="px-4 py-2.5 flex items-center justify-between">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-800 truncate">{{ $item->name }}</div>
                            <div class="text-xs text-gray-500">{{ $item->category?->name }}</div>
                        </div>
                        <span class="badge {{ $item->stock_quantity <= 0 ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' }} ml-2 shrink-0">
                            {{ $item->stock_quantity }} left
                        </span>
                    </div>
                @empty
                    <div class="px-4 py-6 text-center text-sm text-gray-500">All stock levels are healthy.</div>
                @endforelse
            </div>
        </div>

        {{-- Today's Report --}}
        <div class="card lg:col-span-3" x-data="{ activeModal: null }">
            <div class="card-header">
                <h3 class="font-semibold text-gray-800">
                    Today's Report
                    <span class="text-xs font-normal text-gray-400 ml-1">{{ now()->format('d M Y') }}</span>
                </h3>
                <a href="{{ route('admin.dashboard.today-report') }}" target="_blank"
                   class="btn-outline btn-sm">
                    <i class="fas fa-print mr-1.5 text-gray-500"></i> Print
                </a>
            </div>
            <div class="p-4 space-y-3">

                {{-- POS Sales --}}
                <button type="button" @click="activeModal = 'pos'"
                        class="w-full text-left bg-blue-50 hover:bg-blue-100 rounded-xl p-3 transition-colors cursor-pointer group">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-semibold text-blue-800">
                            <i class="fas fa-cash-register mr-1.5"></i>POS Sales
                        </span>
                        <span class="flex items-center gap-2">
                            <span class="font-bold text-blue-900">Rs. {{ number_format($todayReport['pos_total']) }}</span>
                            <i class="fas fa-chevron-right text-blue-400 text-xs group-hover:translate-x-0.5 transition-transform"></i>
                        </span>
                    </div>
                    <div class="flex gap-4 text-xs text-blue-700">
                        <span><i class="fas fa-money-bill-wave mr-1"></i>Cash: Rs. {{ number_format($todayReport['pos_cash']) }}</span>
                        <span><i class="fas fa-university mr-1"></i>Bank: Rs. {{ number_format($todayReport['pos_bank']) }}</span>
                    </div>
                </button>

                {{-- Khata Received --}}
                <button type="button" @click="activeModal = 'khata'"
                        class="w-full text-left bg-green-50 hover:bg-green-100 rounded-xl p-3 transition-colors cursor-pointer group">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-semibold text-green-800">
                            <i class="fas fa-hand-holding-usd mr-1.5"></i>Khata Received
                        </span>
                        <span class="flex items-center gap-2">
                            <span class="font-bold text-green-900">Rs. {{ number_format($todayReport['khata_total']) }}</span>
                            <i class="fas fa-chevron-right text-green-400 text-xs group-hover:translate-x-0.5 transition-transform"></i>
                        </span>
                    </div>
                    <div class="flex gap-4 text-xs text-green-700">
                        <span><i class="fas fa-money-bill-wave mr-1"></i>Cash: Rs. {{ number_format($todayReport['khata_cash']) }}</span>
                        <span><i class="fas fa-university mr-1"></i>Bank: Rs. {{ number_format($todayReport['khata_bank']) }}</span>
                        @if($todayReport['khata_other'] > 0)
                        <span><i class="fas fa-question-circle mr-1"></i>Other: Rs. {{ number_format($todayReport['khata_other']) }}</span>
                        @endif
                    </div>
                </button>

                {{-- Expenses --}}
                <button type="button" @click="activeModal = 'expenses'"
                        class="w-full text-left bg-red-50 hover:bg-red-100 rounded-xl p-3 flex justify-between items-center transition-colors cursor-pointer group">
                    <span class="text-sm font-semibold text-red-800">
                        <i class="fas fa-receipt mr-1.5"></i>Expenses
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="font-bold text-red-900">Rs. {{ number_format($todayReport['expenses']) }}</span>
                        <i class="fas fa-chevron-right text-red-400 text-xs group-hover:translate-x-0.5 transition-transform"></i>
                    </span>
                </button>

                {{-- Returns --}}
                <button type="button" @click="activeModal = 'returns'"
                        class="w-full text-left bg-orange-50 hover:bg-orange-100 rounded-xl p-3 transition-colors cursor-pointer group">
                    <div class="flex justify-between items-center @if($todayReport['return_total'] > 0) mb-2 @endif">
                        <span class="text-sm font-semibold text-orange-800">
                            <i class="fas fa-undo-alt mr-1.5"></i>Returns (Refunded)
                        </span>
                        <span class="flex items-center gap-2">
                            <span class="font-bold text-orange-900">
                                @if($todayReport['return_total'] > 0)– @endif Rs. {{ number_format($todayReport['return_total']) }}
                            </span>
                            <i class="fas fa-chevron-right text-orange-400 text-xs group-hover:translate-x-0.5 transition-transform"></i>
                        </span>
                    </div>
                    @if($todayReport['return_total'] > 0)
                    <div class="flex gap-4 text-xs text-orange-700">
                        <span><i class="fas fa-money-bill-wave mr-1"></i>Cash: Rs. {{ number_format($todayReport['return_cash']) }}</span>
                        <span><i class="fas fa-university mr-1"></i>Bank: Rs. {{ number_format($todayReport['return_bank']) }}</span>
                    </div>
                    @endif
                </button>

                {{-- Grand totals --}}
                <div class="border-t border-gray-200 pt-3 space-y-1.5">
                    <div class="flex justify-between text-sm text-gray-600">
                        <span><i class="fas fa-money-bill-wave text-green-500 mr-1.5"></i>Total Cash In</span>
                        <span class="font-semibold">Rs. {{ number_format($todayReport['total_cash']) }}</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-600">
                        <span><i class="fas fa-university text-blue-500 mr-1.5"></i>Total Bank In</span>
                        <span class="font-semibold">Rs. {{ number_format($todayReport['total_bank']) }}</span>
                    </div>
                    <div class="flex justify-between text-base font-bold text-gray-900 border-t border-gray-200 pt-2 mt-1">
                        <span>Grand Total Received</span>
                        <span class="text-primary-700">Rs. {{ number_format($todayReport['grand_total']) }}</span>
                    </div>
                </div>
            </div>

            {{-- ===== DETAIL MODALS ===== --}}
            <template x-teleport="body">
            <div x-show="activeModal !== null"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
                 style="background:rgba(0,0,0,0.5);"
                 @keydown.escape.window="activeModal = null">

                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl flex flex-col"
                     style="max-height: 85vh;"
                     @click.outside="activeModal = null"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0">

                    {{-- Modal header --}}
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 shrink-0">
                        <h3 class="font-bold text-gray-900 text-base">
                            <span x-show="activeModal === 'pos'"><i class="fas fa-cash-register mr-2 text-blue-600"></i>Today's POS Sales</span>
                            <span x-show="activeModal === 'khata'"><i class="fas fa-hand-holding-usd mr-2 text-green-600"></i>Today's Khata Received</span>
                            <span x-show="activeModal === 'expenses'"><i class="fas fa-receipt mr-2 text-red-500"></i>Today's Expenses</span>
                            <span x-show="activeModal === 'returns'"><i class="fas fa-undo-alt mr-2 text-orange-500"></i>Today's Returns</span>
                        </h3>
                        <button @click="activeModal = null" class="text-gray-400 hover:text-gray-700 w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    {{-- Scrollable content --}}
                    <div class="overflow-y-auto flex-1 p-5">

                        {{-- POS Sales Table --}}
                        <div x-show="activeModal === 'pos'">
                            @if($todayPosOrders->isEmpty())
                                <p class="text-center text-gray-400 py-10">No POS sales today.</p>
                            @else
                            <div class="overflow-x-auto rounded-xl border border-gray-100">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                                        <tr>
                                            <th class="text-left px-4 py-2.5 font-semibold">Time</th>
                                            <th class="text-left px-4 py-2.5 font-semibold">Order #</th>
                                            <th class="text-left px-4 py-2.5 font-semibold">Customer</th>
                                            <th class="text-left px-4 py-2.5 font-semibold">Payment</th>
                                            <th class="text-right px-4 py-2.5 font-semibold">Amount</th>
                                        </tr>
                                    </thead>
                                    @foreach($todayPosOrders as $o)
                                    <tbody x-data="{ open: false }" class="border-b border-gray-100 last:border-0">
                                        {{-- Main order row (clickable) --}}
                                        <tr class="hover:bg-blue-50 cursor-pointer select-none transition-colors"
                                            @click="open = !open">
                                            <td class="px-4 py-3 text-xs text-gray-400 whitespace-nowrap">{{ $o->created_at->format('H:i') }}</td>
                                            <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $o->order_number }}</td>
                                            <td class="px-4 py-3 text-xs text-gray-700">{{ $o->customer_name }}</td>
                                            <td class="px-4 py-3">
                                                @php $pm = match($o->payment_method) {
                                                    'cash' => ['Cash','bg-green-100 text-green-700'],
                                                    'bank_transfer' => ['Bank','bg-blue-100 text-blue-700'],
                                                    'split' => ['Split','bg-teal-100 text-teal-700'],
                                                    'khata' => ['Khata','bg-red-100 text-red-700'],
                                                    'partial' => ['Partial','bg-orange-100 text-orange-700'],
                                                    default => [ucfirst($o->payment_method),'bg-gray-100 text-gray-600'],
                                                }; @endphp
                                                <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $pm[1] }}">{{ $pm[0] }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-right font-bold text-gray-900">
                                                <div class="flex items-center justify-end gap-2">
                                                    Rs. {{ number_format($o->total) }}
                                                    <i class="fas fa-chevron-down text-gray-300 text-xs transition-transform duration-200"
                                                       :class="open ? 'rotate-180 !text-blue-400' : ''"></i>
                                                </div>
                                            </td>
                                        </tr>
                                        {{-- Expanded items row --}}
                                        <tr x-show="open"
                                            x-transition:enter="transition-opacity ease-out duration-150"
                                            x-transition:enter-start="opacity-0"
                                            x-transition:enter-end="opacity-100"
                                            x-transition:leave="transition-opacity ease-in duration-100"
                                            x-transition:leave-start="opacity-100"
                                            x-transition:leave-end="opacity-0"
                                            style="display:none;">
                                            <td colspan="5" class="px-0 bg-blue-50/40">
                                                <div class="px-6 py-3 border-t border-blue-100">
                                                    @if($o->items->isEmpty())
                                                        <p class="text-xs text-gray-400 italic">No items found for this order.</p>
                                                    @else
                                                    <div class="space-y-2">
                                                        @foreach($o->items as $item)
                                                        <div class="flex items-center justify-between text-xs gap-3">
                                                            <div class="flex items-center gap-2 min-w-0">
                                                                <span class="text-blue-500 font-bold shrink-0">{{ $item->quantity }}×</span>
                                                                <span class="font-medium text-gray-800 truncate">{{ $item->product_name }}</span>
                                                                @if($item->color_name)
                                                                <span class="shrink-0 text-[10px] bg-white border border-gray-200 text-gray-500 px-1.5 py-0.5 rounded-full">{{ $item->color_name }}</span>
                                                                @endif
                                                                @if($item->free_quantity)
                                                                <span class="shrink-0 text-[10px] bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full font-medium">+{{ $item->free_quantity }} free</span>
                                                                @endif
                                                            </div>
                                                            <div class="flex items-center gap-3 shrink-0 text-gray-500">
                                                                <span class="text-gray-400">Rs. {{ number_format($item->unit_price) }}/ea</span>
                                                                @if($item->discount_amount > 0)
                                                                <span class="text-red-400 font-medium">–Rs. {{ number_format($item->discount_amount) }}</span>
                                                                @endif
                                                                <span class="font-semibold text-gray-800 w-20 text-right">Rs. {{ number_format($item->line_total) }}</span>
                                                            </div>
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                    @endforeach
                                    <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                        <tr>
                                            <td colspan="4" class="px-4 py-3 text-sm font-bold text-gray-700">{{ $todayPosOrders->count() }} orders</td>
                                            <td class="px-4 py-3 text-right font-bold text-primary-700">Rs. {{ number_format($todayPosOrders->sum('total')) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            {{-- Bank Account Breakdown --}}
                            @php
                                $bankBreakdown = $todayPosOrders
                                    ->filter(fn($o) => in_array($o->payment_method, ['bank_transfer','split']) && $o->bankAccount)
                                    ->groupBy('bank_account_id')
                                    ->map(function($group) {
                                        $account = $group->first()->bankAccount;
                                        $total   = $group->sum(fn($o) => match($o->payment_method) {
                                            'bank_transfer' => (float) $o->total,
                                            'split'         => (float) $o->bank_amount,
                                            default         => 0,
                                        });
                                        return ['account' => $account, 'total' => $total, 'count' => $group->count()];
                                    })
                                    ->filter(fn($r) => $r['total'] > 0)
                                    ->values();
                            @endphp
                            @if($bankBreakdown->isNotEmpty())
                            <div class="mt-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <i class="fas fa-university text-blue-500 text-xs"></i>
                                    <span class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Bank Account Breakdown</span>
                                </div>
                                <div class="grid gap-2">
                                    @foreach($bankBreakdown as $row)
                                    <div class="flex items-center justify-between bg-blue-50 rounded-xl px-4 py-3">
                                        <div>
                                            <div class="text-sm font-semibold text-blue-900">{{ $row['account']->label }}</div>
                                            <div class="text-xs text-blue-600 mt-0.5">
                                                {{ $row['account']->bank_name }}
                                                @if($row['account']->account_number)
                                                    · {{ $row['account']->account_number }}
                                                @endif
                                                <span class="ml-1 text-blue-400">· {{ $row['count'] }} {{ Str::plural('txn', $row['count']) }}</span>
                                            </div>
                                        </div>
                                        <div class="font-bold text-blue-900">Rs. {{ number_format($row['total']) }}</div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            @endif
                        </div>

                        {{-- Khata Received Table --}}
                        <div x-show="activeModal === 'khata'">
                            @if($todayKhataEntries->isEmpty())
                                <p class="text-center text-gray-400 py-10">No khata payments received today.</p>
                            @else
                            <div class="overflow-x-auto rounded-xl border border-gray-100">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                                        <tr>
                                            <th class="text-left px-4 py-2.5 font-semibold">Time</th>
                                            <th class="text-left px-4 py-2.5 font-semibold">Customer</th>
                                            <th class="text-left px-4 py-2.5 font-semibold">Description</th>
                                            <th class="text-left px-4 py-2.5 font-semibold">Method / Bank</th>
                                            <th class="text-right px-4 py-2.5 font-semibold">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @foreach($todayKhataEntries as $e)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-xs text-gray-400 whitespace-nowrap">{{ $e->created_at->format('H:i') }}</td>
                                            <td class="px-4 py-3 text-xs text-gray-700 font-medium">{{ $e->customer?->name ?? '—' }}</td>
                                            <td class="px-4 py-3 text-xs text-gray-500 max-w-xs truncate">{{ $e->description ?: '—' }}</td>
                                            <td class="px-4 py-3">
                                                @if($e->payment_method === 'cash')
                                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-green-100 text-green-700">Cash</span>
                                                @elseif($e->payment_method === 'bank_transfer')
                                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-blue-100 text-blue-700">
                                                        {{ $e->bankAccount?->label ?? 'Bank' }}
                                                    </span>
                                                    @if($e->bankAccount?->account_number)
                                                        <div class="text-[10px] text-gray-400 mt-0.5">{{ $e->bankAccount->account_number }}</div>
                                                    @endif
                                                @else
                                                    <span class="text-xs text-gray-300">—</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-right font-bold text-green-700">+ Rs. {{ number_format($e->amount) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                        <tr>
                                            <td colspan="4" class="px-4 py-3 text-sm font-bold text-gray-700">{{ $todayKhataEntries->count() }} payments</td>
                                            <td class="px-4 py-3 text-right font-bold text-green-700">Rs. {{ number_format($todayKhataEntries->sum('amount')) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            {{-- Bank Account Breakdown --}}
                            @php
                                $khataByBank = $todayKhataEntries
                                    ->where('payment_method', 'bank_transfer')
                                    ->filter(fn($e) => $e->bankAccount)
                                    ->groupBy('bank_account_id')
                                    ->map(fn($g) => [
                                        'account' => $g->first()->bankAccount,
                                        'total'   => $g->sum('amount'),
                                        'count'   => $g->count(),
                                    ])
                                    ->filter(fn($r) => $r['total'] > 0)
                                    ->values();
                                $khataCashTotal = $todayKhataEntries->where('payment_method', 'cash')->sum('amount');
                            @endphp
                            @if($khataByBank->isNotEmpty() || $khataCashTotal > 0)
                            <div class="mt-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <i class="fas fa-university text-green-500 text-xs"></i>
                                    <span class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Payment Breakdown</span>
                                </div>
                                <div class="grid gap-2">
                                    @if($khataCashTotal > 0)
                                    <div class="flex items-center justify-between bg-green-50 rounded-xl px-4 py-3">
                                        <div>
                                            <div class="text-sm font-semibold text-green-900">Cash</div>
                                            <div class="text-xs text-green-600 mt-0.5">{{ $todayKhataEntries->where('payment_method','cash')->count() }} {{ Str::plural('payment', $todayKhataEntries->where('payment_method','cash')->count()) }}</div>
                                        </div>
                                        <div class="font-bold text-green-900">Rs. {{ number_format($khataCashTotal) }}</div>
                                    </div>
                                    @endif
                                    @foreach($khataByBank as $row)
                                    <div class="flex items-center justify-between bg-blue-50 rounded-xl px-4 py-3">
                                        <div>
                                            <div class="text-sm font-semibold text-blue-900">{{ $row['account']->label }}</div>
                                            <div class="text-xs text-blue-600 mt-0.5">
                                                {{ $row['account']->bank_name }}
                                                @if($row['account']->account_number) · {{ $row['account']->account_number }} @endif
                                                <span class="ml-1 text-blue-400">· {{ $row['count'] }} {{ Str::plural('payment', $row['count']) }}</span>
                                            </div>
                                        </div>
                                        <div class="font-bold text-blue-900">Rs. {{ number_format($row['total']) }}</div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            @endif
                        </div>

                        {{-- Expenses Table --}}
                        <div x-show="activeModal === 'expenses'">
                            @if($todayExpensesList->isEmpty())
                                <p class="text-center text-gray-400 py-10">No expenses recorded today.</p>
                            @else
                            <div class="overflow-x-auto rounded-xl border border-gray-100">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                                        <tr>
                                            <th class="text-left px-4 py-2.5 font-semibold">Category</th>
                                            <th class="text-left px-4 py-2.5 font-semibold">Description</th>
                                            <th class="text-left px-4 py-2.5 font-semibold">Payment</th>
                                            <th class="text-right px-4 py-2.5 font-semibold">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @foreach($todayExpensesList as $exp)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-xs font-medium text-gray-700">{{ $exp->category ?? '—' }}</td>
                                            <td class="px-4 py-3 text-xs text-gray-500 max-w-xs">{{ $exp->description ?: '—' }}</td>
                                            <td class="px-4 py-3 text-xs text-gray-500 capitalize">{{ str_replace('_',' ', $exp->payment_method ?? '') ?: '—' }}</td>
                                            <td class="px-4 py-3 text-right font-bold text-red-600">Rs. {{ number_format($exp->amount) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                        <tr>
                                            <td colspan="3" class="px-4 py-3 text-sm font-bold text-gray-700">{{ $todayExpensesList->count() }} expenses</td>
                                            <td class="px-4 py-3 text-right font-bold text-red-600">Rs. {{ number_format($todayExpensesList->sum('amount')) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            @endif
                        </div>

                        {{-- Returns Table --}}
                        <div x-show="activeModal === 'returns'">
                            @if($todayReturnsList->isEmpty())
                                <p class="text-center text-gray-400 py-10">No returns processed today.</p>
                            @else
                            <div class="overflow-x-auto rounded-xl border border-gray-100">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                                        <tr>
                                            <th class="text-left px-4 py-2.5 font-semibold">Time</th>
                                            <th class="text-left px-4 py-2.5 font-semibold">Return #</th>
                                            <th class="text-left px-4 py-2.5 font-semibold">Orig. Order</th>
                                            <th class="text-left px-4 py-2.5 font-semibold">Items</th>
                                            <th class="text-left px-4 py-2.5 font-semibold">Method</th>
                                            <th class="text-right px-4 py-2.5 font-semibold">Refund</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @foreach($todayReturnsList as $ret)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-xs text-gray-400 whitespace-nowrap">{{ $ret->created_at->format('H:i') }}</td>
                                            <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $ret->return_number }}</td>
                                            <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $ret->order?->order_number ?? '—' }}</td>
                                            <td class="px-4 py-3 text-xs text-gray-600">
                                                @foreach($ret->items as $ri)
                                                    <div>{{ $ri->quantity }}× {{ $ri->product_name }}</div>
                                                @endforeach
                                            </td>
                                            <td class="px-4 py-3 text-xs text-gray-500 capitalize">{{ str_replace('_',' ', $ret->refund_method ?? '') }}</td>
                                            <td class="px-4 py-3 text-right font-bold text-orange-600">Rs. {{ number_format($ret->refund_amount) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                        <tr>
                                            <td colspan="5" class="px-4 py-3 text-sm font-bold text-gray-700">{{ $todayReturnsList->count() }} returns</td>
                                            <td class="px-4 py-3 text-right font-bold text-orange-600">Rs. {{ number_format($todayReturnsList->sum('refund_amount')) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            @endif
                        </div>

                    </div>{{-- end scrollable --}}
                </div>
            </div>
            </template>
        </div>
    </div>


</div>
@endsection
