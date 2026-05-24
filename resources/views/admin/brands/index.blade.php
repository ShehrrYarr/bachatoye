@extends('layouts.admin')
@section('title', 'Brands')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Brands</h1>
    <a href="{{ route('admin.brands.create') }}" class="btn-primary"><i class="fas fa-plus mr-2"></i> Add Brand</a>
</div>

<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th class="w-12"></th>
                <th>Name</th>
                <th>Products</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($brands as $brand)
            <tr>
                <td>
                    @if($brand->logo)
                    <img src="{{ $brand->logo_url }}" class="w-10 h-10 object-contain rounded-lg bg-gray-50 border border-gray-200 p-1">
                    @else
                    <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center font-bold text-gray-400 text-sm">
                        {{ strtoupper(substr($brand->name, 0, 2)) }}
                    </div>
                    @endif
                </td>
                <td>
                    <div class="font-semibold text-gray-800">{{ $brand->name }}</div>
                    <div class="text-xs text-gray-400 font-mono">{{ $brand->slug }}</div>
                </td>
                <td class="text-sm font-medium">{{ $brand->products_count }}</td>
                <td>
                    @if($brand->is_active)
                        <span class="badge bg-green-100 text-green-700">Active</span>
                    @else
                        <span class="badge bg-gray-100 text-gray-500">Inactive</span>
                    @endif
                </td>
                <td class="text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.brands.edit', $brand) }}" class="btn-outline btn-sm"><i class="fas fa-edit"></i></a>
                        <form method="POST" action="{{ route('admin.brands.destroy', $brand) }}" onsubmit="return confirm('Delete this brand?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center py-12 text-gray-400">No brands yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
