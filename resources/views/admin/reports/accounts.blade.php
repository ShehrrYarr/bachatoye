@extends('layouts.admin')
@section('title', 'Accounts / Khata Report')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Accounts & Khata Report</h1>
    <button onclick="window.print()" class="btn-outline btn-sm no-print">
        <i class="fas fa-print mr-1"></i> Print
    </button>
</div>

{{-- Summary --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card border-2 border-red-200">
        <div class="stat-icon bg-red-100"><i class="fas fa-hand-holding-usd text-red-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-red-600">Rs. {{ number_format($totalOutstanding) }}</div>
            <div class="text-sm text-gray-500">Total Outstanding</div>
        </div>
    </div>
    <div class="stat-card border-2 border-green-200">
        <div class="stat-icon bg-green-100"><i class="fas fa-piggy-bank text-green-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-green-600">Rs. {{ number_format($totalCredit) }}</div>
            <div class="text-sm text-gray-500">Customer Credits</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-orange-100"><i class="fas fa-users text-orange-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-orange-600">{{ $customersWithDebt }}</div>
            <div class="text-sm text-gray-500">Customers with Debt</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-blue-100"><i class="fas fa-exchange-alt text-blue-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold">{{ $khataOrders }}</div>
            <div class="text-sm text-gray-500">Khata Orders</div>
        </div>
    </div>
</div>

{{-- Customers with outstanding balance --}}
<div class="card overflow-hidden mb-6">
    <div class="card-header">
        <h2 class="font-semibold text-gray-800">Outstanding Balances</h2>
        <span class="text-sm text-gray-500">Customers who owe money</span>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Customer</th>
                <th>Phone</th>
                <th class="text-center">Orders</th>
                <th class="text-right">Total Purchased</th>
                <th class="text-right">Outstanding</th>
                <th>Last Activity</th>
                <th class="text-right no-print">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customersOwing as $customer)
            <tr>
                <td>
                    <a href="{{ route('admin.customers.ledger', $customer) }}" class="font-semibold text-gray-800 hover:text-primary-600">
                        {{ $customer->name }}
                    </a>
                    @if($customer->city)<div class="text-xs text-gray-400">{{ $customer->city }}</div>@endif
                </td>
                <td class="font-mono text-sm">{{ $customer->phone }}</td>
                <td class="text-center text-sm">{{ $customer->orders_count }}</td>
                <td class="text-right font-medium">Rs. {{ number_format($customer->orders->sum('total')) }}</td>
                <td class="text-right font-bold text-red-600">Rs. {{ number_format(abs($customer->credit_balance)) }}</td>
                <td class="text-xs text-gray-500">
                    {{ $customer->orders->max('created_at') ? \Carbon\Carbon::parse($customer->orders->max('created_at'))->format('d M Y') : '—' }}
                </td>
                <td class="text-right no-print">
                    <a href="{{ route('admin.customers.ledger', $customer) }}" class="btn-primary btn-sm">
                        <i class="fas fa-book mr-1"></i> Ledger
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center py-12 text-gray-400">
                    <i class="fas fa-check-circle text-green-400 text-4xl mb-3"></i>
                    <p class="text-green-600 font-medium">All accounts are settled!</p>
                </td>
            </tr>
            @endforelse
        </tbody>
        @if($customersOwing->count())
        <tfoot>
            <tr class="bg-gray-50 font-bold">
                <td colspan="4" class="text-right">Total Outstanding</td>
                <td class="text-right text-red-600">Rs. {{ number_format($totalOutstanding) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

{{-- Customers with credit --}}
@if($customersWithCredit->count())
<div class="card overflow-hidden">
    <div class="card-header">
        <h2 class="font-semibold text-gray-800 text-green-700">Customer Credits</h2>
        <span class="text-sm text-gray-500">Customers with positive balance</span>
    </div>
    <table class="data-table">
        <thead>
            <tr><th>Customer</th><th>Phone</th><th class="text-right">Credit Balance</th><th class="text-right no-print">Action</th></tr>
        </thead>
        <tbody>
            @foreach($customersWithCredit as $customer)
            <tr>
                <td class="font-semibold text-gray-800">{{ $customer->name }}</td>
                <td class="font-mono text-sm">{{ $customer->phone }}</td>
                <td class="text-right font-bold text-green-600">Rs. {{ number_format($customer->credit_balance) }}</td>
                <td class="text-right no-print">
                    <a href="{{ route('admin.customers.ledger', $customer) }}" class="btn-outline btn-sm">Ledger</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
