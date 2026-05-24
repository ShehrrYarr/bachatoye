@extends('layouts.admin')
@section('title', 'Record Purchase')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.purchases.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Record Purchase</h1>
</div>

<form method="POST" action="{{ route('admin.purchases.store') }}"
      x-data="purchaseForm()" @submit.prevent="submitForm">
    @csrf

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
                            <div x-show="showDropdown && searchResults.length > 0"
                                 class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl z-30 max-h-64 overflow-y-auto">
                                <template x-for="p in searchResults" :key="p.id">
                                    <button type="button" @click="addProduct(p)"
                                            class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 text-left transition-colors">
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-800 truncate" x-text="p.name"></div>
                                            <div class="text-xs text-gray-400" x-text="p.sku ? 'SKU: ' + p.sku : ''"></div>
                                        </div>
                                        <div class="text-xs text-gray-500 shrink-0">Last cost: Rs. <span x-text="Number(p.cost_price).toLocaleString()"></span></div>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Items table --}}
                    <div x-show="items.length > 0">
                        <table class="data-table text-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center w-28">Qty</th>
                                    <th class="text-right w-36">Unit Cost (Rs.)</th>
                                    <th class="text-right w-32">Total</th>
                                    <th class="w-10"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, i) in items" :key="i">
                                    <tr>
                                        <td>
                                            <div class="font-medium text-gray-800" x-text="item.name"></div>
                                            <div class="text-xs text-gray-400" x-text="item.sku ? 'SKU: ' + item.sku : ''"></div>
                                            <input type="hidden" :name="`items[${i}][product_id]`" :value="item.id">
                                        </td>
                                        <td>
                                            <input type="number" :name="`items[${i}][quantity]`" x-model.number="item.quantity"
                                                   @input="recalc()" min="1"
                                                   class="w-full text-center border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary-500">
                                        </td>
                                        <td>
                                            <input type="number" :name="`items[${i}][unit_cost]`" x-model.number="item.unit_cost"
                                                   @input="recalc()" min="0" step="0.01"
                                                   class="w-full text-right border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary-500">
                                        </td>
                                        <td class="text-right font-semibold" x-text="'Rs. ' + (item.quantity * item.unit_cost).toLocaleString()"></td>
                                        <td>
                                            <button type="button" @click="removeItem(i)" class="text-red-400 hover:text-red-600 transition-colors">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

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
                <div class="grid grid-cols-3 gap-2 mb-3">
                    <button type="button" @click="payMethod = 'cash'"
                            :class="payMethod === 'cash' ? 'ring-2 ring-green-500 bg-green-50' : 'bg-gray-50'"
                            class="flex flex-col items-center py-2.5 rounded-xl border border-gray-200 text-xs font-semibold transition-all">
                        <i class="fas fa-money-bill-wave text-green-600 mb-1"></i> Cash
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

                <div x-show="payMethod === 'partial'" class="space-y-2 mt-2">
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
                    Paid in full — no Khata entry
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
                    <div x-show="payMethod === 'cash'" class="flex justify-between text-green-600 font-semibold">
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

@push('scripts')
<script>
function purchaseForm() {
    return {
        items: [],
        searchQuery: '',
        searchResults: [],
        showDropdown: false,
        payMethod: 'cash',
        amountPaid: 0,
        total: 0,
        vendorId: '{{ request('vendor_id', '') }}',
        vendorBalance: null,

        async searchProducts() {
            if (this.searchQuery.length < 2) { this.searchResults = []; return; }
            const res = await fetch(`/admin/api/products/search?q=${encodeURIComponent(this.searchQuery)}`);
            this.searchResults = await res.json();
            this.showDropdown = true;
        },

        addProduct(p) {
            const existing = this.items.find(i => i.id === p.id);
            if (existing) {
                existing.quantity++;
            } else {
                this.items.push({
                    id: p.id,
                    name: p.name,
                    sku: p.sku || '',
                    unit_cost: parseFloat(p.cost_price) || 0,
                    quantity: 1,
                });
            }
            this.searchQuery = '';
            this.searchResults = [];
            this.showDropdown = false;
            this.recalc();
        },

        removeItem(i) {
            this.items.splice(i, 1);
            this.recalc();
        },

        recalc() {
            this.total = this.items.reduce((s, i) => s + (i.quantity * i.unit_cost), 0);
        },

        async loadVendor() {
            if (!this.vendorId) { this.vendorBalance = null; return; }
            const res = await fetch(`/admin/api/vendors/${this.vendorId}/balance`);
            const data = await res.json();
            this.vendorBalance = data.balance;
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
            this.$el.submit();
        }
    };
}
</script>
@endpush
@endsection
