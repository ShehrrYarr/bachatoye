@extends('layouts.admin')
@section('title', 'Record Purchase')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.purchases.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Record Purchase</h1>
</div>

<form method="POST" action="{{ route('admin.purchases.store') }}"
      x-data="purchaseForm()" @submit.prevent="submitForm"
      @product-created.window="onProductCreated($event.detail)">
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

                            {{-- Search dropdown --}}
                            <div x-show="showDropdown && searchQuery.length >= 2"
                                 class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl z-30 max-h-72 overflow-y-auto">

                                {{-- Existing results --}}
                                <template x-for="p in searchResults" :key="p.id">
                                    <button type="button" @click="addProduct(p)"
                                            class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 text-left transition-colors border-b border-gray-100 last:border-0">
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-800 truncate" x-text="p.name"></div>
                                            <div class="text-xs text-gray-400" x-text="p.sku ? 'SKU: ' + p.sku : ''"></div>
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

                                {{-- Unit cost --}}
                                <div class="mt-3 flex items-center gap-2">
                                    <label class="text-xs text-gray-500 whitespace-nowrap">Unit Cost (Rs.)</label>
                                    <input type="number" x-model.number="item.unit_cost" @input="recalc()"
                                           min="0" step="0.01"
                                           class="w-32 text-right border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary-500">
                                </div>

                                {{-- Non-colored: single qty --}}
                                <template x-if="!item.has_colors">
                                    <div class="mt-3 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <label class="text-xs text-gray-500">Quantity</label>
                                            <input type="number" x-model.number="item.quantity" @input="recalc()"
                                                   min="1"
                                                   class="w-24 text-center border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary-500">
                                        </div>
                                        <div class="text-sm font-semibold text-gray-700">
                                            Rs. <span x-text="(item.quantity * item.unit_cost).toLocaleString()"></span>
                                        </div>
                                    </div>
                                </template>

                                {{-- Colored: per-color qty rows --}}
                                <template x-if="item.has_colors">
                                    <div class="mt-3">
                                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                                            Quantity by Color
                                        </div>
                                        <div class="space-y-1">
                                            <template x-for="(clr, ci) in item.colors" :key="clr.id">
                                                <div class="flex items-center gap-3 py-2 border-t border-gray-100 first:border-t-0">
                                                    <div class="w-5 h-5 rounded-full border border-gray-300 shrink-0 shadow-sm"
                                                         :style="clr.hex_code ? `background:${clr.hex_code}` : 'background:#e5e7eb'"></div>
                                                    <span class="text-sm text-gray-700 flex-1 min-w-0 truncate" x-text="clr.name"></span>
                                                    <input type="number" x-model.number="clr.quantity" @input="recalc()"
                                                           min="0" placeholder="0"
                                                           class="w-20 text-center border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary-500">
                                                    <div class="text-sm text-gray-600 w-28 text-right shrink-0">
                                                        Rs. <span x-text="((clr.quantity || 0) * item.unit_cost).toLocaleString()"></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        {{-- Color totals footer --}}
                                        <div class="flex items-center justify-between pt-2 mt-1 border-t border-gray-200 text-sm font-semibold">
                                            <span class="text-gray-500">
                                                Total: <span x-text="item.quantity" class="text-gray-800"></span> items
                                            </span>
                                            <span class="text-gray-800">
                                                Rs. <span x-text="(item.quantity * item.unit_cost).toLocaleString()"></span>
                                            </span>
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

