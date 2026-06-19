@extends('layouts.admin')
@section('title', 'Date Range Report')
@section('page-title', 'Date Range Report')

@section('content')

{{-- Date filter --}}
<form method="GET" action="{{ route('admin.date-range-report') }}"
      class="flex flex-wrap items-end gap-3 mb-6">
    <div>
        <label class="form-label text-xs">From</label>
        <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="form-input" required>
    </div>
    <div>
        <label class="form-label text-xs">To</label>
        <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="form-input" required>
    </div>
    <button type="submit" class="btn-primary">
        <i class="fas fa-filter mr-1.5"></i> Generate Report
    </button>
    @if(request()->hasAny(['from','to']))
    <a href="{{ route('admin.date-range-report') }}" class="btn-outline">Reset</a>
    @endif
    <div class="ml-auto">
        <a href="{{ route('admin.date-range-report') }}?from={{ $from->format('Y-m-d') }}&to={{ $to->format('Y-m-d') }}&print=1"
           onclick="window.open(this.href,'_blank'); return false;"
           class="btn-outline btn-sm">
            <i class="fas fa-print mr-1.5"></i> Print
        </a>
    </div>
</form>

{{-- Period label --}}
<div class="flex items-center gap-3 mb-5">
    <h2 class="text-lg font-bold text-gray-900">
        @if($isSingleDay)
            Report for {{ $report['date_from'] }}
        @else
            Report: {{ $report['date_from'] }} — {{ $report['date_to'] }}
        @endif
    </h2>
    <span class="text-xs text-gray-400 bg-gray-100 px-2.5 py-1 rounded-full">
        {{ $productsSold->sum('total_qty') }} items sold · {{ $report['total_orders'] }} POS orders
    </span>
</div>

