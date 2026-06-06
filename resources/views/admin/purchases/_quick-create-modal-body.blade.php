{{-- Body --}}
<div class="p-5 space-y-4 text-sm overflow-y-auto max-h-[70vh]">

    {{-- ── Basic Info ── --}}
    <div class="space-y-3">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Basic Info</p>

        <div class="grid grid-cols-3 gap-3">
            <div class="col-span-2">
                <label class="form-label !text-xs">Name <span class="text-red-500">*</span></label>
                <input type="text" x-model="form.name" class="form-input !py-1.5 !text-sm"
                       placeholder="e.g. Samsung A55 Back Cover">
                <p x-show="errors.name" x-text="errors.name" class="text-red-500 text-xs mt-0.5"></p>
            </div>
            <div>
                <label class="form-label !text-xs">SKU</label>
                <input type="text" x-model="form.sku" class="form-input !py-1.5 !text-sm font-mono"
                       placeholder="e.g. SAM-A55-BC">
                <p x-show="errors.sku" x-text="errors.sku" class="text-red-500 text-xs mt-0.5"></p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="form-label !text-xs">Category</label>
                <select x-model="form.category_id" @change="onCategoryChange()" class="form-select !py-1.5 !text-sm">
                    <option value="">— None —</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label !text-xs">Sub-category</label>
                <select x-model="form.subcategory_id" class="form-select !py-1.5 !text-sm"
                        :disabled="subcategories.length === 0">
                    <option value="">— None —</option>
                    <template x-for="s in subcategories" :key="s.id">
                        <option :value="s.id" x-text="s.name"></option>
                    </template>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="form-label !text-xs">Brand</label>
                <select x-model="form.brand_id" class="form-select !py-1.5 !text-sm">
                    <option value="">— None —</option>
                    @foreach($brands as $brand)
                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label !text-xs">Short Description</label>
                <input type="text" x-model="form.short_description" class="form-input !py-1.5 !text-sm"
                       placeholder="One line summary">
            </div>
        </div>
    </div>

    <hr class="border-gray-100">

    {{-- ── Pricing ── --}}
    <div class="space-y-3">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Pricing</p>
        <div class="grid grid-cols-3 gap-3">
            <div>
                <label class="form-label !text-xs">Cost Price <span class="text-red-500">*</span></label>
                <input type="number" x-model.number="form.cost_price" min="0" step="0.01"
                       class="form-input !py-1.5 !text-sm" placeholder="0">
                <p x-show="errors.cost_price" x-text="errors.cost_price" class="text-red-500 text-xs mt-0.5"></p>
            </div>
            <div>
                <label class="form-label !text-xs">Selling Price <span class="text-red-500">*</span></label>
                <input type="number" x-model.number="form.price" min="0" step="0.01"
                       class="form-input !py-1.5 !text-sm" placeholder="0">
                <p x-show="errors.price" x-text="errors.price" class="text-red-500 text-xs mt-0.5"></p>
            </div>
            <div>
                <label class="form-label !text-xs">Compare at Price</label>
                <input type="number" x-model.number="form.compare_price" min="0" step="0.01"
                       class="form-input !py-1.5 !text-sm" placeholder="0">
            </div>
        </div>
    </div>

    <hr class="border-gray-100">

    {{-- ── Inventory ── --}}
    <div class="space-y-3">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Inventory</p>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="form-label !text-xs">Low Stock Alert</label>
                <input type="number" x-model.number="form.low_stock_threshold" min="0"
                       class="form-input !py-1.5 !text-sm">
            </div>
            <div>
                <label class="form-label !text-xs">Barcode</label>
                <div class="flex gap-1.5">
                    <input type="text" x-model="form.barcode"
                           class="form-input !py-1.5 !text-sm font-mono flex-1" placeholder="Auto-generate if blank">
                    <button type="button" @click="generateBarcode()"
                            class="btn-outline btn-sm shrink-0 whitespace-nowrap !py-1.5">
                        <i class="fas fa-magic"></i>
                    </button>
                </div>
            </div>
        </div>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" x-model="form.show_in_ecom" class="w-4 h-4 text-primary-600 rounded">
            <span class="text-sm text-gray-700 font-medium">Show on website</span>
        </label>
    </div>

    <hr class="border-gray-100">

    {{-- ── Colors ── --}}
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Colors</p>
            <button type="button" @click="addColor()"
                    class="text-xs text-primary-600 hover:text-primary-700 font-semibold flex items-center gap-1">
                <i class="fas fa-plus"></i> Add Color
            </button>
        </div>
        <div class="space-y-1.5">
            <template x-for="(color, i) in colors" :key="i">
                <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2">
                    <label class="relative w-7 h-7 rounded-full cursor-pointer shrink-0 border-2 border-gray-300 shadow-sm overflow-hidden">
                        <div class="absolute inset-0 rounded-full" :style="{ backgroundColor: color.hex_code || '#e5e7eb' }"></div>
                        <input type="color" :value="color.hex_code || '#000000'"
                               @change="color.hex_code = $event.target.value"
                               class="absolute inset-0 opacity-0 w-full h-full cursor-pointer">
                    </label>
                    <span class="text-xs font-mono text-gray-400 w-14 shrink-0" x-text="color.hex_code || '—'"></span>
                    <input type="text" x-model="color.name" placeholder="Color name (e.g. Black)"
                           class="form-input !py-1 !text-sm flex-1">
                    <button type="button" @click="colors.splice(i, 1)"
                            class="text-red-400 hover:text-red-600 shrink-0 px-1">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            </template>
            <p x-show="colors.length === 0" class="text-xs text-gray-400 italic">No colors added — leave empty for a single-variant product.</p>
        </div>
    </div>

    <hr class="border-gray-100">

    {{-- ── Media ── --}}
    <div class="space-y-3">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Media</p>
        <div>
            <label class="form-label !text-xs">Images</label>
            <input type="file" multiple accept="image/*" @change="onImagesChange($event)"
                   class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 cursor-pointer">
            <div x-show="imagePreviews.length > 0" class="flex flex-wrap gap-2 mt-2">
                <template x-for="(src, i) in imagePreviews" :key="i">
                    <div class="relative group w-14 h-14">
                        <img :src="src" class="w-14 h-14 object-cover rounded-lg border border-gray-200">
                        <button type="button" @click="removeImage(i)"
                                class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <i class="fas fa-times" style="font-size:9px;"></i>
                        </button>
                    </div>
                </template>
            </div>
        </div>
        <div>
            <label class="form-label !text-xs">Embed Video <span class="text-gray-400 font-normal">(YouTube/TikTok iframe or URL)</span></label>
            <input type="text" x-model="form.video_embed_url" class="form-input !py-1.5 !text-sm"
                   placeholder="Paste iframe code or embed URL">
        </div>
    </div>

    {{-- General error --}}
    <p x-show="errors.general" x-text="errors.general"
       class="text-xs text-red-600 bg-red-50 rounded-lg px-3 py-2"></p>
</div>
