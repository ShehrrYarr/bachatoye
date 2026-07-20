@extends('layouts.admin')
@section('title', 'Edit Sale — ' . $order->order_number)

@section('content')

<script>
const _orderData = @json($orderData);

function editSale() {
    return {
        items:          _orderData.items.map(i => ({...i})),
        customer:       _orderData.customer,
        paymentMethod:  _orderData.payment_method,
        discount:       _orderData.discount_amount,
        amountPaid:     _orderData.amount_paid,
        cashAmount:     _orderData.cash_amount,
        bankAmount:     _orderData.bank_amount,
        bankAccountId:  _orderData.bank_account_id ? String(_orderData.bank_account_id) : '',
        notes:          _orderData.notes,
        exchangeValue:  _orderData.exchange_value,

        searchQuery:     '',
        searchResults:   [],
        customerSearch:  '',
        customerResults: [],
        colorPickerProduct: null,
        serialPicker:        null,   // { product_name, serials: [...] }
        serialPickerLoading: false,

        subtotal: 0,
        total:    0,
        saving:   false,
        errorMsg: '',

        paymentMethods: [
            { value: 'cash',          label: 'Cash' },
            { value: 'bank_transfer', label: 'Bank Transfer' },
            { value: 'khata',         label: 'Khata' },
            { value: 'partial',       label: 'Partial' },
            { value: 'split',         label: 'Split' },
        ],

        init() { this.recalc(); },

        recalc() {
            this.subtotal = this.items.reduce((s, i) => s + (i.qty * i.unit_price), 0);
            this.total    = Math.max(0, this.subtotal - this.discount - this.exchangeValue);
        },

        fmt(n) { return Math.round(n).toLocaleString(); },

        removeItem(idx) {
            if (this.items.length <= 1) return;
            this.items.splice(idx, 1);
            this.recalc();
        },

        async searchProducts() {
            if (this.searchQuery.trim().length < 2) { this.searchResults = []; return; }
            try {
                const r = await fetch(`/pos/product/search?q=${encodeURIComponent(this.searchQuery)}`);
                this.searchResults = await r.json();
            } catch(e) { this.searchResults = []; }
        },

        addProduct(product) {
            // IMEI search result — the specific unit is already known
            if (product.is_serialized && product.serial_number) {
                this._pushSerialItem(product, product.serial_number, product.serial_id, product.price);
                this.searchQuery   = '';
                this.searchResults = [];
                return;
            }
            // Serialized product tile — pick the unit from this order's shop
            if (product.is_serialized) {
                this.openSerialPicker(product);
                return;
            }
            if (product.colors && product.colors.length > 0) {
                this.colorPickerProduct = product;
                return;
            }
            this._pushItem(product, null, null);
            this.searchQuery   = '';
            this.searchResults = [];
        },

        async openSerialPicker(product) {
            this.serialPickerLoading = true;
            this.serialPicker = { product_id: product.id, product_name: product.name, price: product.price, serials: [] };
            try {
                const r = await fetch(`/pos/orders/${_orderData.id}/serials/${product.id}`);
                const data = await r.json();
                // Hide units already on a line of this edit
                const used = this.items.map(i => i.serial_number).filter(Boolean);
                this.serialPicker.serials = (data.serials || []).filter(s => !used.includes(s.serial_number));
            } catch(e) { this.serialPicker.serials = []; }
            this.serialPickerLoading = false;
        },

        confirmSerialAdd(s) {
            this._pushSerialItem(
                { id: this.serialPicker.product_id, name: this.serialPicker.product_name },
                s.serial_number, s.id,
                s.selling_price || this.serialPicker.price
            );
            this.serialPicker  = null;
            this.searchQuery   = '';
            this.searchResults = [];
        },

        _pushSerialItem(product, serialNumber, serialId, price) {
            if (this.items.some(i => i.serial_number === serialNumber)) {
                this.errorMsg = `Serial ${serialNumber} is already on this sale.`;
                return;
            }
            this.items.push({
                product_id:    product.id,
                product_name:  product.name,
                color_id:      null,
                color_name:    null,
                qty:           1,
                unit_price:    Number(price) || 0,
                cost_price:    Number(product.cost_price) || 0,
                serial_id:     serialId || null,
                serial_number: serialNumber,
            });
            this.recalc();
        },

        confirmColorAdd(color) {
            this._pushItem(this.colorPickerProduct, color.id, color.name);
            this.colorPickerProduct = null;
            this.searchQuery        = '';
            this.searchResults      = [];
        },

        _pushItem(product, colorId, colorName) {
            const existing = this.items.find(
                i => i.product_id === product.id && i.color_id === colorId && !i.serial_number
            );
            if (existing) { existing.qty++; this.recalc(); return; }
            this.items.push({
                product_id:   product.id,
                product_name: product.name,
                color_id:     colorId,
                color_name:   colorName,
                qty:          1,
                unit_price:   product.price,
                cost_price:   product.cost_price,
                serial_id:     null,
                serial_number: null,
            });
            this.recalc();
        },

        async searchCustomers() {
            if (this.customerSearch.trim().length < 2) { this.customerResults = []; return; }
            try {
                const r = await fetch(`/pos/customer/search?q=${encodeURIComponent(this.customerSearch)}`);
                this.customerResults = await r.json();
            } catch(e) { this.customerResults = []; }
        },

        async saveOrder() {
            this.errorMsg = '';
            if (this.items.length === 0) { this.errorMsg = 'Add at least one item.'; return; }
            if (['khata', 'partial'].includes(this.paymentMethod) && !this.customer) {
                this.errorMsg = 'A customer is required for Khata / Partial payment.';
                return;
            }

            this.saving = true;
            try {
                const res = await fetch(`/pos/orders/${_orderData.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type':  'application/json',
                        'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]').content,
                        'Accept':        'application/json',
                    },
                    body: JSON.stringify({
                        items: this.items.map(i => ({
                            product_id:    i.product_id,
                            quantity:      i.qty,
                            unit_price:    i.unit_price,
                            color_id:      i.color_id || null,
                            serial_number: i.serial_number || null,
                        })),
                        payment_method:  this.paymentMethod,
                        discount:        this.discount,
                        amount_paid:     this.amountPaid,
                        cash_amount:     this.cashAmount,
                        bank_amount:     this.bankAmount,
                        bank_account_id: this.bankAccountId || null,
                        customer_id:     this.customer?.id || null,
                        notes:           this.notes,
                    }),
                });

                const data = await res.json();
                if (data.success) {
                    window.location.href = '{{ route("pos.index") }}?edited=' + data.order_number;
                } else {
                    this.errorMsg = data.error || 'Save failed. Please try again.';
                }
            } catch(e) {
                this.errorMsg = 'Network error. Please try again.';
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>

{{-- ── Page header ──────────────────────────────────────────────────────── --}}
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('pos.index') }}" class="btn-outline btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> POS
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-900">Edit Sale —
                <span class="font-mono text-primary-700">{{ $order->order_number }}</span>
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ $order->created_at->format('d M Y, H:i') }}
                · Served by <strong>{{ $order->servedBy?->name ?? '—' }}</strong>
            </p>
        </div>
    </div>
    <a href="{{ route('pos.receipt', $order) }}" target="_blank" class="btn-outline btn-sm">
        <i class="fas fa-print mr-1"></i> Receipt
    </a>
</div>

{{-- ── Main Alpine component ────────────────────────────────────────────── --}}
<div x-data="editSale()" x-init="init()">
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ══════════════════════════════════════════
         LEFT — Items + Add product
    ══════════════════════════════════════════ --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Items table --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Sale Items</h2>
                <span class="text-sm text-gray-500"
                      x-text="`${items.length} item${items.length !== 1 ? 's' : ''}`"></span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                        <tr>
                            <th class="text-left px-5 py-3">Product</th>
                            <th class="text-center px-4 py-3 w-24">Qty</th>
                            <th class="text-right px-4 py-3 w-32">Unit Price</th>
                            <th class="text-right px-4 py-3 w-32">Total</th>
                            <th class="px-4 py-3 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <template x-for="(item, idx) in items" :key="idx">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-3">
                                    <div class="font-medium text-gray-800" x-text="item.product_name"></div>
                                    <div x-show="item.color_name"
                                         class="text-xs text-gray-400 mt-0.5 flex items-center gap-1">
                                        <i class="fas fa-circle text-[8px]"></i>
                                        <span x-text="item.color_name"></span>
                                    </div>
                                    <div x-show="item.serial_number"
                                         class="text-xs font-mono text-indigo-600 mt-0.5 flex items-center gap-1">
                                        <i class="fas fa-barcode text-[10px]"></i>
                                        <span x-text="item.serial_number"></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    {{-- Serialized: one physical unit per line --}}
                                    <template x-if="item.serial_number">
                                        <span class="inline-block w-16 text-center text-sm text-gray-500 font-semibold">1</span>
                                    </template>
                                    <template x-if="!item.serial_number">
                                        <input type="number"
                                               x-model.number="item.qty"
                                               @input="item.qty = Math.max(1, parseInt(item.qty) || 1); recalc()"
                                               min="1"
                                               class="w-16 text-center border border-gray-300 rounded-lg px-2 py-1.5 text-sm
                                                      focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-transparent">
                                    </template>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <span class="text-gray-400 text-xs">Rs.</span>
                                        <input type="number"
                                               x-model.number="item.unit_price"
                                               @input="recalc()"
                                               min="0"
                                               class="w-24 text-right border border-gray-300 rounded-lg px-2 py-1.5 text-sm
                                                      focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-transparent">
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-800"
                                    x-text="`Rs. ${fmt(item.qty * item.unit_price)}`"></td>
                                <td class="px-4 py-3 text-center">
                                    <button @click="removeItem(idx)"
                                            :disabled="items.length === 1"
                                            title="Remove item"
                                            class="text-gray-300 hover:text-red-500 disabled:opacity-30
                                                   disabled:cursor-not-allowed transition-colors">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Add product --}}
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-3">
                <i class="fas fa-plus-circle text-primary-500 mr-1.5"></i> Add Product
            </h2>
            <div class="relative">
                <input type="text"
                       x-model="searchQuery"
                       @input.debounce.300ms="searchProducts()"
                       @keydown.escape="searchResults = []; searchQuery = ''"
                       placeholder="Search by name, SKU, or scan barcode…"
                       class="form-input pr-10 text-sm">
                <i class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
            </div>

            <div x-show="searchResults.length > 0"
                 class="mt-3 border border-gray-200 rounded-xl overflow-hidden divide-y divide-gray-100 max-h-64 overflow-y-auto">
                <template x-for="p in searchResults" :key="p.id">
                    <div class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 cursor-pointer transition-colors"
                         @click="addProduct(p)">
                        <div class="flex items-center gap-3">
                            <img :src="p.image" class="w-8 h-8 rounded-lg object-cover bg-gray-100 shrink-0">
                            <div>
                                <div class="text-sm font-medium text-gray-800" x-text="p.name"></div>
                                <div class="text-xs text-gray-400 mt-0.5">
                                    <span x-text="`Rs. ${Number(p.price).toLocaleString()}`"></span>
                                    <span class="mx-1">·</span>
                                    <span :class="p.stock > 0 ? 'text-green-600' : 'text-red-500'"
                                          x-text="`${p.stock} in stock`"></span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span x-show="p.colors && p.colors.length > 0"
                                  class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full font-medium">
                                Colors
                            </span>
                            <i class="fas fa-plus text-primary-500"></i>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="searchQuery.length >= 2 && searchResults.length === 0"
                 class="mt-3 text-sm text-gray-400 text-center py-4">
                No products found.
            </div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════
         RIGHT — Payment, Customer, Summary
    ══════════════════════════════════════════ --}}
    <div class="space-y-5">

        {{-- Order summary --}}
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Order Summary</h2>
            <div class="space-y-2.5 text-sm">
                <div class="flex items-center justify-between text-gray-600">
                    <span>Subtotal</span>
                    <span x-text="`Rs. ${fmt(subtotal)}`"></span>
                </div>

                <div class="flex items-center justify-between text-gray-600">
                    <span>Discount</span>
                    <div class="flex items-center gap-1">
                        <span class="text-gray-400 text-xs">Rs.</span>
                        <input type="number"
                               x-model.number="discount"
                               @input="recalc()"
                               min="0"
                               class="w-24 text-right border border-gray-200 rounded-lg px-2 py-1 text-sm
                                      focus:outline-none focus:ring-1 focus:ring-primary-400">
                    </div>
                </div>

                <template x-if="exchangeValue > 0">
                    <div class="flex items-center justify-between text-gray-600">
                        <span>Exchange Trade-in</span>
                        <span class="text-orange-600 font-medium"
                              x-text="`-Rs. ${fmt(exchangeValue)}`"></span>
                    </div>
                </template>

                <div class="border-t border-gray-100 pt-2.5 flex items-center justify-between font-bold text-gray-900 text-base">
                    <span>Total</span>
                    <span x-text="`Rs. ${fmt(total)}`"></span>
                </div>
            </div>
        </div>

        {{-- Customer --}}
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Customer</h2>

            {{-- No customer selected --}}
            <div x-show="!customer" class="space-y-2">
                <div class="relative">
                    <input type="text"
                           x-model="customerSearch"
                           @input.debounce.300ms="searchCustomers()"
                           @keydown.escape="customerResults = []; customerSearch = ''"
                           placeholder="Search by name or phone…"
                           class="form-input text-sm pr-8">
                    <i class="fas fa-user-search absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
                </div>

                <div x-show="customerResults.length > 0"
                     class="border border-gray-200 rounded-xl overflow-hidden divide-y divide-gray-100 max-h-40 overflow-y-auto">
                    <template x-for="c in customerResults" :key="c.id">
                        <div class="flex items-center justify-between px-3 py-2.5 hover:bg-gray-50 cursor-pointer transition-colors"
                             @click="customer = c; customerResults = []; customerSearch = ''">
                            <div>
                                <div class="text-sm font-medium text-gray-800" x-text="c.name"></div>
                                <div class="text-xs text-gray-400" x-text="c.phone"></div>
                            </div>
                            <span x-show="c.credit_balance < 0"
                                  class="text-xs text-red-500 font-semibold"
                                  x-text="`Owes Rs. ${fmt(Math.abs(c.credit_balance))}`"></span>
                        </div>
                    </template>
                </div>
                <p class="text-xs text-gray-400">Leave empty for walk-in customer</p>
            </div>

            {{-- Customer selected --}}
            <div x-show="customer"
                 class="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-3">
                <div>
                    <div class="text-sm font-semibold text-gray-800" x-text="customer?.name"></div>
                    <div class="text-xs text-gray-500 mt-0.5" x-text="customer?.phone"></div>
                </div>
                <button @click="customer = null; customerSearch = ''"
                        class="text-gray-400 hover:text-red-500 transition-colors ml-3">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        {{-- Payment --}}
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Payment Method</h2>

            <div class="grid grid-cols-2 gap-2 mb-4">
                <template x-for="m in paymentMethods" :key="m.value">
                    <button @click="paymentMethod = m.value"
                            :class="paymentMethod === m.value
                                ? 'border-primary-400 bg-primary-50 text-primary-700 font-semibold'
                                : 'border-gray-200 text-gray-600 hover:border-gray-300'"
                            class="text-xs border rounded-lg px-3 py-2 transition-all text-center">
                        <span x-text="m.label"></span>
                    </button>
                </template>
            </div>

            {{-- Partial --}}
            <div x-show="paymentMethod === 'partial'" class="space-y-2 mb-3">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Amount Paid (Rs.)</label>
                    <input type="number" x-model.number="amountPaid" min="0"
                           class="form-input text-sm">
                </div>
                <div class="text-xs text-gray-500">
                    On Khata:
                    <span class="font-semibold text-red-500"
                          x-text="`Rs. ${fmt(Math.max(0, total - amountPaid))}`"></span>
                </div>
            </div>

            {{-- Split --}}
            <div x-show="paymentMethod === 'split'" class="space-y-2 mb-3">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Cash (Rs.)</label>
                        <input type="number" x-model.number="cashAmount" min="0"
                               class="form-input text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Bank (Rs.)</label>
                        <input type="number" x-model.number="bankAmount" min="0"
                               class="form-input text-sm">
                    </div>
                </div>
            </div>

            {{-- Bank account selector --}}
            @if($bankAccounts->count())
            <div x-show="['bank_transfer', 'split'].includes(paymentMethod)">
                <label class="text-xs text-gray-500 mb-1 block">Bank Account</label>
                <select x-model="bankAccountId" class="form-select text-sm">
                    <option value="">— Select account —</option>
                    @foreach($bankAccounts as $bank)
                    <option value="{{ $bank->id }}">{{ $bank->label }} — {{ $bank->bank_name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>

        {{-- Notes --}}
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Notes</h2>
            <textarea x-model="notes" rows="2" class="form-input text-sm resize-none"
                      placeholder="Order notes…"></textarea>
        </div>

        {{-- Error --}}
        <div x-show="errorMsg"
             class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl"
             x-text="errorMsg"></div>

        {{-- Save --}}
        <button @click="saveOrder()"
                :disabled="saving || items.length === 0"
                class="w-full btn-primary py-3 justify-center text-base disabled:opacity-50 disabled:cursor-not-allowed">
            <span x-show="!saving">
                <i class="fas fa-save mr-2"></i> Save Changes
            </span>
            <span x-show="saving">
                <i class="fas fa-spinner fa-spin mr-2"></i> Saving…
            </span>
        </button>

    </div>
</div>

{{-- Serial picker modal (units at this order's shop) --}}
<template x-teleport="body">
    <div x-show="serialPicker" x-transition
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center"
         @keydown.escape.window="serialPicker = null">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-96 max-h-[80vh] flex flex-col"
             @click.outside="serialPicker = null">
            <h3 class="font-bold text-gray-900 text-base mb-1" x-text="serialPicker?.product_name"></h3>
            <p class="text-xs text-gray-500 mb-4">Select the unit (IMEI / Serial):</p>

            <div x-show="serialPickerLoading" class="text-center py-8 text-gray-300">
                <i class="fas fa-spinner fa-spin text-2xl"></i>
            </div>
            <div x-show="!serialPickerLoading && (serialPicker?.serials || []).length === 0"
                 class="text-center py-8 text-sm text-gray-400">
                No units in stock for this product.
            </div>

            <div class="space-y-2 overflow-y-auto flex-1">
                <template x-for="s in serialPicker?.serials || []" :key="s.id">
                    <button @click="confirmSerialAdd(s)"
                            class="w-full flex items-center justify-between px-4 py-3 rounded-xl border border-gray-300
                                   hover:border-indigo-400 hover:bg-indigo-50 transition-all text-left">
                        <div class="min-w-0">
                            <div class="text-sm font-mono font-medium text-gray-800 truncate" x-text="s.serial_number"></div>
                            <div class="text-xs text-gray-400 mt-0.5 truncate"
                                 x-text="Object.entries(s.attributes || {}).map(([k,v]) => `${k}: ${v}`).join(' · ')"></div>
                        </div>
                        <span class="text-sm font-bold text-indigo-700 shrink-0 ml-3"
                              x-text="`Rs. ${Number(s.selling_price).toLocaleString()}`"></span>
                    </button>
                </template>
            </div>

            <button @click="serialPicker = null"
                    class="btn-outline w-full mt-4 justify-center text-sm">Cancel</button>
        </div>
    </div>
</template>

{{-- Color picker modal --}}
<template x-teleport="body">
    <div x-show="colorPickerProduct" x-transition
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center"
         @keydown.escape.window="colorPickerProduct = null">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-80"
             @click.outside="colorPickerProduct = null">
            <h3 class="font-bold text-gray-900 text-base mb-1"
                x-text="colorPickerProduct?.name"></h3>
            <p class="text-xs text-gray-500 mb-4">Select a color variant:</p>
            <div class="space-y-2">
                <template x-for="color in colorPickerProduct?.colors || []" :key="color.id">
                    <button @click="confirmColorAdd(color)"
                            :disabled="color.stock_quantity <= 0"
                            class="w-full flex items-center justify-between px-4 py-3 rounded-xl border transition-all"
                            :class="color.stock_quantity > 0
                                ? 'border-gray-300 hover:border-primary-400 hover:bg-primary-50 cursor-pointer'
                                : 'border-gray-200 bg-gray-50 opacity-50 cursor-not-allowed'">
                        <div class="flex items-center gap-3">
                            <div class="w-5 h-5 rounded-full border border-gray-300 shrink-0"
                                 :style="color.hex_code ? `background:${color.hex_code}` : 'background:#e5e7eb'">
                            </div>
                            <span class="text-sm font-medium text-gray-800" x-text="color.name"></span>
                        </div>
                        <span class="text-xs font-semibold"
                              :class="color.stock_quantity > 0 ? 'text-green-600' : 'text-red-400'"
                              x-text="color.stock_quantity > 0 ? color.stock_quantity + ' left' : 'Out'">
                        </span>
                    </button>
                </template>
            </div>
            <button @click="colorPickerProduct = null"
                    class="btn-outline w-full mt-4 justify-center text-sm">Cancel</button>
        </div>
    </div>
</template>

</div>{{-- end x-data --}}

@endsection
