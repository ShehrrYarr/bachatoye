@extends('layouts.admin')
@section('title', 'Banners')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Banners</h1>
    <a href="{{ route('admin.banners.create') }}" class="btn-primary"><i class="fas fa-plus mr-2"></i> Add Banner</a>
</div>

<div class="card">
    <div class="overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th class="w-24">Preview</th>
                <th>Title</th>
                <th>Position</th>
                <th>Date Range</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($banners as $banner)
            <tr>
                <td>
                    <img src="{{ $banner->image_url }}" loading="lazy" class="w-20 h-12 object-cover rounded-lg bg-gray-100">
                </td>
                <td>
                    <div class="font-medium text-gray-800">{{ $banner->title ?? '(No title)' }}</div>
                    @if($banner->subtitle)<div class="text-xs text-gray-400 truncate max-w-xs">{{ $banner->subtitle }}</div>@endif
                </td>
                <td>
                    <span class="badge
                        @if($banner->position === 'hero') bg-blue-100 text-blue-700
                        @elseif($banner->position === 'promo') bg-purple-100 text-purple-700
                        @else bg-gray-100 text-gray-600 @endif">
                        {{ ucfirst($banner->position) }}
                    </span>
                </td>
                <td class="text-xs text-gray-500">
                    @if($banner->starts_at || $banner->ends_at)
                        {{ $banner->starts_at?->format('d M') ?? '—' }} → {{ $banner->ends_at?->format('d M') ?? '—' }}
                    @else
                        <span class="text-gray-400">Always on</span>
                    @endif
                </td>
                <td>
                    @if($banner->is_active)
                        <span class="badge bg-green-100 text-green-700">Active</span>
                    @else
                        <span class="badge bg-gray-100 text-gray-500">Inactive</span>
                    @endif
                </td>
                <td class="text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.banners.edit', $banner) }}" class="btn-outline btn-sm"><i class="fas fa-edit"></i></a>
                        <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" onsubmit="return confirm('Delete this banner?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center py-12 text-gray-400">No banners yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection
