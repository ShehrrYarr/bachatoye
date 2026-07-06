@extends('layouts.admin')
@section('title', 'Edit Brand')

@section('content')
@php $rPrefix = auth()->user()->hasRole('admin') ? 'admin' : 'salesman'; @endphp
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route("{$rPrefix}.brands.index") }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Edit Brand: {{ $brand->name }}</h1>
</div>

<div class="max-w-xl">
    <form method="POST" action="{{ route("{$rPrefix}.brands.update", $brand) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="card p-6 space-y-4">
            @if($brand->logo)
            <div class="flex items-center gap-3">
                <img src="{{ $brand->logo_url }}" class="w-16 h-16 object-contain rounded-xl bg-gray-50 border border-gray-200 p-2">
                <div>
                    <p class="text-sm text-gray-600">Current logo</p>
                    <label class="flex items-center gap-2 mt-1 cursor-pointer">
                        <input type="checkbox" name="remove_logo" value="1" class="w-4 h-4 text-red-500 rounded">
                        <span class="text-sm text-red-500">Remove logo</span>
                    </label>
                </div>
            </div>
            @endif
            <div>
                <label class="form-label">Brand Name *</label>
                <input type="text" name="name" value="{{ old('name', $brand->name) }}" class="form-input @error('name') border-red-500 @enderror" required>
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label">Description</label>
                <textarea name="description" rows="2" class="form-textarea">{{ old('description', $brand->description) }}</textarea>
            </div>
            <div>
                <label class="form-label">Logo</label>
                <input type="file" name="logo" accept="image/*" class="form-input">
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $brand->is_active) ? 'checked' : '' }} class="w-4 h-4 text-primary-600 rounded">
                <label for="is_active" class="text-sm font-medium text-gray-700 cursor-pointer">Active</label>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">Update Brand</button>
                <a href="{{ route("{$rPrefix}.brands.index") }}" class="btn-outline">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
