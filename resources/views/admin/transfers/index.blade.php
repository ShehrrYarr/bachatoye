@extends('layouts.admin')
@section('title', 'Stock Transfers')

@section('content')
@php $rPrefix = auth()->user()->panelPrefix(); @endphp
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Stock Transfers</h1>
    @if(auth()->user()->isAdmin())
    <a href="{{ route('admin.transfers.create') }}" class="btn-primary"><i class="fas fa-exchange-alt mr-2"></i> New Transfer</a>
    @endif
</div>

@if(auth()->user()->isAdmin())
<div class="card p-4 mb-5">
    <form method="GET" action="{{ route('admin.transfers.index') }}" class="flex flex-wrap gap-3 items-end">
        <select name="shop" class="form-select text-sm">
            <option value="">All Shops</option>
            @foreach($shops as $shop)
            <option value="{{ $shop->id }}" {{ request('shop') == $shop->id ? 'selected' : '' }}>{{ $shop->name }}</option>
            @endforeach
        </select>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input text-sm">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input text-sm">
        <button type="submit" class="btn-primary btn-sm">Filter</button>
        @if(request()->hasAny(['shop','date_from','date_to']))
        <a href="{{ route('admin.transfers.index') }}" class="btn-outline btn-sm">Clear</a>
        @endif
    </form>
</div>
@endif

<div class="card">
    <div class="overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>Transfer #</th>
                <th>From</th>
                <th>To</th>
                <th class="text-center">Items</th>
                <th class="text-center">Qty</th>
                <th>By</th>
                <th>Date</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transfers as $transfer)
            <tr>
                <td class="font-mono text-sm font-semibold">{{ $transfer->transfer_number }}</td>
                <td class="text-sm">{{ $transfer->from_label }}</td>
                <td class="text-sm font-medium text-gray-800">{{ $transfer->to_label }}</td>
                <td class="text-center text-sm">{{ $transfer->total_items }}</td>
                <td class="text-center font-semibold">{{ $transfer->total_qty }}</td>
                <td class="text-sm text-gray-500">{{ $transfer->creator?->name ?? '—' }}</td>
                <td class="text-xs text-gray-500">{{ $transfer->created_at->format('d M Y h:i A') }}</td>
                <td class="text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route("{$rPrefix}.transfers.show", $transfer) }}" class="btn-outline btn-sm" title="View"><i class="fas fa-eye"></i></a>
                        <a href="{{ route("{$rPrefix}.transfers.slip", $transfer) }}" target="_blank" class="btn-outline btn-sm" title="Print Slip"><i class="fas fa-print"></i></a>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center py-12 text-gray-400">No transfers yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    @if($transfers->hasPages())
    <div class="p-4 border-t border-gray-200">{{ $transfers->links() }}</div>
    @endif
</div>
@endsection
