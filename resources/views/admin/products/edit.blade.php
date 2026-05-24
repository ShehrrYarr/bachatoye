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
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">— None —</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
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
                    <div class="grid grid-cols-2 gap-4" x-show="trackInventory">
                        <div>
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" min="0" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Low Stock Threshold</label>
                            <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', $product->low_stock_threshold) }}" min="0" class="form-input">
                        </div>
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
                        <p class="text-sm text-gray-600 mb-3">Existing images (click star to set primary, click × to delete):</p>
                        <div class="grid grid-cols-4 gap-3">
                            @foreach($product->images as $img)
                            <div class="relative group">
                                <img src="{{ $img->url }}" class="w-full aspect-square object-cover rounded-xl bg-gray-100
                                    {{ $img->is_primary ? 'ring-2 ring-primary-500' : '' }}">
                                @if($img->is_primary)
                                <div class="absolute top-1 left-1 bg-primary-600 text-white text-xs px-1.5 py-0.5 rounded">Primary</div>
                                @else
                                <form method="POST" action="{{ route('admin.products.images.primary', [$product, $img]) }}"
                                      class="absolute top-1 left-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="w-6 h-6 bg-yellow-400 text-white rounded-full text-xs" title="Set as primary">
                                        <i class="fas fa-star"></i>
                                    </button>
                                </form>
                                @endif
                                <form method="POST" action="{{ route('admin.products.images.delete', $img) }}"
                                      onsubmit="return confirm('Delete image?')"
                                      class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-6 h-6 bg-red-500 text-white rounded-full text-xs">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
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
                            <form method="POST" action="{{ route('admin.products.videos.delete', $video) }}"
                                  onsubmit="return confirm('Delete video?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </form>
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
function productEditForm() {
    return {
        trackInventory: {{ old('track_inventory', $product->track_inventory) ? 'true' : 'false' }},
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
