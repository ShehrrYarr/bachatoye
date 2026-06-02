@extends('layouts.pos')

@push('styles')
<style>body { overflow: auto !important; height: auto !important; }</style>
@endpush

@section('content')
<div class="min-h-screen bg-gray-100 p-4 md:p-6" x-data="exchangeApp()">

    {{-- Header --}}
    <div class="max-w-6xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('pos.index') }}" class="btn-outline btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back to POS
                </a>
                <h1 class="text-xl font-bold text-gray-900">
                    <i class="fas fa-sync-alt mr-2 text-indigo-600"></i>Process Exchange
                </h1>
            </div>
        </div>

        {{-- Step 1: Order Lookup --}}
        <div class="card p-5 mb-6">
            <h2 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs flex items-center justify-center font-bold">1</span>
                Find Original Order
            </h2>
            <div class="flex gap-3">
                <input type="text" x-model="orderSearch"
                       @keydown.enter="findOrder()"
                       placeholder="Enter order number (e.g. ORD-0001)"
                       class="form-input flex-1">
                <button @click="findOrder()" :disabled="searching"
                        class="btn-primary px-5 disabled:opacity-50">
                    <span x-show="!searching"><i class="fas fa-search mr-1"></i> Find</span>
                    <span x-show="searching"><i class="fas fa-spinner fa-spin mr-1"></i> Searching...</span>
                </button>
            </div>
            <div x-show="error" class="alert-error mt-3" x-text="error"></div>
        </div>

        {{-- Steps 2 & 3: Order found --}}
        <div x-show="order" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Left: Item selection + New items --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Step 2: Select item to exchange --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs flex items-center justify-center font-bold">2</span>
                            Select Item Being Returned
                        </h2>
                        <div class="text-sm text-gray-500">
                            <span x-text="order?.order_number"></span>
                            &bull; <span x-text="order?.customer_name"></span>
                        </div>
                    </div>
                    <div class="card-body space-y-3">
                        <template x-for="item in orderItems" :key="item.id">
                            <div class="flex items-center gap-3 p-3 border-2 rounded-xl cursor-pointer transition-all"
                                 :style="selectedItem?.id === item.id
                                     ? 'border-color:#818cf8; background-color:#eef2ff;'
                                     : 'border-color:#e5e7eb;'"
                                 @click="selectItem(item)">
                                <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 transition-colors"
                                     :class="selectedItem?.id === item.id ? 'border-indigo-600 bg-indigo-600' : 'border-gray-300'">
                                    <i x-show="selectedItem?.id === item.id" class="fas fa-check text-white" style="font-size:10px;"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-semibold text-gray-800" x-text="item.product_name"></div>
                                    <div class="text-xs text-gray-500"
                                         x-text="`Rs. ${Number(item.unit_price).toLocaleString()} × ${item.quantity}`"></div>
                                </div>
                                <div class="text-sm font-bold text-indigo-700 shrink-0"
                                     x-text="`Rs. ${Number(item.unit_price * item.quantity).toLocaleString()}`"></div>
                            </div>
                        </template>

                        {{-- Exchange value (shows when item selected) --}}
                        <div x-show="selectedItem" class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="flex-1">
                                    <label class="form-label text-sm">
                                        Return Quantity
                                        <span class="text-gray-400 font-normal" x-text="`(max: ${selectedItem?.quantity})`"></span>
                                    </label>
                                    <input type="number" x-model.number="returnQty"
                                           :max="selectedItem?.quantity" min="1"
                                           @change="exchangeValue = parseFloat(selectedItem.unit_price) * returnQty; recalculate()"
                                           class="form-input text-sm">
                                </div>
                                <div class="flex-1">
                                    <label class="form-label text-sm">Exchange / Trade-in Value (Rs.)</label>
                                    <input type="number" x-model.number="exchangeValue"
                                           min="0" @input="recalculate()"
                                           class="form-input text-sm font-bold text-indigo-700">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 3: Add new items --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs flex items-center justify-center font-bold">3</span>
                            Add New Item(s) for Customer
                        </h2>
                    </div>
                    <div class="card-body">
                        {{-- Product Search --}}
                        <div class="relative mb-4">
                            <input type="text"
                                   x-model="productSearch"
                                   @input.debounce.300ms="searchProducts()"
                                   @keydown.escape="productResults = []"
                                   placeholder="Search product name or scan barcode..."
                                   class="form-input pl-9 text-sm">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>

                            {{-- Search Results Dropdown --}}
                            <div x-show="productResults.length > 0"
                                 @click.outside="productResults = []"
                                 class="absolute top-full left-0 right-0 z-20 bg-white border border-gray-200 rounded-xl shadow-lg mt-1 max-h-60 overflow-y-auto">
                                <template x-for="p in productResults" :key="p.id">
                                    <div @click="addToNewCart(p)"
                                         class="flex items-center gap-3 px-3 py-2.5 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0">
                                        <img :src="p.image" class="w-10 h-10 rounded-lg object-cover bg-gray-100">
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-800 truncate" x-text="p.name"></div>
                                            <div class="text-xs text-gray-500" x-text="`Stock: ${p.stock}`"></div>
                                        </div>
                                        <div class="text-sm font-bold text-indigo-700 shrink-0"
                                             x-text="`Rs. ${Number(p.price).toLocaleString()}`"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Color picker modal --}}
                        <div x-show="pendingProduct" class="mb-4 p-3 bg-indigo-50 border border-indigo-200 rounded-xl">
                            <div class="text-sm font-semibold text-indigo-800 mb-2">
                                Select color for: <span x-text="pendingProduct?.name"></span>
                            </div>
                            <div class="flex flex-wrap gap-2 mb-3">
                                <button @click="confirmAddWithColor(null)"
                                        class="px-3 py-1.5 text-xs rounded-lg border-2 transition-all"
                                        :class="pendingColor === null ? 'border-indigo-500 bg-indigo-100 font-bold' : 'border-gray-300 hover:border-gray-400'">
                                    No specific color
                                </button>
                                <template x-for="c in pendingProduct?.colors" :key="c.id">
                                    <button @click="pendingColor = c"
                                            class="px-3 py-1.5 text-xs rounded-lg border-2 transition-all flex items-center gap-1.5"
                                            :class="pendingColor?.id === c.id ? 'border-indigo-500 bg-indigo-100 font-bold' : 'border-gray-300 hover:border-gray-400'">
                                        <span class="w-3 h-3 rounded-full border border-gray-300"
                                              :style="{ backgroundColor: c.hex_code }"></span>
                                        <span x-text="c.name"></span>
                                        <span class="text-gray-400" x-text="`(${c.stock_quantity})`"></span>
                                    </button>
                                </template>
                            </div>
                            <div class="flex gap-2">
                                <button @click="confirmAddWithColor(pendingColor)"
                                        class="btn-primary btn-sm">
                                    <i class="fas fa-plus mr-1"></i> Add to Cart
                                </button>
                                <button @click="pendingProduct = null; pendingColor = null"
                                        class="btn-outline btn-sm">Cancel</button>
                            </div>
                        </div>

                        {{-- New Items Cart --}}
                        <div x-show="newCart.length > 0" class="space-y-2">
                            <template x-for="(item, idx) in newCart" :key="idx">
                                <div class="flex items-center gap-3 p-2.5 bg-gray-50 rounded-xl border border-gray-200">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-semibold text-gray-800 truncate" x-text="item.name"></div>
                                        <div class="text-xs text-gray-500" x-text="item.color_name ? `Color: ${item.color_name}` : ''"></div>
                                    </div>
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <button @click="item.qty = Math.max(1, item.qty - 1); recalculate()"
                                                class="w-6 h-6 rounded-full bg-gray-200 hover:bg-gray-300 text-xs font-bold flex items-center justify-center">−</button>
                                        <span class="text-sm font-bold w-6 text-center" x-text="item.qty"></span>
                                        <button @click="item.qty++; recalculate()"
                                                class="w-6 h-6 rounded-full bg-gray-200 hover:bg-gray-300 text-xs font-bold flex items-center justify-center">+</button>
                                    </div>
                                    <div class="text-sm font-bold text-gray-800 shrink-0 w-20 text-right"
                                         x-text="`Rs. ${Number(item.price * item.qty).toLocaleString()}`"></div>
                                    <button @click="newCart.splice(idx, 1); recalculate()"
                                            class="text-red-400 hover:text-red-600 transition-colors ml-1">
                                        <i class="fas fa-times text-xs"></i>
                                    </button>
                                </div>
                            </template>
                        </div>

                        <div x-show="newCart.length === 0" class="text-center py-6 text-gray-400 text-sm">
                            <i class="fas fa-box-open text-2xl mb-2 block"></i>
                            Search and add the new item(s) the customer wants
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Summary + Payment + Confirm --}}
            <div>
                <div class="card p-5 sticky top-6 space-y-4">
                    <h3 class="font-bold text-gray-900 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs flex items-center justify-center font-bold">4</span>
                        Exchange Summary
                    </h3>

                    {{-- Summary rows --}}
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>New items subtotal</span>
                            <span class="font-semibold" x-text="`Rs. ${newSubtotal.toLocaleString()}`"></span>
                        </div>
                        <div class="flex justify-between text-indigo-700">
                            <span class="flex items-center gap-1">
                                <i class="fas fa-sync-alt text-xs"></i>
                                Trade-in value
                            </span>
                            <span class="font-semibold" x-text="`− Rs. ${Number(exchangeValue).toLocaleString()}`"></span>
                        </div>
                        <div class="border-t border-gray-200 pt-2 flex justify-between font-bold text-base"
                             :class="difference >= 0 ? 'text-gray-900' : 'text-green-700'">
                            <span x-text="difference >= 0 ? 'Amount to Collect' : 'Cashback to Customer'"></span>
                            <span x-text="`Rs. ${Math.abs(difference).toLocaleString()}`"></span>
                        </div>
                        <div x-show="difference < 0" class="text-xs text-green-600 bg-green-50 rounded-lg p-2 text-center">
                            <i class="fas fa-info-circle mr-1"></i>
                            Return <strong x-text="`Rs. ${Math.abs(difference).toLocaleString()}`"></strong> cash to customer
                        </div>
                    </div>

                    {{-- Payment method (only if customer owes money) --}}
                    <div x-show="difference > 0">
                        <label class="form-label text-sm">Payment Method for Difference</label>
                        <select x-model="paymentMethod" @change="bankAccountId = null; recalculate()" class="form-select text-sm">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="split">Split (Cash + Bank)</option>
                        </select>

                        {{-- Bank account selector for bank_transfer --}}
                        <div x-show="paymentMethod === 'bank_transfer'" x-transition class="mt-3">
                            @if($bankAccounts->count())
                            <label class="form-label text-sm"><i class="fas fa-university mr-1 text-blue-500"></i>Select Bank Account *</label>
                            <div class="space-y-2">
                                @foreach($bankAccounts as $bank)
                                <button type="button"
                                        @click="bankAccountId = {{ $bank->id }}"
                                        :class="bankAccountId === {{ $bank->id }}
                                            ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-400'
                                            : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50'"
                                        class="w-full flex items-center justify-between px-4 py-2.5 border rounded-xl transition-all text-left">
                                    <div>
                                        <div class="text-sm font-semibold text-gray-800">{{ $bank->label }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ $bank->bank_name }}
                                            @if($bank->account_number) · {{ $bank->account_number }} @endif
                                        </div>
                                    </div>
                                    <i class="fas fa-check-circle text-blue-500 text-lg"
                                       x-show="bankAccountId === {{ $bank->id }}"></i>
                                </button>
                                @endforeach
                            </div>
                            <p x-show="!bankAccountId" class="text-xs text-orange-500 font-medium mt-2">
                                <i class="fas fa-exclamation-triangle mr-1"></i>Please select a bank account to continue.
                            </p>
                            @else
                            <div class="text-xs text-orange-600 bg-orange-50 border border-orange-200 rounded-xl px-4 py-3">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                No bank accounts set up. <a href="{{ route('admin.bank-accounts.index') }}" target="_blank" class="underline font-semibold">Add one here</a>.
                            </div>
                            @endif
                        </div>

                        {{-- Split amounts + bank selector --}}
                        <div x-show="paymentMethod === 'split'" class="mt-2 space-y-2">
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-xs text-gray-500 mb-1 block">Cash (Rs.)</label>
                                    <input type="number" x-model.number="splitCash"
                                           min="0" @input="recalculate()"
                                           class="form-input text-sm">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 mb-1 block">Bank (Rs.)</label>
                                    <input type="number" x-model.number="splitBank"
                                           min="0" @input="recalculate()"
                                           class="form-input text-sm">
                                </div>
                            </div>
                            @if($bankAccounts->count())
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block"><i class="fas fa-university mr-1 text-blue-400"></i>Via Bank Account</label>
                                <select x-model="bankAccountId" class="form-select text-sm">
                                    <option value="">— Select bank account —</option>
                                    @foreach($bankAccounts as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->label }} — {{ $bank->bank_name }}{{ $bank->account_number ? ' · '.$bank->account_number : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="form-label text-sm">Notes (optional)</label>
                        <textarea x-model="notes" rows="2"
                                  class="form-textarea text-sm"
                                  placeholder="Any notes about this exchange..."></textarea>
                    </div>

                    {{-- Validation hints --}}
                    <div x-show="!selectedItem" class="text-xs text-amber-600 flex items-center gap-1">
                        <i class="fas fa-exclamation-triangle"></i> Select the item being returned
                    </div>
                    <div x-show="selectedItem && newCart.length === 0" class="text-xs text-amber-600 flex items-center gap-1">
                        <i class="fas fa-exclamation-triangle"></i> Add at least one new item
                    </div>

                    {{-- Confirm Button --}}
                    <button @click="confirmExchange()"
                            :disabled="!canConfirm || processing"
                            class="w-full justify-center font-semibold py-3 rounded-xl text-white transition-all"
                            :style="canConfirm
                                ? 'background-color:#4f46e5; cursor:pointer;'
                                : 'background-color:#9ca3af; opacity:0.5; cursor:not-allowed;'">
                        <span x-show="!processing">
                            <i class="fas fa-sync-alt mr-2"></i> Confirm Exchange
                        </span>
                        <span x-show="processing">
                            <i class="fas fa-spinner fa-spin mr-2"></i> Processing...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function exchangeApp() {
    return {
        // ── Order Search ─────────────────────────────────────────────────────
        orderSearch: '',
        order: null,
        orderItems: [],
        searching: false,
        error: '',

        // ── Return Item ──────────────────────────────────────────────────────
        selectedItem: null,
        returnQty: 1,
        exchangeValue: 0,

        // ── New Items ────────────────────────────────────────────────────────
        productSearch: '',
        productResults: [],
        pendingProduct: null,
        pendingColor: null,
        newCart: [],

        // ── Summary ──────────────────────────────────────────────────────────
        newSubtotal: 0,
        difference: 0,
        paymentMethod: 'cash',
        bankAccountId: null,
        splitCash: 0,
        splitBank: 0,
        notes: '',
        processing: false,

        get canConfirm() {
            if (!this.selectedItem || this.newCart.length === 0) return false;
            // When customer owes money and bank is involved, require a bank account
            if (this.difference > 0) {
                if (this.paymentMethod === 'bank_transfer' && !this.bankAccountId) return false;
            }
            return true;
        },

        // ── Helpers ──────────────────────────────────────────────────────────

        async findOrder() {
            if (!this.orderSearch.trim()) return;
            this.searching = true;
            this.error = '';
            this.order = null;
            this.selectedItem = null;
            this.newCart = [];
            this.newSubtotal = 0;
            this.difference = 0;

            try {
                const res  = await fetch(`/pos/exchange/order/${encodeURIComponent(this.orderSearch.trim())}`);
                const data = await res.json();
                if (data.error) {
                    this.error = data.error;
                } else {
                    this.order = data;
                    this.orderItems = data.items || [];
                }
            } catch (e) {
                this.error = 'Network error. Please try again.';
            }
            this.searching = false;
        },

        selectItem(item) {
            // Spread into a plain object to break Alpine.js Proxy entanglement
            // with the x-for scope — prevents the loop from re-rendering incorrectly.
            this.selectedItem  = { ...item };
            this.returnQty     = parseInt(item.quantity) || 1;
            this.exchangeValue = parseFloat(item.unit_price) * (parseInt(item.quantity) || 1);
            this.recalculate();
        },

        recalculate() {
            this.newSubtotal = this.newCart.reduce((sum, i) => sum + (i.price * i.qty), 0);
            this.difference  = this.newSubtotal - parseFloat(this.exchangeValue || 0);
        },

        async searchProducts() {
            const q = this.productSearch.trim();
            if (!q) { this.productResults = []; return; }
            try {
                const res  = await fetch(`/pos/product/search?q=${encodeURIComponent(q)}`);
                this.productResults = await res.json();
            } catch (e) { this.productResults = []; }
        },

        addToNewCart(product) {
            this.productSearch  = '';
            this.productResults = [];

            if (product.colors && product.colors.length > 0) {
                this.pendingProduct = product;
                this.pendingColor   = null;
            } else {
                this._pushToCart(product, null);
            }
        },

        confirmAddWithColor(color) {
            if (!this.pendingProduct) return;
            this._pushToCart(this.pendingProduct, color);
            this.pendingProduct = null;
            this.pendingColor   = null;
        },

        _pushToCart(product, color) {
            const existing = this.newCart.find(i =>
                i.product_id === product.id && (color ? i.color_id === color.id : !i.color_id)
            );
            if (existing) {
                existing.qty++;
            } else {
                this.newCart.push({
                    product_id: product.id,
                    name:       product.name,
                    price:      product.price,
                    color_id:   color?.id   || null,
                    color_name: color?.name || null,
                    qty:        1,
                });
            }
            this.recalculate();
        },

        async confirmExchange() {
            if (!this.canConfirm || this.processing) return;
            this.processing = true;

            const payMethod = this.difference <= 0 ? 'none'
                            : this.paymentMethod;

            const payload = {
                original_order_id: this.order.id,
                return_item_id:    this.selectedItem.id,
                return_quantity:   this.returnQty,
                exchange_value:    parseFloat(this.exchangeValue || 0),
                new_items: this.newCart.map(i => ({
                    product_id: i.product_id,
                    quantity:   i.qty,
                    unit_price: i.price,
                    color_id:   i.color_id || null,
                })),
                payment_method:  payMethod,
                cash_amount:     payMethod === 'split' ? this.splitCash : null,
                bank_amount:     payMethod === 'split' ? this.splitBank : null,
                bank_account_id: ['bank_transfer', 'split'].includes(payMethod) ? this.bankAccountId : null,
                notes:           this.notes,
            };

            try {
                const res  = await fetch('/pos/exchange', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();

                if (data.success) {
                    // Open the new sale receipt
                    window.open(`/pos/receipt/${data.order_id}`, '_blank', 'width=400,height=650');

                    // Show cashback alert if needed
                    if (data.cashback > 0) {
                        alert(`Exchange complete!\nReturn Rs. ${data.cashback.toLocaleString()} cash to the customer.`);
                    }

                    // Reset the page for next exchange
                    this.order         = null;
                    this.orderSearch   = '';
                    this.orderItems    = [];
                    this.selectedItem  = null;
                    this.returnQty     = 1;
                    this.exchangeValue = 0;
                    this.newCart       = [];
                    this.newSubtotal   = 0;
                    this.difference    = 0;
                    this.paymentMethod = 'cash';
                    this.bankAccountId = null;
                    this.splitCash     = 0;
                    this.splitBank     = 0;
                    this.notes         = '';
                    this.error         = '';
                } else {
                    alert(data.error || 'Exchange failed. Please try again.');
                }
            } catch (e) {
                alert('Network error. Please try again.');
            }

            this.processing = false;
        },
    };
}
</script>
@endpush
@endsection
