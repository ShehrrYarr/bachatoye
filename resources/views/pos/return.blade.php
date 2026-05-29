@extends('layouts.pos')

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
            <h2 class="font-semibold text-gray-800 mb-4">Find Order</h2>
            <div class="flex gap-3">
                <input type="text" x-model="orderSearch" @keydown.enter="findOrder()"
                       placeholder="Enter order number (e.g. ORD-20240101-0001)"
                       class="form-input flex-1">
                <button @click="findOrder()" :disabled="searching"
                        class="btn-primary px-5">
                    <span x-show="!searching"><i class="fas fa-search mr-1"></i> Find</span>
                    <span x-show="searching"><i class="fas fa-spinner fa-spin mr-1"></i> Searching...</span>
                </button>
            </div>
            <div x-show="error" class="alert-error mt-3" x-text="error"></div>
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
                                <div class="flex items-center gap-3 p-3 border rounded-xl transition-colors"
                                     :class="item.returnable_qty === 0
                                        ? 'border-gray-200 bg-gray-50 opacity-60'
                                        : item.selected ? 'border-primary-300 bg-primary-50' : 'border-gray-200'">

                                    {{-- Checkbox — disabled when fully returned --}}
                                    <input type="checkbox" x-model="item.selected"
                                           :disabled="item.returnable_qty === 0"
                                           @change="recalculate()"
                                           class="w-4 h-4 text-primary-600 rounded disabled:cursor-not-allowed">

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-sm font-semibold text-gray-800" x-text="item.product_name"></span>
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
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Return Summary --}}
            <div>
                <div class="card p-5 sticky top-6">
                    <h3 class="font-bold text-gray-900 mb-4">Return Summary</h3>

                    <div class="space-y-2 text-sm mb-4">
                        <div class="flex justify-between text-gray-600">
                            <span>Items selected</span>
                            <span x-text="selectedCount"></span>
                        </div>
                        <div class="flex justify-between font-bold text-base text-gray-900 border-t border-gray-200 pt-2">
                            <span>Refund Amount</span>
                            <span class="text-green-700" x-text="`Rs. ${refundAmount.toLocaleString()}`"></span>
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
                        <select x-model="refundMethod" class="form-select text-sm">
                            <option value="cash">Cash Refund</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="khata_credit" x-show="order?.customer_id">Add to Khata Credit</option>
                        </select>
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
                            :disabled="selectedCount === 0 || !reason || processingReturn"
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
function returnApp() {
    return {
        orderSearch: '{{ request("order", "") }}',
        order: null,
        returnItems: [],
        searching: false,
        error: '',
        restock: true,
        refundMethod: 'cash',
        reason: '',
        returnNotes: '',
        selectedCount: 0,
        refundAmount: 0,
        processingReturn: false,

        async findOrder() {
            if (!this.orderSearch.trim()) return;
            this.searching = true;
            this.error = '';
            this.order = null;
            try {
                const res = await fetch(`/pos/return/order/${encodeURIComponent(this.orderSearch)}`);
                const data = await res.json();
                if (data.error) {
                    this.error = data.error;
                } else {
                    this.order = data;
                    this.returnItems = data.items.map(i => ({
                        ...i,
                        selected: false,
                        return_qty: i.returnable_qty,   // default to max returnable
                    }));
                }
            } catch(e) { this.error = 'Failed to find order. Please try again.'; }
            this.searching = false;
        },

        recalculate() {
            const selected = this.returnItems.filter(i => i.selected);
            this.selectedCount = selected.length;
            this.refundAmount = selected.reduce((sum, i) => sum + (i.unit_price * i.return_qty), 0);
        },

        async processReturn() {
            if (this.selectedCount === 0 || !this.reason || this.processingReturn) return;
            this.processingReturn = true;
            try {
                const items = this.returnItems
                    .filter(i => i.selected)
                    .map(i => ({ order_item_id: i.id, quantity: i.return_qty }));

                const res = await fetch('/pos/return', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({
                        order_id: this.order.id,
                        items,
                        restock: this.restock,
                        refund_method: this.refundMethod,
                        reason: this.reason,
                        notes: this.returnNotes,
                    })
                });
                const data = await res.json();
                if (data.success && data.return_id) {
                    window.open(`/pos/return/${data.return_id}/receipt`, '_blank', 'width=400,height=600');
                    this.order = null;
                    this.orderSearch = '';
                    this.returnItems = [];
                    this.selectedCount = 0;
                    this.refundAmount = 0;
                    this.reason = '';
                    this.returnNotes = '';
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
