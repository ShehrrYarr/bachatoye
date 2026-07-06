@extends('layouts.admin')
@section('title', 'Sections')
@section('page-title', 'Sections')

@section('content')
@php $rPrefix = auth()->user()->hasRole('admin') ? 'admin' : 'salesman'; @endphp
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-900">Sections</h1>
        <p class="text-sm text-gray-500 mt-0.5">Group categories into sections and restrict salesmen access per section.</p>
    </div>
    <a href="{{ route("{$rPrefix}.sections.create") }}" class="btn-primary">
        <i class="fas fa-plus mr-2"></i> Add Section
    </a>
</div>

<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Section Name</th>
                <th>Slug</th>
                <th>Barcode Prefix</th>
                <th>Categories</th>
                <th>Sort Order</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sections as $section)
            <tr>
                <td class="font-semibold text-gray-800">{{ $section->name }}</td>
                <td class="font-mono text-xs text-gray-400">{{ $section->slug }}</td>
                <td>
                    @if($section->barcode_prefix)
                        <span class="font-mono text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded">{{ $section->barcode_prefix }}-####</span>
                    @else
                        <span class="text-xs text-gray-400">Global sequence</span>
                    @endif
                </td>
                <td>
                    <span class="badge bg-blue-100 text-blue-700">{{ $section->categories_count }} categories</span>
                </td>
                <td class="text-gray-500">{{ $section->sort_order }}</td>
                <td class="text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route("{$rPrefix}.sections.edit", $section) }}" class="btn-outline btn-sm">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" action="{{ route("{$rPrefix}.sections.destroy", $section) }}"
                              onsubmit="return confirm('Delete this section? Categories will be unassigned.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center py-10 text-gray-400">
                    <i class="fas fa-layer-group text-4xl mb-2"></i>
                    <p>No sections yet. Create your first section.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