{{-- ── Quick Create Product Modal ─────────────────────────────────────── --}}
<div x-data="purchaseCreateModal()"
     x-show="$store.quickCreate.open"
     x-transition.opacity
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="display:none;">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/50" @click="close()"></div>

    {{-- Modal panel --}}
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto z-10">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-900">
                <i class="fas fa-plus-circle text-green-500 mr-2"></i>Create New Product
            </h2>
            <button type="button" @click="close()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <div class="p-6 space-y-4">

            {{-- Name --}}
            <div>
                <label class="form-label">Product Name <span class="text-red-500">*</span></label>
                <input type="text" x-model="form.name" class="form-input" placeholder="e.g. Samsung A55 Cover" required>
                <p x-show="errors.name" x-text="errors.name" class="form-error mt-1"></p>
            </div>

            {{-- Category + Brand --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Category</label>
                    <select x-model="form.category_id" class="form-select">
                        <option value="">— None —</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Brand</label>
                    <select x-model="form.brand_id" class="form-select">
                        <option value="">— None —</option>
                        @foreach($brands as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- SKU --}}
            <div>
                <label class="form-label">SKU <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="text" x-model="form.sku" class="form-input font-mono" placeholder="Leave blank to skip">
                <p x-show="errors.sku" x-text="errors.sku" class="form-error mt-1"></p>
            </div>

            {{-- Cost + Selling Price --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Cost Price (Rs.) <span class="text-red-500">*</span></label>
                    <input type="number" x-model.number="form.cost_price" min="0" step="0.01" class="form-input">
                    <p class="form-hint">What you pay the vendor</p>
                    <p x-show="errors.cost_price" x-text="errors.cost_price" class="form-error mt-1"></p>
                </div>
                <div>
                    <label class="form-label">Selling Price (Rs.) <span class="text-red-500">*</span></label>
                    <input type="number" x-model.number="form.price" min="0" step="0.01" class="form-input">
                    <p class="form-hint">What you sell it for</p>
                    <p x-show="errors.price" x-text="errors.price" class="form-error mt-1"></p>
                </div>
            </div>

            {{-- Low stock + Show in ecom --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Low Stock Alert</label>
                    <input type="number" x-model.number="form.low_stock_threshold" min="0" class="form-input">
                </div>
                <div class="flex flex-col justify-end pb-0.5">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="form.show_in_ecom" class="w-4 h-4 text-primary-600 rounded">
                        <span class="text-sm font-medium text-gray-700">Show on website</span>
                    </label>
                    <p class="text-xs text-gray-400 mt-1 ml-6">Visible to customers online</p>
                </div>
            </div>

            {{-- General error --}}
            <p x-show="errors.general" x-text="errors.general" class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2"></p>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 flex gap-3 justify-end">
            <button type="button" @click="close()" class="btn-outline">Cancel</button>
            <button type="button" @click="save()" :disabled="saving"
                    class="btn-primary" :class="saving ? 'opacity-60 cursor-wait' : ''">
                <template x-if="saving">
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                </template>
                <template x-if="!saving">
                    <i class="fas fa-check mr-2"></i>
                </template>
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

            const hasColors = Array.isArray(p.colors) && p.colors.length > 0;
            this.items.push({
                id:         p.id,
                name:       p.name,
                sku:        p.sku || '',
                unit_cost:  parseFloat(p.cost_price) || 0,
                has_colors: hasColors,
                colors:     hasColors ? p.colors.map(c => ({
                                id:       c.id,
                                name:     c.name,
                                hex_code: c.hex_code || '',
                                quantity: 0,
                            })) : [],
                quantity:   hasColors ? 0 : 1,
            });

            this.searchQuery  = '';
            this.searchResults = [];
            this.showDropdown = false;
            this.recalc();
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
            });
            this.total = this.items.reduce((s, i) => s + (i.quantity * i.unit_cost), 0);
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

            // Flatten into individual purchase line items
            const flatItems = [];
            this.items.forEach(item => {
                if (item.has_colors) {
                    item.colors
                        .filter(c => (parseInt(c.quantity) || 0) > 0)
                        .forEach(c => {
                            flatItems.push({
                                product_id: item.id,
                                quantity:   parseInt(c.quantity),
                                unit_cost:  item.unit_cost,
                                color_id:   c.id,
                                color_name: c.name,
                            });
                        });
                } else {
                    flatItems.push({
                        product_id: item.id,
                        quantity:   item.quantity,
                        unit_cost:  item.unit_cost,
                    });
                }
            });

            // Inject hidden fields into the form
            const container = document.getElementById('flatItemsContainer');
            container.innerHTML = '';
            flatItems.forEach((row, i) => {
                Object.entries(row).forEach(([key, val]) => {
                    const input = document.createElement('input');
                    input.type  = 'hidden';
                    input.name  = `items[${i}][${key}]`;
                    input.value = val;
                    container.appendChild(input);
                });
            });

            this.$el.submit();
        }
    };
}

function purchaseCreateModal() {
    return {
        saving: false,
        form: {
            name: '',
            category_id: '',
            brand_id: '',
            sku: '',
            cost_price: 0,
            price: 0,
            low_stock_threshold: 5,
            show_in_ecom: false,
        },
        errors: {},

        init() {
            // Watch for the store opening and prefill name
            this.$watch('$store.quickCreate.open', (val) => {
                if (val) {
                    this.errors = {};
                    this.form = {
                        name: this.$store.quickCreate.prefillName || '',
                        category_id: '',
                        brand_id: '',
                        sku: '',
                        cost_price: 0,
                        price: 0,
                        low_stock_threshold: 5,
                        show_in_ecom: false,
                    };
                }
            });
        },

        close() {
            this.$store.quickCreate.open = false;
        },

        async save() {
            this.errors = {};

            if (!this.form.name.trim()) {
                this.errors.name = 'Product name is required.';
                return;
            }
            if (!this.form.cost_price || this.form.cost_price <= 0) {
                this.errors.cost_price = 'Cost price is required.';
                return;
            }
            if (!this.form.price || this.form.price <= 0) {
                this.errors.price = 'Selling price is required.';
                return;
            }

            this.saving = true;
            try {
                const res = await fetch('{{ route('admin.api.products.quick-create') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.form),
                });

                const data = await res.json();

                if (!res.ok) {
                    // Laravel validation errors come as { errors: { field: [msg] } }
                    if (data.errors) {
                        Object.entries(data.errors).forEach(([key, msgs]) => {
                            this.errors[key] = msgs[0];
                        });
                    } else {
                        this.errors.general = data.message || 'Something went wrong.';
                    }
                    return;
                }

                // Dispatch event so purchaseForm picks up the new product
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
