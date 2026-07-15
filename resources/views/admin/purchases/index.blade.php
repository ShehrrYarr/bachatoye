@extends('layouts.admin')
@section('title', 'Purchases')

@section('content')
@php
    $isAdmin = auth()->user()->hasRole('admin');
    $rPrefix = $isAdmin ? 'admin' : 'salesman';
@endphp
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Purchases</h1>
    @if($isAdmin || auth()->user()->can('purchases.manage'))
    <a href="{{ route("{$rPrefix}.purchases.create") }}" class="btn-primary btn-sm">
        <i class="fas fa-plus mr-1"></i> Record Purchase
    </a>
    @endif
</div>

{{-- Filters --}}
<form method="GET" class="flex flex-wrap gap-3 mb-5">
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
    <input type="date" name="from" value="{{ request('from') }}" class="form-input w-40" placeholder="From date">
    <input type="date" name="to" value="{{ request('to') }}" class="form-input w-40" placeholder="To date">
    <button type="submit" class="btn-outline btn-sm">Filter</button>
    @if(request()->hasAny(['vendor','status','from','to']))
        <a href="{{ route("{$rPrefix}.purchases.index") }}" class="btn-outline btn-sm">Clear</a>
    @endif
</form>

<div class="card">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Vendor</th>
                    <th>Reference</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Paid</th>
                    <th class="text-right">Balance</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                <tr>
                    <td class="text-sm">
                        {{ $purchase->purchase_date->format('d M Y') }}
                        <div class="text-xs text-gray-400">{{ $purchase->created_at->format('h:i A') }}</div>
                    </td>
                    <td>
                        @if($purchase->vendor)
                            <a href="{{ route('admin.vendors.show', $purchase->vendor) }}" class="text-primary-600 hover:underline font-medium">
                                {{ $purchase->vendor->name }}
                            </a>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="font-mono text-xs text-gray-500">{{ $purchase->reference ?? '—' }}</td>
                    <td class="text-right font-semibold">Rs. {{ number_format($purchase->total) }}</td>
                    <td class="text-right">Rs. {{ number_format($purchase->amount_paid) }}</td>
                    <td class="text-right {{ $purchase->balance_due > 0 ? 'text-red-600 font-semibold' : 'text-gray-400' }}">
                        {{ $purchase->balance_due > 0 ? 'Rs. '.number_format($purchase->balance_due) : '—' }}
                    </td>
                    <td>
                        <span class="badge
                            @if($purchase->payment_status === 'paid') bg-green-100 text-green-700
                            @elseif($purchase->payment_status === 'partial') bg-orange-100 text-orange-700
                            @else bg-red-100 text-red-700 @endif">
                            {{ ucfirst($purchase->payment_status) }}
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center gap-1.5">
                            <a href="{{ route("{$rPrefix}.purchases.show", $purchase) }}" class="btn-outline btn-sm">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if(auth()->user()->hasRole('admin'))
                            <a href="{{ route('admin.purchases.edit', $purchase) }}" class="btn-outline btn-sm text-blue-600 border-blue-200 hover:bg-blue-50">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.purchases.destroy', $purchase) }}"
                                  onsubmit="return confirm('Delete purchase {{ $purchase->reference ?? 'PUR-'.$purchase->id }}? Stock and vendor ledger will be reversed.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-outline btn-sm text-red-600 border-red-200 hover:bg-red-50">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-gray-400 py-10">
                        No purchases recorded yet. <a href="{{ route("{$rPrefix}.purchases.create") }}" class="text-primary-600 hover:underline">Record first purchase</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($purchases->count() > 0)
            <tfoot>
                <tr class="bg-gray-50 font-semibold">
                    <td colspan="3" class="text-right text-sm text-gray-500">Page Total</td>
                    <td class="text-right">Rs. {{ number_format($purchases->sum('total')) }}</td>
                    <td class="text-right">Rs. {{ number_format($purchases->sum('amount_paid')) }}</td>
                    <td class="text-right text-red-600">Rs. {{ number_format($purchases->sum(fn($p) => $p->balance_due)) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    @if($purchases->hasPages())
    <div class="px-5 py-4 border-t border-gray-100">{{ $purchases->links() }}</div>
    @endif
</div>
@endsection
