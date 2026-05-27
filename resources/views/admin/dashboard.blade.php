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
        <div class="card lg:col-span-3">
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
                <div class="bg-blue-50 rounded-xl p-3">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-semibold text-blue-800">
                            <i class="fas fa-cash-register mr-1.5"></i>POS Sales
                        </span>
                        <span class="font-bold text-blue-900">Rs. {{ number_format($todayReport['pos_total']) }}</span>
                    </div>
                    <div class="flex gap-4 text-xs text-blue-700">
                        <span><i class="fas fa-money-bill-wave mr-1"></i>Cash: Rs. {{ number_format($todayReport['pos_cash']) }}</span>
                        <span><i class="fas fa-university mr-1"></i>Bank: Rs. {{ number_format($todayReport['pos_bank']) }}</span>
                    </div>
                </div>

                {{-- Khata Received --}}
                <div class="bg-green-50 rounded-xl p-3">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-semibold text-green-800">
                            <i class="fas fa-hand-holding-usd mr-1.5"></i>Khata Received
                        </span>
                        <span class="font-bold text-green-900">Rs. {{ number_format($todayReport['khata_total']) }}</span>
                    </div>
                    <div class="flex gap-4 text-xs text-green-700">
                        <span><i class="fas fa-money-bill-wave mr-1"></i>Cash: Rs. {{ number_format($todayReport['khata_cash']) }}</span>
                        <span><i class="fas fa-university mr-1"></i>Bank: Rs. {{ number_format($todayReport['khata_bank']) }}</span>
                        @if($todayReport['khata_other'] > 0)
                        <span><i class="fas fa-question-circle mr-1"></i>Other: Rs. {{ number_format($todayReport['khata_other']) }}</span>
                        @endif
                    </div>
                </div>

                {{-- Expenses --}}
                <div class="bg-red-50 rounded-xl p-3 flex justify-between items-center">
                    <span class="text-sm font-semibold text-red-800">
                        <i class="fas fa-receipt mr-1.5"></i>Expenses
                    </span>
                    <span class="font-bold text-red-900">Rs. {{ number_format($todayReport['expenses']) }}</span>
                </div>

                {{-- Divider + Grand totals --}}
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
        </div>
    </div>


</div>
@endsection
