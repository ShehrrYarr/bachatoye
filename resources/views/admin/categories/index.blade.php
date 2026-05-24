@extends('layouts.admin')
@section('title', 'Categories')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Categories</h1>
    <a href="{{ route('admin.categories.create') }}" class="btn-primary">
        <i class="fas fa-plus mr-2"></i> Add Category
    </a>
</div>

<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th class="w-12"></th>
                <th>Name</th>
                <th>Parent</th>
                <th>Products</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $cat)
            <tr>
                <td>
                    @if($cat->image)
                    <img src="{{ $cat->image_url }}" class="w-10 h-10 object-cover rounded-lg bg-gray-100">
                    @else
                    <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-folder text-gray-400"></i>
                    </div>
                    @endif
                </td>
                <td>
                    <div class="font-semibold text-gray-800">{{ $cat->name }}</div>
                    <div class="text-xs text-gray-400 font-mono">{{ $cat->slug }}</div>
                </td>
                <td class="text-sm text-gray-600">{{ $cat->parent?->name ?? '—' }}</td>
                <td class="text-sm font-medium">{{ $cat->products_count }}</td>
                <td>
                    @if($cat->is_active)
                        <span class="badge bg-green-100 text-green-700">Active</span>
                    @else
                        <span class="badge bg-gray-100 text-gray-500">Inactive</span>
                    @endif
                </td>
                <td class="text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.categories.edit', $cat) }}" class="btn-outline btn-sm">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.categories.destroy', $cat) }}"
                              onsubmit="return confirm('Delete this category?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center py-12 text-gray-400">No categories yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
