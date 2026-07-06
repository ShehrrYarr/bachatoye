@extends('layouts.admin')
@section('title', 'Coupon Codes')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Coupon Codes</h1>
    <a href="{{ route('admin.coupons.create') }}" class="btn-primary">
        <i class="fas fa-plus mr-2"></i> Create Coupon
    </a>
</div>

<div class="card">
    <div class="overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Discount</th>
                <th>Scope</th>
                <th>Expiry</th>
                <th>Used</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($coupons as $coupon)
            <tr>
                <td>
                    <span class="font-mono font-bold text-sm tracking-widest text-primary-700 bg-primary-50 px-2 py-1 rounded-lg">{{ $coupon->code }}</span>
                </td>
                <td>
                    <div class="font-semibold text-gray-800">{{ $coupon->name }}</div>
                    @if($coupon->description)
                        <div class="text-xs text-gray-400 mt-0.5">{{ Str::limit($coupon->description, 50) }}</div>
                    @endif
                </td>
                <td>
                    <span class="badge {{ $coupon->type === 'percentage' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                        {{ $coupon->badge_label }}
                    </span>
                    @if($coupon->min_order_amount)
                        <div class="text-xs text-gray-400 mt-0.5">Min: Rs. {{ number_format($coupon->min_order_amount) }}</div>
                    @endif
                </td>
                <td class="text-sm text-gray-600">
                    @if($coupon->applies_to === 'products')
                        <span class="text-orange-600"><i class="fas fa-box text-xs mr-1"></i>{{ $coupon->products_count }} product(s)</span>
                    @elseif($coupon->applies_to === 'categories')
                        <span class="text-teal-600"><i class="fas fa-tags text-xs mr-1"></i>{{ $coupon->categories_count }} category(ies)</span>
                    @else
                        <span class="text-gray-400">All products</span>
                    @endif
                </td>
                <td class="text-xs text-gray-500">
                    @if($coupon->expires_at)
                        {{ $coupon->expires_at->format('d M Y') }}
                        @if($coupon->expires_at->isPast())
                            <span class="text-red-500 block">Expired</span>
                        @endif
                    @else
                        <span class="text-gray-400">No expiry</span>
                    @endif
                </td>
                <td class="text-sm text-gray-600">{{ $coupon->times_used }}×</td>
                <td>
                    <form method="POST" action="{{ route('admin.coupons.toggle', $coupon) }}">
                        @csrf @method('PATCH')
                        <button type="submit"
                                class="badge cursor-pointer {{ $coupon->isActive() ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $coupon->isActive() ? 'Active' : 'Inactive' }}
                        </button>
                    </form>
                </td>
                <td class="text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn-outline btn-sm">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}"
                              onsubmit="return confirm('Delete coupon {{ $coupon->code }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center py-16 text-gray-400">
                    <i class="fas fa-ticket-alt text-4xl mb-3 block opacity-30"></i>
                    No coupons created yet.
                    <a href="{{ route('admin.coupons.create') }}" class="text-primary-600 hover:underline ml-1">Create your first coupon.</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

@if($coupons->hasPages())
<div class="mt-4">{{ $coupons->links() }}</div>
@endif
@endsection
