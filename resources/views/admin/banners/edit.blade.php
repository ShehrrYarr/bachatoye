@extends('layouts.admin')
@section('title', 'Edit Banner')

@section('content')
@php $rPrefix = auth()->user()->panelPrefix(); @endphp
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route("{$rPrefix}.banners.index") }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Edit Banner</h1>
</div>

<div class="max-w-2xl">
    <form method="POST" action="{{ route("{$rPrefix}.banners.update", $banner) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="card p-6 space-y-4">
            <div>
                <img src="{{ $banner->image_url }}" loading="lazy" class="w-full h-40 object-cover rounded-xl bg-gray-100 mb-3">
                <label class="form-label">Replace Image</label>
                <input type="file" name="image" accept="image/*" class="form-input">
            </div>
            <div>
                <label class="form-label">Position *</label>
                <select name="position" class="form-select" required>
                    <option value="hero" {{ old('position', $banner->position) === 'hero' ? 'selected' : '' }}>Hero (main slider)</option>
                    <option value="promo" {{ old('position', $banner->position) === 'promo' ? 'selected' : '' }}>Promo</option>
                    <option value="sidebar" {{ old('position', $banner->position) === 'sidebar' ? 'selected' : '' }}>Sidebar</option>
                </select>
            </div>
            <div>
                <label class="form-label">Title</label>
                <input type="text" name="title" value="{{ old('title', $banner->title) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Subtitle</label>
                <input type="text" name="subtitle" value="{{ old('subtitle', $banner->subtitle) }}" class="form-input">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Link URL</label>
                    <input type="url" name="link_url" value="{{ old('link_url', $banner->link_url) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Button Text</label>
                    <input type="text" name="button_text" value="{{ old('button_text', $banner->button_text) }}" class="form-input">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Start Date</label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $banner->starts_at?->format('Y-m-d\TH:i')) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">End Date</label>
                    <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $banner->ends_at?->format('Y-m-d\TH:i')) }}" class="form-input">
                </div>
            </div>
            <div>
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $banner->sort_order) }}" min="0" class="form-input w-28">
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $banner->is_active) ? 'checked' : '' }} class="w-4 h-4 text-primary-600 rounded">
                <label for="is_active" class="text-sm font-medium text-gray-700 cursor-pointer">Active</label>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">Update Banner</button>
                <a href="{{ route("{$rPrefix}.banners.index") }}" class="btn-outline">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
