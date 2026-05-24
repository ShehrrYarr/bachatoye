@extends('layouts.admin')
@section('title', 'Add Product')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.products.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Add New Product</h1>
</div>

<form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data"
      x-data="productForm()">
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Main info --}}
        <div class="xl:col-span-2 space-y-5">

            {{-- Basic Info --}}
            <div class="card">
                <div class="card-header"><h2 class="font-semibold text-gray-800">Basic Information</h2></div>
                <div class="card-body space-y-4">
                    <div>
                        <label class="form-label">Product Name *</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-input @error('name') border-red-500 @enderror" required>
                        @error('name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">— None —</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Brand</label>
                            <select name="brand_id" class="form-select">
                                <option value="">— None —</option>
                                @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Short Description</label>
                        <textarea name="short_description" rows="2" class="form-textarea">{{ old('short_description') }}</textarea>
                        <p class="form-hint">Displayed on product card and detail page header</p>
                    </div>
                    <div>
                        <label class="form-label">Full Description</label>
                        <textarea name="description" rows="5" class="form-textarea">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Pricing --}}
            <div class="card">
                <div class="card-header"><h2 class="font-semibold text-gray-800">Pricing</h2></div>
                <div class="card-body">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="form-label">Selling Price (Rs.) *</label>
                            <input type="number" name="price" value="{{ old('price') }}" step="0.01" min="0"
                                   class="form-input @error('price') border-red-500 @enderror" required>
                            @error('price') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Cost Price (Rs.)</label>
                            <input type="number" name="cost_price" value="{{ old('cost_price') }}" step="0.01" min="0"
                                   class="form-input">
                            <p class="form-hint">For P&L reports</p>
                        </div>
                        <div>
                            <label class="form-label">Compare-at Price (Rs.)</label>
                            <input type="number" name="compare_price" value="{{ old('compare_price') }}" step="0.01" min="0"
                                   class="form-input">
                            <p class="form-hint">Shown as strikethrough</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Inventory --}}
            <div class="card">
                <div class="card-header"><h2 class="font-semibold text-gray-800">Inventory</h2></div>
                <div class="card-body space-y-4">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="track_inventory" id="track_inventory" value="1"
                               {{ old('track_inventory', true) ? 'checked' : '' }}
                               x-model="trackInventory" class="w-4 h-4 text-primary-600 rounded">
                        <label for="track_inventory" class="text-sm font-medium text-gray-700 cursor-pointer">Track inventory for this product</label>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="trackInventory">
                        <div>
                            <label class="form-label">Stock Quantity *</label>
                            <input type="number" name="stock_quantity" value="{{ old('stock_quantity', 0) }}" min="0"
                                   class="form-input @error('stock_quantity') border-red-500 @enderror">
                            @error('stock_quantity') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Low Stock Threshold</label>
                            <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', 5) }}" min="0"
                                   class="form-input">
                            <p class="form-hint">Alert when stock drops below this</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">SKU</label>
                            <input type="text" name="sku" value="{{ old('sku') }}" class="form-input font-mono">
                        </div>
                        <div>
                            <label class="form-label">Barcode</label>
                            <div class="flex gap-2">
                                <input type="text" name="barcode" id="barcode" value="{{ old('barcode') }}"
                                       class="form-input font-mono flex-1">
                                <button type="button" @click="generateBarcode()"
                                        class="btn-outline btn-sm shrink-0 whitespace-nowrap">
                                    <i class="fas fa-magic mr-1"></i> Generate EAN-13
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Images --}}
            <div class="card">
                <div class="card-header"><h2 class="font-semibold text-gray-800">Images</h2></div>
                <div class="card-body">
                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-primary-400 transition-colors cursor-pointer"
                         onclick="document.getElementById('imageInput').click()">
                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-300 mb-2"></i>
                        <p class="text-sm text-gray-500">Click to upload images (JPG, PNG, WebP)</p>
                        <p class="text-xs text-gray-400 mt-1">Multiple images allowed — first image will be the primary</p>
                    </div>
                    <input type="file" id="imageInput" name="images[]" multiple accept="image/*"
                           class="hidden" @change="previewImages($event)">

                    <div class="grid grid-cols-4 gap-3 mt-4" x-show="imagePreviews.length > 0">
                        <template x-for="(src, i) in imagePreviews" :key="i">
                            <div class="relative group">
                                <img :src="src" class="w-full aspect-square object-cover rounded-xl bg-gray-100">
                                <div class="absolute top-1 left-1 bg-primary-600 text-white text-xs px-1.5 py-0.5 rounded" x-show="i === 0">Primary</div>
                                <button type="button" @click="removePreview(i)"
                                        class="absolute top-1 right-1 w-6 h-6 bg-red-500 text-white rounded-full text-xs opacity-0 group-hover:opacity-100 transition-opacity">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Videos --}}
            <div class="card">
                <div class="card-header"><h2 class="font-semibold text-gray-800">Videos</h2></div>
                <div class="card-body space-y-4">
                    <div>
                        <label class="form-label">Embed Code (YouTube / TikTok)</label>
                        <textarea name="video_embed_url" rows="4" class="form-textarea font-mono text-xs"
                                  placeholder='Paste the full &lt;iframe&gt; embed code or just the embed URL'>{{ old('video_embed_url') }}</textarea>
                        <p class="form-hint">Go to YouTube → Share → Embed and paste the entire &lt;iframe&gt; code</p>
                    </div>
                    <div>
                        <label class="form-label">Or Upload Video File</label>
                        <input type="file" name="video_file" accept="video/*" class="form-input">
                    </div>
                </div>
            </div>

            {{-- Colors --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="font-semibold text-gray-800">Colors <span class="text-xs text-gray-400 font-normal">(optional)</span></h2>
                </div>
                <div class="card-body space-y-3" x-data="{ colors: [] }">
                    <p class="text-xs text-gray-500">Add color variants if this product comes in multiple colors. Each color tracks its own stock quantity.</p>
                    <input type="hidden" name="has_colors" value="0">

                    <template x-for="(color, i) in colors" :key="i">
                        <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-xl border border-gray-200">
                            <input type="hidden" :name="`colors[${i}][id]`" value="">
                            {{-- Swatch preview --}}
                            <div class="w-8 h-8 rounded-full border border-gray-300 shrink-0 transition-colors"
                                 :style="color.hex_code ? `background:${color.hex_code}` : 'background:#e5e7eb'"></div>
                            {{-- Name --}}
                            <input type="text" :name="`colors[${i}][name]`" x-model="color.name"
                                   placeholder="Color name (e.g. Red)" class="form-input flex-1 text-sm" required>
                            {{-- Hex code --}}
                            <input type="text" :name="`colors[${i}][hex_code]`" x-model="color.hex_code"
                                   placeholder="#FF0000" maxlength="7"
                                   class="form-input w-24 text-sm font-mono text-center">
                            {{-- Stock --}}
                            <div class="shrink-0">
                                <label class="text-xs text-gray-400 block text-center mb-0.5">Stock</label>
                                <input type="number" :name="`colors[${i}][stock_quantity]`" x-model="color.stock_quantity"
                                       min="0" class="form-input w-20 text-sm text-center">
                            </div>
                            {{-- Remove --}}
                            <button type="button" @click="colors.splice(i, 1)"
                                    class="btn-danger btn-sm shrink-0"><i class="fas fa-times"></i></button>
                        </div>
                    </template>

                    <button type="button" @click="colors.push({ name: '', hex_code: '', stock_quantity: 0 })"
                            class="btn-outline btn-sm">
                        <i class="fas fa-plus mr-1"></i> Add Color
                    </button>
                </div>
            </div>

            {{-- Social Links --}}
            <div class="card">
                <div class="card-header"><h2 class="font-semibold text-gray-800">Social Links</h2></div>
                <div class="card-body space-y-3" x-data="{ links: [{ platform: 'facebook', url: '' }] }">
                    <template x-for="(link, i) in links" :key="i">
                        <div class="flex gap-2">
                            <select x-model="link.platform" :name="`social_links[${i}][platform]`" class="form-select w-40 shrink-0">
                                <option value="facebook">Facebook</option>
                                <option value="instagram">Instagram</option>
                                <option value="tiktok">TikTok</option>
                                <option value="youtube">YouTube</option>
                            </select>
                            <input type="url" x-model="link.url" :name="`social_links[${i}][url]`"
                                   placeholder="https://..." class="form-input flex-1">
                            <button type="button" @click="links.splice(i,1)" x-show="links.length > 1"
                                    class="btn-danger btn-sm shrink-0"><i class="fas fa-times"></i></button>
                        </div>
                    </template>
                    <button type="button" @click="links.push({ platform: 'facebook', url: '' })"
                            class="btn-outline btn-sm">
                        <i class="fas fa-plus mr-1"></i> Add Link
                    </button>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">

            {{-- Publish --}}
            <div class="card p-5">
                <h2 class="font-semibold text-gray-800 mb-4">Publish</h2>
                <div class="space-y-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary-600 rounded">
                        <span class="text-sm font-medium text-gray-700">Active (visible on store)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}
                               class="w-4 h-4 text-primary-600 rounded">
                        <span class="text-sm font-medium text-gray-700">Featured product</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_new_arrival" value="1" {{ old('is_new_arrival') ? 'checked' : '' }}
                               class="w-4 h-4 text-primary-600 rounded">
                        <span class="text-sm font-medium text-gray-700">New Arrival</span>
                    </label>
                </div>
                <div class="flex gap-2 mt-5">
                    <button type="submit" class="btn-primary flex-1 justify-center">
                        <i class="fas fa-save mr-2"></i> Save Product
                    </button>
                </div>
                <a href="{{ route('admin.products.index') }}" class="block text-center text-sm text-gray-500 hover:text-gray-700 mt-3">
                    Cancel
                </a>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
function productForm() {
    return {
        trackInventory: {{ old('track_inventory', true) ? 'true' : 'false' }},
        imagePreviews: [],

        previewImages(e) {
            const files = Array.from(e.target.files);
            this.imagePreviews = [];
            files.forEach(file => {
                const reader = new FileReader();
                reader.onload = (ev) => this.imagePreviews.push(ev.target.result);
                reader.readAsDataURL(file);
            });
        },

        removePreview(i) {
            this.imagePreviews.splice(i, 1);
        },

        async generateBarcode() {
            try {
                const res = await fetch('/admin/products/generate-barcode');
                const data = await res.json();
                document.getElementById('barcode').value = data.barcode;
            } catch(e) {}
        },
    };
}
</script>
@endpush
@endsection
