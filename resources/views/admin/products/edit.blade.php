@extends('layouts.admin')
@section('title', 'Edit: ' . $product->name)

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.products.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Edit Product</h1>
    <span class="text-gray-400 text-sm">{{ $product->name }}</span>
</div>

<form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data"
      x-data="productEditForm()">
    @csrf @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <div class="xl:col-span-2 space-y-5">

            {{-- Basic Info --}}
            <div class="card">
                <div class="card-header"><h2 class="font-semibold text-gray-800">Basic Information</h2></div>
                <div class="card-body space-y-4">
                    <div>
                        <label class="form-label">Product Name *</label>
                        <input type="text" name="name" value="{{ old('name', $product->name) }}"
                               class="form-input @error('name') border-red-500 @enderror" required>
                        @error('name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                         x-data="subcatPicker('{{ old('category_id', $product->category_id) }}', '{{ old('subcategory_id', $product->subcategory_id) }}')">
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
                                    <option :value="sub.id" x-text="sub.name"
                                            :selected="sub.id == subcategoryId"></option>
                                </template>
                            </select>
                            <p class="form-hint" x-show="subcategories.length === 0 && categoryId">No sub categories for this category.</p>
                        </div>
                        <div>
                            <label class="form-label">Brand</label>
                            <select name="brand_id" class="form-select">
                                <option value="">— None —</option>
                                @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Short Description</label>
                        <textarea name="short_description" rows="2" class="form-textarea">{{ old('short_description', $product->short_description) }}</textarea>
                    </div>
                    <div>
                        <label class="form-label">Full Description</label>
                        <textarea name="description" rows="5" class="form-textarea">{{ old('description', $product->description) }}</textarea>
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
                            <input type="number" name="price" value="{{ old('price', $product->price) }}" step="0.01" min="0" class="form-input" required>
                        </div>
                        <div>
                            <label class="form-label">Cost Price (Rs.)</label>
                            <input type="number" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}" step="0.01" min="0" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Compare-at Price</label>
                            <input type="number" name="compare_price" value="{{ old('compare_price', $product->compare_price) }}" step="0.01" min="0" class="form-input">
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
                               {{ old('track_inventory', $product->track_inventory) ? 'checked' : '' }}
                               x-model="trackInventory" class="w-4 h-4 text-primary-600 rounded">
                        <label for="track_inventory" class="text-sm font-medium text-gray-700 cursor-pointer">Track inventory</label>
                    </div>
                    <div class="flex items-center gap-3 pl-1" x-show="trackInventory">
                        <input type="hidden" name="is_serialized" value="0">
                        <input type="checkbox" name="is_serialized" id="is_serialized" value="1"
                               {{ old('is_serialized', $product->is_serialized) ? 'checked' : '' }}
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

                        {{-- Store Attribute --}}
                        <div>
                            <label class="form-label text-sm">
                                <i class="fas fa-tags text-indigo-500 mr-1"></i>Store Attribute
                                <span class="font-normal text-gray-400 ml-1">(shown as price-selector on the product page)</span>
                            </label>
                            <select name="primary_serial_attribute_id" class="form-select">
                                <option value="">— None —</option>
                                @foreach($attributeDefs as $def)
                                <option value="{{ $def->id }}"
                                        {{ old('primary_serial_attribute_id', $product->primary_serial_attribute_id) == $def->id ? 'selected' : '' }}>
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
                                           {{ in_array($def->id, old('serial_attribute_ids', $product->serial_attribute_ids ?? [])) ? 'checked' : '' }}
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
                        <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', $product->low_stock_threshold) }}" min="0" class="form-input w-48">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">SKU</label>
                            <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" class="form-input font-mono">
                        </div>
                        <div>
                            <label class="form-label">Barcode</label>
                            <div class="flex gap-2">
                                <input type="text" name="barcode" id="barcode" value="{{ old('barcode', $product->barcode) }}" class="form-input font-mono flex-1">
                                <button type="button" @click="generateBarcode()" class="btn-outline btn-sm shrink-0">Generate</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Existing images --}}
            <div class="card">
                <div class="card-header"><h2 class="font-semibold text-gray-800">Images</h2></div>
                <div class="card-body space-y-4">
                    @if($product->images->count())
                    <div>
                        <p class="text-sm text-gray-600 mb-3">Hover an image — ⭐ to set primary, × to delete.</p>
                        <div class="grid grid-cols-4 gap-3">
                            @foreach($product->images as $img)
                            <div class="relative group">
                                <img src="{{ $img->url }}" class="w-full aspect-square object-cover rounded-xl bg-gray-100
                                    {{ $img->is_primary ? 'ring-2 ring-primary-500' : '' }}">
                                @if($img->is_primary)
                                    <div class="absolute top-1 left-1 bg-primary-600 text-white text-xs px-1.5 py-0.5 rounded">Primary</div>
                                @else
                                    <button type="button"
                                            onclick="editPageSetPrimary('{{ route('admin.products.images.primary', [$product, $img]) }}')"
                                            class="absolute top-1 left-1 w-6 h-6 bg-yellow-400 text-white rounded-full text-xs opacity-0 group-hover:opacity-100 transition-opacity"
                                            title="Set as primary">
                                        <i class="fas fa-star"></i>
                                    </button>
                                @endif
                                <button type="button"
                                        onclick="editPageDeleteImage('{{ route('admin.products.images.delete', $img) }}')"
                                        class="absolute top-1 right-1 w-6 h-6 bg-red-500 text-white rounded-full text-xs opacity-0 group-hover:opacity-100 transition-opacity">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div>
                        <label class="form-label">Add More Images</label>
                        <input type="file" name="images[]" multiple accept="image/*" class="form-input">
                    </div>
                </div>
            </div>

            {{-- Videos --}}
            <div class="card">
                <div class="card-header"><h2 class="font-semibold text-gray-800">Videos</h2></div>
                <div class="card-body space-y-4">
                    @if($product->videos->count())
                    <div class="space-y-2">
                        @foreach($product->videos as $video)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                            <span class="text-sm text-gray-700">
                                <i class="fas fa-video mr-2 text-gray-400"></i>
                                {{ $video->type === 'embed' ? 'Embedded video' : 'Uploaded file' }}
                            </span>
                            <button type="button"
                                    onclick="editPageDeleteVideo('{{ route('admin.products.videos.delete', $video) }}')"
                                    class="btn-danger btn-sm">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        @endforeach
                    </div>
                    @endif
                    <div>
                        <label class="form-label">Add Embed Code</label>
                        <textarea name="video_embed_url" rows="4" class="form-textarea font-mono text-xs"
                                  placeholder='Paste the full &lt;iframe&gt; embed code from YouTube/TikTok, or just the embed URL'></textarea>
                        <p class="form-hint">Go to YouTube → Share → Embed and paste the entire &lt;iframe&gt; code here</p>
                    </div>
                    <div>
                        <label class="form-label">Or Upload Video</label>
                        <input type="file" name="video_file" accept="video/*" class="form-input">
                    </div>
                </div>
            </div>

            {{-- Colors --}}
            @php
                $existingColors = $product->colors->map(fn($c) => [
                    'id'             => $c->id,
                    'name'           => $c->name,
                    'hex_code'       => $c->hex_code ?? '',
                    'stock_quantity' => $c->stock_quantity,
                ])->values()->toArray();
            @endphp
            <div class="card">
                <div class="card-header">
                    <h2 class="font-semibold text-gray-800">Colors <span class="text-xs text-gray-400 font-normal">(optional)</span></h2>
                </div>
                <div class="card-body space-y-3"
                     x-data="{ colors: {{ json_encode($existingColors ?: []) }} }">
                    <p class="text-xs text-gray-500">Each color tracks its own stock. Removing a color here will delete it permanently.</p>

                    <template x-for="(color, i) in colors" :key="color.id || ('new_' + i)">
                        <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-xl border border-gray-200">
                            <input type="hidden" :name="`colors[${i}][id]`" :value="color.id || ''">
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

                            {{-- Stock quantity per color --}}
                            <div class="shrink-0 w-24">
                                <input type="number" :name="`colors[${i}][stock_quantity]`"
                                       x-model.number="color.stock_quantity"
                                       min="0" step="1"
                                       placeholder="Stock"
                                       class="form-input text-sm text-center w-full"
                                       title="Stock for this color">
                            </div>

                            {{-- Remove --}}
                            <button type="button" @click="colors.splice(i, 1)"
                                    class="btn-danger btn-sm shrink-0"><i class="fas fa-times"></i></button>
                        </div>
                    </template>

                    <button type="button" @click="colors.push({ id: '', name: '', hex_code: '', stock_quantity: 0 })"
                            class="btn-outline btn-sm">
                        <i class="fas fa-plus mr-1"></i> Add Color
                    </button>
                </div>
            </div>

            {{-- Social links --}}
            <div class="card">
                <div class="card-header"><h2 class="font-semibold text-gray-800">Social Links</h2></div>
                <div class="card-body space-y-3" x-data="{ links: {{ json_encode($product->socialLinks->map(fn($l) => ['platform' => $l->platform, 'url' => $l->url])->toArray() ?: [['platform' => 'facebook', 'url' => '']]) }} }">
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
                    <button type="button" @click="links.push({ platform: 'facebook', url: '' })" class="btn-outline btn-sm">
                        <i class="fas fa-plus mr-1"></i> Add Link
                    </button>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">
            <div class="card p-5">
                <h2 class="font-semibold text-gray-800 mb-4">Publish</h2>
                <div class="space-y-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary-600 rounded">
                        <span class="text-sm font-medium text-gray-700">Active</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary-600 rounded">
                        <span class="text-sm font-medium text-gray-700">Featured</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="show_in_ecom" value="1" {{ old('show_in_ecom', $product->show_in_ecom) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary-600 rounded">
                        <span class="text-sm font-medium text-gray-700">Show on website
                            <span class="block text-xs font-normal text-gray-400">Uncheck to make this product POS-only (hidden from the online store)</span>
                        </span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="free_delivery" value="1" {{ old('free_delivery', $product->free_delivery) ? 'checked' : '' }}
                               class="w-4 h-4 text-green-600 rounded">
                        <span class="text-sm font-medium text-gray-700">Free Delivery
                            <span class="block text-xs font-normal text-gray-400">Delivery is free for orders containing this product, regardless of order total</span>
                        </span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="cod_enabled" value="1" {{ old('cod_enabled', $product->cod_enabled ?? true) ? 'checked' : '' }}
                               class="w-4 h-4 text-yellow-600 rounded">
                        <span class="text-sm font-medium text-gray-700">COD Available
                            <span class="block text-xs font-normal text-gray-400">Allow Cash on Delivery for this product</span>
                        </span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_new_arrival" value="1" {{ old('is_new_arrival', $product->is_new_arrival) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary-600 rounded">
                        <span class="text-sm font-medium text-gray-700">New Arrival</span>
                    </label>
                </div>
                <div class="flex gap-2 mt-5">
                    <button type="submit" class="btn-primary flex-1 justify-center">
                        <i class="fas fa-save mr-2"></i> Update
                    </button>
                </div>
                <div class="flex gap-2 mt-2">
                    <a href="{{ route('admin.products.show', $product) }}" class="btn-outline flex-1 justify-center text-sm">View</a>
                    <a href="{{ route('products.show', $product->slug) }}" target="_blank" class="btn-outline flex-1 justify-center text-sm">
                        <i class="fas fa-external-link-alt mr-1"></i> Store
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
function subcatPicker(initCat, initSub) {
    return {
        categoryId:    initCat ? String(initCat) : '',
        subcategoryId: initSub ? String(initSub) : '',
        subcategories: @json($subcategories),

        async init() {
            // subcategories already pre-loaded from server for edit form
        },

        async loadSubcategories() {
            this.subcategoryId = '';
            this.subcategories = [];
            if (!this.categoryId) return;
            try {
                const res  = await fetch(`/admin/categories/${this.categoryId}/subcategories`);
                this.subcategories = await res.json();
            } catch (e) { this.subcategories = []; }
        },
    };
}

function productEditForm() {
    return {
        trackInventory: {{ old('track_inventory', $product->track_inventory) ? 'true' : 'false' }},
        isSerial: {{ old('is_serialized', $product->is_serialized) ? 'true' : 'false' }},
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

// AJAX helpers — avoids nested <form> tags that break the update button
const _csrf = document.querySelector('meta[name=csrf-token]').content;

function _editPageAjax(url, method) {
    const fd = new FormData();
    fd.append('_token', _csrf);
    fd.append('_method', method);
    return fetch(url, { method: 'POST', body: fd });
}

async function editPageSetPrimary(url) {
    await _editPageAjax(url, 'PATCH');
    location.reload();
}

async function editPageDeleteImage(url) {
    if (!confirm('Delete this image?')) return;
    await _editPageAjax(url, 'DELETE');
    location.reload();
}

async function editPageDeleteVideo(url) {
    if (!confirm('Delete this video?')) return;
    await _editPageAjax(url, 'DELETE');
    location.reload();
}
</script>
@endpush
@endsection
