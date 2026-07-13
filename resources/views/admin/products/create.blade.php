@extends('layouts.admin')
@section('title', 'Add Product')

@section('content')
@php $rPrefix = auth()->user()->panelPrefix(); @endphp
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route("{$rPrefix}.products.index") }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Add New Product</h1>
</div>

<form method="POST" action="{{ route("{$rPrefix}.products.store") }}" enctype="multipart/form-data"
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
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-data="subcatPicker('{{ old('category_id') }}', '{{ old('subcategory_id') }}')">
                        <div>
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" x-model="categoryId" @change="loadSubcategories()">
                                <option value="">— None —</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Sub Category <span class="text-gray-400 font-normal text-xs">(optional)</span></label>
                            <select name="subcategory_id" class="form-select" x-model="subcategoryId" :disabled="subcategories.length === 0">
                                <option value="">— None —</option>
                                <template x-for="sub in subcategories" :key="sub.id">
                                    <option :value="sub.id" x-text="sub.name"></option>
                                </template>
                            </select>
                            <p class="form-hint" x-show="subcategories.length === 0 && categoryId">No sub categories for this category.</p>
                        </div>
                        <div>
                            <label class="form-label">Brand <span class="text-red-500">*</span></label>
                            <select name="brand_id" class="form-select @error('brand_id') border-red-500 @enderror" required>
                                <option value="">Select brand...</option>
                                @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                            @error('brand_id') <p class="form-error">{{ $message }}</p> @enderror
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

            {{-- Pricing (hidden for serialized products — prices are per unit) --}}
            <div class="card" x-show="!isSerial" x-cloak>
                <div class="card-header"><h2 class="font-semibold text-gray-800">Pricing</h2></div>
                <div class="card-body">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="form-label">Selling Price (Rs.) *</label>
                            <input type="number" name="price" value="{{ old('price') }}" step="0.01" min="0"
                                   class="form-input @error('price') border-red-500 @enderror"
                                   :required="!isSerial">
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

            {{-- Pricing notice for serialized products --}}
            <div class="card border border-indigo-200 bg-indigo-50/50" x-show="isSerial" x-cloak>
                <div class="card-header bg-indigo-50 border-b border-indigo-100">
                    <h2 class="font-semibold text-indigo-800 flex items-center gap-2">
                        <i class="fas fa-info-circle text-indigo-500"></i> Pricing
                    </h2>
                </div>
                <div class="card-body text-sm text-indigo-700 space-y-1">
                    <p><i class="fas fa-tag mr-1 text-indigo-400"></i><strong>Selling price</strong> is entered per unit when recording a purchase (each IMEI / serial has its own price).</p>
                    <p><i class="fas fa-store mr-1 text-indigo-400"></i>The <strong>online store price</strong> comes from the attribute prices you configure on the product page (e.g. 4GB → Rs. 35,000).</p>
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
                    <div class="flex items-center gap-3 pl-1" x-show="trackInventory">
                        <input type="hidden" name="is_serialized" value="0">
                        <input type="checkbox" name="is_serialized" id="is_serialized" value="1"
                               {{ old('is_serialized') ? 'checked' : '' }}
                               x-model="isSerial"
                               class="w-4 h-4 text-indigo-600 rounded">
                        <label for="is_serialized" class="text-sm font-medium text-gray-700 cursor-pointer">
                            <i class="fas fa-barcode text-indigo-500 mr-1 text-xs"></i>
                            Serialized — track IMEI / Serial numbers per unit
                        </label>
                    </div>

                    {{-- Attribute configuration (shown only when serialized is checked) --}}
                    @if(!$attributeDefs->isEmpty())
                    <div x-show="isSerial && trackInventory" x-cloak
                         class="ml-1 pl-4 border-l-2 border-indigo-100 space-y-4">

                        {{-- Online Store Display mode --}}
                        <div>
                            <label class="form-label text-sm">
                                <i class="fas fa-store text-indigo-500 mr-1"></i>Online Store Display
                            </label>
                            <div class="space-y-2 mt-1">
                                <label class="flex items-start gap-2 cursor-pointer">
                                    <input type="radio" name="attribute_display_mode" value="single"
                                           x-model="attrMode" class="mt-0.5 text-indigo-600">
                                    <span class="text-sm text-gray-700">Single attribute
                                        <span class="block text-xs text-gray-400">Grouped price chips (e.g. Memory: 6GB / 8GB) — for new mobiles</span>
                                    </span>
                                </label>
                                <label class="flex items-start gap-2 cursor-pointer">
                                    <input type="radio" name="attribute_display_mode" value="per_unit"
                                           x-model="attrMode" class="mt-0.5 text-indigo-600">
                                    <span class="text-sm text-gray-700">Multiple attributes
                                        <span class="block text-xs text-gray-400">Each unit sold separately with all its attributes — for used mobiles</span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        {{-- Store Attribute (only meaningful in single-attribute mode) --}}
                        <div x-show="attrMode === 'single'">
                            <label class="form-label text-sm">
                                <i class="fas fa-tags text-indigo-500 mr-1"></i>Store Attribute
                                <span class="font-normal text-gray-400 ml-1">(shown as price-selector on the product page)</span>
                            </label>
                            <select name="primary_serial_attribute_id" class="form-select">
                                <option value="">— None —</option>
                                @foreach($attributeDefs as $def)
                                <option value="{{ $def->id }}"
                                        {{ old('primary_serial_attribute_id') == $def->id ? 'selected' : '' }}>
                                    {{ $def->name }}
                                    ({{ implode(', ', array_slice($def->options, 0, 4)) }}{{ count($def->options) > 4 ? '…' : '' }})
                                </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Serial Entry Fields --}}
                        <div>
                            <label class="form-label text-sm">
                                <i class="fas fa-list-check text-green-600 mr-1"></i>Serial Entry Fields
                                <span class="font-normal text-gray-400 ml-1">(fields shown on the purchase form — leave all unchecked for all)</span>
                            </label>
                            <div class="grid grid-cols-2 gap-x-4 gap-y-2 mt-1">
                                @foreach($attributeDefs as $def)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox"
                                           name="serial_attribute_ids[]"
                                           value="{{ $def->id }}"
                                           {{ in_array($def->id, old('serial_attribute_ids', [])) ? 'checked' : '' }}
                                           class="w-4 h-4 text-indigo-600 rounded border-gray-300">
                                    <span class="text-sm text-gray-700">{{ $def->name }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <div x-show="trackInventory">
                        <label class="form-label">Low Stock Threshold</label>
                        <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', 5) }}" min="0"
                               class="form-input w-48">
                        <p class="form-hint">Alert when stock drops below this</p>
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
                            <input type="hidden" :name="`colors[${i}][hex_code]`" :value="color.hex_code">

                            {{-- Color picker swatch (click to open native picker) --}}
                            <label class="relative shrink-0 w-9 h-9 cursor-pointer" title="Click to pick a color">
                                <div class="w-9 h-9 rounded-full border-2 border-gray-300 shadow-sm transition-colors"
                                     :style="{ backgroundColor: color.hex_code || '#e5e7eb' }"></div>
                                <input type="color"
                                       :value="color.hex_code || '#000000'"
                                       @change="color.hex_code = $event.target.value"
                                       class="absolute inset-0 opacity-0 w-full h-full cursor-pointer rounded-full">
                            </label>

                            {{-- Hex value display --}}
                            <span class="text-xs font-mono text-gray-400 shrink-0 w-16 text-center"
                                  x-text="color.hex_code || '—'"></span>

                            {{-- Name --}}
                            <input type="text" :name="`colors[${i}][name]`" x-model="color.name"
                                   placeholder="Color name (e.g. Red)" class="form-input flex-1 text-sm" required>

                            {{-- Remove --}}
                            <button type="button" @click="colors.splice(i, 1)"
                                    class="btn-danger btn-sm shrink-0"><i class="fas fa-times"></i></button>
                        </div>
                    </template>

                    <button type="button" @click="colors.push({ name: '', hex_code: '' })"
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
                        <input type="checkbox" name="show_in_ecom" value="1" {{ old('show_in_ecom', true) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary-600 rounded">
                        <span class="text-sm font-medium text-gray-700">Show on website
                            <span class="block text-xs font-normal text-gray-400">Uncheck to make this product POS-only (hidden from the online store)</span>
                        </span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="free_delivery" value="1" {{ old('free_delivery') ? 'checked' : '' }}
                               class="w-4 h-4 text-green-600 rounded">
                        <span class="text-sm font-medium text-gray-700">Free Delivery
                            <span class="block text-xs font-normal text-gray-400">Delivery is free for orders containing this product, regardless of order total</span>
                        </span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="cod_enabled" value="1" {{ old('cod_enabled', true) ? 'checked' : '' }}
                               class="w-4 h-4 text-yellow-600 rounded">
                        <span class="text-sm font-medium text-gray-700">COD Available
                            <span class="block text-xs font-normal text-gray-400">Allow Cash on Delivery for this product</span>
                        </span>
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
                <a href="{{ route("{$rPrefix}.products.index") }}" class="block text-center text-sm text-gray-500 hover:text-gray-700 mt-3">
                    Cancel
                </a>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
function subcatPicker(initCat, initSub) {
    return {
        categoryId:    initCat || '',
        subcategoryId: initSub || '',
        subcategories: [],

        async init() {
            if (this.categoryId) await this.loadSubcategories();
        },

        async loadSubcategories() {
            this.subcategoryId = '';
            this.subcategories = [];
            if (!this.categoryId) return;
            try {
                const res  = await fetch(`/{{ $rPrefix }}/categories/${this.categoryId}/subcategories`);
                this.subcategories = await res.json();
            } catch (e) { this.subcategories = []; }
        },
    };
}

function productForm() {
    return {
        trackInventory: {{ old('track_inventory', true) ? 'true' : 'false' }},
        isSerial: {{ old('is_serialized') ? 'true' : 'false' }},
        attrMode: '{{ old('attribute_display_mode', 'single') }}',
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
                const categoryId = document.querySelector('[name="category_id"]')?.value || '';
                const url = '/admin/products/generate-barcode' + (categoryId ? '?category_id=' + categoryId : '');
                const res = await fetch(url);
                const data = await res.json();
                document.getElementById('barcode').value = data.barcode;
            } catch(e) {}
        },
    };
}
</script>
@endpush
@endsection
