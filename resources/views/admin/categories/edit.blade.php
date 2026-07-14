@extends('layouts.admin')
@section('title', 'Edit Category')

@section('content')
@php $rPrefix = auth()->user()->panelPrefix(); @endphp
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route("{$rPrefix}.categories.index") }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Edit Category: {{ $category->name }}</h1>
</div>

<div class="max-w-xl">
    <form method="POST" action="{{ route("{$rPrefix}.categories.update", $category) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="card p-6 space-y-4">
            @if($category->image)
            <div class="flex items-center gap-3">
                <img src="{{ $category->image_url }}" loading="lazy" class="w-16 h-16 object-cover rounded-xl bg-gray-100">
                <div>
                    <p class="text-sm text-gray-600">Current image</p>
                    <label class="flex items-center gap-2 mt-1 cursor-pointer">
                        <input type="checkbox" name="remove_image" value="1" class="w-4 h-4 text-red-500 rounded">
                        <span class="text-sm text-red-500">Remove image</span>
                    </label>
                </div>
            </div>
            @endif
            <div>
                <label class="form-label">Category Name *</label>
                <input type="text" name="name" value="{{ old('name', $category->name) }}" class="form-input @error('name') border-red-500 @enderror" required>
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label">Parent Category</label>
                <select name="parent_id" class="form-select">
                    <option value="">— Top Level —</option>
                    @foreach($parents as $p)
                    @if($p->id !== $category->id)
                    <option value="{{ $p->id }}" {{ old('parent_id', $category->parent_id) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endif
                    @endforeach
                </select>
            </div>
            @if($sections->count())
            <div>
                <label class="form-label">Section <span class="text-gray-400 font-normal text-xs">(for salesman access control)</span></label>
                <select name="section_id" class="form-select">
                    <option value="">— No Section —</option>
                    @foreach($sections as $sec)
                    <option value="{{ $sec->id }}" {{ old('section_id', $category->section_id) == $sec->id ? 'selected' : '' }}>{{ $sec->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label class="form-label">Description</label>
                <textarea name="description" rows="2" class="form-textarea">{{ old('description', $category->description) }}</textarea>
            </div>
            <div>
                <label class="form-label">Image</label>
                <input type="file" name="image" accept="image/*" class="form-input">
            </div>
            <div>
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order) }}" min="0" class="form-input w-32">
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }} class="w-4 h-4 text-primary-600 rounded">
                <label for="is_active" class="text-sm font-medium text-gray-700 cursor-pointer">Active</label>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="free_delivery" id="free_delivery" value="1" {{ old('free_delivery', $category->free_delivery) ? 'checked' : '' }} class="w-4 h-4 text-green-600 rounded">
                <label for="free_delivery" class="text-sm font-medium text-gray-700 cursor-pointer">
                    Free Delivery
                    <span class="block text-xs font-normal text-gray-400">All products in this category ship free, regardless of order total</span>
                </label>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">Update Category</button>
                <a href="{{ route("{$rPrefix}.categories.index") }}" class="btn-outline">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
