@extends('layouts.admin')
@section('title', 'Record Purchase')

@section('content')
@php $rPrefix = auth()->user()->hasRole('admin') ? 'admin' : 'salesman'; @endphp
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route("{$rPrefix}.purchases.index") }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Record Purchase</h1>
</div>

<form method="POST" action="{{ route("{$rPrefix}.purchases.store") }}"
      x-data="purchaseForm()" @submit.prevent="submitForm"
      @product-created.window="onProductCreated($event.detail)">
    @csrf

    {{-- Same/Different attributes prompt (shown when a serialized product with attributes is added) --}}
    <template x-if="attrPrompt">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="chooseAttrMode('different')"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center shrink-0">
                        <i class="fas fa-tags text-indigo-600"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-bold text-gray-900">Unit Attributes</h3>
                        <p class="text-xs text-gray-500 truncate" x-text="attrPrompt.name"></p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mb-4">
                    Will all units of this product have the <strong>same attributes</strong>
                    (Memory, PTA Status, etc.), or different ones per unit?
                </p>
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" @click="chooseAttrMode('same')"
                            class="flex flex-col items-center gap-1.5 border-2 border-indigo-200 hover:border-indigo-500 hover:bg-indigo-50 rounded-xl py-4 px-3 transition-all">
                        <i class="fas fa-clone text-indigo-500 text-lg"></i>
                        <span class="text-sm font-semibold text-gray-800">Same for all</span>
                        <span class="text-[11px] text-gray-400 text-center leading-tight">Enter attributes once, applied to every unit</span>
                    </button>
                    <button type="button" @click="chooseAttrMode('different')"
                            class="flex flex-col items-center gap-1.5 border-2 border-gray-200 hover:border-gray-400 hover:bg-gray-50 rounded-xl py-4 px-3 transition-all">
                        <i class="fas fa-list-ul text-gray-500 text-lg"></i>
                        <span class="text-sm font-semibold text-gray-800">Different per unit</span>
                        <span class="text-[11px] text-gray-400 text-center leading-tight">Pick attributes on each unit separately</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    @if($errors->any())
    <div class="mb-5 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
        <strong><i class="fas fa-exclamation-circle mr-1"></i>Please fix the following:</strong>
        <ul class="mt-1 ml-4 list-disc">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Left: items --}}
        <div class="xl:col-span-2 space-y-5">

            {{-- Product search & add --}}
            <div class="card">
                <div class="card-header"><h2 class="font-semibold text-gray-800">Add Products</h2></div>
                <div class="card-body space-y-3">
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <input type="text" x-model="searchQuery" @input.debounce.300ms="searchProducts()"
                                   @focus="showDropdown = true" @click.outside="showDropdown = false"
                                   placeholder="Search product by name or SKU..."
                                   class="form-input pl-9">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>

                            {{-- Search dropdown --}}
                            <div x-show="showDropdown && searchQuery.length >= 2"
                                 class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl z-30 max-h-72 overflow-y-auto">

                                {{-- Existing results --}}
                                <template x-for="p in searchResults" :key="p.id">
                                    <button type="button" @click="addProduct(p)"
                                            class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 text-left transition-colors border-b border-gray-100 last:border-0">
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-800 truncate" x-text="p.name"></div>
                                            <div class="flex items-center gap-1.5 flex-wrap mt-0.5">
                                                <span class="text-xs text-gray-400" x-show="p.sku" x-text="'SKU: ' + p.sku"></span>
                                                <span x-show="p.category"
                                                      class="text-xs font-medium bg-indigo-50 text-indigo-600 px-1.5 py-0.5 rounded"
                                                      x-text="p.category"></span>
                                                <template x-if="p.subcategory">
                                                    <span class="flex items-center gap-1 text-xs font-medium bg-purple-50 text-purple-600 px-1.5 py-0.5 rounded">
                                                        <i class="fas fa-angle-right text-[9px]"></i>
                                                        <span x-text="p.subcategory"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <template x-if="p.colors && p.colors.length > 0">
                                                <span class="text-xs bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded-full font-medium"
                                                      x-text="p.colors.length + ' colors'"></span>
                                            </template>
                                            <span class="text-xs text-gray-500">Last cost: Rs. <span x-text="Number(p.cost_price).toLocaleString()"></span></span>
                                        </div>
                                    </button>
                                </template>

                                {{-- No results message --}}
                                <div x-show="searchResults.length === 0" class="px-4 py-2.5 text-xs text-gray-400 border-b border-gray-100">
                                    No existing products found
                                </div>

                                {{-- Always show "Create new product" option --}}
                                <button type="button" @click="openCreateModal()"
                                        class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-green-50 text-left transition-colors text-green-700">
                                    <div class="w-7 h-7 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                                        <i class="fas fa-plus text-green-600 text-xs"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold">Create new product</div>
                                        <div class="text-xs text-green-600" x-text="'Add \'' + searchQuery + '\' as a new product'"></div>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Item cards --}}
                    <div x-show="items.length > 0" class="space-y-3">
                        <template x-for="(item, i) in items" :key="item.id">
                            <div class="border border-gray-200 rounded-xl p-4 bg-white">
                                {{-- Product header row --}}
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-semibold text-gray-800" x-text="item.name"></div>
                                        <div class="text-xs text-gray-400 mt-0.5" x-text="item.sku ? 'SKU: ' + item.sku : ''"></div>
                                    </div>
                                    <button type="button" @click="removeItem(i)"
                                            class="text-red-400 hover:text-red-600 transition-colors shrink-0 mt-0.5 p-1">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>

                                {{-- Unit cost (hidden for serialized products — cost entered per unit below) --}}
                                <template x-if="!item.is_serialized">
                                    <div class="mt-3 flex items-center gap-2">
                                        <label class="text-xs text-gray-500 whitespace-nowrap">Unit Cost (Rs.)</label>
                                        <input type="number" x-model.number="item.unit_cost" @input="recalc()"
                                               min="0" step="0.01"
                                               class="w-32 text-right border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary-500">
                                    </div>
                                </template>

                                {{-- Non-colored: single qty --}}
                                <template x-if="!item.has_colors">
                                    <div class="mt-3 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <label class="text-xs text-gray-500">Quantity</label>
                                            <input type="number" x-model.number="item.quantity" @input="recalc(); syncSerials(item)"
                                                   min="1"
                                                   class="w-24 text-center border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary-500">
                                        </div>
                                        <div class="text-sm font-semibold text-gray-700">
                                            Rs. <span x-text="(item.is_serialized ? item.serials.reduce((s,sn)=>s+(parseFloat(sn.cost_price)||0),0) : item.quantity*item.unit_cost).toLocaleString()"></span>
                                        </div>
                                    </div>
                                </template>

                                {{-- Colored: per-color qty rows --}}
                                <template x-if="item.has_colors">
                                    <div class="mt-3">
                                        <div class="flex items-center gap-2 mb-2 flex-wrap">
                                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Quantity by Color</span>

                                            {{-- Same / Different attribute mode toggle --}}
                                            <template x-if="item.is_serialized && item.attrDefs && item.attrDefs.length > 0">
                                                <div class="flex items-center gap-1 ml-auto bg-gray-100 rounded-lg p-0.5">
                                                    <button type="button" @click="item.attrMode = 'same'"
                                                            :class="item.attrMode === 'same' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                                            class="text-[11px] font-semibold px-2.5 py-1 rounded-md transition-all">
                                                        Same attributes
                                                    </button>
                                                    <button type="button" @click="item.attrMode = 'different'"
                                                            :class="item.attrMode === 'different' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                                            class="text-[11px] font-semibold px-2.5 py-1 rounded-md transition-all">
                                                        Per unit
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                        <div class="space-y-1">
                                            <template x-for="(clr, ci) in item.colors" :key="clr.id">
                                                <div class="py-2 border-t border-gray-100 first:border-t-0">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-5 h-5 rounded-full border border-gray-300 shrink-0 shadow-sm"
                                                             :style="clr.hex_code ? `background:${clr.hex_code}` : 'background:#e5e7eb'"></div>
                                                        <span class="text-sm text-gray-700 flex-1 min-w-0 truncate" x-text="clr.name"></span>
                                                        <input type="number" x-model.number="clr.quantity" @input="recalc(); syncSerials(item)"
                                                               min="0" placeholder="0"
                                                               class="w-20 text-center border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary-500">
                                                        <div class="text-sm text-gray-600 w-28 text-right shrink-0">
                                                            Rs. <span x-text="(item.is_serialized ? (clr.serials||[]).reduce((s,sn)=>s+(parseFloat(sn.cost_price)||0),0) : (clr.quantity||0)*item.unit_cost).toLocaleString()"></span>
                                                        </div>
                                                    </div>
                                                    {{-- Shared attributes for this color (entered once, applied to all its units) --}}
                                                    <template x-if="item.is_serialized && item.attrMode === 'same' && (clr.quantity || 0) > 0 && item.attrDefs && item.attrDefs.length > 0">
                                                        <div class="pl-7 mt-2">
                                                            <div class="border border-indigo-200 bg-indigo-50/60 rounded-xl p-3">
                                                                <div class="text-[10px] font-bold text-indigo-700 uppercase tracking-wide mb-2">
                                                                    <i class="fas fa-clone mr-1"></i> Attributes for all <span x-text="clr.quantity"></span> <span x-text="clr.name"></span> unit(s)
                                                                </div>
                                                                <div class="grid grid-cols-2 gap-2">
                                                                    <template x-for="(ad, adi) in item.attrDefs" :key="adi">
                                                                        <div>
                                                                            <label class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide" x-text="ad.name"></label>
                                                                            <select class="w-full border border-gray-300 bg-white rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400"
                                                                                    @change="clr.sharedAttributes[ad.name] = $event.target.value">
                                                                                <option value="">— Select —</option>
                                                                                <template x-for="(opt, oi) in ad.options" :key="oi">
                                                                                    <option :value="opt" x-text="opt"
                                                                                            :selected="clr.sharedAttributes[ad.name] === opt"></option>
                                                                                </template>
                                                                            </select>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    {{-- Serial inputs per color unit (serialized products) --}}
                                                    <template x-if="item.is_serialized && (clr.quantity || 0) > 0">
                                                        <div class="pl-7 mt-2 space-y-2">
                                                            <template x-for="(csn, csi) in (clr.serials || [])" :key="csi">
                                                                <div class="border border-gray-200 rounded-xl p-2.5 bg-gray-50 space-y-2">
                                                                    <div class="text-[10px] font-semibold text-gray-400 uppercase" x-text="`Unit ${csi+1}`"></div>
                                                                    <input type="text"
                                                                           x-model="clr.serials[csi].serial"
                                                                           :placeholder="`IMEI / Serial #${csi+1}`"
                                                                           @keydown.enter.prevent="focusNextSerial($event)"
                                                                           @input="csn.serialError = null"
                                                                           @blur="checkSerialUnique(clr.serials[csi])"
                                                                           data-serial-input
                                                                           class="w-full border rounded-lg px-3 py-1.5 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-colors"
                                                                           :class="serialInputClass(csn)">
                                                                    <div x-show="csn.serialChecking" class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                                                                        <i class="fas fa-spinner fa-spin"></i> Checking…
                                                                    </div>
                                                                    <div x-show="csn.serialError" class="text-xs text-red-600 mt-1 flex items-center gap-1">
                                                                        <i class="fas fa-exclamation-circle"></i>
                                                                        <span x-text="csn.serialError"></span>
                                                                    </div>
                                                                    <div class="grid grid-cols-2 gap-2">
                                                                        <div>
                                                                            <label class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide">Cost (Rs.)</label>
                                                                            <input type="number" x-model.number="clr.serials[csi].cost_price"
                                                                                   @input="recalc()"
                                                                                   min="0" step="1" placeholder="0"
                                                                                   class="w-full border border-gray-300 bg-white rounded-lg px-2 py-1.5 text-sm text-right focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                                                        </div>
                                                                        <div>
                                                                            <label class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide">Price (Rs.)</label>
                                                                            <input type="number" x-model.number="clr.serials[csi].selling_price"
                                                                                   min="0" step="1" placeholder="0"
                                                                                   class="w-full border border-gray-300 bg-white rounded-lg px-2 py-1.5 text-sm text-right focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                                                        </div>
                                                                    </div>
                                                                    {{-- Per-product attribute dropdowns (colored variant, hidden in "same attributes" mode) --}}
                                                                    <template x-if="item.attrDefs && item.attrDefs.length > 0 && item.attrMode !== 'same'">
                                                                        <div class="grid grid-cols-2 gap-2">
                                                                            <template x-for="(ad, adi) in item.attrDefs" :key="adi">
                                                                                <div>
                                                                                    <label class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide" x-text="ad.name"></label>
                                                                                    <select class="w-full border border-gray-300 bg-white rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400"
                                                                                            @change="clr.serials[csi].attributes[ad.name] = $event.target.value">
                                                                                        <option value="">— Select —</option>
                                                                                        <template x-for="(opt, oi) in ad.options" :key="oi">
                                                                                            <option :value="opt" x-text="opt"
                                                                                                    :selected="clr.serials[csi].attributes[ad.name] === opt"></option>
                                                                                        </template>
                                                                                    </select>
                                                                                </div>
                                                                            </template>
                                                                        </div>
                                                                    </template>

                                                                    {{-- Extra freeform fields --}}
                                                                    <template x-for="(ef, efi) in csn.extraFields" :key="efi">
                                                                        <div class="flex items-center gap-2">
                                                                            <input type="text" x-model="ef.key"
                                                                                   placeholder="Field name"
                                                                                   class="w-28 shrink-0 border border-gray-300 bg-white rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                                                            <span class="text-gray-300 text-sm shrink-0">:</span>
                                                                            <input type="text" x-model="ef.value"
                                                                                   placeholder="Value"
                                                                                   class="flex-1 border border-gray-300 bg-white rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                                                            <button type="button" @click="csn.extraFields.splice(efi, 1)"
                                                                                    class="text-red-400 hover:text-red-600 transition-colors shrink-0">
                                                                                <i class="fas fa-times text-xs"></i>
                                                                            </button>
                                                                        </div>
                                                                    </template>

                                                                    {{-- + Add field button --}}
                                                                    <button type="button"
                                                                            @click="csn.extraFields.push({ key: '', value: '' })"
                                                                            class="w-full text-xs text-indigo-600 hover:text-indigo-800 border border-dashed border-indigo-300 hover:border-indigo-500 rounded-lg py-1.5 transition-colors flex items-center justify-center gap-1.5">
                                                                        <i class="fas fa-plus text-[10px]"></i> Add field
                                                                    </button>

                                                                    {{-- Unit image upload --}}
                                                                    <div class="pt-1">
                                                                        <label class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide block mb-1">
                                                                            <i class="fas fa-camera mr-1"></i> Unit Photo
                                                                        </label>
                                                                        <template x-if="!csn.imagePreviewUrl">
                                                                            <div>
                                                                                <input type="file" accept="image/*"
                                                                                       @change="uploadSerialImage(clr.serials[csi], $event.target.files[0])"
                                                                                       class="w-full text-xs border border-dashed border-gray-300 rounded-lg px-2 py-1.5 file:mr-2 file:text-xs file:border-0 file:rounded file:bg-indigo-50 file:text-indigo-700 file:px-2 file:py-0.5 cursor-pointer">
                                                                                <div x-show="csn.imageUploading" class="text-xs text-indigo-600 mt-1 flex items-center gap-1">
                                                                                    <i class="fas fa-spinner fa-spin"></i> Uploading…
                                                                                </div>
                                                                                <div x-show="csn.imageError" x-text="csn.imageError" class="text-xs text-red-500 mt-1"></div>
                                                                            </div>
                                                                        </template>
                                                                        <template x-if="csn.imagePreviewUrl">
                                                                            <div class="flex items-center gap-2">
                                                                                <img :src="csn.imagePreviewUrl" class="w-14 h-14 object-cover rounded-lg border border-gray-200">
                                                                                <button type="button"
                                                                                        @click="clr.serials[csi].image_path = null; clr.serials[csi].imagePreviewUrl = null"
                                                                                        class="text-xs text-red-500 hover:text-red-700 flex items-center gap-1">
                                                                                    <i class="fas fa-times"></i> Remove
                                                                                </button>
                                                                            </div>
                                                                        </template>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                        {{-- Color totals footer --}}
                                        <div class="flex items-center justify-between pt-2 mt-1 border-t border-gray-200 text-sm font-semibold">
                                            <span class="text-gray-500">
                                                Total: <span x-text="item.quantity" class="text-gray-800"></span> items
                                            </span>
                                            <span class="text-gray-800">
                                                Rs. <span x-text="(item.is_serialized ? item.colors.reduce((cs,clr)=>cs+(clr.serials||[]).reduce((ss,sn)=>ss+(parseFloat(sn.cost_price)||0),0),0) : item.quantity*item.unit_cost).toLocaleString()"></span>
                                            </span>
                                        </div>
                                    </div>
                                </template>

                                {{-- Serial inputs for non-colored serialized items --}}
                                <template x-if="item.is_serialized && !item.has_colors">
                                    <div class="mt-3 pt-3 border-t border-indigo-100">
                                        <div class="flex items-center gap-2 mb-3 flex-wrap">
                                            <i class="fas fa-barcode text-indigo-500 text-xs"></i>
                                            <span class="text-xs font-semibold text-indigo-700">Serial / IMEI Numbers</span>
                                            <span class="text-xs text-red-500 font-bold">*</span>
                                            <span class="text-xs text-gray-400"
                                                  x-text="`(${item.serials.filter(s => s.serial && s.serial.trim()).length}/${item.quantity} entered)`"></span>

                                            {{-- Same / Different attribute mode toggle --}}
                                            <template x-if="item.attrDefs && item.attrDefs.length > 0">
                                                <div class="flex items-center gap-1 ml-auto bg-gray-100 rounded-lg p-0.5">
                                                    <button type="button" @click="item.attrMode = 'same'"
                                                            :class="item.attrMode === 'same' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                                            class="text-[11px] font-semibold px-2.5 py-1 rounded-md transition-all">
                                                        Same attributes
                                                    </button>
                                                    <button type="button" @click="item.attrMode = 'different'"
                                                            :class="item.attrMode === 'different' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                                            class="text-[11px] font-semibold px-2.5 py-1 rounded-md transition-all">
                                                        Per unit
                                                    </button>
                                                </div>
                                            </template>
                                        </div>

                                        {{-- Shared attributes (entered once, applied to all units) --}}
                                        <template x-if="item.attrMode === 'same' && item.attrDefs && item.attrDefs.length > 0">
                                            <div class="border border-indigo-200 bg-indigo-50/60 rounded-xl p-3 mb-3">
                                                <div class="text-[10px] font-bold text-indigo-700 uppercase tracking-wide mb-2">
                                                    <i class="fas fa-clone mr-1"></i> Attributes for all <span x-text="item.quantity"></span> unit(s)
                                                </div>
                                                <div class="grid grid-cols-2 gap-2">
                                                    <template x-for="(ad, adi) in item.attrDefs" :key="adi">
                                                        <div>
                                                            <label class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide" x-text="ad.name"></label>
                                                            <select class="w-full border border-gray-300 bg-white rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400"
                                                                    @change="item.sharedAttributes[ad.name] = $event.target.value">
                                                                <option value="">— Select —</option>
                                                                <template x-for="(opt, oi) in ad.options" :key="oi">
                                                                    <option :value="opt" x-text="opt"
                                                                            :selected="item.sharedAttributes[ad.name] === opt"></option>
                                                                </template>
                                                            </select>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <div class="space-y-3">
                                            <template x-for="(sn, si) in item.serials" :key="si">
                                                <div class="border border-gray-200 rounded-xl p-3 bg-gray-50 space-y-2">
                                                    {{-- Row header --}}
                                                    <div class="text-xs font-semibold text-gray-500" x-text="`Unit ${si+1}`"></div>

                                                    {{-- IMEI / Serial --}}
                                                    <input type="text"
                                                           x-model="item.serials[si].serial"
                                                           :placeholder="`IMEI / Serial #${si+1}`"
                                                           @keydown.enter.prevent="focusNextSerial($event)"
                                                           @input="sn.serialError = null"
                                                           @blur="checkSerialUnique(item.serials[si])"
                                                           data-serial-input
                                                           class="w-full border rounded-lg px-3 py-1.5 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-colors"
                                                           :class="serialInputClass(sn)">
                                                    <div x-show="sn.serialChecking" class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                                                        <i class="fas fa-spinner fa-spin"></i> Checking…
                                                    </div>
                                                    <div x-show="sn.serialError" class="text-xs text-red-600 mt-1 flex items-center gap-1">
                                                        <i class="fas fa-exclamation-circle"></i>
                                                        <span x-text="sn.serialError"></span>
                                                    </div>

                                                    {{-- Cost + Selling price --}}
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <div>
                                                            <label class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide">Cost Price (Rs.)</label>
                                                            <input type="number" x-model.number="item.serials[si].cost_price"
                                                                   @input="recalc()"
                                                                   min="0" step="1" placeholder="0"
                                                                   class="w-full border border-gray-300 bg-white rounded-lg px-2 py-1.5 text-sm text-right focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                                        </div>
                                                        <div>
                                                            <label class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide">Selling Price (Rs.)</label>
                                                            <input type="number" x-model.number="item.serials[si].selling_price"
                                                                   min="0" step="1" placeholder="0"
                                                                   class="w-full border border-gray-300 bg-white rounded-lg px-2 py-1.5 text-sm text-right focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                                        </div>
                                                    </div>

                                                    {{-- Custom attribute dropdowns (hidden in "same attributes" mode) --}}
                                                    <template x-if="item.attrDefs && item.attrDefs.length > 0 && item.attrMode !== 'same'">
                                                        <div class="grid grid-cols-2 gap-2">
                                                            <template x-for="(ad, adi) in item.attrDefs" :key="adi">
                                                                <div>
                                                                    <label class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide" x-text="ad.name"></label>
                                                                    <select class="w-full border border-gray-300 bg-white rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400"
                                                                            @change="item.serials[si].attributes[ad.name] = $event.target.value">
                                                                        <option value="">— Select —</option>
                                                                        <template x-for="(opt, oi) in ad.options" :key="oi">
                                                                            <option :value="opt" x-text="opt"
                                                                                    :selected="item.serials[si].attributes[ad.name] === opt"></option>
                                                                        </template>
                                                                    </select>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>

                                                    {{-- Extra freeform fields --}}
                                                    <template x-for="(ef, efi) in sn.extraFields" :key="efi">
                                                        <div class="flex items-center gap-2">
                                                            <input type="text" x-model="ef.key"
                                                                   placeholder="Field name"
                                                                   class="w-32 shrink-0 border border-gray-300 bg-white rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                                            <span class="text-gray-300 text-sm shrink-0">:</span>
                                                            <input type="text" x-model="ef.value"
                                                                   placeholder="Value"
                                                                   class="flex-1 border border-gray-300 bg-white rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                                            <button type="button" @click="sn.extraFields.splice(efi, 1)"
                                                                    class="text-red-400 hover:text-red-600 transition-colors shrink-0">
                                                                <i class="fas fa-times text-xs"></i>
                                                            </button>
                                                        </div>
                                                    </template>

                                                    {{-- + Add field button --}}
                                                    <button type="button"
                                                            @click="sn.extraFields.push({ key: '', value: '' })"
                                                            class="w-full text-xs text-indigo-600 hover:text-indigo-800 border border-dashed border-indigo-300 hover:border-indigo-500 rounded-lg py-1.5 transition-colors flex items-center justify-center gap-1.5">
                                                        <i class="fas fa-plus text-[10px]"></i> Add field
                                                    </button>

                                                    {{-- Unit image upload --}}
                                                    <div class="pt-1">
                                                        <label class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide block mb-1">
                                                            <i class="fas fa-camera mr-1"></i> Unit Photo
                                                        </label>
                                                        <template x-if="!sn.imagePreviewUrl">
                                                            <div>
                                                                <input type="file" accept="image/*"
                                                                       @change="uploadSerialImage(item.serials[si], $event.target.files[0])"
                                                                       class="w-full text-xs border border-dashed border-gray-300 rounded-lg px-2 py-1.5 file:mr-2 file:text-xs file:border-0 file:rounded file:bg-indigo-50 file:text-indigo-700 file:px-2 file:py-0.5 cursor-pointer">
                                                                <div x-show="sn.imageUploading" class="text-xs text-indigo-600 mt-1 flex items-center gap-1">
                                                                    <i class="fas fa-spinner fa-spin"></i> Uploading…
                                                                </div>
                                                                <div x-show="sn.imageError" x-text="sn.imageError" class="text-xs text-red-500 mt-1"></div>
                                                            </div>
                                                        </template>
                                                        <template x-if="sn.imagePreviewUrl">
                                                            <div class="flex items-center gap-2">
                                                                <img :src="sn.imagePreviewUrl" class="w-14 h-14 object-cover rounded-lg border border-gray-200">
                                                                <button type="button"
                                                                        @click="item.serials[si].image_path = null; item.serials[si].imagePreviewUrl = null"
                                                                        class="text-xs text-red-500 hover:text-red-700 flex items-center gap-1">
                                                                    <i class="fas fa-times"></i> Remove
                                                                </button>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- Hidden container for flattened form fields (populated by submitForm) --}}
                    <div id="flatItemsContainer"></div>

                    <div x-show="items.length === 0" class="text-center py-8 text-gray-400">
                        <i class="fas fa-box-open text-3xl mb-2 block"></i>
                        Search and add products above
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            <div class="card">
                <div class="card-header"><h2 class="font-semibold text-gray-800">Notes</h2></div>
                <div class="card-body">
                    <textarea name="notes" rows="2" class="form-textarea" placeholder="Optional notes about this purchase..."></textarea>
                </div>
            </div>
        </div>

        {{-- Right: vendor + payment + summary --}}
        <div class="space-y-5">

            {{-- Vendor --}}
            <div class="card p-5">
                <h2 class="font-semibold text-gray-800 mb-3">Vendor <span class="text-red-500">*</span></h2>
                <select name="vendor_id" x-model="vendorId" @change="loadVendor()"
                        :class="!vendorId ? 'border-red-300 bg-red-50' : 'border-gray-300'"
                        class="form-select mb-2" required>
                    <option value="">— Select a Vendor —</option>
                    @foreach($vendors as $v)
                    <option value="{{ $v->id }}" {{ request('vendor_id') == $v->id ? 'selected' : '' }}>{{ $v->name }}{{ $v->company ? ' ('.$v->company.')' : '' }}</option>
                    @endforeach
                </select>
                <p x-show="!vendorId" class="text-xs text-red-500 mt-1">A vendor must be selected to record a purchase.</p>
                <div x-show="vendorBalance != null" class="text-xs text-gray-500 flex justify-between mt-1">
                    <span>Current balance owed:</span>
                    <span class="font-semibold" :class="vendorBalance > 0 ? 'text-red-600' : 'text-green-600'"
                          x-text="'Rs. ' + Math.abs(vendorBalance).toLocaleString() + (vendorBalance > 0 ? ' (owed)' : ' (overpaid)')"></span>
                </div>
                <div class="mt-3">
                    <label class="form-label text-sm">Reference / Invoice #</label>
                    <input type="text" name="reference" class="form-input" placeholder="Bill number from vendor">
                </div>
            </div>

            {{-- Purchase date --}}
            <div class="card p-5">
                <label class="form-label">Purchase Date *</label>
                <input type="date" name="purchase_date" value="{{ date('Y-m-d') }}" class="form-input" required>
            </div>

            {{-- Payment --}}
            <div class="card p-5">
                <h2 class="font-semibold text-gray-800 mb-3">Payment</h2>
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <button type="button" @click="payMethod = 'cash'"
                            :class="payMethod === 'cash' ? 'ring-2 ring-green-500 bg-green-50' : 'bg-gray-50'"
                            class="flex flex-col items-center py-2.5 rounded-xl border border-gray-200 text-xs font-semibold transition-all">
                        <i class="fas fa-money-bill-wave text-green-600 mb-1"></i> Cash
                    </button>
                    <button type="button" @click="payMethod = 'bank_transfer'"
                            :class="payMethod === 'bank_transfer' ? 'ring-2 ring-blue-500 bg-blue-50' : 'bg-gray-50'"
                            class="flex flex-col items-center py-2.5 rounded-xl border border-gray-200 text-xs font-semibold transition-all">
                        <i class="fas fa-university text-blue-600 mb-1"></i> Bank
                    </button>
                    <button type="button" @click="payMethod = 'credit'"
                            :class="payMethod === 'credit' ? 'ring-2 ring-red-400 bg-red-50' : 'bg-gray-50'"
                            class="flex flex-col items-center py-2.5 rounded-xl border border-gray-200 text-xs font-semibold transition-all">
                        <i class="fas fa-file-invoice text-red-500 mb-1"></i> Credit
                    </button>
                    <button type="button" @click="payMethod = 'partial'"
                            :class="payMethod === 'partial' ? 'ring-2 ring-orange-400 bg-orange-50' : 'bg-gray-50'"
                            class="flex flex-col items-center py-2.5 rounded-xl border border-gray-200 text-xs font-semibold transition-all">
                        <i class="fas fa-adjust text-orange-500 mb-1"></i> Partial
                    </button>
                </div>
                <input type="hidden" name="payment_method" :value="payMethod">
                <input type="hidden" name="partial_pay_via" :value="partialPayVia">
                <input type="hidden" name="partial_bank_account_id" :value="partialBankId">

                {{-- Bank account selector (bank_transfer full payment) --}}
                @if($bankAccounts->count())
                <div x-show="payMethod === 'bank_transfer'" class="mt-2 space-y-1">
                    <label class="form-label text-sm"><i class="fas fa-university mr-1 text-blue-500"></i>Select Bank Account *</label>
                    <select name="bank_account_id" class="form-select text-sm">
                        <option value="">— Choose bank account —</option>
                        @foreach($bankAccounts as $bank)
                        <option value="{{ $bank->id }}">
                            {{ $bank->label }} — {{ $bank->bank_name }}{{ $bank->account_number ? ' · '.$bank->account_number : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div x-show="payMethod === 'partial'" class="space-y-2 mt-2">
                    {{-- Cash / Bank toggle --}}
                    <div class="flex gap-2">
                        <button type="button" @click="partialPayVia = 'cash'; partialBankId = ''"
                                :class="partialPayVia === 'cash' ? 'ring-2 ring-green-500 bg-green-50' : 'bg-gray-50'"
                                class="flex-1 flex items-center justify-center gap-1 py-1.5 rounded-lg border border-gray-200 text-xs font-semibold transition-all">
                            <i class="fas fa-money-bill-wave text-green-500"></i> Cash
                        </button>
                        <button type="button" @click="partialPayVia = 'bank'"
                                :class="partialPayVia === 'bank' ? 'ring-2 ring-blue-500 bg-blue-50' : 'bg-gray-50'"
                                class="flex-1 flex items-center justify-center gap-1 py-1.5 rounded-lg border border-gray-200 text-xs font-semibold transition-all">
                            <i class="fas fa-university text-blue-500"></i> Bank
                        </button>
                    </div>

                    {{-- Bank account selector for partial --}}
                    @if($bankAccounts->count())
                    <div x-show="partialPayVia === 'bank'" x-transition>
                        <select x-model="partialBankId" class="form-select text-sm">
                            <option value="">— Choose bank account —</option>
                            @foreach($bankAccounts as $bank)
                            <option value="{{ $bank->id }}">
                                {{ $bank->label }} — {{ $bank->bank_name }}{{ $bank->account_number ? ' · '.$bank->account_number : '' }}
                            </option>
                            @endforeach
                        </select>
                        <p x-show="!partialBankId" class="text-xs text-orange-500 mt-0.5">
                            <i class="fas fa-exclamation-triangle mr-1"></i>Please select a bank account.
                        </p>
                    </div>
                    @endif

                    <label class="form-label text-sm">Amount Paid Now (Rs.)</label>
                    <input type="number" name="amount_paid" x-model.number="amountPaid"
                           @input="recalc()" min="0" step="0.01" :max="total"
                           class="form-input">
                    <div class="flex justify-between text-xs text-gray-500">
                        <span>Remaining on credit:</span>
                        <span class="font-semibold text-red-600" x-text="'Rs. ' + Math.max(0, total - amountPaid).toLocaleString()"></span>
                    </div>
                </div>

                <div x-show="payMethod === 'credit'" class="mt-2 p-2 bg-red-50 rounded-lg text-xs text-red-700">
                    Full amount will be added to vendor's Khata
                </div>
                <div x-show="payMethod === 'cash'" class="mt-2 p-2 bg-green-50 rounded-lg text-xs text-green-700">
                    <i class="fas fa-money-bill-wave mr-1"></i>Paid in full by cash — no Khata entry
                </div>
                <div x-show="payMethod === 'bank_transfer'" class="mt-2 p-2 bg-blue-50 rounded-lg text-xs text-blue-700">
                    <i class="fas fa-university mr-1"></i>Paid in full via bank transfer — no Khata entry
                </div>
            </div>

            {{-- Summary --}}
            <div class="card p-5 bg-gray-50">
                <h2 class="font-semibold text-gray-800 mb-3">Summary</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Items</span>
                        <span x-text="items.length + ' product(s)'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Total Qty</span>
                        <span x-text="items.reduce((s, i) => s + i.quantity, 0)"></span>
                    </div>
                    <div class="flex justify-between border-t border-gray-200 pt-2 font-bold text-base">
                        <span>Total</span>
                        <span x-text="'Rs. ' + total.toLocaleString()"></span>
                    </div>
                    <div x-show="payMethod === 'cash' || payMethod === 'bank_transfer'" class="flex justify-between text-green-600 font-semibold">
                        <span>Paying Now</span>
                        <span x-text="'Rs. ' + total.toLocaleString()"></span>
                    </div>
                    <div x-show="payMethod === 'partial'" class="flex justify-between text-orange-600 font-semibold">
                        <span>On Credit</span>
                        <span x-text="'Rs. ' + Math.max(0, total - amountPaid).toLocaleString()"></span>
                    </div>
                    <div x-show="payMethod === 'credit'" class="flex justify-between text-red-600 font-semibold">
                        <span>On Credit (Khata)</span>
                        <span x-text="'Rs. ' + total.toLocaleString()"></span>
                    </div>
                </div>

                <button type="submit" :disabled="items.length === 0"
                        class="btn-primary w-full justify-center mt-4 btn-lg"
                        :class="items.length === 0 ? 'opacity-50 cursor-not-allowed' : ''">
                    <i class="fas fa-save mr-2"></i> Save Purchase
                </button>
                <p class="text-xs text-gray-400 text-center mt-2">Stock will be updated automatically</p>
            </div>
        </div>
    </div>

</form>

{{-- ── Quick Create Product Modal ─────────────────────────────────────── --}}
{{-- Build categories JSON in a PHP block to avoid Blade bracket-parsing issues --}}
@php
    $categoriesJson = json_encode($categories->map(function($c) {
        return [
            'id'       => $c->id,
            'name'     => $c->name,
            'children' => $c->children->map(function($s) {
                return ['id' => $s->id, 'name' => $s->name];
            })->values(),
        ];
    })->values());
@endphp
<script>
    const _categoriesData    = {!! $categoriesJson !!};
    const _serialAttrDefs         = {!! json_encode($serialAttributeDefs->map(fn($d) => ['name' => $d->name, 'options' => $d->options])->values()) !!};
    const _serialCheckUrl         = '/{{ auth()->user()->hasRole('admin') ? 'admin' : 'salesman' }}/api/serials/check';
    const _serialImageUploadUrl   = '/{{ auth()->user()->hasRole('admin') ? 'admin' : 'salesman' }}/purchases/temp-serial-image';
</script>

<div x-data="purchaseCreateModal()"
     x-show="$store.quickCreate.open"
     x-transition.opacity
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display:none;">

    <div class="absolute inset-0 bg-black/50" @click="close()"></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-auto my-8 flex flex-col z-10">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 shrink-0">
            <h2 class="font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-plus-circle text-green-500"></i> Create New Product
            </h2>
            <button type="button" @click="close()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        @include('admin.purchases._quick-create-modal-body')

        {{-- Footer --}}
        <div class="px-5 py-3 border-t border-gray-200 flex gap-3 justify-end shrink-0">
            <button type="button" @click="close()" class="btn-outline btn-sm">Cancel</button>
            <button type="button" @click="save()" :disabled="saving"
                    class="btn-primary btn-sm" :class="saving ? 'opacity-60 cursor-wait' : ''">
                <i class="fas fa-spinner fa-spin mr-1.5" x-show="saving"></i>
                <i class="fas fa-check mr-1.5" x-show="!saving"></i>
                <span x-text="saving ? 'Creating...' : 'Create & Add to Purchase'"></span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Shared store so the modal can talk to the purchase form
document.addEventListener('alpine:init', () => {
    Alpine.store('quickCreate', {
        open: false,
        prefillName: '',
    });
});

function purchaseForm() {
    return {
        items: [],
        searchQuery: '',
        searchResults: [],
        showDropdown: false,
        attrPrompt: null,   // item awaiting the Same/Different attributes choice
        payMethod: 'cash',
        amountPaid: 0,
        partialPayVia: 'cash',
        partialBankId: '',
        total: 0,
        vendorId: '{{ request('vendor_id', '') }}',
        vendorBalance: null,

        async searchProducts() {
            if (this.searchQuery.length < 2) { this.searchResults = []; return; }
            const res = await fetch(`/{{ auth()->user()->hasRole('admin') ? 'admin' : 'salesman' }}/api/products/search?q=${encodeURIComponent(this.searchQuery)}`);
            this.searchResults = await res.json();
            this.showDropdown = true;
        },

        openCreateModal() {
            this.showDropdown = false;
            this.$store.quickCreate.prefillName = this.searchQuery;
            this.$store.quickCreate.open = true;
            this.searchQuery = '';
            this.searchResults = [];
        },

        addProduct(p) {
            // Prevent duplicate
            if (this.items.find(i => i.id === p.id)) {
                alert(`"${p.name}" is already in the list.`);
                this.searchQuery = '';
                this.searchResults = [];
                this.showDropdown = false;
                return;
            }

            const hasColors  = Array.isArray(p.colors) && p.colors.length > 0;
            const isSerial   = p.is_serialized || false;
            // Attribute defs for this product: either the subset the admin chose, or all active defs
            const attrDefs   = isSerial ? (p.serial_attr_defs || []) : [];
            const newItem = {
                id:            p.id,
                name:          p.name,
                sku:           p.sku || '',
                unit_cost:     parseFloat(p.cost_price) || 0,
                has_colors:    hasColors,
                is_serialized: isSerial,
                attrDefs:      attrDefs,
                attrMode:      'different',      // 'same' = one attribute set for all units
                sharedAttributes: {},
                colors:        hasColors ? p.colors.map(c => ({
                                   id:       c.id,
                                   name:     c.name,
                                   hex_code: c.hex_code || '',
                                   quantity: 0,
                                   serials:  [],
                                   sharedAttributes: {},
                               })) : [],
                quantity:      hasColors ? 0 : 1,
                serials:       (!hasColors && isSerial) ? [{ serial:'', cost_price:'', selling_price:'', attributes:{}, extraFields:[], serialError:null, serialChecking:false }] : [],
            };
            this.items.push(newItem);

            // Ask how attributes should be entered for this batch
            if (isSerial && attrDefs.length > 0) {
                this.attrPrompt = this.items[this.items.length - 1];
            }

            this.searchQuery  = '';
            this.searchResults = [];
            this.showDropdown = false;
            this.recalc();
        },

        chooseAttrMode(mode) {
            if (this.attrPrompt) this.attrPrompt.attrMode = mode;
            this.attrPrompt = null;
        },

        removeItem(i) {
            this.items.splice(i, 1);
            this.recalc();
        },

        recalc() {
            this.items.forEach(item => {
                if (item.has_colors) {
                    item.quantity = item.colors.reduce((s, c) => s + (parseInt(c.quantity) || 0), 0);
                }
                if (item.is_serialized) {
                    if (!item.has_colors) {
                        item.serial_cost_total = item.serials.reduce((s, sn) => s + (parseFloat(sn.cost_price) || 0), 0);
                    } else {
                        item.serial_cost_total = item.colors.reduce((cs, clr) =>
                            cs + (clr.serials || []).reduce((ss, sn) => ss + (parseFloat(sn.cost_price) || 0), 0), 0);
                    }
                }
            });
            this.total = this.items.reduce((s, i) => {
                if (i.is_serialized) return s + (i.serial_cost_total || 0);
                return s + (i.quantity * i.unit_cost);
            }, 0);
        },

        newSerialRow() {
            return { serial: '', cost_price: '', selling_price: '', attributes: {}, extraFields: [],
                     serialError: null, serialChecking: false,
                     image_path: null, imagePreviewUrl: null, imageUploading: false, imageError: null };
        },

        async uploadSerialImage(snObj, file) {
            if (!file) return;
            snObj.imageUploading = true;
            snObj.imageError = null;
            const fd = new FormData();
            fd.append('image', file);
            fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
            try {
                const res = await fetch(_serialImageUploadUrl, { method: 'POST', body: fd });
                if (!res.ok) throw new Error('Upload failed');
                const data = await res.json();
                snObj.image_path     = data.path;
                snObj.imagePreviewUrl = data.url;
            } catch (e) {
                snObj.imageError = 'Upload failed — please try again';
            } finally {
                snObj.imageUploading = false;
            }
        },

        serialInputClass(snObj) {
            if (snObj.serialError)                        return 'border-red-400 bg-red-50';
            if (snObj.serial && snObj.serial.trim())      return 'border-green-400 bg-green-50';
            return 'border-gray-300 bg-white';
        },

        collectAllSerials() {
            const list = [];
            this.items.forEach(item => {
                if (!item.is_serialized) return;
                if (!item.has_colors) {
                    item.serials.forEach(sn => { if (sn.serial && sn.serial.trim()) list.push(sn.serial.trim()); });
                } else {
                    item.colors.forEach(clr => {
                        (clr.serials || []).forEach(sn => { if (sn.serial && sn.serial.trim()) list.push(sn.serial.trim()); });
                    });
                }
            });
            return list;
        },

        async checkSerialUnique(snObj) {
            const val = (snObj.serial || '').trim();
            snObj.serialError = null;
            if (!val) return;

            // Check for duplicates within the current form (cross-item)
            const all = this.collectAllSerials();
            const upper = val.toUpperCase();
            const count = all.filter(s => s.toUpperCase() === upper).length;
            if (count > 1) {
                snObj.serialError = 'Duplicate — already entered in this form';
                return;
            }

            // Check against the database
            snObj.serialChecking = true;
            try {
                const res  = await fetch(`${_serialCheckUrl}?serial=${encodeURIComponent(val)}`);
                const data = await res.json();
                if (data.exists) snObj.serialError = 'Already registered in the system';
            } catch (e) {
                // network error — let server validate on submit
            } finally {
                snObj.serialChecking = false;
            }
        },

        syncSerials(item) {
            if (!item.is_serialized) return;
            if (!item.has_colors) {
                const qty = parseInt(item.quantity) || 0;
                while (item.serials.length < qty) item.serials.push(this.newSerialRow());
                item.serials.splice(qty);
            } else {
                item.colors.forEach(clr => {
                    if (!clr.serials) clr.serials = [];
                    const qty = parseInt(clr.quantity) || 0;
                    while (clr.serials.length < qty) clr.serials.push(this.newSerialRow());
                    clr.serials.splice(qty);
                });
            }
        },

        focusNextSerial(event) {
            const inputs = Array.from(document.querySelectorAll('[data-serial-input]'));
            const idx    = inputs.indexOf(event.target);
            if (idx >= 0 && idx < inputs.length - 1) inputs[idx + 1].focus();
        },

        async loadVendor() {
            if (!this.vendorId) { this.vendorBalance = null; return; }
            const res  = await fetch(`/admin/api/vendors/${this.vendorId}/balance`);
            const data = await res.json();
            this.vendorBalance = data.balance;
        },

        // Called by the modal after a product is created — add it to the list
        onProductCreated(p) {
            this.addProduct(p);
        },

        submitForm() {
            if (!this.vendorId) {
                alert('Please select a vendor before saving the purchase.');
                return;
            }
            if (this.items.length === 0) {
                alert('Please add at least one product.');
                return;
            }

            // Validate colored products have at least one non-zero color qty
            for (const item of this.items) {
                if (item.has_colors && item.quantity === 0) {
                    alert(`Please enter at least one color quantity for: ${item.name}`);
                    return;
                }
            }

            // Validate serial numbers for serialized products
            for (const item of this.items) {
                if (!item.is_serialized) continue;

                if (!item.has_colors) {
                    const filled = item.serials.filter(s => s.serial && s.serial.trim()).length;
                    if (filled < item.quantity) {
                        alert(`⚠️ Serial numbers required for: ${item.name}\n\nPlease enter all ${item.quantity} IMEI / Serial number(s).\n(${filled} of ${item.quantity} entered)`);
                        return;
                    }
                } else {
                    for (const clr of item.colors) {
                        const qty = parseInt(clr.quantity) || 0;
                        if (qty === 0) continue;
                        const serials = clr.serials || [];
                        const filled  = serials.filter(s => s.serial && s.serial.trim()).length;
                        if (filled < qty) {
                            alert(`⚠️ Serial numbers required for: ${item.name} — ${clr.name}\n\nPlease enter all ${qty} IMEI / Serial number(s).\n(${filled} of ${qty} entered)`);
                            return;
                        }
                    }
                }
            }

            // Cross-item global uniqueness check (catches duplicates across different products)
            const allSerials = this.collectAllSerials();
            const seenSerials = {};
            for (const s of allSerials) {
                const up = s.toUpperCase();
                if (seenSerials[up]) {
                    alert(`Duplicate serial number: "${s}" appears more than once.\nEach serial/IMEI must be unique across all products.`);
                    return;
                }
                seenSerials[up] = true;
            }

            // Block submission if any inline serial error is still showing
            const hasSerialErrors = this.items.some(item => {
                if (!item.is_serialized) return false;
                if (!item.has_colors) return item.serials.some(sn => sn.serialError);
                return item.colors.some(clr => (clr.serials || []).some(sn => sn.serialError));
            });
            if (hasSerialErrors) {
                alert('Please fix the serial number errors highlighted in red before saving.');
                return;
            }

            // "Same attributes" mode: copy the shared attribute set onto every unit
            const cleanAttrs = obj => Object.fromEntries(
                Object.entries(obj || {}).filter(([k, v]) => v !== '' && v != null)
            );
            this.items.forEach(item => {
                if (!item.is_serialized || item.attrMode !== 'same') return;
                if (!item.has_colors) {
                    const shared = cleanAttrs(item.sharedAttributes);
                    item.serials.forEach(sn => { sn.attributes = { ...shared }; });
                } else {
                    item.colors.forEach(clr => {
                        const shared = cleanAttrs(clr.sharedAttributes);
                        (clr.serials || []).forEach(sn => { sn.attributes = { ...shared }; });
                    });
                }
            });

            // Build hidden fields and inject into form
            const container = document.getElementById('flatItemsContainer');
            container.innerHTML = '';
            let flatIdx = 0;

            const injectField = (idx, key, val) => {
                const inp = document.createElement('input');
                inp.type  = 'hidden';
                inp.name  = `items[${idx}][${key}]`;
                inp.value = val;
                container.appendChild(inp);
            };
            const injectSerials = (idx, serials) => {
                (serials || []).forEach((snObj, j) => {
                    const mk = (key, val) => {
                        const inp = document.createElement('input');
                        inp.type  = 'hidden';
                        inp.name  = `items[${idx}][serials][${j}][${key}]`;
                        inp.value = val ?? '';
                        container.appendChild(inp);
                    };
                    mk('serial',        snObj.serial        || '');
                    mk('cost_price',    snObj.cost_price    || '');
                    mk('selling_price', snObj.selling_price || '');
                    if (snObj.image_path) mk('image_path', snObj.image_path);

                    // Merge pre-defined attribute dropdowns + user-added extra fields
                    const allAttrs = { ...(snObj.attributes || {}) };
                    (snObj.extraFields || []).forEach(ef => {
                        const k = (ef.key || '').trim();
                        if (k) allAttrs[k] = ef.value || '';
                    });
                    Object.entries(allAttrs).forEach(([k, v]) => {
                        const inp = document.createElement('input');
                        inp.type  = 'hidden';
                        inp.name  = `items[${idx}][serials][${j}][attributes][${k}]`;
                        inp.value = v || '';
                        container.appendChild(inp);
                    });
                });
            };

            this.items.forEach(item => {
                if (item.has_colors) {
                    item.colors
                        .filter(c => (parseInt(c.quantity) || 0) > 0)
                        .forEach(c => {
                            injectField(flatIdx, 'product_id', item.id);
                            injectField(flatIdx, 'quantity',   parseInt(c.quantity));
                            injectField(flatIdx, 'unit_cost',  item.unit_cost);
                            injectField(flatIdx, 'color_id',   c.id);
                            injectField(flatIdx, 'color_name', c.name);
                            if (item.is_serialized) injectSerials(flatIdx, c.serials);
                            flatIdx++;
                        });
                } else {
                    injectField(flatIdx, 'product_id', item.id);
                    injectField(flatIdx, 'quantity',   item.quantity);
                    injectField(flatIdx, 'unit_cost',  item.unit_cost);
                    if (item.is_serialized) injectSerials(flatIdx, item.serials);
                    flatIdx++;
                }
            });

            this.$el.submit();
        }
    };
}

function purchaseCreateModal() {
    return {
        saving: false,
        colors: [],
        imageFiles: [],
        imagePreviews: [],
        subcategories: [],
        errors: {},
        form: {
            name: '',
            sku: '',
            category_id: '',
            subcategory_id: '',
            brand_id: '',
            short_description: '',
            barcode: '',
            cost_price: '',
            price: '',
            compare_price: '',
            low_stock_threshold: 5,
            show_in_ecom: false,
            video_embed_url: '',
        },

        init() {
            this.$watch('$store.quickCreate.open', (val) => {
                if (val) this.reset();
            });
        },

        reset() {
            this.errors        = {};
            this.colors        = [];
            this.imageFiles    = [];
            this.imagePreviews = [];
            this.subcategories = [];
            this.form = {
                name:                this.$store.quickCreate.prefillName || '',
                sku:                 '',
                category_id:         '',
                subcategory_id:      '',
                brand_id:            '',
                short_description:   '',
                barcode:             '',
                cost_price:          '',
                price:               '',
                compare_price:       '',
                low_stock_threshold: 5,
                show_in_ecom:        false,
                video_embed_url:     '',
            };
        },

        onCategoryChange() {
            this.form.subcategory_id = '';
            const cat = _categoriesData.find(c => c.id == this.form.category_id);
            this.subcategories = cat ? cat.children : [];
        },

        addColor() {
            this.colors.push({ name: '', hex_code: '#3b82f6' });
        },

        onImagesChange(e) {
            const files = Array.from(e.target.files);
            this.imageFiles    = files;
            this.imagePreviews = files.map(f => URL.createObjectURL(f));
        },

        removeImage(i) {
            this.imagePreviews.splice(i, 1);
            this.imageFiles.splice(i, 1);
        },

        async generateBarcode() {
            const res  = await fetch('{{ route("{$rPrefix}.products.generate_barcode") }}');
            const data = await res.json();
            this.form.barcode = data.barcode;
        },

        close() {
            this.$store.quickCreate.open = false;
        },

        async save() {
            this.errors = {};
            if (!this.form.name.trim())    { this.errors.name       = 'Required'; return; }
            if (!this.form.cost_price)     { this.errors.cost_price = 'Required'; return; }
            if (!this.form.price)          { this.errors.price      = 'Required'; return; }

            this.saving = true;
            try {
                // Use FormData so image files are included
                const fd = new FormData();

                // Scalar fields
                const fields = [
                    'name','sku','category_id','subcategory_id','brand_id','short_description',
                    'barcode','cost_price','price','compare_price','low_stock_threshold',
                    'video_embed_url',
                ];
                fields.forEach(k => {
                    if (this.form[k] !== '' && this.form[k] !== null && this.form[k] !== undefined) {
                        fd.append(k, this.form[k]);
                    }
                });
                fd.append('show_in_ecom', this.form.show_in_ecom ? '1' : '0');

                // Images
                this.imageFiles.forEach(f => fd.append('images[]', f));

                // Colors (only rows with a name)
                this.colors.filter(c => c.name.trim()).forEach((c, i) => {
                    fd.append(`colors[${i}][name]`,     c.name);
                    fd.append(`colors[${i}][hex_code]`, c.hex_code || '');
                });

                const res = await fetch('{{ route("{$rPrefix}.api.products.quick-create") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept':       'application/json',
                    },
                    body: fd,
                });

                const data = await res.json();

                if (!res.ok) {
                    if (data.errors) {
                        Object.entries(data.errors).forEach(([k, msgs]) => {
                            this.errors[k] = msgs[0];
                        });
                    } else {
                        this.errors.general = data.message || 'Something went wrong.';
                    }
                    return;
                }

                // Hand off to purchaseForm to add to the list
                this.$dispatch('product-created', data);
                this.close();

            } catch (e) {
                this.errors.general = 'Network error. Please try again.';
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endpush
@endsection
