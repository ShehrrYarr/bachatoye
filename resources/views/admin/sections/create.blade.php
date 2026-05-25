@extends('layouts.admin')
@section('title', 'Add Section')
@section('page-title', 'Add Section')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.sections.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Add Section</h1>
</div>

<div class="max-w-lg">
    <form method="POST" action="{{ route('admin.sections.store') }}">
        @csrf
        <div class="card p-6 space-y-4">
            <div>
                <label class="form-label">Section Name *</label>
                <input type="text" name="name" value="{{ old('name') }}"
                       class="form-input @error('name') border-red-500 @enderror"
                       placeholder="e.g. Mobiles, Accessories" required>
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
                <p class="form-hint">The slug is auto-generated from the name.</p>
            </div>
            <div>
                <label class="form-label">Barcode Prefix <span class="text-gray-400 font-normal text-xs">(optional)</span></label>
                <input type="text" name="barcode_prefix" value="{{ old('barcode_prefix') }}"
                       class="form-input w-40 font-mono uppercase @error('barcode_prefix') border-red-500 @enderror"
                       placeholder="e.g. MOB" maxlength="10"
                       oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '')">
                @error('barcode_prefix') <p class="form-error">{{ $message }}</p> @enderror
                <p class="form-hint">Alphanumeric, max 10 chars. Barcodes for this section will be <code class="text-xs bg-gray-100 px-1 rounded">PREFIX-0001</code>, <code class="text-xs bg-gray-100 px-1 rounded">PREFIX-0002</code>, … Leave blank to use the global numeric sequence.</p>
            </div>
            <div>
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}"
                       min="0" class="form-input w-32">
                <p class="form-hint">Lower numbers appear first.</p>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">Create Section</button>
                <a href="{{ route('admin.sections.index') }}" class="btn-outline">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