{{-- Summary cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">

    {{-- POS Sales --}}
    <div class="card p-4 border-l-4 border-blue-500">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-semibold text-blue-700"><i class="fas fa-cash-register mr-1.5"></i>POS Sales</span>
            <span class="text-lg font-bold text-blue-900">Rs. {{ number_format($report['pos_total']) }}</span>
        </div>
        <div class="flex gap-4 text-xs text-blue-600">
            <span><i class="fas fa-money-bill-wave mr-1"></i>Cash: Rs. {{ number_format($report['pos_cash']) }}</span>
            <span><i class="fas fa-university mr-1"></i>Bank: Rs. {{ number_format($report['pos_bank']) }}</span>
        </div>
    </div>

    {{-- Khata Collected --}}
    <div class="card p-4 border-l-4 border-green-500">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-semibold text-green-700"><i class="fas fa-book mr-1.5"></i>Khata Collected</span>
            <span class="text-lg font-bold text-green-900">Rs. {{ number_format($report['khata_total']) }}</span>
        </div>
        <div class="flex gap-4 text-xs text-green-600">
            <span><i class="fas fa-money-bill-wave mr-1"></i>Cash: Rs. {{ number_format($report['khata_cash']) }}</span>
            <span><i class="fas fa-university mr-1"></i>Bank: Rs. {{ number_format($report['khata_bank']) }}</span>
        </div>
    </div>

    {{-- Expenses --}}
    <div class="card p-4 border-l-4 border-red-400">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-semibold text-red-700"><i class="fas fa-receipt mr-1.5"></i>Expenses</span>
            <span class="text-lg font-bold text-red-900">Rs. {{ number_format($report['expense_total']) }}</span>
        </div>
        <div class="flex gap-4 text-xs text-red-600">
            <span><i class="fas fa-money-bill-wave mr-1"></i>Cash: Rs. {{ number_format($report['expense_cash']) }}</span>
            <span><i class="fas fa-university mr-1"></i>Bank: Rs. {{ number_format($report['expense_bank']) }}</span>
        </div>
    </div>

    {{-- Returns --}}
    <div class="card p-4 border-l-4 border-orange-400">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-semibold text-orange-700"><i class="fas fa-undo mr-1.5"></i>Returns / Refunds</span>
            <span class="text-lg font-bold text-orange-900">Rs. {{ number_format($report['return_total']) }}</span>
        </div>
        <div class="flex gap-4 text-xs text-orange-600">
            <span><i class="fas fa-money-bill-wave mr-1"></i>Cash: Rs. {{ number_format($report['return_cash']) }}</span>
            <span><i class="fas fa-university mr-1"></i>Bank: Rs. {{ number_format($report['return_bank']) }}</span>
        </div>
    </div>

    {{-- Purchases --}}
    <div class="card p-4 border-l-4 border-purple-400">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-semibold text-purple-700"><i class="fas fa-truck-loading mr-1.5"></i>Purchases</span>
            <span class="text-lg font-bold text-purple-900">Rs. {{ number_format($report['purchases_total']) }}</span>
        </div>
        <div class="flex flex-wrap gap-3 text-xs text-purple-600">
            <span>Paid: Rs. {{ number_format($report['purchases_paid']) }}</span>
            @if($report['purchases_due'] > 0)
            <span class="text-red-500">Due: Rs. {{ number_format($report['purchases_due']) }}</span>
            @endif
            <span><i class="fas fa-money-bill-wave mr-1"></i>Cash: Rs. {{ number_format($report['purchases_cash']) }}</span>
            <span><i class="fas fa-university mr-1"></i>Bank: Rs. {{ number_format($report['purchases_bank']) }}</span>
        </div>
    </div>

    {{-- Vendor Payments --}}
    @if(($report['vendor_pay_total'] ?? 0) > 0)
    <div class="card p-4 border-l-4 border-yellow-400">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-semibold text-yellow-700"><i class="fas fa-handshake mr-1.5"></i>Vendor Payments (Out)</span>
            <span class="text-lg font-bold text-yellow-900">– Rs. {{ number_format($report['vendor_pay_total']) }}</span>
        </div>
        <div class="flex flex-wrap gap-3 text-xs text-yellow-700">
            @if($report['vendor_pay_cash'] > 0)
            <span><i class="fas fa-money-bill-wave mr-1"></i>Cash: Rs. {{ number_format($report['vendor_pay_cash']) }}</span>
            @endif
            @if($report['vendor_pay_bank'] > 0)
            <span><i class="fas fa-university mr-1"></i>Bank: Rs. {{ number_format($report['vendor_pay_bank']) }}</span>
            @endif
        </div>
    </div>
    @endif

    {{-- Net totals --}}
    <div class="card p-4 border-l-4 border-gray-800 bg-gray-50">
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-bold text-gray-800">Net Position</span>
            <span class="text-xl font-extrabold {{ $report['grand_total'] >= 0 ? 'text-green-700' : 'text-red-600' }}">
                Rs. {{ number_format($report['grand_total']) }}
            </span>
        </div>
        <div class="grid grid-cols-2 gap-2 text-xs">
            <div class="bg-white rounded-lg px-3 py-2 border border-gray-200">
                <div class="text-gray-500 mb-0.5">Cash In Hand</div>
                <div class="font-bold {{ $report['total_cash'] >= 0 ? 'text-gray-900' : 'text-red-600' }}">
                    Rs. {{ number_format($report['total_cash']) }}
                </div>
            </div>
            <div class="bg-white rounded-lg px-3 py-2 border border-gray-200">
                <div class="text-gray-500 mb-0.5">Bank Net</div>
                <div class="font-bold {{ $report['total_bank'] >= 0 ? 'text-gray-900' : 'text-red-600' }}">
                    Rs. {{ number_format($report['total_bank']) }}
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Products sold table --}}
<div class="card overflow-hidden">
    <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-bold text-gray-900"><i class="fas fa-boxes text-gray-400 mr-2"></i>Products Sold</h3>
        <span class="text-xs text-gray-400">{{ $productsSold->count() }} products · {{ number_format($productsSold->sum('total_qty')) }} units</span>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th class="text-center">Qty Sold</th>
                <th class="text-right">Revenue</th>
            </tr>
        </thead>
        <tbody>
            @forelse($productsSold as $i => $product)
            <tr>
                <td class="text-gray-400 text-xs w-10">{{ $i + 1 }}</td>
                <td class="font-medium text-gray-800">{{ $product->product_name }}</td>
                <td class="text-center">
                    <span class="inline-block bg-blue-50 text-blue-700 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                        {{ number_format($product->total_qty) }}
                    </span>
                </td>
                <td class="text-right font-semibold text-gray-800">Rs. {{ number_format($product->total_revenue) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center py-12 text-gray-400">
                    <i class="fas fa-box-open text-3xl mb-3"></i>
                    <p class="text-sm">No products sold in this period</p>
                </td>
            </tr>
            @endforelse
        </tbody>
        @if($productsSold->count() > 0)
        <tfoot>
            <tr class="bg-gray-50 font-semibold">
                <td colspan="2" class="text-right text-gray-600 text-sm">Total</td>
                <td class="text-center text-gray-800">{{ number_format($productsSold->sum('total_qty')) }}</td>
                <td class="text-right text-gray-800">Rs. {{ number_format($productsSold->sum('total_revenue')) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

@endsection
