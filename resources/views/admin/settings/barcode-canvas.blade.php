@extends('layouts.admin')
@section('title', 'Barcode Canvas')

@push('styles')
<style>
    .canvas-stage {
        background:
            linear-gradient(45deg, #e5e7eb 25%, transparent 25%, transparent 75%, #e5e7eb 75%),
            linear-gradient(45deg, #e5e7eb 25%, transparent 25%, transparent 75%, #e5e7eb 75%);
        background-size: 20px 20px;
        background-position: 0 0, 10px 10px;
        background-color: #f9fafb;
    }
    .canvas-el { transition: outline-color .1s; outline: 2px dashed transparent; outline-offset: 3px; }
    .canvas-el:hover { outline-color: #c7d2fe; }
    .canvas-el.selected { outline-color: #6366f1; }
    .align-btn { padding: 6px 0; flex: 1; border: 1px solid #d1d5db; background: #fff; color: #6b7280; cursor: pointer; font-size: 12px; }
    .align-btn.active { background: #eef2ff; color: #4338ca; border-color: #a5b4fc; }
    .align-btn:first-child { border-radius: 8px 0 0 8px; }
    .align-btn:last-child { border-radius: 0 8px 8px 0; }
</style>
@endpush

@section('content')
<div x-data="barcodeCanvas()" x-init="init()">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.settings.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
            <div>
                <h1 class="text-xl font-bold text-gray-900">Barcode Canvas</h1>
                <p class="text-sm text-gray-500 mt-0.5">Drag elements to position them. Click an element to edit its style.</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-sm text-green-600 font-medium transition-opacity duration-300"
                  :class="savedFlash ? 'opacity-100' : 'opacity-0'">
                <i class="fas fa-check mr-1"></i>Saved
            </span>
            <button @click="resetDefault()" class="btn-outline btn-sm">
                <i class="fas fa-undo mr-1.5"></i> Reset to Default
            </button>
            <button @click="save()" :disabled="saving" class="btn-primary">
                <i class="fas fa-save mr-2"></i>
                <span x-text="saving ? 'Saving...' : 'Save Design'"></span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- ── Left: controls ── --}}
        <div class="space-y-5">

            {{-- Label size --}}
            <div class="card p-4">
                <h2 class="font-semibold text-gray-800 text-sm mb-3">
                    <i class="fas fa-ruler-combined text-indigo-500 mr-1.5"></i>Label Size
                </h2>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label text-xs">Width (inches)</label>
                        <input type="number" x-model.number="tpl.w" min="0.5" max="12" step="0.1" class="form-input text-sm">
                    </div>
                    <div>
                        <label class="form-label text-xs">Height (inches)</label>
                        <input type="number" x-model.number="tpl.h" min="0.3" max="12" step="0.1" class="form-input text-sm">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label text-xs">Preview Zoom</label>
                    <select x-model.number="zoom" class="form-select text-sm">
                        <option value="2">2×</option>
                        <option value="3">3×</option>
                        <option value="4">4×</option>
                    </select>
                </div>
            </div>

            {{-- Elements list --}}
            <div class="card p-4">
                <h2 class="font-semibold text-gray-800 text-sm mb-3">
                    <i class="fas fa-layer-group text-indigo-500 mr-1.5"></i>Elements
                </h2>
                <div class="space-y-1">
                    <template x-for="key in Object.keys(labels)" :key="key">
                        <div class="flex items-center gap-2.5 rounded-lg px-2 py-1.5 cursor-pointer transition-colors"
                             :class="selected === key ? 'bg-indigo-50 ring-1 ring-indigo-200' : 'hover:bg-gray-50'"
                             @click="selected = key">
                            <input type="checkbox" x-model="tpl.elements[key].visible" @click.stop
                                   class="w-4 h-4 rounded border-gray-300 text-indigo-600">
                            <span class="text-sm text-gray-700 flex-1" x-text="labels[key]"></span>
                            <i class="fas fa-pen text-xs" :class="selected === key ? 'text-indigo-500' : 'text-gray-300'"></i>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Properties panel --}}
            <div class="card p-4" x-show="selected" x-cloak>
                <h2 class="font-semibold text-gray-800 text-sm mb-3">
                    <i class="fas fa-sliders-h text-indigo-500 mr-1.5"></i>
                    <span x-text="selected ? labels[selected] : ''"></span> Properties
                </h2>

                <template x-if="selected">
                    <div class="space-y-3">

                        {{-- Position --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="form-label text-xs">X (%)</label>
                                <input type="number" x-model.number="tpl.elements[selected].x" min="0" max="100" step="0.5" class="form-input text-sm">
                            </div>
                            <div>
                                <label class="form-label text-xs">Y (%)</label>
                                <input type="number" x-model.number="tpl.elements[selected].y" min="0" max="100" step="0.5" class="form-input text-sm">
                            </div>
                        </div>

                        {{-- Alignment (anchor) --}}
                        <div>
                            <label class="form-label text-xs">Alignment</label>
                            <div class="flex">
                                <button type="button" class="align-btn" :class="tpl.elements[selected].align === 'left' && 'active'"
                                        @click="tpl.elements[selected].align = 'left'"><i class="fas fa-align-left"></i></button>
                                <button type="button" class="align-btn" :class="tpl.elements[selected].align === 'center' && 'active'"
                                        @click="tpl.elements[selected].align = 'center'"><i class="fas fa-align-center"></i></button>
                                <button type="button" class="align-btn" :class="tpl.elements[selected].align === 'right' && 'active'"
                                        @click="tpl.elements[selected].align = 'right'"><i class="fas fa-align-right"></i></button>
                            </div>
                            <p class="form-hint text-xs mt-1">Anchor point relative to the X position.</p>
                        </div>

                        <button type="button" class="btn-outline btn-sm w-full text-xs"
                                @click="tpl.elements[selected].x = 50; tpl.elements[selected].align = 'center'">
                            <i class="fas fa-arrows-alt-h mr-1"></i> Center Horizontally
                        </button>

                        {{-- Text properties --}}
                        <template x-if="selected !== 'barcode'">
                            <div class="space-y-3">
                                <div>
                                    <label class="form-label text-xs">Font Size (px)</label>
                                    <input type="number" x-model.number="tpl.elements[selected].size" min="5" max="60" step="1" class="form-input text-sm">
                                </div>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="tpl.elements[selected].bold" class="w-4 h-4 rounded border-gray-300 text-indigo-600">
                                    <span class="text-sm text-gray-700 font-bold">Bold</span>
                                </label>
                            </div>
                        </template>

                        {{-- Barcode-specific properties --}}
                        <template x-if="selected === 'barcode'">
                            <div class="space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="form-label text-xs">Bar Height (px)</label>
                                        <input type="number" x-model.number="tpl.elements.barcode.barHeight" min="10" max="200" step="2" class="form-input text-sm">
                                    </div>
                                    <div>
                                        <label class="form-label text-xs">Bar Thickness</label>
                                        <input type="number" x-model.number="tpl.elements.barcode.barWidth" min="0.5" max="4" step="0.25" class="form-input text-sm">
                                    </div>
                                </div>
                                <p class="form-hint text-xs">The digits are a separate element — see "Barcode Digits" in the list.</p>
                            </div>
                        </template>

                    </div>
                </template>
            </div>
        </div>

        {{-- ── Right: canvas ── --}}
        <div class="lg:col-span-2">
            <div class="card p-6">
                <div class="canvas-stage rounded-xl p-8 flex items-center justify-center overflow-auto" style="min-height: 420px;">

                    {{-- The label --}}
                    <div x-ref="canvas"
                         class="bg-white shadow-lg relative shrink-0"
                         :style="{ width: canvasW + 'px', height: canvasH + 'px' }"
                         @click.self="selected = null">

                        {{-- Text elements --}}
                        <template x-for="key in ['shop_name', 'product_name', 'barcode_text', 'price', 'sku']" :key="key">
                            <div class="canvas-el"
                                 :class="selected === key && 'selected'"
                                 x-show="tpl.elements[key].visible"
                                 :style="elStyle(key)"
                                 @pointerdown.prevent="startDrag($event, key)"
                                 x-text="sample[key]"></div>
                        </template>

                        {{-- Barcode element --}}
                        <div class="canvas-el"
                             :class="selected === 'barcode' && 'selected'"
                             x-show="tpl.elements.barcode.visible"
                             :style="elStyle('barcode')"
                             @pointerdown.prevent="startDrag($event, 'barcode')"
                             x-effect="renderBarcode(tpl.elements.barcode.barHeight, tpl.elements.barcode.barWidth, zoom)">
                            <svg x-ref="bcsvg"></svg>
                        </div>

                    </div>
                </div>

                <p class="text-center text-sm text-gray-400 mt-3">
                    Label: <span class="font-semibold text-gray-600" x-text="tpl.w + '″ × ' + tpl.h + '″'"></span>
                    · shown at <span x-text="zoom"></span>× zoom
                </p>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
function barcodeCanvas() {
    return {
        tpl: @json($template),
        zoom: 3,
        selected: null,
        saving: false,
        savedFlash: false,

        labels: {
            shop_name:    'Shop Name',
            product_name: 'Product Name',
            barcode:      'Barcode',
            barcode_text: 'Barcode Digits',
            price:        'Price',
            sku:          'SKU',
        },

        sample: {
            shop_name:    @json(\App\Models\Setting::get('shop_name', 'MobileHub')),
            product_name: 'Sample Product Name',
            barcode:      '123456789012',
            barcode_text: '123456789012',
            price:        'Rs. 1,999',
            sku:          'SKU-0001',
        },

        init() {},

        get canvasW() { return Math.round(this.tpl.w * 96 * this.zoom); },
        get canvasH() { return Math.round(this.tpl.h * 96 * this.zoom); },

        elStyle(key) {
            const e = this.tpl.elements[key];
            const transform = e.align === 'center' ? 'translateX(-50%)'
                            : e.align === 'right'  ? 'translateX(-100%)' : 'none';
            const style = {
                position:   'absolute',
                left:       e.x + '%',
                top:        e.y + '%',
                transform:  transform,
                cursor:     'move',
                whiteSpace: 'nowrap',
                userSelect: 'none',
                touchAction: 'none',
            };
            if (key !== 'barcode') {
                style.fontSize   = (e.size * this.zoom) + 'px';
                style.fontWeight = e.bold ? '700' : '400';
                style.color      = key === 'price' ? '#be123c' : '#111';
                if (key === 'barcode_text') style.fontFamily = 'monospace';
            }
            return style;
        },

        startDrag(evt, key) {
            this.selected = key;
            const rect = this.$refs.canvas.getBoundingClientRect();
            const e = this.tpl.elements[key];
            const drag = { startX: evt.clientX, startY: evt.clientY, origX: e.x, origY: e.y };

            const move = (ev) => {
                e.x = Math.round(Math.min(100, Math.max(0, drag.origX + (ev.clientX - drag.startX) / rect.width  * 100)) * 2) / 2;
                e.y = Math.round(Math.min(100, Math.max(0, drag.origY + (ev.clientY - drag.startY) / rect.height * 100)) * 2) / 2;
            };
            const up = () => {
                window.removeEventListener('pointermove', move);
                window.removeEventListener('pointerup', up);
            };
            window.addEventListener('pointermove', move);
            window.addEventListener('pointerup', up);
        },

        renderBarcode() {
            const b = this.tpl.elements.barcode;
            this.$nextTick(() => {
                if (!this.$refs.bcsvg) return;
                try {
                    JsBarcode(this.$refs.bcsvg, this.sample.barcode, {
                        format:       'CODE128',
                        width:        b.barWidth * this.zoom,
                        height:       b.barHeight * this.zoom,
                        displayValue: false,
                        margin:       0,
                        background:   'transparent',
                        lineColor:    '#000000',
                    });
                } catch (err) { /* invalid interim values while typing */ }
            });
        },

        async save() {
            this.saving = true;
            try {
                const res = await fetch(@json(route('admin.settings.barcode-canvas.save')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token()),
                    },
                    body: JSON.stringify(this.tpl),
                });
                if (!res.ok) throw new Error('Save failed');
                this.savedFlash = true;
                setTimeout(() => this.savedFlash = false, 2000);
            } catch (err) {
                alert('Could not save the design. Please try again.');
            } finally {
                this.saving = false;
            }
        },

        resetDefault() {
            if (!confirm('Reset the label design to the default layout?')) return;
            this.tpl = JSON.parse(JSON.stringify(@json(\App\Http\Controllers\Admin\SettingController::DEFAULT_BARCODE_TEMPLATE)));
            this.selected = null;
        },
    };
}
</script>
@endpush
