@extends('layouts.admin')
@section('title', 'Dashboard')
@section('page-title', $shop?->name . ' — Dashboard')

@section('content')

{{-- Quick actions --}}
<div class="flex flex-wrap gap-3 mb-6">
    <a href="{{ route('pos.index') }}" class="btn-primary btn-lg">
        <i class="fas fa-cash-register mr-2"></i> Open POS
    </a>
    <a href="{{ route('shop.customers.index') }}" class="btn-outline btn-lg">
        <i class="fas fa-users mr-2"></i> Customers
    </a>
    <a href="{{ route('shop.inventory.index') }}" class="btn-outline btn-lg">
        <i class="fas fa-warehouse mr-2"></i> Stock
    </a>
    <a href="{{ route('shop.expenses.index') }}" class="btn-outline btn-lg">
        <i class="fas fa-receipt mr-2"></i> Expenses
    </a>
</div>

{{-- Stat cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <div class="stat-icon bg-green-100"><i class="fas fa-money-bill-wave text-green-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900">Rs. {{ number_format($todaySales) }}</div>
            <div class="text-sm text-gray-500">Today's Sales ({{ $todayOrders }} orders)</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-blue-100"><i class="fas fa-calendar text-blue-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900">Rs. {{ number_format($monthSales) }}</div>
            <div class="text-sm text-gray-500">This Month</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-red-100"><i class="fas fa-book text-red-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900">Rs. {{ number_format($outstandingKhata) }}</div>
            <div class="text-sm text-gray-500">Khata Outstanding ({{ $customersCount }} customers)</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-purple-100"><i class="fas fa-boxes text-purple-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900">{{ number_format($stockUnits) }}</div>
            <div class="text-sm text-gray-500">Stock Units at Shop</div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Today's report --}}
    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-gray-800">Today's Report — {{ $todayReport['date'] }}</h2>
                <a href="{{ route('shop.dashboard.today-report') }}" target="_blank" class="btn-outline btn-sm">
                    <i class="fas fa-print mr-1"></i> Print
                </a>
            </div>
            <div class="p-5">
                <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr><th></th><th class="text-right">Cash</th><th class="text-right">Bank</th><th class="text-right">Total</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-medium text-gray-700">POS Sales</td>
                            <td class="text-right">Rs. {{ number_format($todayReport['pos_cash']) }}</td>
                            <td class="text-right">Rs. {{ number_format($todayReport['pos_bank']) }}</td>
                            <td class="text-right font-semibold">Rs. {{ number_format($todayReport['pos_total']) }}</td>
                        </tr>
                        <tr>
                            <td class="font-medium text-gray-700">Khata Payments Received</td>
                            <td class="text-right">Rs. {{ number_format($todayReport['khata_cash']) }}</td>
                            <td class="text-right">Rs. {{ number_format($todayReport['khata_bank']) }}</td>
                            <td class="text-right font-semibold">Rs. {{ number_format($todayReport['khata_total']) }}</td>
                        </tr>
                        <tr class="text-red-600">
                            <td class="font-medium">Returns / Refunds</td>
                            <td class="text-right">- Rs. {{ number_format($todayReport['return_cash']) }}</td>
                            <td class="text-right">- Rs. {{ number_format($todayReport['return_bank']) }}</td>
                            <td class="text-right font-semibold">- Rs. {{ number_format($todayReport['return_total']) }}</td>
                        </tr>
                        <tr class="text-red-600">
                            <td class="font-medium">Expenses</td>
                            <td class="text-right">- Rs. {{ number_format($todayReport['expense_cash']) }}</td>
                            <td class="text-right">- Rs. {{ number_format($todayReport['expense_bank']) }}</td>
                            <td class="text-right font-semibold">- Rs. {{ number_format($todayReport['expenses']) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50 font-bold text-gray-900">
                            <td>Net</td>
                            <td class="text-right">Rs. {{ number_format($todayReport['total_cash']) }}</td>
                            <td class="text-right">Rs. {{ number_format($todayReport['total_bank']) }}</td>
                            <td class="text-right">Rs. {{ number_format($todayReport['grand_total']) }}</td>
                        </tr>
                    </tfoot>
                </table>
                </div>
            </div>
        </div>

        {{-- Recent orders --}}
        <div class="card mt-6">
            <div class="card-header"><h2 class="font-semibold text-gray-800">Recent Orders</h2></div>
            <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr><th>Order #</th><th>Customer</th><th class="text-right">Total</th><th>Payment</th><th>Time</th></tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                    <tr>
                        <td class="font-mono text-sm">{{ $order->order_number }}</td>
                        <td class="text-sm">{{ $order->customer?->name ?? $order->customer_name ?? 'Walk-in' }}</td>
                        <td class="text-right font-semibold">Rs. {{ number_format($order->total) }}</td>
                        <td><span class="badge bg-gray-100 text-gray-600">{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</span></td>
                        <td class="text-xs text-gray-500">{{ $order->created_at->format('h:i A') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-8 text-gray-400">No orders yet today.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    {{-- Khata reminders --}}
    <div>
        <div class="card">
            <div class="card-header"><h2 class="font-semibold text-gray-800"><i class="fas fa-bell text-orange-500 mr-1.5"></i>Khata Reminders</h2></div>
            <div class="divide-y divide-gray-100">
                @forelse($khataReminders as $reminder)
                <div class="p-4 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <div class="font-medium text-gray-800 text-sm truncate">{{ $reminder->customer?->name }}</div>
                        <div class="text-xs text-gray-400">Promised: {{ \Carbon\Carbon::parse($reminder->promise_date)->format('d M Y') }}</div>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="font-semibold text-red-600 text-sm">Rs. {{ number_format(abs($reminder->customer?->credit_balance ?? 0)) }}</div>
                        <a href="{{ route('shop.customers.ledger', $reminder->customer) }}" class="text-xs text-primary-600 hover:underline">Khata</a>
                    </div>
                </div>
                @empty
                <div class="p-6 text-center text-gray-400 text-sm">No pending payment promises.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
