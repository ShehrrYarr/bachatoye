@extends('layouts.pos')

@push('styles')
<style>body { overflow: auto !important; height: auto !important; }</style>
@endpush

@section('content')
<div class="min-h-screen bg-gray-100 p-6" x-data="buybackApp()">

    {{-- Header --}}
    <div class="max-w-5xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('pos.index') }}" class="btn-outline btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back to POS
                </a>
                <h1 class="text-xl font-bold text-gray-900">Buy Back a Phone</h1>
            </div>
        </div>

        {{-- Serial Lookup --}}
        <div class="card p-5 mb-6">
            <h2 class="font-semibold text-gray-800 mb-1">Find the Unit</h2>
            <p class="text-xs text-gray-400 mb-4">Scan or type the serial / IMEI number of the phone being sold back. The seller does not need to be the original buyer.</p>
            <div class="flex gap-3">
                <input type="text" x-model="serialSearch" @keydown.enter="lookup()"
                       placeholder="Serial / IMEI number..."
                       class="form-input flex-1 font-mono">
                <button @click="lookup()" :disabled="searching"
                        class="btn-primary px-5">
                    <span x-show="!searching"><i class="fas fa-search mr-1"></i> Search</span>
                    <span x-show="searching"><i class="fas fa-spinner fa-spin mr-1"></i> Searching...</span>
                </button>
            </div>
            <div x-show="error" class="alert-error mt-3" x-text="error"></div>
        </div>

        {{-- Unit found --}}
        <div x-show="unit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="lg:col-span-2 space-y-6">

                {{-- Unit + provenance card --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-gray-800">Unit Details</h2>
                        <span class="text-xs font-mono font-semibold bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full" x-text="unit?.serial_number"></span>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                            <div>
                                <div class="text-gray-500 text-xs mb-1">Product</div>
                                <div class="font-medium" x-text="unit?.product_name"></div>
                            </div>
                            <div x-show="unit?.color_name">
                                <div class="text-gray-500 text-xs mb-1">Color</div>
                                <div class="font-medium" x-text="unit?.color_name"></div>
                            </div>
                        </div>

                        {{-- Provenance: this is purely informational — never blocks the transaction --}}
                        <div x-show="unit?.sale" class="bg-blue-50 border border-blue-200 rounded-xl p-3">
                            <div class="text-xs font-semibold text-blue-800 mb-1.5">
                                <i class="fas fa-history mr-1"></i>Last Sale on Record
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs text-blue-700">
                                <span>Order: <span class="font-semibold" x-text="unit?.sale?.order_number"></span></span>
                                <span>Date: <span class="font-semibold" x-text="unit?.sale?.date"></span></span>
                                <span>Buyer: <span class="font-semibold" x-text="unit?.sale?.customer_name"></span></span>
                                <span x-show="unit?.sale?.customer_phone">Phone: <span class="font-semibold" x-text="unit?.sale?.customer_phone"></span></span>
                                <span x-show="unit?.sale?.price">Sold for: <span class="font-semibold">Rs. <span x-text="Number(unit?.sale?.price || 0).toLocaleString()"></span></span></span>
                            </div>
                            <p class="text-[11px] text-blue-500 mt-1.5">
                                For your information only — the seller does not need to match this buyer.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Seller --}}
                <div class="card p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Seller Details</h3>

                    <div class="relative mb-3">
                        <label class="form-label text-sm">Search Existing Customer / Vendor (optional)</label>
                        <input type="text" x-model="sellerLookup" @input.debounce.300ms="searchSeller()"
                               placeholder="Search by name or phone..."
                               class="form-input text-sm">
                        <div x-show="sellerResults.length > 0" class="absolute z-10 w-full bg-white border border-gray-200 rounded-xl shadow-lg mt-1 max-h-56 overflow-y-auto">
                            <template x-for="s in sellerResults" :key="s.type + '-' + s.id">
                                <div @click="selectSeller(s)" class="px-4 py-2.5 hover:bg-gray-50 cursor-pointer flex items-center justify-between gap-2 border-b border-gray-100 last:border-0">
                                    <div>
                                        <div class="text-sm font-medium text-gray-800" x-text="s.name"></div>
                                        <div class="text-xs text-gray-500" x-text="s.phone"></div>
                                    </div>
                                    <span class="text-[10px] font-semibold uppercase px-2 py-0.5 rounded-full"
                                          :class="s.type === 'vendor' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                                          x-text="s.type"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div x-show="matchedSeller" class="bg-green-50 border border-green-200 rounded-lg px-3 py-2 mb-3 flex items-center justify-between">
                        <span class="text-sm text-green-800">
                            <i class="fas fa-check-circle mr-1"></i>Matched <span class="font-semibold" x-text="matchedSeller?.type"></span>: <span class="font-semibold" x-text="matchedSeller?.name"></span>
                        </span>
                        <button @click="clearMatchedSeller()" class="text-xs text-green-600 underline">Clear</button>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label text-sm">Seller Name *</label>
                            <input type="text" x-model="sellerName" class="form-input text-sm">
                        </div>
                        <div>
                            <label class="form-label text-sm">Phone</label>
                            <input type="text" x-model="sellerPhone" class="form-input text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="form-label text-sm">CNIC (optional)</label>
                            <input type="text" x-model="sellerCnic" class="form-input text-sm" placeholder="xxxxx-xxxxxxx-x">
                        </div>
                    </div>
                </div>

                {{-- Reclassify for online store --}}
                <div class="card p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Resale Listing</h3>
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-3 flex items-center gap-3">
                        <i class="fas fa-tags text-purple-500 shrink-0"></i>
                        <div class="flex-1 min-w-0">
                            <label class="text-xs font-semibold text-purple-800 block">Reclassify for Online Store</label>
                            <p class="text-[11px] text-purple-500 mt-0.5">Move this unit to a different subcategory (e.g. Old Mobiles)</p>
                        </div>
                        <select x-model.number="newSubcategoryId" class="form-select text-sm w-44 shrink-0">
                            <option :value="null">— No change —</option>
                            <template x-for="sub in SUBCATEGORIES" :key="sub.id">
                                <option :value="sub.id" x-text="sub.name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-3 flex items-center gap-3 mt-2">
                        <i class="fas fa-tag text-purple-400 shrink-0"></i>
                        <div class="flex-1 min-w-0">
                            <label class="text-xs font-semibold text-purple-800 block">New Selling Price</label>
                            <p class="text-[11px] text-purple-500 mt-0.5">Price shown on the website / POS for this used unit</p>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <span class="text-xs text-gray-500 font-semibold">Rs.</span>
                            <input type="number" x-model.number="newSellingPrice" min="0" step="1"
                                   class="w-28 text-center text-sm font-semibold text-purple-700 border border-purple-300 rounded-lg py-1.5 px-2 focus:outline-none focus:ring-2 focus:ring-purple-400 bg-white">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payment summary --}}
            <div>
                <div class="card p-5 sticky top-6">
                    <h3 class="font-bold text-gray-900 mb-4">Payout</h3>

                    <div class="mb-4">
                        <label class="form-label text-sm">Amount to Pay (Rs.) *</label>
                        <input type="number" x-model.number="amountPaid" min="0" step="1" placeholder="0"
                               class="w-full text-lg font-bold text-green-700 border-2 border-green-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-green-400 bg-green-50">
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-sm">Payment Method</label>
                        <select x-model="paymentMethod" @change="bankAccountId = null" class="form-select text-sm">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>

                    <div x-show="paymentMethod === 'bank_transfer'" x-transition class="mb-4">
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

                    <div class="mb-4">
                        <label class="form-label text-sm">Notes</label>
                        <textarea x-model="notes" rows="2" class="form-textarea text-sm" placeholder="Optional notes..."></textarea>
                    </div>

                    <button @click="processBuyback()"
                            :disabled="!sellerName || !amountPaid || processing || (paymentMethod === 'bank_transfer' && !bankAccountId)"
                            class="btn-primary w-full justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!processing"><i class="fas fa-hand-holding-usd mr-2"></i> Confirm Buyback</span>
                        <span x-show="processing"><i class="fas fa-spinner fa-spin mr-2"></i> Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const SUBCATEGORIES = @json($subcategories);

