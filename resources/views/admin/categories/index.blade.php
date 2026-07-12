@extends('layouts.admin')
@section('title', 'Categories')

@section('content')
@php $rPrefix = auth()->user()->hasRole('admin') ? 'admin' : 'salesman'; @endphp
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Categories</h1>
    <a href="{{ route("{$rPrefix}.categories.create") }}" class="btn-primary">
        <i class="fas fa-plus mr-2"></i> Add Category
    </a>
</div>

<div class="card p-4 mb-5">
    <form method="GET" action="{{ route("{$rPrefix}.categories.index") }}" class="flex flex-wrap gap-3 items-end">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search categories..."
               class="form-input text-sm flex-1 min-w-48" autofocus>
        <button type="submit" class="btn-primary btn-sm">Search</button>
        @if(request('q'))
        <a href="{{ route("{$rPrefix}.categories.index") }}" class="btn-outline btn-sm">Clear</a>
        @endif
    </form>
</div>

<div class="card">
    <div class="overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th class="w-12"></th>
                <th>Name</th>
                <th>Section</th>
                <th>Products</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $cat)
                {{-- Parent row --}}
                <tr class="bg-white">
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
                        @if($cat->children_count > 0)
                            <div class="text-xs text-indigo-500 mt-0.5">
                                <i class="fas fa-sitemap text-xs mr-1"></i>{{ $cat->children_count }} sub {{ Str::plural('category', $cat->children_count) }}
                            </div>
                        @endif
                    </td>
                    <td class="text-sm text-gray-500">{{ $cat->section?->name ?? '—' }}</td>
                    <td class="text-sm font-medium">{{ $cat->products_count }}</td>
                    <td>
                        <span class="badge {{ $cat->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $cat->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route("{$rPrefix}.categories.create", ['parent_id' => $cat->id]) }}"
                               class="btn-outline btn-sm text-indigo-600 border-indigo-200 hover:bg-indigo-50"
                               title="Add sub category">
                                <i class="fas fa-plus text-xs"></i> Sub
                            </a>
                            <a href="{{ route("{$rPrefix}.categories.edit", $cat) }}" class="btn-outline btn-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route("{$rPrefix}.categories.destroy", $cat) }}"
                                  onsubmit="return confirm('Delete this category and all its sub categories?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>

                {{-- Sub category rows --}}
                @foreach($cat->children as $sub)
                <tr class="bg-indigo-50/40">
                    <td>
                        <div class="flex items-center justify-end pr-1">
                            <i class="fas fa-level-up-alt fa-rotate-90 text-indigo-300 text-xs"></i>
                        </div>
                    </td>
                    <td>
                        <div class="pl-4 flex items-center gap-2">
                            <div>
                                <div class="font-medium text-gray-700 text-sm">{{ $sub->name }}</div>
                                <div class="text-xs text-gray-400 font-mono">{{ $sub->slug }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="text-sm text-gray-400">—</td>
                    <td class="text-sm font-medium text-gray-600">{{ $sub->subcategory_products_count }}</td>
                    <td>
                        <span class="badge {{ $sub->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $sub->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route("{$rPrefix}.categories.edit", $sub) }}" class="btn-outline btn-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route("{$rPrefix}.categories.destroy", $sub) }}"
                                  onsubmit="return confirm('Delete this sub category?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach

            @empty
            <tr>
                <td colspan="6" class="text-center py-12 text-gray-400">No categories yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection
