@extends('layouts.admin')
@section('title', 'Vendors')

@section('content')
@php $rPrefix = auth()->user()->panelPrefix(); @endphp
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Vendors</h1>
    @can('vendors.manage')
    <a href="{{ route("{$rPrefix}.vendors.create") }}" class="btn-primary btn-sm">
        <i class="fas fa-plus mr-1"></i> Add Vendor
    </a>
    @endcan
</div>

@php $balanceFilter = request('balance') === 'outstanding' ? 'we_owe' : request('balance'); @endphp

{{-- Totals — each card applies its filter --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <a href="{{ route("{$rPrefix}.vendors.index", ['balance' => 'owes_us']) }}"
       class="stat-card hover:shadow-md transition-all {{ $balanceFilter === 'owes_us' ? 'ring-2 ring-green-500' : '' }}">
        <div class="stat-icon bg-green-100"><i class="fas fa-hand-holding-usd text-green-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-green-600">Rs. {{ number_format($totals->owes_us) }}</div>
            <div class="text-sm text-gray-500">Vendors owe us · {{ (int) $totals->owes_us_count }} {{ Str::plural('vendor', (int) $totals->owes_us_count) }}</div>
        </div>
    </a>
    <a href="{{ route("{$rPrefix}.vendors.index", ['balance' => 'we_owe']) }}"
       class="stat-card hover:shadow-md transition-all {{ $balanceFilter === 'we_owe' ? 'ring-2 ring-red-400' : '' }}">
        <div class="stat-icon bg-red-100"><i class="fas fa-file-invoice-dollar text-red-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold text-red-600">Rs. {{ number_format($totals->we_owe) }}</div>
            <div class="text-sm text-gray-500">We owe vendors · {{ (int) $totals->we_owe_count }} {{ Str::plural('vendor', (int) $totals->we_owe_count) }}</div>
        </div>
    </a>
</div>

{{-- Filters --}}
<form method="GET" class="flex flex-wrap gap-3 mb-5">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name, phone, company..."
           class="form-input w-64">
    <select name="balance" class="form-select w-44">
        <option value="">All Vendors</option>
        <option value="owes_us" {{ $balanceFilter === 'owes_us' ? 'selected' : '' }}>They owe us</option>
        <option value="we_owe" {{ $balanceFilter === 'we_owe' ? 'selected' : '' }}>We owe them</option>
    </select>
    <button type="submit" class="btn-outline btn-sm">Filter</button>
    @if(request()->hasAny(['q','balance']))
        <a href="{{ route("{$rPrefix}.vendors.index") }}" class="btn-outline btn-sm">Clear</a>
    @endif
</form>

<div class="card">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Vendor</th>
                    <th>Contact</th>
                    <th class="text-center">Purchases</th>
                    <th class="text-center">Sales</th>
                    <th class="text-right">Balance</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vendors as $vendor)
                <tr>
                    <td>
                        <div class="font-medium text-gray-800">{{ $vendor->name }}</div>
                        @if($vendor->company)
                            <div class="text-xs text-gray-400">{{ $vendor->company }}</div>
                        @endif
                    </td>
                    <td>
                        <div class="text-sm">{{ $vendor->phone ?? '—' }}</div>
                        @if($vendor->email)
                            <div class="text-xs text-gray-400">{{ $vendor->email }}</div>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge bg-blue-100 text-blue-700">{{ $vendor->purchases_count }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-green-100 text-green-700">{{ $vendor->sales_count }}</span>
                    </td>
                    <td class="text-right">
                        @if($vendor->balance > 0)
                            <span class="font-semibold text-red-600">We owe Rs. {{ number_format($vendor->balance) }}</span>
                        @elseif($vendor->balance < 0)
                            <span class="font-semibold text-green-600">Owes us Rs. {{ number_format(abs($vendor->balance)) }}</span>
                        @else
                            <span class="text-gray-400">Settled</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route("{$rPrefix}.vendors.show", $vendor) }}" class="btn-outline btn-sm" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route("{$rPrefix}.vendors.khata", $vendor) }}"
                               class="btn-outline btn-sm text-purple-600 border-purple-300 hover:bg-purple-50" title="Khata / Ledger">
                                <i class="fas fa-book"></i>
                            </a>
                            @can('vendors.manage')
                            <a href="{{ route("{$rPrefix}.vendors.edit", $vendor) }}" class="btn-outline btn-sm" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-gray-400 py-8">No vendors found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($vendors->hasPages())
    <div class="px-5 py-4 border-t border-gray-100">{{ $vendors->links() }}</div>
    @endif
</div>
@endsection
