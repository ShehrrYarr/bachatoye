@extends('layouts.pos')

@push('styles')
<style>body { overflow: auto !important; height: auto !important; }</style>
@endpush

@section('content')
<div class="min-h-screen bg-gray-100 p-6" x-data="returnApp()">

    {{-- Header --}}
    <div class="max-w-5xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('pos.index') }}" class="btn-outline btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back to POS
                </a>
                <h1 class="text-xl font-bold text-gray-900">Process Return / Refund</h1>
            </div>
        </div>

        {{-- Order Lookup --}}
        <div class="card p-5 mb-6">
            <h2 class="font-semibold text-gray-800 mb-1">Find Order</h2>
            <p class="text-xs text-gray-400 mb-4">Enter an order number <span class="font-mono">ORD-…</span>, a product SKU / barcode, or an item name</p>
            <div class="flex gap-3">
                <input type="text" x-model="orderSearch" @keydown.enter="findOrder()"
                       placeholder="Order number, SKU / barcode, or item name..."
                       class="form-input flex-1">
                <button @click="findOrder()" :disabled="searching"
                        class="btn-primary px-5">
                    <span x-show="!searching"><i class="fas fa-search mr-1"></i> Search</span>
                    <span x-show="searching"><i class="fas fa-spinner fa-spin mr-1"></i> Searching...</span>
                </button>
            </div>
            <div x-show="error" class="alert-error mt-3" x-text="error"></div>

            {{-- SKU search results list --}}
            <div x-show="orderList.length > 0" class="mt-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
                    <i class="fas fa-list mr-1"></i>
                    <span x-text="orderList.length + ' order(s) found — only the latest can be selected'"></span>
                </p>
                <div class="space-y-2">
                    <template x-for="(o, idx) in orderList" :key="o.id">
                        <div @click="idx === 0 && selectOrder(o.order_number)"
                             :class="idx === 0
                                 ? 'border-indigo-300 bg-indigo-50 cursor-pointer hover:bg-indigo-100'
                                 : 'border-gray-200 bg-gray-50 opacity-50 cursor-not-allowed'"
                             class="flex items-center justify-between gap-3 border rounded-xl px-4 py-3 transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                <div :class="idx === 0 ? 'bg-indigo-600' : 'bg-gray-300'"
                                     class="w-6 h-6 rounded-full flex items-center justify-center shrink-0">
                                    <i class="fas fa-receipt text-white text-xs"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-gray-800 font-mono" x-text="o.order_number"></div>
                                    <div class="text-xs text-gray-500 truncate">
                                        <span x-text="o.product_name"></span>
                                        <span class="mx-1 text-gray-300">·</span>
                                        <span x-text="o.customer_name"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-xs text-gray-500" x-text="o.date + ' ' + o.time"></div>
                                <div class="text-xs font-semibold text-gray-700" x-text="'Rs. ' + Number(o.total).toLocaleString()"></div>
                            </div>
                            <div x-show="idx === 0" class="shrink-0">
                                <span class="text-xs bg-indigo-100 text-indigo-700 font-semibold px-2 py-0.5 rounded-full">Latest</span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Order Found --}}
        <div x-show="order" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Order info --}}
            <div class="lg:col-span-2">
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-gray-800">Order Details</h2>
                        <span class="text-sm text-gray-500" x-text="order?.order_number"></span>
                    </div>
                    <div class="card-body">
                        {{-- Customer / Date --}}
                        <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                            <div>
                                <div class="text-gray-500 text-xs mb-1">Date</div>
                                <div class="font-medium" x-text="order?.date"></div>
                            </div>
                            <div>
                                <div class="text-gray-500 text-xs mb-1">Customer</div>
                                <div class="font-medium" x-text="order?.customer_name || 'Walk-in'"></div>
                            </div>
                            <div>
                                <div class="text-gray-500 text-xs mb-1">Total Paid</div>
                                <div class="font-semibold text-primary-700" x-text="`Rs. ${Number(order?.total || 0).toLocaleString()}`"></div>
                            </div>
                            <div>
                                <div class="text-gray-500 text-xs mb-1">Payment</div>
                                <div class="font-medium" x-text="order?.payment_method"></div>
                            </div>
                        </div>

                        {{-- Items to return --}}
                        <h3 class="font-semibold text-gray-700 text-sm mb-3">Select items to return:</h3>
                        <div class="space-y-3">
                            <template x-for="item in returnItems" :key="item.id">
                                <div class="border rounded-xl transition-colors"
                                     :class="item.returnable_qty === 0
                                        ? 'border-gray-200 bg-gray-50 opacity-60'
                                        : item.selected ? 'border-primary-300 bg-primary-50' : 'border-gray-200'">
                                    <div class="flex items-center gap-3 p-3">
                                        {{-- Checkbox — disabled when fully returned --}}
                                        <input type="checkbox" x-model="item.selected"
                                               :disabled="item.returnable_qty === 0"
                                               @change="recalculate()"
                                               class="w-4 h-4 text-primary-600 rounded disabled:cursor-not-allowed">

                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="text-sm font-semibold text-gray-800" x-text="item.product_name"></span>
                                                {{-- Serialized badge --}}
                                                <span x-show="item.is_serialized"
                                                      class="text-xs font-mono font-semibold bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                                                    <i class="fas fa-barcode text-[10px]"></i>
                                                    <span x-text="item.serial_code"></span>
                                                </span>
                                                {{-- Fully returned badge --}}
                                                <span x-show="item.returnable_qty === 0"
                                                      class="text-xs font-semibold bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full">
                                                    Fully Returned
                                                </span>
                                                {{-- Partially returned badge --}}
                                                <span x-show="item.already_returned > 0 && item.returnable_qty > 0"
                                                      class="text-xs font-semibold bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full"
                                                      x-text="`${item.already_returned} already returned`">
                                                </span>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-0.5">
                                                <span x-text="`Rs. ${Number(item.unit_price).toLocaleString()} × ${item.quantity} ordered`"></span>
                                                <span x-show="item.returnable_qty > 0 && item.returnable_qty < item.quantity"
                                                      class="text-orange-600 font-medium"
                                                      x-text="` — ${item.returnable_qty} returnable`"></span>
                                            </div>
                                        </div>

                                        {{-- Qty input — only when selected and returnable --}}
                                        <div x-show="item.selected && item.returnable_qty > 0" class="flex items-center gap-2">
                                            <label class="text-xs text-gray-500">Qty:</label>
                                            <input type="number" x-model.number="item.return_qty"
                                                   :max="item.returnable_qty" min="1"
                                                   @change="recalculate()"
                                                   class="w-16 text-center text-sm border border-gray-300 rounded-lg py-1 px-2 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                            <span class="text-xs font-semibold text-primary-700"
                                                  x-text="`Rs. ${Number(item.unit_price * item.return_qty).toLocaleString()}`"></span>
                                        </div>
                                    </div>

                                    {{-- New cost price — only for selected serialized items --}}
                                    <div x-show="item.selected && item.is_serialized && item.returnable_qty > 0"
                                         class="px-3 pb-3 pt-0">
                                        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-3 flex items-center gap-3">
                                            <i class="fas fa-tag text-indigo-500 shrink-0"></i>
                                            <div class="flex-1 min-w-0">
                                                <label class="text-xs font-semibold text-indigo-800 block">Set New Cost Price for this unit</label>
                                                <p class="text-[11px] text-indigo-500 mt-0.5">
                                                    Original cost: Rs. <span x-text="Number(item.current_cost_price || 0).toLocaleString()"></span>.
                                                    Update if the returned unit's value has changed.
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-1 shrink-0">
                                                <span class="text-xs text-gray-500 font-semibold">Rs.</span>
                                                <input type="number" x-model.number="item.new_cost_price"
                                                       min="0" step="1"
                                                       class="w-28 text-center text-sm font-semibold text-indigo-700 border border-indigo-300 rounded-lg py-1.5 px-2 focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white">
                                            </div>
                                        </div>

                                        {{-- Reclassify subcategory for online store --}}
                                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-3 flex items-center gap-3 mt-2">
                                            <i class="fas fa-tags text-purple-500 shrink-0"></i>
                                            <div class="flex-1 min-w-0">
                                                <label class="text-xs font-semibold text-purple-800 block">Reclassify for Online Store</label>
                                                <p class="text-[11px] text-purple-500 mt-0.5">Move this unit to a different subcategory (e.g. Old Mobiles)</p>
                                            </div>
                                            <select x-model.number="item.new_subcategory_id"
                                                    class="form-select text-sm w-44 shrink-0">
                                                <option :value="null">— No change —</option>
                                                <template x-for="sub in SUBCATEGORIES" :key="sub.id">
                                                    <option :value="sub.id" x-text="sub.name"></option>
                                                </template>
                                            </select>
                                        </div>

                                        {{-- New selling price — shown when subcategory was changed --}}
                                        <div x-show="item.new_subcategory_id && item.new_subcategory_id !== item.current_subcategory_id"
                                             class="bg-purple-50 border border-purple-200 rounded-lg p-3 flex items-center gap-3 mt-1">
                                            <i class="fas fa-tag text-purple-400 shrink-0"></i>
                                            <div class="flex-1 min-w-0">
                                                <label class="text-xs font-semibold text-purple-800 block">New Selling Price</label>
                                                <p class="text-[11px] text-purple-500 mt-0.5">Set the price shown on the website for this used unit</p>
                                            </div>
                                            <div class="flex items-center gap-1 shrink-0">
                                                <span class="text-xs text-gray-500 font-semibold">Rs.</span>
                                                <input type="number" x-model.number="item.new_selling_price"
                                                       min="0" step="1"
                                                       class="w-28 text-center text-sm font-semibold text-purple-700 border border-purple-300 rounded-lg py-1.5 px-2 focus:outline-none focus:ring-2 focus:ring-purple-400 bg-white">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Return Summary --}}
            <div>
                <div class="card p-5 sticky top-6">
                    <h3 class="font-bold text-gray-900 mb-4">Return Summary</h3>

                    <div class="space-y-3 text-sm mb-4">
                        <div class="flex justify-between text-gray-600">
                            <span>Items selected</span>
                            <span x-text="selectedCount"></span>
                        </div>

                        {{-- Editable refund amount --}}
                        <div class="border-t border-gray-200 pt-3">
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5 block">
                                Refund Amount (Rs.)
                            </label>
                            <input type="number"
                                   x-model.number="customRefundAmount"
                                   @input="customAmountEdited = true"
                                   min="0" step="1" placeholder="0"
                                   class="w-full text-lg font-bold text-green-700 border-2 border-green-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-green-400 bg-green-50">
                            {{-- Show hint when amount differs from calculated --}}
                            <p x-show="customAmountEdited && customRefundAmount !== refundAmount && refundAmount > 0"
                               class="text-xs text-amber-600 mt-1.5 flex items-center gap-1">
                                <i class="fas fa-info-circle"></i>
                                Calculated: Rs. <span x-text="refundAmount.toLocaleString()"></span>
                            </p>
                        </div>
                    </div>

                    {{-- Restock option --}}
                    <div class="flex items-center gap-2 mb-4 p-3 bg-gray-50 rounded-xl">
                        <input type="checkbox" x-model="restock" id="restock" class="w-4 h-4 text-primary-600 rounded">
                        <label for="restock" class="text-sm text-gray-700 cursor-pointer">Restock returned items</label>
                    </div>

                    {{-- Refund method --}}
                    <div class="mb-4">
                        <label class="form-label text-sm">Refund Method</label>
                        <select x-model="refundMethod" @change="bankAccountId = null" class="form-select text-sm">
                            <option value="cash">Cash Refund</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="khata_credit" x-show="order?.customer_id">Add to Khata Credit</option>
                            <option value="khata_credit" x-show="order?.vendor_id">Add to Vendor Ledger</option>
                        </select>
                    </div>

                    {{-- Bank account selector (shown when bank_transfer is selected) --}}
                    <div x-show="refundMethod === 'bank_transfer'" x-transition class="mb-4">
                        @if($bankAccounts->count())
                        <label class="form-label text-sm"><i class="fas fa-university mr-1 text-blue-500"></i>Select Bank Account *</label>
                        <div class="space-y-2">
                            @foreach($bankAccounts as $bank)
                            <button type="button"
                                    @click="bankAccountId = {{ $bank->id }}"
                                    :class="bankAccountId === {{ $bank->id }}
                                        ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-400'
                                        : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50'"
                                    class="w-full flex items-center justify-between px-4 py-3 border rounded-xl transition-all text-left">
                                <div>
                                    <div class="text-sm font-semibold text-gray-800">{{ $bank->label }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">
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
                            No bank accounts set up.
                            <a href="{{ route('admin.bank-accounts.index') }}" target="_blank" class="underline font-semibold">Add one here</a>.
                        </div>
                        @endif
                    </div>

                    {{-- Reason --}}
                    <div class="mb-4">
                        <label class="form-label text-sm">Return Reason *</label>
                        <select x-model="reason" class="form-select text-sm">
                            <option value="">Select reason...</option>
                            <option value="defective">Defective / Not working</option>
                            <option value="wrong_item">Wrong item delivered</option>
                            <option value="customer_changed_mind">Customer changed mind</option>
                            <option value="damaged">Damaged in transit</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    {{-- Notes --}}
                    <div class="mb-4">
                        <label class="form-label text-sm">Notes</label>
                        <textarea x-model="returnNotes" rows="2" class="form-textarea text-sm" placeholder="Optional notes..."></textarea>
                    </div>

                    <button @click="processReturn()"
                            :disabled="selectedCount === 0 || !reason || processingReturn || (refundMethod === 'bank_transfer' && !bankAccountId)"
                            class="btn-danger w-full justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!processingReturn"><i class="fas fa-undo mr-2"></i> Process Return</span>
                        <span x-show="processingReturn"><i class="fas fa-spinner fa-spin mr-2"></i> Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const SUBCATEGORIES = @json($subcategories);

function returnApp() {
    return {
        orderSearch: '{{ request("order", "") }}',
        order: null,
        orderList: [],
        returnItems: [],
        searching: false,
        error: '',
        restock: true,
        refundMethod: 'cash',
        bankAccountId: null,
        reason: '',
        returnNotes: '',
        selectedCount: 0,
        refundAmount: 0,
        customRefundAmount: 0,
        customAmountEdited: false,
        processingReturn: false,

        async findOrder() {
            const q = this.orderSearch.trim();
            if (!q) return;
            this.searching = true;
            this.error = '';
            this.order = null;
            this.orderList = [];

            // Order number → direct lookup
            if (q.toUpperCase().startsWith('ORD-')) {
                await this._loadOrder(q);
            } else {
                // SKU / barcode → show list
                try {
                    const res  = await fetch(`/pos/return/search/sku?q=${encodeURIComponent(q)}`);
                    const data = await res.json();
                    if (data.error) {
                        this.error = data.error;
                    } else {
                        this.orderList = data.orders;
                    }
                } catch(e) { this.error = 'Search failed. Please try again.'; }
            }
            this.searching = false;
        },

        async selectOrder(orderNumber) {
            this.searching = true;
            this.error = '';
            this.orderList = [];
            await this._loadOrder(orderNumber);
            this.searching = false;
        },

        async _loadOrder(orderNumber) {
            try {
                const res  = await fetch(`/pos/return/order/${encodeURIComponent(orderNumber)}`);
                const data = await res.json();
                if (data.error) {
                    this.error = data.error;
                } else {
                    this.order = data;
                    this.returnItems = data.items.map(i => ({
                        ...i,
                        selected: false,
                        return_qty: i.returnable_qty,
                        new_cost_price: i.current_cost_price,
                        new_subcategory_id: i.current_subcategory_id ?? null,
                        new_selling_price: i.current_selling_price ?? null,
                    }));
                }
            } catch(e) { this.error = 'Failed to load order. Please try again.'; }
        },

        recalculate() {
            const selected = this.returnItems.filter(i => i.selected);
            this.selectedCount = selected.length;
            this.refundAmount = selected.reduce((sum, i) => sum + (i.unit_price * i.return_qty), 0);
            // Only auto-fill if user hasn't manually edited the amount
            if (!this.customAmountEdited) {
                this.customRefundAmount = this.refundAmount;
            }
        },

        async processReturn() {
            if (this.selectedCount === 0 || !this.reason || this.processingReturn) return;
            this.processingReturn = true;
            try {
                const items = this.returnItems
                    .filter(i => i.selected)
                    .map(i => ({
                        order_item_id: i.id,
                        quantity: i.return_qty,
                        new_cost_price:     i.is_serialized ? i.new_cost_price     : null,
                        new_subcategory_id: i.is_serialized ? i.new_subcategory_id : null,
                        new_selling_price:  i.is_serialized ? i.new_selling_price  : null,
                    }));

                const res = await fetch('/pos/return', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({
                        order_id: this.order.id,
                        items,
                        restock: this.restock,
                        refund_method: this.refundMethod,
                        bank_account_id: this.refundMethod === 'bank_transfer' ? this.bankAccountId : null,
                        custom_refund_amount: this.customRefundAmount,
                        reason: this.reason,
                        notes: this.returnNotes,
                    })
                });
                const data = await res.json();
                if (data.success && data.return_id) {
                    window.open(`/pos/return/${data.return_id}/receipt`, '_blank', 'width=400,height=600');
                    this.order = null;
                    this.orderSearch = '';
                    this.orderList = [];
                    this.returnItems = [];
                    this.selectedCount = 0;
                    this.refundAmount = 0;
                    this.customRefundAmount = 0;
                    this.customAmountEdited = false;
                    this.reason = '';
                    this.returnNotes = '';
                    this.refundMethod = 'cash';
                    this.bankAccountId = null;
                } else {
                    this.error = data.error || data.message || 'Return failed. Please try again.';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            } catch(e) { alert('Network error.'); }
            this.processingReturn = false;
        },
    };
}
</script>
@endpush
@endsection