function buybackApp() {
    return {
        serialSearch: '',
        unit: null,
        searching: false,
        error: '',

        sellerLookup: '',
        sellerResults: [],
        matchedSeller: null,
        sellerName: '',
        sellerPhone: '',
        sellerCnic: '',

        newSubcategoryId: null,
        newSellingPrice: null,

        amountPaid: null,
        paymentMethod: 'cash',
        bankAccountId: null,
        notes: '',
        processing: false,

        async lookup() {
            const q = this.serialSearch.trim();
            if (!q) return;
            this.searching = true;
            this.error = '';
            this.unit = null;
            try {
                const res  = await fetch(`/pos/buyback/lookup?serial=${encodeURIComponent(q)}`);
                const data = await res.json();
                if (data.error) {
                    this.error = data.error;
                } else {
                    this.unit = data;
                    this.newSubcategoryId = data.current_subcategory_id ?? null;
                    this.newSellingPrice  = data.current_selling_price ?? null;
                    this.amountPaid = data.current_cost_price || null;
                }
            } catch(e) { this.error = 'Lookup failed. Please try again.'; }
            this.searching = false;
        },

        async searchSeller() {
            const q = this.sellerLookup.trim();
            if (q.length < 2) { this.sellerResults = []; return; }
            try {
                const res  = await fetch(`/pos/customer/search?q=${encodeURIComponent(q)}`);
                this.sellerResults = await res.json();
            } catch(e) { this.sellerResults = []; }
        },

        selectSeller(s) {
            this.matchedSeller = s;
            this.sellerName  = s.name;
            this.sellerPhone = s.phone;
            this.sellerLookup = '';
            this.sellerResults = [];
        },

        clearMatchedSeller() {
            this.matchedSeller = null;
        },

        async processBuyback() {
            if (!this.sellerName || !this.amountPaid || this.processing) return;
            this.processing = true;
            this.error = '';
            try {
                const res = await fetch('/pos/buyback', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({
                        serial_number_id: this.unit.serial_number_id,
                        seller_name: this.sellerName,
                        seller_phone: this.sellerPhone,
                        seller_cnic: this.sellerCnic,
                        seller_customer_id: this.matchedSeller?.type === 'customer' ? this.matchedSeller.id : null,
                        seller_vendor_id: this.matchedSeller?.type === 'vendor' ? this.matchedSeller.id : null,
                        amount_paid: this.amountPaid,
                        payment_method: this.paymentMethod,
                        bank_account_id: this.paymentMethod === 'bank_transfer' ? this.bankAccountId : null,
                        new_subcategory_id: this.newSubcategoryId,
                        new_selling_price: this.newSellingPrice,
                        notes: this.notes,
                    })
                });
                const data = await res.json();
                if (data.success && data.buyback_id) {
                    window.open(`/pos/buyback/${data.buyback_id}/receipt`, '_blank', 'width=400,height=600');
                    this.unit = null;
                    this.serialSearch = '';
                    this.sellerLookup = '';
                    this.sellerResults = [];
                    this.matchedSeller = null;
                    this.sellerName = '';
                    this.sellerPhone = '';
                    this.sellerCnic = '';
                    this.newSubcategoryId = null;
                    this.newSellingPrice = null;
                    this.amountPaid = null;
                    this.paymentMethod = 'cash';
                    this.bankAccountId = null;
                    this.notes = '';
                } else {
                    this.error = data.error || data.message || 'Buyback failed. Please try again.';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            } catch(e) { alert('Network error.'); }
            this.processing = false;
        },
    };
}
</script>
@endpush
@endsection
