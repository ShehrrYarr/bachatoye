@extends('layouts.admin')
@section('title', 'Edit Section')
@section('page-title', 'Edit Section')

@section('content')
@php $rPrefix = auth()->user()->hasRole('admin') ? 'admin' : 'salesman'; @endphp
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route("{$rPrefix}.sections.index") }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Edit Section: {{ $section->name }}</h1>
</div>

<div class="max-w-lg space-y-5">
    <form method="POST" action="{{ route("{$rPrefix}.sections.update", $section) }}">
        @csrf @method('PUT')
        <div class="card p-6 space-y-4">
            <div>
                <label class="form-label">Section Name *</label>
                <input type="text" name="name" value="{{ old('name', $section->name) }}"
                       class="form-input @error('name') border-red-500 @enderror" required>
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label">Barcode Prefix <span class="text-gray-400 font-normal text-xs">(optional)</span></label>
                <input type="text" name="barcode_prefix" value="{{ old('barcode_prefix', $section->barcode_prefix) }}"
                       class="form-input w-40 font-mono uppercase @error('barcode_prefix') border-red-500 @enderror"
                       placeholder="e.g. MOB" maxlength="10"
                       oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '')">
                @error('barcode_prefix') <p class="form-error">{{ $message }}</p> @enderror
                <p class="form-hint">Alphanumeric, max 10 chars. Leave blank to use the global numeric sequence.</p>
            </div>
            <div>
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $section->sort_order) }}"
                       min="0" class="form-input w-32">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">Update Section</button>
                <a href="{{ route("{$rPrefix}.sections.index") }}" class="btn-outline">Cancel</a>
            </div>
        </div>
    </form>

    {{-- Categories in this section --}}
    @if($section->categories->count())
    <div class="card p-6">
        <h2 class="font-semibold text-gray-800 mb-3">Categories in this Section</h2>
        <div class="flex flex-wrap gap-2">
            @foreach($section->categories as $cat)
            <span class="badge bg-gray-100 text-gray-700 text-sm px-3 py-1">{{ $cat->name }}</span>
            @endforeach
        </div>
        <p class="text-xs text-gray-400 mt-3">To change a category's section, edit the category.</p>
    </div>
    @endif
</div>
@endsection
