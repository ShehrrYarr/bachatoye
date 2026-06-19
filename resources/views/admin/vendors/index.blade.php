@extends('layouts.admin')
@section('title', 'Vendors')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Vendors</h1>
    <a href="{{ route('admin.vendors.create') }}" class="btn-primary btn-sm">
        <i class="fas fa-plus mr-1"></i> Add Vendor
    </a>
</div>

{{-- Filters --}}
<form method="GET" class="flex flex-wrap gap-3 mb-5">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name, phone, company..."
           class="form-input w-64">
    <select name="balance" class="form-select w-44">
        <option value="">All Vendors</option>
        <option value="outstanding" {{ request('balance') === 'outstanding' ? 'selected' : '' }}>With Outstanding Balance</option>
    </select>
    <button type="submit" class="btn-outline btn-sm">Filter</button>
    @if(request()->hasAny(['q','balance']))
        <a href="{{ route('admin.vendors.index') }}" class="btn-outline btn-sm">Clear</a>
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
                    <th class="text-right">Balance Owed</th>
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
                    <td class="text-right">
                        @if($vendor->balance > 0)
                            <span class="font-semibold text-red-600">Rs. {{ number_format($vendor->balance) }}</span>
                        @elseif($vendor->balance < 0)
                            <span class="font-semibold text-green-600">Cr. Rs. {{ number_format(abs($vendor->balance)) }}</span>
                        @else
                            <span class="text-gray-400">Settled</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.vendors.show', $vendor) }}" class="btn-outline btn-sm" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.vendors.khata', $vendor) }}"
                               class="btn-outline btn-sm text-purple-600 border-purple-300 hover:bg-purple-50" title="Khata / Ledger">
                                <i class="fas fa-book"></i>
                            </a>
                            <a href="{{ route('admin.vendors.edit', $vendor) }}" class="btn-outline btn-sm" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-gray-400 py-8">No vendors found.</td>
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
