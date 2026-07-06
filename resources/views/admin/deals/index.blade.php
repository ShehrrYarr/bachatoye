@extends('layouts.admin')
@section('title', 'Deals & Promotions')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Deals & Promotions</h1>
    <a href="{{ route('admin.deals.create') }}" class="btn-primary"><i class="fas fa-plus mr-2"></i> Create Deal</a>
</div>

<div class="card">
    <div class="overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>Deal Name</th>
                <th>Type</th>
                <th>Badge</th>
                <th>Date Range</th>
                <th>Products</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($deals as $deal)
            <tr>
                <td>
                    <div class="font-semibold text-gray-800">{{ $deal->name }}</div>
                </td>
                <td>
                    <span class="badge
                        @if($deal->type === 'percentage') bg-blue-100 text-blue-700
                        @elseif($deal->type === 'flat') bg-purple-100 text-purple-700
                        @else bg-green-100 text-green-700 @endif">
                        @if($deal->type === 'percentage') % Off
                        @elseif($deal->type === 'flat') Flat Off
                        @else Buy X Get Y @endif
                    </span>
                </td>
                <td>
                    <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">{{ $deal->badge_label }}</span>
                </td>
                <td class="text-xs text-gray-500">
                    @if($deal->starts_at || $deal->ends_at)
                        {{ $deal->starts_at?->format('d M') ?? '—' }} → {{ $deal->ends_at?->format('d M') ?? '—' }}
                    @else <span class="text-gray-400">Always on</span>
                    @endif
                </td>
                <td class="text-sm text-gray-600">
                    @if($deal->products->count())
                        {{ $deal->products->count() }} products
                    @elseif($deal->categories->count())
                        {{ $deal->categories->count() }} categories
                    @else
                        <span class="text-gray-400">All products</span>
                    @endif
                </td>
                <td>
                    @if($deal->isActive())
                        <span class="badge bg-green-100 text-green-700">Active</span>
                    @elseif($deal->is_active)
                        <span class="badge bg-yellow-100 text-yellow-700">Scheduled</span>
                    @else
                        <span class="badge bg-gray-100 text-gray-500">Inactive</span>
                    @endif
                </td>
                <td class="text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.deals.edit', $deal) }}" class="btn-outline btn-sm"><i class="fas fa-edit"></i></a>
                        <form method="POST" action="{{ route('admin.deals.destroy', $deal) }}" onsubmit="return confirm('Delete this deal?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center py-12 text-gray-400">No deals created yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection
