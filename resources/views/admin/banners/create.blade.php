@extends('layouts.admin')
@section('title', 'Add Banner')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.banners.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Add Banner</h1>
</div>

<div class="max-w-2xl">
    <form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="card p-6 space-y-4">
            <div>
                <label class="form-label">Banner Image *</label>
                <input type="file" name="image" accept="image/*" class="form-input @error('image') border-red-500 @enderror" required>
                <p class="form-hint">Hero: 1200×420px recommended. Promo: 600×240px recommended.</p>
                @error('image') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label">Position *</label>
                <select name="position" class="form-select @error('position') border-red-500 @enderror" required>
                    <option value="hero" {{ old('position') === 'hero' ? 'selected' : '' }}>Hero (main slider)</option>
                    <option value="promo" {{ old('position') === 'promo' ? 'selected' : '' }}>Promo (secondary banners)</option>
                    <option value="sidebar" {{ old('position') === 'sidebar' ? 'selected' : '' }}>Sidebar</option>
                </select>
            </div>
            <div>
                <label class="form-label">Title</label>
                <input type="text" name="title" value="{{ old('title') }}" class="form-input" placeholder="Banner headline">
            </div>
            <div>
                <label class="form-label">Subtitle</label>
                <input type="text" name="subtitle" value="{{ old('subtitle') }}" class="form-input" placeholder="Supporting text">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Link URL</label>
                    <input type="url" name="link_url" value="{{ old('link_url') }}" class="form-input" placeholder="https://...">
                </div>
                <div>
                    <label class="form-label">Button Text</label>
                    <input type="text" name="button_text" value="{{ old('button_text', 'Shop Now') }}" class="form-input">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Start Date</label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">End Date</label>
                    <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" class="form-input">
                </div>
            </div>
            <div>
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" class="form-input w-28">
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="w-4 h-4 text-primary-600 rounded">
                <label for="is_active" class="text-sm font-medium text-gray-700 cursor-pointer">Active</label>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">Save Banner</button>
                <a href="{{ route('admin.banners.index') }}" class="btn-outline">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
