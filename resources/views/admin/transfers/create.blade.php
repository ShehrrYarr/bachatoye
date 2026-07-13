@extends('layouts.admin')
@section('title', 'New Stock Transfer')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.transfers.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">New Stock Transfer</h1>
</div>

<form method="POST" action="{{ route('admin.transfers.store') }}" x-data="transferForm()" @submit="return items.length > 0">
    @csrf
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: direction + product picker + items --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Direction --}}
            <div class="card p-5">
                <h2 class="font-semibold text-gray-800 mb-3">Direction</h2>
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <label class="flex items-center gap-3 p-4 border rounded-xl cursor-pointer transition-all"
                           :class="direction === 'to_shop' ? 'border-primary-400 bg-primary-50' : 'border-gray-200 hover:border-gray-300'">
                        <input type="radio" name="direction" value="to_shop" x-model="direction" @change="resetItems()" class="w-4 h-4 text-primary-600">
                        <div>
                            <div class="text-sm font-semibold text-gray-800">Send to Sub Shop</div>
                            <div class="text-xs text-gray-400">Main Shop → Sub Shop</div>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 p-4 border rounded-xl cursor-pointer transition-all"
                           :class="direction === 'from_shop' ? 'border-primary-400 bg-primary-50' : 'border-gray-200 hover:border-gray-300'">
                        <input type="radio" name="direction" value="from_shop" x-model="direction" @change="resetItems()" class="w-4 h-4 text-primary-600">
                        <div>
                            <div class="text-sm font-semibold text-gray-800">Pull back to Main</div>
                            <div class="text-xs text-gray-400">Sub Shop → Main Shop</div>
                        </div>
                    </label>
                </div>
                <div>
                    <label class="form-label">Sub Shop *</label>
                    <select name="shop_id" x-model="shopId" @change="resetItems()" class="form-select" required>
                        <option value="">Select shop...</option>
                        @foreach($shops as $shop)
                        <option value="{{ $shop->id }}">{{ $shop->name }} ({{ $shop->code }})</option>
                        @endforeach
                    </select>
                    @error('shop_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Product search --}}
            <div class="card p-5" x-show="shopId" x-cloak>
                <h2 class="font-semibold text-gray-800 mb-3">Add Products
                    <span class="text-xs font-normal text-gray-400" x-text="direction === 'to_shop' ? '(availability at Main Shop)' : '(availability at the sub shop)'"></span>
                </h2>
                <div class="relative">
                    <input type="text" x-model="search" @input.debounce.300ms="doSearch()" placeholder="Search product by name, SKU, or barcode..."
                           class="form-input" autocomplete="off">
                    <div x-show="results.length > 0" @click.outside="results = []"
                         class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-72 overflow-y-auto">
                        <template x-for="p in results" :key="p.id">
                            <button type="button" @click="addProduct(p)"
                                    class="w-full flex items-center justify-between gap-3 px-4 py-3 hover:bg-gray-50 text-left border-b border-gray-50 last:border-0">
                                <div>
                                    <div class="text-sm font-medium text-gray-800" x-text="p.name"></div>
                                    <div class="text-xs text-gray-400" x-text="p.sku"></div>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="badge bg-green-100 text-green-700" x-text="p.stock + ' available'"></span>
                                    <span class="badge bg-indigo-50 text-indigo-600 ml-1" x-show="p.is_serialized">Serialized</span>
                                </div>
                            </button>
                        </template>
                    </div>
                    <div x-show="searching" class="absolute right-3 top-3 text-gray-400"><i class="fas fa-spinner fa-spin"></i></div>
                </div>
            </div>

            {{-- Items --}}
            <template x-for="(item, idx) in items" :key="item._uid">
                <div class="card p-5">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div>
                            <div class="font-semibold text-gray-800" x-text="item.name"></div>
                            <div class="text-xs text-gray-400">
                                <span x-text="item.available + ' available at source'"></span>
                                <span x-show="item.is_serialized" class="text-indigo-500 ml-1">· Serialized</span>
                            </div>
                        </div>
                        <button type="button" @click="items.splice(idx, 1)" class="text-red-400 hover:text-red-600"><i class="fas fa-times"></i></button>
                    </div>

                    <input type="hidden" :name="'items['+idx+'][product_id]'" :value="item.id">

                    {{-- Serialized: pick serials --}}
                    <template x-if="item.is_serialized">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-600">Serial numbers to transfer</span>
                                <span class="text-xs text-gray-400" x-text="item.selectedSerials.length + ' selected'"></span>
                            </div>
                            <input type="hidden" :name="'items['+idx+'][quantity]'" :value="Math.max(item.selectedSerials.length, 1)">
                            <template x-for="sid in item.selectedSerials" :key="sid">
                                <input type="hidden" :name="'items['+idx+'][serial_ids][]'" :value="sid">
                            </template>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-56 overflow-y-auto border border-gray-100 rounded-xl p-3">
                                <template x-for="s in item.serials" :key="s.id">
                                    <label class="flex items-center gap-2 text-sm cursor-pointer p-2 rounded-lg hover:bg-gray-50"
                                           :class="item.selectedSerials.includes(s.id) ? 'bg-primary-50' : ''">
                                        <input type="checkbox" :value="s.id" @change="toggleSerial(item, s.id)"
                                               :checked="item.selectedSerials.includes(s.id)" class="w-4 h-4 text-primary-600 rounded">
                                        <span class="font-mono text-xs" x-text="s.serial_number"></span>
                                        <span class="text-xs text-gray-400 truncate" x-text="Object.values(s.attributes || {}).join(' / ')"></span>
                                    </label>
                                </template>
                                <div x-show="item.serials.length === 0" class="col-span-2 text-center text-gray-400 text-sm py-4">
                                    No in-stock serials at the source shop.
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Non-serialized: qty + color --}}
                    <template x-if="!item.is_serialized">
                        <div class="grid grid-cols-2 gap-4">
                            <template x-if="item.colors.length > 0">
                                <div>
                                    <label class="form-label">Color</label>
                                    <select :name="'items['+idx+'][color_id]'" x-model="item.color_id" @change="syncColorStock(item)" class="form-select">
                                        <option value="">No color</option>
                                        <template x-for="c in item.colors" :key="c.id">
                                            <option :value="c.id" x-text="c.name + ' (' + c.stock + ' available)'"></option>
                                        </template>
                                    </select>
                                </div>
                            </template>
                            <div>
                                <label class="form-label">Quantity *</label>
                                <input type="number" :name="'items['+idx+'][quantity]'" x-model.number="item.quantity"
                                       min="1" :max="item.available" class="form-input" required>
                                <p class="text-xs text-red-500 mt-1" x-show="item.quantity > item.available">More than available at source!</p>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            @error('items') <div class="alert-error"><i class="fas fa-exclamation-circle mt-0.5"></i><span>{{ $message }}</span></div> @enderror
            @error('stock') <div class="alert-error"><i class="fas fa-exclamation-circle mt-0.5"></i><span>{{ $message }}</span></div> @enderror
        </div>

        {{-- Right: summary --}}
        <div class="space-y-5">
            <div class="card p-5">
                <h2 class="font-semibold text-gray-800 mb-3">Summary</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Direction</span>
                        <span class="font-semibold" x-text="direction === 'to_shop' ? 'Main → Sub Shop' : 'Sub Shop → Main'"></span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Products</span>
                        <span class="font-semibold" x-text="items.length"></span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Total Units</span>
                        <span class="font-bold" x-text="totalQty()"></span></div>
                </div>
            </div>

            <div class="card p-5">
                <label class="form-label">Note</label>
                <textarea name="note" rows="3" class="form-input" placeholder="Optional note...">{{ old('note') }}</textarea>
            </div>

            <div class="card p-5 space-y-3">
                <button type="submit" class="btn-primary w-full justify-center btn-lg" :disabled="items.length === 0 || !shopId"
                        :class="(items.length === 0 || !shopId) ? 'opacity-50 cursor-not-allowed' : ''">
                    <i class="fas fa-exchange-alt mr-2"></i> Transfer Now
                </button>
                <p class="text-xs text-gray-400 text-center">Stock moves instantly when you press Transfer.</p>
            </div>
        </div>
    </div>

    <script>
        function transferForm() {
            return {
                direction: 'to_shop',
                shopId: '',
                search: '',
                searching: false,
                results: [],
                items: [],
                _uid: 0,

                fromShopParam() {
                    // Source location: main when sending to a shop, the shop when pulling back
                    return this.direction === 'to_shop' ? '' : this.shopId;
                },
                resetItems() {
                    this.items = [];
                    this.results = [];
                    this.search = '';
                },
                async doSearch() {
                    if (this.search.trim().length < 2) { this.results = []; return; }
                    this.searching = true;
                    try {
                        const res = await fetch(`{{ route('admin.transfers.search') }}?q=${encodeURIComponent(this.search)}&from_shop_id=${this.fromShopParam()}`);
                        this.results = await res.json();
                    } catch (e) { this.results = []; }
                    this.searching = false;
                },
                async addProduct(p) {
                    this.results = [];
                    this.search = '';
                    if (this.items.some(i => i.id === p.id)) return;

                    const item = {
                        _uid: ++this._uid,
                        id: p.id,
                        name: p.name,
                        is_serialized: p.is_serialized,
                        colors: p.colors || [],
                        color_id: '',
                        quantity: 1,
                        available: p.stock,
                        productStock: p.stock,
                        serials: [],
                        selectedSerials: [],
                    };
                    this.items.push(item);

                    if (p.is_serialized) {
                        try {
                            const res = await fetch(`{{ route('admin.transfers.serials') }}?product_id=${p.id}&from_shop_id=${this.fromShopParam()}`);
                            item.serials = await res.json();
                        } catch (e) { item.serials = []; }
                    }
                },
                toggleSerial(item, sid) {
                    const i = item.selectedSerials.indexOf(sid);
                    if (i === -1) item.selectedSerials.push(sid);
                    else item.selectedSerials.splice(i, 1);
                },
                syncColorStock(item) {
                    if (!item.color_id) { item.available = item.productStock; return; }
                    const c = item.colors.find(c => c.id == item.color_id);
                    item.available = c ? c.stock : item.productStock;
                },
                totalQty() {
                    return this.items.reduce((sum, i) =>
                        sum + (i.is_serialized ? i.selectedSerials.length : (parseInt(i.quantity) || 0)), 0);
                },
            };
        }
    </script>
</form>
@endsection
