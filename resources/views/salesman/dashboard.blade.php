@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')
<div class="mb-6">
    <h1 class="text-xl font-bold text-gray-900">Welcome, {{ auth()->user()->name }}</h1>
    <p class="text-sm text-gray-500 mt-0.5">{{ now()->format('l, d F Y') }}</p>
</div>

{{-- Quick access buttons --}}
<div class="flex flex-wrap gap-3 mb-6">
    @can('pos.access')
    <a href="{{ route('pos.index') }}" class="btn-primary btn-lg">
        <i class="fas fa-cash-register mr-2"></i> Open POS
    </a>
    @endcan
    @can('orders.view')
    <a href="{{ route('admin.orders.index') }}" class="btn-outline btn-lg">
        <i class="fas fa-shopping-bag mr-2"></i> Orders
    </a>
    @endcan
    @can('inventory.view')
    <a href="{{ route('admin.inventory.index') }}" class="btn-outline btn-lg">
        <i class="fas fa-boxes mr-2"></i> Inventory
    </a>
    @endcan
    @can('customers.view')
    <a href="{{ route('admin.customers.index') }}" class="btn-outline btn-lg">
        <i class="fas fa-users mr-2"></i> Customers
    </a>
    @endcan
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <div class="stat-icon bg-primary-100"><i class="fas fa-chart-line text-primary-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900">Rs. {{ number_format($todaySales) }}</div>
            <div class="text-sm text-gray-500">My Sales Today</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-blue-100"><i class="fas fa-receipt text-blue-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900">{{ $todayOrders }}</div>
            <div class="text-sm text-gray-500">Orders Today</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-green-100"><i class="fas fa-calendar-week text-green-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900">Rs. {{ number_format($monthSales) }}</div>
            <div class="text-sm text-gray-500">This Month</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-orange-100"><i class="fas fa-exclamation-triangle text-orange-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-orange-600">{{ $lowStockCount }}</div>
            <div class="text-sm text-gray-500">Low Stock Alerts</div>
        </div>
    </div>
</div>

