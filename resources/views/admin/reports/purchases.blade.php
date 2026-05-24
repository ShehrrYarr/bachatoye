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
<form method="GET" class="flex flex-wrap gap-3 mb-6">
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
    <input type="date" name="from" value="{{ request('from', now()->startOfMonth()->toDateString()) }}" class="form-input w-40">
    <input type="date" name="to" value="{{ request('to', now()->toDateString()) }}" class="form-input w-40">
    <button type="submit" class="btn-primary btn-sm">Generate Report</button>
    <a href="{{ route('admin.reports.purchases') }}" class="btn-outline btn-sm">Reset</a>
</form>

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
@endsection