{{-- ── Today's Report ─────────────────────────────────────────────── --}}
<div class="card mb-6" x-data="{ activeModal: null }">
    <div class="card-header">
        <h3 class="font-semibold text-gray-800">
            Today's Report
            <span class="text-xs font-normal text-gray-400 ml-1">{{ now()->format('d M Y') }}</span>
        </h3>
    </div>
    <div class="p-4 space-y-3">

        {{-- POS Sales --}}
        <button type="button" @click="activeModal = 'pos'"
                class="w-full text-left bg-blue-50 hover:bg-blue-100 rounded-xl p-3 transition-colors cursor-pointer group">
            <div class="flex justify-between items-center mb-1.5">
                <span class="text-sm font-semibold text-blue-800">
                    <i class="fas fa-cash-register mr-1.5"></i>My POS Sales
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
            <div class="flex justify-between items-center mb-1.5">
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
            </div>
        </button>

        {{-- Returns --}}
        <button type="button" @click="activeModal = 'returns'"
                class="w-full text-left bg-orange-50 hover:bg-orange-100 rounded-xl p-3 transition-colors cursor-pointer group">
            <div class="flex justify-between items-center @if($todayReport['return_total'] > 0) mb-1.5 @endif">
                <span class="text-sm font-semibold text-orange-800">
                    <i class="fas fa-undo-alt mr-1.5"></i>Returns Processed
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

        {{-- Purchases (only if permission) --}}
        @can('purchases.view')
        <button type="button" @click="activeModal = 'purchases'"
                class="w-full text-left bg-purple-50 hover:bg-purple-100 rounded-xl p-3 transition-colors cursor-pointer group">
            <div class="flex justify-between items-center @if($todayReport['purchases_total'] > 0) mb-1.5 @endif">
                <span class="text-sm font-semibold text-purple-800">
                    <i class="fas fa-truck mr-1.5"></i>Purchases (Stock In)
                </span>
                <span class="flex items-center gap-2">
                    <span class="font-bold text-purple-900">Rs. {{ number_format($todayReport['purchases_total']) }}</span>
                    <i class="fas fa-chevron-right text-purple-400 text-xs group-hover:translate-x-0.5 transition-transform"></i>
                </span>
            </div>
            @if($todayReport['purchases_total'] > 0)
            <div class="flex gap-4 text-xs text-purple-700">
                <span><i class="fas fa-check-circle mr-1"></i>Paid: Rs. {{ number_format($todayReport['purchases_paid']) }}</span>
                @if($todayReport['purchases_due'] > 0)
                <span><i class="fas fa-clock mr-1"></i>Due: Rs. {{ number_format($todayReport['purchases_due']) }}</span>
                @endif
            </div>
            @endif
        </button>
        @endcan

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

    {{-- ── MODALS ── --}}
    <template x-teleport="body">
    <div x-show="activeModal !== null"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
         style="background:rgba(0,0,0,0.5);"
         @keydown.escape.window="activeModal = null">

        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl flex flex-col"
             style="max-height:85vh;"
             @click.outside="activeModal = null"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0">

            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 shrink-0">
                <h3 class="font-bold text-gray-900 text-base">
                    <span x-show="activeModal === 'pos'"><i class="fas fa-cash-register mr-2 text-blue-600"></i>My POS Sales Today</span>
                    <span x-show="activeModal === 'khata'"><i class="fas fa-hand-holding-usd mr-2 text-green-600"></i>Khata Received Today</span>
                    <span x-show="activeModal === 'returns'"><i class="fas fa-undo-alt mr-2 text-orange-500"></i>Returns Processed Today</span>
                    <span x-show="activeModal === 'purchases'"><i class="fas fa-truck mr-2 text-purple-600"></i>Today's Purchases</span>
                </h3>
                <button @click="activeModal = null" class="text-gray-400 hover:text-gray-700 w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Scrollable content --}}
            <div class="overflow-y-auto flex-1 p-5">

                {{-- POS Sales --}}
                <div x-show="activeModal === 'pos'">
                    @if($todayPosOrders->isEmpty())
                        <p class="text-center text-gray-400 py-10">No POS sales today.</p>
                    @else
                    <div class="overflow-x-auto rounded-xl border border-gray-100">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                                <tr>
                                    <th class="text-left px-4 py-2.5">Time</th>
                                    <th class="text-left px-4 py-2.5">Order #</th>
                                    <th class="text-left px-4 py-2.5">Customer</th>
                                    <th class="text-left px-4 py-2.5">Payment</th>
                                    <th class="text-right px-4 py-2.5">Amount</th>
                                </tr>
                            </thead>
                            @foreach($todayPosOrders as $o)
                            <tbody x-data="{ open: false }" class="border-b border-gray-100 last:border-0">
                                <tr class="hover:bg-blue-50 cursor-pointer select-none transition-colors" @click="open = !open">
                                    <td class="px-4 py-3 text-xs text-gray-400">{{ $o->created_at->format('H:i') }}</td>
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
                                <tr x-show="open" style="display:none;"
                                    x-transition:enter="transition-opacity ease-out duration-150"
                                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                                    <td colspan="5" class="px-0 bg-blue-50/40">
                                        <div class="px-6 py-3 border-t border-blue-100">
                                            @if($o->items->isEmpty())
                                                <p class="text-xs text-gray-400 italic">No items.</p>
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
                                                    </div>
                                                    <div class="flex items-center gap-3 shrink-0 text-gray-500">
                                                        <span class="text-gray-400">Rs. {{ number_format($item->unit_price) }}/ea</span>
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
                    @endif
                </div>

                {{-- Khata --}}
                <div x-show="activeModal === 'khata'">
                    @if($todayKhataEntries->isEmpty())
                        <p class="text-center text-gray-400 py-10">No khata payments today.</p>
                    @else
                    <div class="overflow-x-auto rounded-xl border border-gray-100">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                                <tr>
                                    <th class="text-left px-4 py-2.5">Time</th>
                                    <th class="text-left px-4 py-2.5">Customer</th>
                                    <th class="text-left px-4 py-2.5">Description</th>
                                    <th class="text-left px-4 py-2.5">Method / Bank</th>
                                    <th class="text-right px-4 py-2.5">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($todayKhataEntries as $e)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-xs text-gray-400">{{ $e->created_at->format('H:i') }}</td>
                                    <td class="px-4 py-3 text-xs font-medium text-gray-700">{{ $e->customer?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-500 max-w-xs truncate">{{ $e->description ?: '—' }}</td>
                                    <td class="px-4 py-3">
                                        @if($e->payment_method === 'cash')
                                            <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-green-100 text-green-700">Cash</span>
                                        @elseif($e->payment_method === 'bank_transfer')
                                            <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-blue-100 text-blue-700">{{ $e->bankAccount?->label ?? 'Bank' }}</span>
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
                    @endif
                </div>

                {{-- Returns --}}
                <div x-show="activeModal === 'returns'">
                    @if($todayReturnsList->isEmpty())
                        <p class="text-center text-gray-400 py-10">No returns processed today.</p>
                    @else
                    <div class="overflow-x-auto rounded-xl border border-gray-100">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                                <tr>
                                    <th class="text-left px-4 py-2.5">Time</th>
                                    <th class="text-left px-4 py-2.5">Return #</th>
                                    <th class="text-left px-4 py-2.5">Orig. Order</th>
                                    <th class="text-left px-4 py-2.5">Items</th>
                                    <th class="text-left px-4 py-2.5">Method</th>
                                    <th class="text-right px-4 py-2.5">Refund</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($todayReturnsList as $ret)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-xs text-gray-400">{{ $ret->created_at->format('H:i') }}</td>
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

                {{-- Purchases --}}
                @can('purchases.view')
                <div x-show="activeModal === 'purchases'">
                    @if($todayPurchasesList->isEmpty())
                        <p class="text-center text-gray-400 py-10">No purchases today.</p>
                    @else
                    <div class="overflow-x-auto rounded-xl border border-gray-100">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                                <tr>
                                    <th class="text-left px-4 py-2.5">Ref / Invoice</th>
                                    <th class="text-left px-4 py-2.5">Vendor</th>
                                    <th class="text-left px-4 py-2.5">Payment</th>
                                    <th class="text-left px-4 py-2.5">Status</th>
                                    <th class="text-right px-4 py-2.5">Total</th>
                                </tr>
                            </thead>
                            @foreach($todayPurchasesList as $p)
                            <tbody x-data="{ open: false }" class="border-b border-gray-100 last:border-0">
                                <tr class="hover:bg-purple-50 cursor-pointer select-none transition-colors" @click="open = !open">
                                    <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $p->reference ?: '—' }}</td>
                                    <td class="px-4 py-3 text-xs font-medium text-gray-700">{{ $p->vendor?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-500 capitalize">{{ str_replace('_',' ', $p->payment_method) }}</td>
                                    <td class="px-4 py-3">
                                        @php $st = match($p->payment_status) {
                                            'paid' => 'bg-green-100 text-green-700',
                                            'partial' => 'bg-yellow-100 text-yellow-700',
                                            default => 'bg-red-100 text-red-700',
                                        }; @endphp
                                        <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $st }}">{{ ucfirst($p->payment_status) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-gray-900">
                                        <div class="flex items-center justify-end gap-2">
                                            Rs. {{ number_format($p->total) }}
                                            <i class="fas fa-chevron-down text-gray-300 text-xs transition-transform duration-200"
                                               :class="open ? 'rotate-180 !text-purple-400' : ''"></i>
                                        </div>
                                    </td>
                                </tr>
                                <tr x-show="open" style="display:none;"
                                    x-transition:enter="transition-opacity ease-out duration-150"
                                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                                    <td colspan="5" class="px-0 bg-purple-50/40">
                                        <div class="px-6 py-3 border-t border-purple-100 space-y-2">
                                            @foreach($p->items as $item)
                                            <div class="flex items-center justify-between text-xs gap-3">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="text-purple-500 font-bold shrink-0">{{ $item->quantity }}×</span>
                                                    <span class="font-medium text-gray-800 truncate">{{ $item->product_name }}</span>
                                                    @if($item->color_name)
                                                    <span class="shrink-0 text-[10px] bg-white border border-gray-200 text-gray-500 px-1.5 py-0.5 rounded-full">{{ $item->color_name }}</span>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-3 shrink-0 text-gray-500">
                                                    <span class="text-gray-400">Rs. {{ number_format($item->unit_cost) }}/ea</span>
                                                    <span class="font-semibold text-gray-800 w-20 text-right">Rs. {{ number_format($item->line_total) }}</span>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            @endforeach
                            <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                <tr>
                                    <td colspan="4" class="px-4 py-3 text-sm font-bold text-gray-700">{{ $todayPurchasesList->count() }} purchases</td>
                                    <td class="px-4 py-3 text-right font-bold text-purple-700">Rs. {{ number_format($todayPurchasesList->sum('total')) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @endif
                </div>
                @endcan

            </div>{{-- end scrollable --}}
        </div>
    </div>
    </template>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Recent orders --}}
    <div class="card">
        <div class="card-header">
            <h2 class="font-semibold text-gray-800">My Recent Orders</h2>
            @can('orders.view')
            <a href="{{ route('admin.orders.index') }}" class="text-sm text-primary-600 hover:underline">View All</a>
            @endcan
        </div>
        <table class="data-table text-sm">
            <thead><tr><th>Order #</th><th>Customer</th><th class="text-right">Total</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($recentOrders as $order)
                <tr>
                    <td>
                        @can('orders.view')
                        <a href="{{ route('admin.orders.show', $order) }}" class="text-primary-600 hover:underline font-mono">{{ $order->order_number }}</a>
                        @else
                        <span class="font-mono">{{ $order->order_number }}</span>
                        @endcan
                    </td>
                    <td>{{ $order->customer_name }}</td>
                    <td class="text-right font-semibold">Rs. {{ number_format($order->total) }}</td>
                    <td>
                        <span class="badge {{ $order->status === 'delivered' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-6 text-gray-400">No orders today.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Low stock (if inventory access) --}}
    @can('inventory.view')
    <div class="card">
        <div class="card-header">
            <h2 class="font-semibold text-gray-800">Low Stock Alerts</h2>
            <a href="{{ route('admin.inventory.low_stock') }}" class="text-sm text-orange-600 hover:underline">View All</a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($lowStockItems as $product)
            <div class="flex items-center justify-between px-4 py-3">
                <div>
                    <div class="text-sm font-medium text-gray-800">{{ $product->name }}</div>
                    <div class="text-xs {{ $product->stock_quantity <= 0 ? 'text-red-500' : 'text-orange-500' }}">
                        {{ $product->stock_quantity <= 0 ? 'Out of Stock' : $product->stock_quantity.' units left' }}
                    </div>
                </div>
                @can('inventory.manage')
                <a href="{{ route('admin.inventory.adjust.form', $product) }}" class="btn-outline btn-sm">Restock</a>
                @endcan
            </div>
            @empty
            <div class="px-4 py-6 text-center text-gray-400 text-sm">
                <i class="fas fa-check-circle text-green-400 text-2xl mb-2"></i>
                <p>All products are well stocked</p>
            </div>
            @endforelse
        </div>
    </div>
    @else
    {{-- Pending orders if no inventory access --}}
    <div class="card">
        <div class="card-header"><h2 class="font-semibold text-gray-800">Pending Orders</h2></div>
        <table class="data-table text-sm">
            <thead><tr><th>Order #</th><th>Customer</th><th class="text-right">Total</th><th>Date</th></tr></thead>
            <tbody>
                @forelse($pendingOrders as $order)
                <tr>
                    <td class="font-mono">{{ $order->order_number }}</td>
                    <td>{{ $order->customer_name }}</td>
                    <td class="text-right font-semibold">Rs. {{ number_format($order->total) }}</td>
                    <td class="text-xs text-gray-500">{{ $order->created_at->format('d M') }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-6 text-gray-400">No pending orders.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endcan
</div>
@endsection
