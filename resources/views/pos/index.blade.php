@extends('layouts.pos')

@section('content')
<div class="pos-grid no-print" x-data="posApp()" x-init="init()" @keydown.f1.window.prevent="showCostPrice = !showCostPrice">

    {{-- ===== LEFT PANEL: Products ===== --}}
    <div class="pos-left bg-gray-100 flex flex-col">

        {{-- Top bar --}}
        <div class="bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-3 sticky top-0 z-10">
            {{-- Logo/name --}}
            <div class="font-bold text-gray-800 text-sm shrink-0 hidden sm:block">
                {{ \App\Models\Setting::get('shop_name', 'MobileHub') }}
            </div>

            {{-- Barcode / search input --}}
            <div class="flex-1 relative">
                <input type="text" id="barcodeInput" x-ref="barcodeInput"
                       @keydown.enter.prevent="handleBarcodeEnter()"
                       @input.debounce.300ms="searchProducts()"
                       x-model="searchQuery"
                       placeholder="Scan barcode or search product..."
                       class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 bg-gray-50">
                <i class="fas fa-barcode absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <button @click="searchQuery = ''; searchResults = []; loadProducts()" x-show="searchQuery"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Session info --}}
            <div class="shrink-0 flex items-center gap-2">
                @if($session)
                <div class="text-xs text-gray-500 hidden md:block">
                    Session: <span class="font-semibold text-green-600">Open</span>
                </div>
                <button @click="showCloseSession = true"
                        class="text-xs bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1.5 rounded-lg font-medium transition-colors">
                    Close Session
                </button>
                @else
                <button @click="showOpenSession = true"
                        class="text-xs bg-green-100 hover:bg-green-200 text-green-700 px-3 py-1.5 rounded-lg font-medium transition-colors">
                    Open Session
                </button>
                @endif
                {{-- Held Orders badge (shown when at least 1 order is held) --}}
                <button x-show="heldOrders.length > 0" @click="showHeldOrders = true"
                        class="relative text-xs bg-amber-100 hover:bg-amber-200 text-amber-700 px-3 py-1.5 rounded-lg font-medium transition-colors flex items-center gap-1.5">
                    <i class="fas fa-layer-group"></i>
                    <span x-text="'Held (' + heldOrders.length + ')'"></span>
                </button>
                <a href="{{ route('pos.return.index') }}"
                   class="text-xs bg-purple-100 hover:bg-purple-200 text-purple-700 px-3 py-1.5 rounded-lg font-medium transition-colors">
                    <i class="fas fa-undo mr-1"></i>Return
                </a>
                <a href="{{ route('pos.exchange.index') }}"
                   class="text-xs bg-indigo-100 hover:bg-indigo-200 text-indigo-700 px-3 py-1.5 rounded-lg font-medium transition-colors">
                    <i class="fas fa-sync-alt mr-1"></i>Exchange
                </a>
                <a href="{{ auth()->user()->hasRole('admin') ? route('admin.dashboard') : route('salesman.dashboard') }}"
                   class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg font-medium transition-colors">
                    <i class="fas fa-th-large mr-1"></i>Dashboard
                </a>
            </div>
        </div>

        {{-- Parent category grid (shown when no search + no parent selected + no product category) --}}
        <div class="p-4 flex-1 overflow-y-auto" x-show="!searchQuery && !activeParent && !selectedCategory">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3">
                <template x-for="cat in categories" :key="cat.id">
                    <div @click="selectCategory(cat)"
                         class="cursor-pointer group bg-white rounded-xl border border-gray-200 shadow-sm hover:border-primary-400 hover:shadow-md transition-all overflow-hidden flex flex-col">
                        <div class="aspect-square overflow-hidden bg-gray-100">
                            <template x-if="cat.image">
                                <img :src="cat.image" :alt="cat.name"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                            </template>
                            <template x-if="!cat.image">
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="fas fa-tag text-3xl text-gray-300"></i>
                                </div>
                            </template>
                        </div>
                        <div class="px-2 py-1.5">
                            <div class="text-xs font-semibold text-gray-800 truncate group-hover:text-primary-700 transition-colors" x-text="cat.name"></div>
                            <template x-if="cat.children && cat.children.length > 0">
                                <div class="text-[10px] text-gray-400 mt-0.5" x-text="cat.children.length + ' subcategories'"></div>
                            </template>
                        </div>
                    </div>
                </template>
                <template x-if="categories.length === 0">
                    <div class="col-span-5 text-center py-12 text-gray-400">
                        <i class="fas fa-tags text-4xl mb-3"></i>
                        <p class="text-sm">No categories found</p>
                    </div>
                </template>
            </div>
        </div>

        {{-- Subcategory grid (shown when a parent is active but no leaf category selected) --}}
        <div class="flex-1 flex flex-col overflow-hidden" x-show="!searchQuery && activeParent && !selectedCategory">
            {{-- Subcategory breadcrumb --}}
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex items-center gap-2 shrink-0">
                <button @click="clearCategory()"
                        class="flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-800 transition-colors">
                    <i class="fas fa-arrow-left text-xs"></i> Categories
                </button>
                <span class="text-gray-300">/</span>
                <span class="text-xs font-semibold text-gray-800" x-text="activeParent?.name"></span>
            </div>
            <div class="p-4 flex-1 overflow-y-auto">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3">
                    <template x-for="sub in (activeParent?.children || [])" :key="sub.id">
                        <div @click="selectSubcategory(sub)"
                             class="cursor-pointer group bg-white rounded-xl border border-gray-200 shadow-sm hover:border-primary-400 hover:shadow-md transition-all overflow-hidden flex flex-col">
                            <div class="aspect-square overflow-hidden bg-gray-100">
                                <template x-if="sub.image">
                                    <img :src="sub.image" :alt="sub.name"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                </template>
                                <template x-if="!sub.image">
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="fas fa-tag text-3xl text-gray-300"></i>
                                    </div>
                                </template>
                            </div>
                            <div class="px-2 py-1.5">
                                <div class="text-xs font-semibold text-gray-800 truncate group-hover:text-primary-700 transition-colors" x-text="sub.name"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Product grid (shown when searching OR a leaf category is selected) --}}
        <div class="flex-1 flex flex-col overflow-hidden" x-show="searchQuery || selectedCategory">
            {{-- Breadcrumb + back button --}}
            <div x-show="selectedCategory && !searchQuery"
                 class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex items-center gap-2 shrink-0">
                <button @click="clearCategory()"
                        class="flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-800 transition-colors">
                    <i class="fas fa-arrow-left text-xs"></i>
                    <span x-text="activeParent ? activeParent.name : 'Categories'"></span>
                </button>
                <span class="text-gray-300">/</span>
                <span class="text-xs font-semibold text-gray-800" x-text="selectedCategory?.name"></span>
            </div>

            <div class="p-4 flex-1 overflow-y-auto">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3">
                    <template x-for="product in displayProducts" :key="product._key || ('p_' + product.id)">
                        <div @click="addToCart(product)"
                             class="pos-product-tile relative"
                             :class="[
                                 product.stock <= 0 ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
                                 product.serial_number ? 'ring-2 ring-indigo-400 ring-offset-1' : '',
                             ]">
                            {{-- Generic IMEI badge (serialized product tile, no specific unit) --}}
                            <template x-if="product.is_serialized && !product.serial_number">
                                <span class="absolute top-1.5 right-1.5 bg-indigo-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full leading-none flex items-center gap-0.5 z-10">
                                    <i class="fas fa-barcode"></i> IMEI
                                </span>
                            </template>
                            {{-- Specific-unit badge (IMEI search result) --}}
                            <template x-if="product.serial_number">
                                <span class="absolute top-1.5 right-1.5 bg-indigo-700 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-lg leading-none flex items-center gap-0.5 z-10">
                                    <i class="fas fa-fingerprint text-[8px]"></i> Unit
                                </span>
                            </template>
                            <div class="h-20 bg-gray-100 rounded-lg overflow-hidden mb-2">
                                <img :src="product.image" :alt="product.name" class="w-full h-full object-cover">
                            </div>
                            <div class="text-xs font-semibold text-gray-800 leading-tight line-clamp-2 mb-1" x-text="product.name"></div>
                            {{-- Show IMEI for serial search results --}}
                            <template x-if="product.serial_number">
                                <div class="text-[10px] font-mono text-indigo-600 mb-0.5 truncate" x-text="product.serial_number"></div>
                            </template>
                            <div class="text-xs font-bold text-primary-700" x-text="`Rs. ${Number(product.price).toLocaleString()}`"></div>
                            <div class="text-xs mt-0.5" :class="product.stock <= 0 ? 'text-red-500' : 'text-gray-400'"
                                 x-text="product.stock <= 0 ? 'Out of Stock' : `Stock: ${product.stock}`"></div>
                        </div>
                    </template>
                    <template x-if="displayProducts.length === 0 && !loading">
                        <div class="col-span-5 text-center py-12 text-gray-400">
                            <i class="fas fa-box-open text-4xl mb-3"></i>
                            <p class="text-sm">No products found</p>
                        </div>
                    </template>
                    <template x-if="loading">
                        <div class="col-span-5 text-center py-12 text-gray-400">
                            <i class="fas fa-spinner fa-spin text-3xl mb-3"></i>
                            <p class="text-sm">Loading...</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== RIGHT PANEL: Cart ===== --}}
    <div class="pos-cart bg-white border-l border-gray-200">

        {{-- Cart header --}}
        <div class="px-4 py-2 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h2 class="font-bold text-gray-800">Current Sale</h2>
                <span x-show="showCostPrice"
                      class="text-xs bg-amber-100 text-amber-700 border border-amber-300 px-2 py-0.5 rounded-full font-semibold animate-pulse">
                    <i class="fas fa-eye mr-1"></i>Cost
                </span>
            </div>
            <div class="flex items-center gap-2">
                {{-- Hold Order button --}}
                <button @click="holdOrder()" x-show="cart.length > 0"
                        title="Park this cart and start a new sale"
                        class="text-xs text-amber-600 hover:text-amber-800 bg-amber-50 hover:bg-amber-100 border border-amber-200 px-2.5 py-1 rounded-lg font-medium transition-colors flex items-center gap-1">
                    <i class="fas fa-pause-circle"></i> Hold
                </button>
                <button @click="clearCart()" x-show="cart.length > 0"
                        class="text-xs text-red-400 hover:text-red-600 transition-colors">
                    <i class="fas fa-trash mr-1"></i> Clear
                </button>
            </div>
        </div>

        {{-- Customer section --}}
        <div class="px-3 py-2 border-b border-gray-100 bg-gray-50">
            <div x-show="!selectedCustomer">
                <div class="relative">
                    <input type="text" x-model="customerSearch" @input.debounce.300ms="searchCustomers()"
                           placeholder="Search customer (optional)..."
                           class="w-full text-xs px-3 py-1.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary-500">
                    <div x-show="customerResults.length > 0"
                         class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-20 overflow-hidden">
                        <template x-for="cust in customerResults" :key="cust.id">
                            <div @click="selectCustomer(cust)"
                                 class="px-3 py-2 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0">
                                <div class="text-xs font-semibold text-gray-800" x-text="cust.name"></div>
                                <div class="text-xs text-gray-500" x-text="cust.phone"></div>
                            </div>
                        </template>
                        <div @click="showNewCustomer = true; customerResults = []"
                             class="px-3 py-2 hover:bg-primary-50 cursor-pointer text-xs text-primary-600 font-medium flex items-center gap-1">
                            <i class="fas fa-plus"></i> Add new customer
                        </div>
                    </div>
                </div>
            </div>
            <div x-show="selectedCustomer" class="flex items-center justify-between">
                <div>
                    <div class="text-xs font-semibold text-gray-800" x-text="selectedCustomer?.name"></div>
                    <div class="text-xs text-gray-500" x-text="selectedCustomer?.phone"></div>
                    <div x-show="selectedCustomer?.credit_balance < 0"
                         class="text-xs text-red-500 font-medium"
                         x-text="`Khata: Rs. ${Math.abs(selectedCustomer?.credit_balance || 0).toLocaleString()}`"></div>
                </div>
                <button @click="selectedCustomer = null; customerSearch = ''" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
        </div>

        {{-- Cart items --}}
        <div class="pos-cart-items px-4 py-2">
            <template x-if="cart.length === 0">
                <div class="text-center py-6 text-gray-300">
                    <i class="fas fa-shopping-cart text-3xl mb-2"></i>
                    <p class="text-sm">Cart is empty</p>
                    <p class="text-xs mt-0.5">Scan a barcode or click a product</p>
                </div>
            </template>

            <template x-for="(item, index) in cart" :key="item._key">
                <div class="py-1.5 border-b border-gray-100 last:border-0">
                    <div class="flex items-start gap-2">
                        <img :src="item.image" class="w-8 h-8 object-cover rounded-lg bg-gray-100 shrink-0 mt-0.5">
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-semibold text-gray-800 leading-tight line-clamp-1 mb-1" x-text="item.name"></div>
                            {{-- IMEI + attributes for serialized items --}}
                            <template x-if="item.is_serialized">
                                <div class="mb-1 space-y-1">
                                    <span class="inline-flex items-center gap-1 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-md px-1.5 py-0.5 text-[10px] font-mono font-semibold">
                                        <i class="fas fa-barcode text-[9px]"></i>
                                        <span x-text="item.serial_number"></span>
                                    </span>
                                    <template x-if="item.attributes && Object.keys(item.attributes).length > 0">
                                        <div class="flex flex-wrap gap-1">
                                            <template x-for="[k, v] in Object.entries(item.attributes || {})" :key="k">
                                                <span class="inline-flex items-center gap-0.5 bg-gray-100 text-gray-600 rounded px-1.5 py-0.5 text-[10px] font-medium"
                                                      x-show="v && v.trim()">
                                                    <span x-text="v"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <div class="flex items-center gap-2">
                                {{-- Qty controls: hidden for serialized (always qty 1) --}}
                                <template x-if="!item.is_serialized">
                                    <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                                        <button @click="decreaseQty(index)" class="px-2 py-1 text-gray-500 hover:bg-gray-50 text-sm">–</button>
                                        <input type="number" x-model.number="item.quantity" @change="updateQty(index)"
                                               class="w-10 text-center text-xs font-semibold border-0 focus:outline-none py-1" min="1">
                                        <button @click="increaseQty(index)" class="px-2 py-1 text-gray-500 hover:bg-gray-50 text-sm">+</button>
                                    </div>
                                </template>
                                <template x-if="item.is_serialized">
                                    <div class="flex items-center bg-gray-50 border border-gray-200 rounded-lg px-2 py-1">
                                        <span class="text-xs font-semibold text-gray-500">Qty: 1</span>
                                    </div>
                                </template>
                                {{-- Unit price editable --}}
                                <div class="flex items-center gap-1 flex-1">
                                    <span class="text-xs text-gray-400">@</span>
                                    <input type="number" x-model.number="item.price" @change="recalculate()"
                                           class="w-20 text-xs font-semibold text-primary-700 border border-gray-200 rounded px-1.5 py-1 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                </div>
                                <button @click="removeFromCart(index)" class="text-red-400 hover:text-red-600 ml-auto shrink-0">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="text-right text-xs font-bold text-gray-800 mt-1"
                         x-text="`Rs. ${(item.price * item.quantity).toLocaleString()}`"></div>
                    <div x-show="showCostPrice" class="text-right mt-0.5 space-y-0.5">
                        <div class="text-xs text-amber-600 font-medium"
                             x-text="`Cost: Rs. ${(item.cost_price * item.quantity).toLocaleString()}`"></div>
                        <div class="text-xs text-green-600 font-medium"
                             x-text="`Margin: Rs. ${((item.price - item.cost_price) * item.quantity).toLocaleString()}`"></div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Totals & payment --}}
        <div class="border-t border-gray-200 px-3 py-2 space-y-2">
            {{-- Discount --}}
            <div class="flex items-center gap-2">
                <label class="text-xs text-gray-500 shrink-0">Discount:</label>
                {{-- Type toggle --}}
                <div class="flex rounded-lg border border-gray-300 overflow-hidden shrink-0">
                    <button type="button" @click="discountType = 'flat'; recalculate()"
                            class="px-2 py-1 text-xs font-semibold transition-colors"
                            :class="discountType === 'flat' ? 'bg-primary-600 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">
                        Rs.
                    </button>
                    <button type="button" @click="discountType = 'percent'; recalculate()"
                            class="px-2 py-1 text-xs font-semibold transition-colors border-l border-gray-300"
                            :class="discountType === 'percent' ? 'bg-primary-600 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">
                        %
                    </button>
                </div>
                <input type="number" x-model.number="discountValue" @input="recalculate()" min="0"
                       :max="discountType === 'percent' ? 100 : undefined"
                       :placeholder="discountType === 'percent' ? '0–100' : '0'"
                       class="flex-1 text-xs border border-gray-300 rounded-lg px-2 py-1 focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>

            {{-- Subtotal / Discount / Total --}}
            <div class="space-y-0.5">
                <div class="flex justify-between text-gray-500 text-xs">
                    <span>Subtotal</span>
                    <span x-text="`Rs. ${subtotal.toLocaleString()}`"></span>
                </div>
                <div class="flex justify-between text-xs" x-show="discountAmount > 0">
                    <span class="text-red-500">
                        Discount<span x-show="discountType === 'percent'" x-text="` (${discountValue}%)`"></span>
                    </span>
                    <span class="text-red-500" x-text="`– Rs. ${discountAmount.toLocaleString()}`"></span>
                </div>

                <div class="flex justify-between font-bold text-sm text-gray-900 pt-0.5 border-t border-gray-100">
                    <span>Total</span>
                    <span class="text-primary-700" x-text="`Rs. ${total.toLocaleString()}`"></span>
                </div>
            </div>

            {{-- Payment method --}}
            <div class="grid grid-cols-5 gap-1">
                <button @click="setPayment('cash')"
                        :class="paymentMethod === 'cash' ? 'bg-primary-600 text-white border-primary-600' : 'border-gray-300 text-gray-600 hover:border-primary-300'"
                        class="border rounded-lg py-1.5 text-xs font-semibold transition-all flex flex-col items-center gap-0.5">
                    <i class="fas fa-money-bill-wave text-xs"></i> Cash
                </button>
                <button @click="setPayment('bank_transfer')"
                        :class="paymentMethod === 'bank_transfer' ? 'bg-primary-600 text-white border-primary-600' : 'border-gray-300 text-gray-600 hover:border-primary-300'"
                        class="border rounded-lg py-1.5 text-xs font-semibold transition-all flex flex-col items-center gap-0.5">
                    <i class="fas fa-university text-xs"></i> Bank
                </button>
                <button @click="setPayment('split')"
                        :class="paymentMethod === 'split' ? 'bg-teal-600 text-white border-teal-600' : 'border-gray-300 text-gray-600 hover:border-teal-300'"
                        class="border rounded-lg py-1.5 text-xs font-semibold transition-all flex flex-col items-center gap-0.5">
                    <i class="fas fa-random text-xs"></i> Split
                </button>
                <button @click="setPayment('khata')" :disabled="!selectedCustomer"
                        :class="paymentMethod === 'khata' ? 'bg-primary-600 text-white border-primary-600' : 'border-gray-300 text-gray-600 hover:border-primary-300'"
                        class="border rounded-lg py-1.5 text-xs font-semibold transition-all flex flex-col items-center gap-0.5 disabled:opacity-40 disabled:cursor-not-allowed"
                        title="Select a customer first">
                    <i class="fas fa-book text-xs"></i> Khata
                </button>
                <button @click="setPayment('partial')" :disabled="!selectedCustomer"
                        :class="paymentMethod === 'partial' ? 'bg-orange-500 text-white border-orange-500' : 'border-gray-300 text-gray-600 hover:border-orange-300'"
                        class="border rounded-lg py-1.5 text-xs font-semibold transition-all flex flex-col items-center gap-0.5 disabled:opacity-40 disabled:cursor-not-allowed"
                        title="Select a customer first">
                    <i class="fas fa-code-branch text-xs"></i> Part
                </button>
            </div>

            {{-- Bank account selector (shown for bank_transfer only; split has its own inline selector below) --}}
            @if($bankAccounts->count())
            <div x-show="paymentMethod === 'bank_transfer'" class="space-y-1">
                <label class="text-xs font-semibold text-blue-700 block mb-1">
                    <i class="fas fa-university mr-1"></i>Select Bank Account
                </label>
                <select x-model="bankAccountId"
                        class="w-full text-sm border border-blue-300 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-blue-400 bg-white font-medium">
                    <option value="">— Choose bank account —</option>
                    @foreach($bankAccounts as $bank)
                    <option value="{{ $bank->id }}">
                        {{ $bank->label }} — {{ $bank->bank_name }}{{ $bank->account_number ? ' · ' . $bank->account_number : '' }}
                    </option>
                    @endforeach
                </select>
                <p x-show="!bankAccountId && paymentMethod === 'bank_transfer'"
                   class="text-xs text-orange-500 font-medium">
                    <i class="fas fa-exclamation-triangle mr-1"></i>Please select a bank account.
                </p>
            </div>
            @else
            <div x-show="paymentMethod === 'bank_transfer'"
                 class="text-xs text-orange-600 bg-orange-50 border border-orange-200 rounded-lg px-3 py-2">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                No bank accounts set up. <a href="{{ route('admin.bank-accounts.index') }}" target="_blank" class="underline font-semibold">Add one here</a>.
            </div>
            @endif

            {{-- Cash tendered (full cash payment) --}}
            <div x-show="paymentMethod === 'cash'" class="space-y-1">
                <div class="flex items-center gap-2">
                    <label class="text-xs text-gray-500 shrink-0 w-16">Received:</label>
                    <input type="number" x-model.number="cashReceived" @input="calcChange()" min="0"
                           class="flex-1 text-sm font-semibold border border-gray-300 rounded-lg px-2 py-1 focus:outline-none focus:ring-1 focus:ring-primary-500">
                </div>
                <div x-show="cashReceived > 0" class="flex justify-between text-xs font-bold"
                     :class="cashReceived >= total ? 'text-green-700' : 'text-red-500'">
                    <span x-text="cashReceived >= total ? 'Change:' : 'Short:'"></span>
                    <span x-text="`Rs. ${Math.abs(cashReceived - total).toLocaleString()}`"></span>
                </div>
                <div class="grid grid-cols-4 gap-1">
                    <template x-for="amount in quickCash">
                        <button @click="cashReceived = amount; calcChange()"
                                class="text-xs border border-gray-200 rounded-lg py-0.5 hover:bg-gray-50 text-gray-600 font-medium"
                                x-text="`${amount >= 1000 ? amount/1000+'k' : amount}`"></button>
                    </template>
                </div>
            </div>

            {{-- Partial payment --}}
            <div x-show="paymentMethod === 'partial'" class="space-y-1 bg-orange-50 border border-orange-200 rounded-xl p-2">
                <div class="text-xs font-semibold text-orange-700 mb-1">
                    <i class="fas fa-code-branch mr-1"></i> Partial Payment (Cash + Khata)
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs text-gray-600 shrink-0 w-20">Paid Now:</label>
                    <input type="number" x-model.number="partialAmountPaid" @input="calcPartialKhata()"
                           :max="total" min="0" placeholder="0"
                           class="flex-1 text-sm font-bold border border-orange-300 rounded-lg px-2 py-1 focus:outline-none focus:ring-1 focus:ring-orange-400 bg-white">
                </div>
                <div class="grid grid-cols-4 gap-1">
                    <template x-for="amount in quickCash">
                        <button @click="partialAmountPaid = Math.min(amount, total); calcPartialKhata()"
                                class="text-xs border border-orange-200 rounded-lg py-0.5 hover:bg-orange-100 text-orange-700 font-medium bg-white"
                                x-text="`${amount >= 1000 ? amount/1000+'k' : amount}`"></button>
                    </template>
                </div>
                <div class="border-t border-orange-200 pt-1 space-y-0.5 text-xs">
                    <div class="flex justify-between text-gray-600">
                        <span>Cash Received:</span>
                        <span class="font-semibold text-green-700" x-text="`Rs. ${partialAmountPaid.toLocaleString()}`"></span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Added to Khata:</span>
                        <span class="font-bold text-red-600" x-text="`Rs. ${Math.max(0, total - partialAmountPaid).toLocaleString()}`"></span>
                    </div>
                </div>
            </div>

            {{-- Promise date for Khata / Partial --}}
            <div x-show="paymentMethod === 'khata' || paymentMethod === 'partial'"
                 class="space-y-1 bg-purple-50 border border-purple-200 rounded-xl p-2">
                <div class="text-xs font-semibold text-purple-700 mb-1">
                    <i class="fas fa-calendar-check mr-1"></i> Promise Date
                    <span class="font-normal text-purple-500">(optional)</span>
                </div>
                <input type="date" x-model="promiseDate"
                       :min="new Date().toISOString().split('T')[0]"
                       class="w-full text-sm border border-purple-300 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-purple-400 bg-white">
                <p class="text-[10px] text-purple-500 leading-tight">
                    When customer promises to pay — a reminder will appear 5 days before.
                </p>
            </div>

            {{-- Split payment: cash + bank --}}
            <div x-show="paymentMethod === 'split'" class="space-y-1 bg-teal-50 border border-teal-200 rounded-xl p-2">
                <div class="text-xs font-semibold text-teal-700 mb-1">
                    <i class="fas fa-random mr-1"></i> Split Payment (Cash + Bank)
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs text-gray-600 shrink-0 w-16"><i class="fas fa-money-bill-wave text-green-500 mr-1"></i>Cash:</label>
                    <input type="number" x-model.number="splitCash" @input="calcSplitBank()" min="0"
                           class="flex-1 text-sm font-bold border border-teal-300 rounded-lg px-2 py-1 focus:outline-none focus:ring-1 focus:ring-teal-400 bg-white"
                           placeholder="0">
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs text-gray-600 shrink-0 w-16"><i class="fas fa-university text-blue-500 mr-1"></i>Bank:</label>
                    <input type="number" x-model.number="splitBank" @input="calcSplitCash()" min="0"
                           class="flex-1 text-sm font-bold border border-teal-300 rounded-lg px-2 py-1 focus:outline-none focus:ring-1 focus:ring-teal-400 bg-white"
                           placeholder="0">
                </div>
                {{-- Bank account selector inside split --}}
                @if($bankAccounts->count())
                <div class="flex items-center gap-2">
                    <label class="text-xs text-gray-600 shrink-0 w-16"><i class="fas fa-credit-card text-blue-400 mr-1"></i>Via:</label>
                    <select x-model="bankAccountId"
                            class="flex-1 text-xs border border-teal-300 rounded-lg px-2 py-1 focus:outline-none focus:ring-1 focus:ring-teal-400 bg-white font-medium">
                        <option value="">— Bank account —</option>
                        @foreach($bankAccounts as $bank)
                        <option value="{{ $bank->id }}">
                            {{ $bank->label }} — {{ $bank->bank_name }}{{ $bank->account_number ? ' · ' . $bank->account_number : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @else
                <div class="text-xs text-orange-600 bg-orange-50 border border-orange-200 rounded-lg px-2 py-1.5">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    No bank accounts set up. <a href="{{ route('admin.bank-accounts.index') }}" target="_blank" class="underline font-semibold">Add one here</a>.
                </div>
                @endif
                <div class="grid grid-cols-4 gap-1">
                    <template x-for="amount in quickCash">
                        <button @click="splitCash = Math.min(amount, total); splitBank = Math.max(0, total - splitCash)"
                                class="text-xs border border-teal-200 rounded-lg py-0.5 hover:bg-teal-100 text-teal-700 font-medium bg-white"
                                x-text="`${amount >= 1000 ? amount/1000+'k' : amount}`"></button>
                    </template>
                </div>
                <div class="border-t border-teal-200 pt-1 space-y-0.5 text-xs">
                    <div class="flex justify-between text-gray-600">
                        <span>Total collected:</span>
                        <span class="font-semibold" :class="(splitCash + splitBank) >= total ? 'text-green-700' : 'text-red-600'"
                              x-text="`Rs. ${(splitCash + splitBank).toLocaleString()}`"></span>
                    </div>
                    <div x-show="(splitCash + splitBank) > total" class="text-orange-600 font-medium text-xs">
                        Change: <span x-text="`Rs. ${((splitCash + splitBank) - total).toLocaleString()}`"></span>
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            <input type="text" x-model="orderNotes" placeholder="Order notes (optional)..."
                   class="w-full text-xs border border-gray-300 rounded-lg px-3 py-1 focus:outline-none focus:ring-1 focus:ring-primary-500">

            {{-- Place order button --}}
            <button @click="placeOrder()"
                    :disabled="cart.length === 0 || processingOrder"
                    class="w-full btn-primary py-2.5 justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!processingOrder"><i class="fas fa-check-circle mr-2"></i> Complete Sale</span>
                <span x-show="processingOrder"><i class="fas fa-spinner fa-spin mr-2"></i> Processing...</span>
            </button>
        </div>
    </div>

    {{-- Serial Prompt Modal — appears when a serialized product tile is clicked --}}
    <template x-teleport="body">
        <div x-show="serialPromptProduct" x-transition
             class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center"
             @keydown.window.escape="serialPromptProduct = null">
            <div class="bg-white rounded-2xl shadow-2xl p-6 w-96">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center shrink-0">
                        <i class="fas fa-barcode text-indigo-600 text-lg"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-bold text-gray-900 text-base truncate" x-text="serialPromptProduct?.name"></h3>
                        <p class="text-xs text-gray-500">Scan or type the IMEI / Serial number</p>
                    </div>
                </div>

                <input type="text"
                       id="serialPromptInput"
                       x-model="serialPromptInput"
                       @keydown.enter.prevent="confirmSerialPrompt()"
                       @keydown.escape="serialPromptProduct = null"
                       placeholder="Scan barcode or type IMEI..."
                       autocomplete="off"
                       class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">

                <div x-show="serialPromptError" class="mt-2 flex items-start gap-1.5 text-xs text-red-600">
                    <i class="fas fa-exclamation-circle mt-0.5 shrink-0"></i>
                    <span x-text="serialPromptError"></span>
                </div>

                <div class="flex gap-2 mt-4">
                    <button @click="serialPromptProduct = null"
                            class="flex-1 py-2.5 px-4 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button @click="confirmSerialPrompt()"
                            :disabled="serialPromptLoading"
                            class="flex-1 py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition-colors flex items-center justify-center gap-1.5"
                            :class="serialPromptLoading ? 'opacity-70 cursor-wait' : ''">
                        <i class="fas fa-spinner fa-spin" x-show="serialPromptLoading"></i>
                        <i class="fas fa-plus" x-show="!serialPromptLoading"></i>
                        <span x-text="serialPromptLoading ? 'Checking...' : 'Add to Cart'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- Color Picker Modal — kept inside posApp() so it can access colorPickerProduct --}}
    <template x-teleport="body">
        <div x-show="colorPickerProduct" x-transition class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
            <div class="bg-white rounded-2xl shadow-2xl p-6 w-80" @click.outside="colorPickerProduct = null">
                <h3 class="font-bold text-gray-900 text-base mb-1" x-text="colorPickerProduct?.name"></h3>
                <p class="text-xs text-gray-500 mb-4">Select a color variant to add to cart:</p>
                <div class="space-y-2">
                    <template x-for="color in colorPickerProduct?.colors || []" :key="color.id">
                        <button type="button"
                                @click="selectColorAndAdd(color)"
                                :disabled="color.stock_quantity <= 0"
                                class="w-full flex items-center justify-between px-4 py-3 rounded-xl border transition-all"
                                :class="color.stock_quantity > 0
                                    ? 'border-gray-300 hover:border-primary-400 hover:bg-primary-50 cursor-pointer'
                                    : 'border-gray-200 bg-gray-50 opacity-50 cursor-not-allowed'">
                            <div class="flex items-center gap-3">
                                <div class="w-6 h-6 rounded-full border border-gray-300 shrink-0"
                                     :style="color.hex_code ? `background:${color.hex_code}` : 'background:#e5e7eb'"></div>
                                <span class="text-sm font-medium text-gray-800" x-text="color.name"></span>
                            </div>
                            <span class="text-xs font-semibold"
                                  :class="color.stock_quantity > 0 ? 'text-green-600' : 'text-red-400'"
                                  x-text="color.stock_quantity > 0 ? color.stock_quantity + ' left' : 'Out of stock'"></span>
                        </button>
                    </template>
                </div>
                <button @click="colorPickerProduct = null" class="btn-outline w-full mt-4 justify-center text-sm">Cancel</button>
            </div>
        </div>
    </template>

    {{-- ── Held Orders Modal ─────────────────────────────────────────────── --}}
    <template x-teleport="body">
        <div x-show="showHeldOrders"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
             @keydown.window.escape="showHeldOrders = false"
             @click.self="showHeldOrders = false">

            {{-- Fixed width via inline style so it never goes full-screen --}}
            <div class="bg-white rounded-2xl shadow-2xl flex flex-col"
                 style="width:440px; max-width:100%; max-height:85vh; min-height:0;">

                {{-- Header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100" style="flex-shrink:0;">
                    <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                        <span class="w-7 h-7 bg-amber-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-layer-group text-amber-600 text-sm"></i>
                        </span>
                        Held Orders
                        <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-semibold"
                              x-text="heldOrders.length"></span>
                    </h3>
                    <button @click="showHeldOrders = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>

                {{-- Scrollable body --}}
                <div style="flex:1 1 0%; overflow-y:auto; padding:12px 16px;">

                    {{-- Empty state --}}
                    <div x-show="heldOrders.length === 0" class="text-center py-12 text-gray-400">
                        <i class="fas fa-inbox text-4xl mb-3"></i>
                        <p class="text-sm font-medium">No held orders</p>
                        <p class="text-xs mt-1" style="color:#d1d5db;">Press <strong style="color:#9ca3af;">Hold</strong> in the cart to park an order</p>
                    </div>

                    {{-- Held order cards --}}
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <template x-for="(held, idx) in heldOrders" :key="held.id">
                            <div style="border:1px solid #fde68a; background:#fffbeb; border-radius:12px; padding:14px;">

                                {{-- Top row: label + total --}}
                                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:8px; margin-bottom:6px;">
                                    <div style="min-width:0;">
                                        <div style="font-weight:600; font-size:14px; color:#1f2937; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" x-text="held.label"></div>
                                        <div style="font-size:11px; color:#6b7280; display:flex; align-items:center; gap:6px; margin-top:2px; flex-wrap:wrap;">
                                            <i class="fas fa-clock" style="font-size:9px;"></i>
                                            <span x-text="held.heldAt"></span>
                                            <span style="color:#d1d5db;">·</span>
                                            <span x-text="held.itemCount + (held.itemCount === 1 ? ' item' : ' items')"></span>
                                            <template x-if="held.customer">
                                                <span style="display:flex; align-items:center; gap:3px; color:#2563eb;">
                                                    <span style="color:#d1d5db;">·</span>
                                                    <i class="fas fa-user" style="font-size:9px;"></i>
                                                    <span x-text="held.customer.name"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                    <div style="font-weight:700; font-size:14px; color:#b91c1c; flex-shrink:0;"
                                         x-text="'Rs. ' + Number(held.total).toLocaleString()"></div>
                                </div>

                                {{-- Item preview --}}
                                <div style="margin-bottom:10px; padding-left:6px; border-left:2px solid #fde68a;">
                                    <template x-for="(item, j) in held.cart.slice(0, 3)" :key="j">
                                        <div style="font-size:11px; color:#4b5563; display:flex; align-items:center; gap:5px; padding:1px 0; overflow:hidden;">
                                            <span style="display:inline-flex; align-items:center; justify-content:center; width:16px; height:16px; background:white; border:1px solid #fde68a; color:#d97706; border-radius:50%; font-weight:700; font-size:9px; flex-shrink:0;"
                                                  x-text="item.quantity"></span>
                                            <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; flex:1;" x-text="item.name"></span>
                                            <span style="margin-left:auto; flex-shrink:0; color:#9ca3af; font-size:11px;"
                                                  x-text="'Rs. ' + Number(item.price * item.quantity).toLocaleString()"></span>
                                        </div>
                                    </template>
                                    <div x-show="held.cart.length > 3"
                                         style="font-size:10px; color:#d97706; font-style:italic; padding-left:20px; margin-top:2px;"
                                         x-text="'+ ' + (held.cart.length - 3) + ' more item(s)'"></div>
                                </div>

                                {{-- Actions --}}
                                <div style="display:flex; gap:8px;">
                                    <button @click="resumeHeldOrder(idx)"
                                            style="flex:1; background:#f59e0b; color:white; font-size:12px; font-weight:600; padding:8px 0; border-radius:10px; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; transition:background 0.15s;"
                                            onmouseover="this.style.background='#d97706'" onmouseout="this.style.background='#f59e0b'">
                                        <i class="fas fa-play" style="font-size:10px;"></i> Resume
                                    </button>
                                    <button @click="deleteHeldOrder(idx)"
                                            style="padding:8px 14px; color:#f87171; border:1px solid #fecaca; border-radius:10px; background:white; cursor:pointer; font-size:12px; transition:all 0.15s;"
                                            onmouseover="this.style.color='#dc2626';this.style.borderColor='#f87171';this.style.background='#fff1f2';"
                                            onmouseout="this.style.color='#f87171';this.style.borderColor='#fecaca';this.style.background='white';">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Footer tip --}}
                <div style="padding:10px 20px; border-top:1px solid #f3f4f6; flex-shrink:0; font-size:10px; color:#9ca3af; text-align:center;">
                    <i class="fas fa-info-circle" style="margin-right:3px;"></i>
                    Resuming an order with items in cart will auto-hold the current cart
                </div>
            </div>
        </div>
    </template>

    {{-- Open Session Modal --}}
    <div x-show="showOpenSession" x-transition class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center" x-data="{ cash: '' }">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-80" @click.outside="showOpenSession = false">
            <h3 class="font-bold text-gray-900 text-lg mb-4">Open POS Session</h3>
            <div class="mb-4">
                <label class="form-label text-sm">Opening Cash (Rs.)</label>
                <input type="number" x-model="cash" class="form-input" placeholder="0">
            </div>
            <div class="flex gap-3">
                <button @click="openSession(cash)" class="btn-primary flex-1 justify-center">Open Session</button>
                <button @click="showOpenSession = false" class="btn-outline flex-1 justify-center">Cancel</button>
            </div>
        </div>
    </div>

    {{-- Close Session Modal --}}
    <div x-show="showCloseSession" x-transition class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-80" @click.outside="showCloseSession = false">
            <h3 class="font-bold text-gray-900 text-lg mb-2">Close POS Session</h3>
            <p class="text-sm text-gray-500 mb-4">This will close the current session and generate a summary.</p>
            <div class="flex gap-3">
                <button @click="closeSession()" class="btn-danger flex-1 justify-center">Close Session</button>
                <button @click="showCloseSession = false" class="btn-outline flex-1 justify-center">Cancel</button>
            </div>
        </div>
    </div>

    {{-- New Customer Modal --}}
    <div x-show="showNewCustomer" x-transition class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-96" @click.outside="showNewCustomer = false">
            <h3 class="font-bold text-gray-900 text-lg mb-4">Add New Customer</h3>
            <div class="space-y-3">
                <div>
                    <label class="form-label text-sm">Name *</label>
                    <input type="text" x-model="newCustomer.name" class="form-input" placeholder="Customer name">
                </div>
                <div>
                    <label class="form-label text-sm">Phone *</label>
                    <input type="tel" x-model="newCustomer.phone" class="form-input" placeholder="03XX-XXXXXXX">
                </div>
                <div>
                    <label class="form-label text-sm">Address</label>
                    <input type="text" x-model="newCustomer.address" class="form-input" placeholder="Optional">
                </div>
            </div>
            <div class="flex gap-3 mt-4">
                <button @click="createCustomer()" class="btn-primary flex-1 justify-center">Save Customer</button>
                <button @click="showNewCustomer = false" class="btn-outline flex-1 justify-center">Cancel</button>
            </div>
        </div>
    </div>
</div>

{{-- ===== DAILY SUMMARY BAR ===== --}}
<div class="no-print fixed bottom-0 left-0 right-0 bg-gray-900 text-white z-40 border-t border-gray-700"
     x-data="posStats()">

    {{-- Summary bar (clickable) --}}
    <div class="flex items-center gap-2 px-3 py-1.5 text-xs overflow-x-auto">
        <span class="text-gray-400 font-semibold shrink-0">TODAY</span>
        <div class="w-px h-4 bg-gray-700 shrink-0"></div>

        {{-- Sales chip --}}
        <button @click="activeTab = 'sales'; showDailyModal = true"
                class="flex items-center gap-1.5 bg-green-900/60 hover:bg-green-800 text-green-300 px-2.5 py-1 rounded-lg shrink-0 transition-colors">
            <i class="fas fa-shopping-cart"></i>
            <span class="font-semibold" x-text="'Rs. ' + fmt(stats.total_revenue)"></span>
            <span class="text-green-500" x-text="stats.order_count + ' orders'"></span>
        </button>

        <div class="w-px h-4 bg-gray-700 shrink-0"></div>

        {{-- Cash chip --}}
        <div class="flex items-center gap-1.5 text-emerald-300 shrink-0">
            <i class="fas fa-money-bill-wave text-xs"></i>
            <span class="text-xs">Cash:</span>
            <span class="font-semibold text-xs" x-text="'Rs. ' + fmt(stats.cash_total)"></span>
        </div>

        <div class="w-px h-4 bg-gray-700 shrink-0"></div>

        {{-- Bank chip --}}
        <div class="flex items-center gap-1.5 text-blue-300 shrink-0">
            <i class="fas fa-university text-xs"></i>
            <span class="text-xs">Bank:</span>
            <span class="font-semibold text-xs" x-text="'Rs. ' + fmt(stats.bank_total)"></span>
        </div>

        <div class="w-px h-4 bg-gray-700 shrink-0"></div>

        {{-- Returns chip --}}
        <button @click="activeTab = 'returns'; showDailyModal = true"
                class="flex items-center gap-1.5 bg-red-900/60 hover:bg-red-800 text-red-300 px-2.5 py-1 rounded-lg shrink-0 transition-colors">
            <i class="fas fa-undo"></i>
            <span class="font-semibold" x-text="'Rs. ' + fmt(stats.total_refunded)"></span>
            <span class="text-red-400" x-text="stats.return_count + ' returns'"></span>
        </button>

        <div class="w-px h-4 bg-gray-700 shrink-0"></div>

        {{-- Payments received chip --}}
        <button @click="activeTab = 'payments'; showDailyModal = true"
                class="flex items-center gap-1.5 bg-yellow-900/60 hover:bg-yellow-800 text-yellow-300 px-2.5 py-1 rounded-lg shrink-0 transition-colors">
            <i class="fas fa-hand-holding-usd"></i>
            <span class="font-semibold" x-text="'Rs. ' + fmt(stats.total_collected)"></span>
            <span class="text-yellow-500" x-text="stats.payment_count + ' payments'"></span>
        </button>

        <div class="w-px h-4 bg-gray-700 shrink-0"></div>

        {{-- Net --}}
        <div class="flex items-center gap-1.5 shrink-0">
            <i class="fas fa-chart-line" :class="netRevenue >= 0 ? 'text-blue-400' : 'text-red-400'"></i>
            <span class="text-gray-400">Net:</span>
            <span class="font-bold" :class="netRevenue >= 0 ? 'text-blue-400' : 'text-red-400'" x-text="'Rs. ' + fmt(netRevenue)"></span>
        </div>

        <div class="ml-auto shrink-0">
            <button @click="showDailyModal = true"
                    class="text-gray-400 hover:text-white text-xs px-2 py-1 rounded hover:bg-gray-700 transition-colors">
                <i class="fas fa-table mr-1"></i>Details
            </button>
        </div>
    </div>

    {{-- ===== DAILY DETAIL DRAWER ===== --}}
    <div x-show="showDailyModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-4"
         class="fixed inset-0 bg-black/60 z-50 flex items-end"
         @keydown.escape.window="showDailyModal = false">

        <div class="bg-white w-full rounded-t-2xl shadow-2xl flex flex-col"
             style="max-height: 82vh;"
             @click.outside="showDailyModal = false">

            {{-- Drawer header --}}
            <div class="flex items-center justify-between px-5 pt-4 pb-3 border-b border-gray-100 shrink-0">
                <div class="flex items-center gap-3">
                    <h2 class="font-bold text-gray-900 text-base">Today's Activity</h2>
                    <span class="text-xs text-gray-400">{{ now()->format('d M Y') }}</span>
                </div>
                <button @click="showDailyModal = false" class="text-gray-400 hover:text-gray-700">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            {{-- Tabs --}}
            <div class="flex border-b border-gray-100 px-5 shrink-0">
                <button @click="activeTab = 'sales'"
                        :class="activeTab === 'sales' ? 'border-b-2 border-primary-600 text-primary-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="py-2.5 px-4 text-sm transition-colors mr-2">
                    <i class="fas fa-shopping-cart mr-1.5"></i>
                    Sales <span class="ml-1 text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full font-semibold" x-text="stats.order_count"></span>
                </button>
                <button @click="activeTab = 'returns'"
                        :class="activeTab === 'returns' ? 'border-b-2 border-red-500 text-red-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="py-2.5 px-4 text-sm transition-colors">
                    <i class="fas fa-undo mr-1.5"></i>
                    Returns <span class="ml-1 text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded-full font-semibold" x-text="stats.return_count"></span>
                </button>
                <button @click="activeTab = 'payments'"
                        :class="activeTab === 'payments' ? 'border-b-2 border-yellow-500 text-yellow-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="py-2.5 px-4 text-sm transition-colors">
                    <i class="fas fa-hand-holding-usd mr-1.5"></i>
                    Payments <span class="ml-1 text-xs bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded-full font-semibold" x-text="stats.payment_count"></span>
                </button>
            </div>

            {{-- Tab content --}}
            <div class="overflow-y-auto flex-1">

                {{-- ======== SALES TABLE ======== --}}
                <div x-show="activeTab === 'sales'" class="p-4">

                    {{-- Sales totals summary --}}
                    <div class="grid grid-cols-3 gap-3 mb-4">
                        <div class="bg-green-50 border border-green-100 rounded-xl px-4 py-3 text-center">
                            <div class="text-lg font-bold text-green-700" x-text="'Rs. ' + fmt(stats.total_revenue)"></div>
                            <div class="text-xs text-green-600">Total Sales</div>
                        </div>
                        <div class="bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 text-center">
                            <div class="text-lg font-bold text-blue-700" x-text="stats.order_count"></div>
                            <div class="text-xs text-blue-600">Orders</div>
                        </div>
                        <div class="bg-purple-50 border border-purple-100 rounded-xl px-4 py-3 text-center">
                            <div class="text-lg font-bold text-purple-700" x-text="'Rs. ' + fmt(stats.order_count > 0 ? Math.round(stats.total_revenue / stats.order_count) : 0)"></div>
                            <div class="text-xs text-purple-600">Avg. Order</div>
                        </div>
                    </div>

                    <div x-show="activityLoading" class="text-center py-16 text-gray-300">
                        <i class="fas fa-spinner fa-spin text-4xl mb-3"></i>
                        <p class="text-sm text-gray-400">Loading...</p>
                    </div>
                    <div x-show="!activityLoading && orders.length === 0" class="text-center py-16 text-gray-300">
                        <i class="fas fa-shopping-cart text-4xl mb-3"></i>
                        <p class="text-sm text-gray-400">No sales today yet</p>
                    </div>
                    <div x-show="!activityLoading && orders.length > 0" class="overflow-x-auto rounded-xl border border-gray-100">
                        @php
                            $canEditSale   = auth()->user()->hasRole('admin') || auth()->user()->hasPermissionTo('pos.edit_sale');
                            $canDeleteSale = auth()->user()->hasRole('admin') || auth()->user()->hasPermissionTo('pos.delete_sale');
                        @endphp
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                                <tr>
                                    <th class="text-left px-4 py-2.5 font-semibold">Time</th>
                                    <th class="text-left px-4 py-2.5 font-semibold">Order #</th>
                                    <th class="text-left px-4 py-2.5 font-semibold">Customer</th>
                                    <th class="text-left px-4 py-2.5 font-semibold">Items Sold</th>
                                    <th class="text-left px-4 py-2.5 font-semibold">Payment</th>
                                    <th class="text-right px-4 py-2.5 font-semibold">Total</th>
                                    <th class="px-4 py-2.5 font-semibold"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <template x-for="(o, idx) in orders" :key="idx">
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap" x-text="o.time"></td>
                                        <td class="px-4 py-3">
                                            <span class="font-mono text-xs text-gray-700" x-text="o.order_number"></span>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-700">
                                            <div class="font-medium" x-text="o.customer_name"></div>
                                            <div class="text-gray-400" x-show="o.customer_phone" x-text="o.customer_phone"></div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="space-y-0.5">
                                                <template x-for="(it, j) in o.items" :key="j">
                                                    <div class="text-xs text-gray-700 flex items-center gap-1">
                                                        <span class="inline-block w-5 h-5 bg-primary-100 text-primary-700 rounded-full text-center leading-5 font-bold shrink-0 text-[10px]" x-text="it.quantity"></span>
                                                        <span x-text="it.product_name"></span>
                                                        <span class="text-gray-400 ml-auto whitespace-nowrap" x-text="'× Rs.' + fmt(it.unit_price)"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                                  :class="pmLabel(o.payment_method).cls"
                                                  x-text="pmLabel(o.payment_method).label"></span>
                                            <div x-show="o.payment_method === 'split'" class="mt-1 space-y-0.5">
                                                <div class="text-xs text-green-600">
                                                    <i class="fas fa-money-bill-wave text-[10px] mr-0.5"></i><span x-text="'Rs. ' + fmt(o.cash_amount)"></span>
                                                </div>
                                                <div class="text-xs text-blue-600">
                                                    <i class="fas fa-university text-[10px] mr-0.5"></i><span x-text="'Rs. ' + fmt(o.bank_amount)"></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="font-bold text-gray-900" x-text="'Rs. ' + fmt(o.total)"></div>
                                            <div x-show="o.discount_amount > 0" class="text-xs text-red-400"
                                                 x-text="'-Rs.' + fmt(o.discount_amount) + ' disc.'"></div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <a :href="`/pos/receipt/${o.id}`" target="_blank"
                                                   class="inline-flex items-center gap-1 text-xs bg-gray-50 hover:bg-gray-100 text-gray-600 font-medium px-2.5 py-1 rounded-lg transition-colors whitespace-nowrap">
                                                    <i class="fas fa-receipt text-[10px]"></i> Receipt
                                                </a>
                                                @if($canEditSale)
                                                <a :href="`/pos/orders/${o.id}/edit`"
                                                   class="inline-flex items-center gap-1 text-xs bg-blue-50 hover:bg-blue-100 text-blue-600 font-medium px-2.5 py-1 rounded-lg transition-colors whitespace-nowrap">
                                                    <i class="fas fa-edit text-[10px]"></i> Edit
                                                </a>
                                                @endif
                                                @if($canDeleteSale)
                                                <button @click="deleteSale(o.id, o.order_number, idx)"
                                                        class="inline-flex items-center gap-1 text-xs bg-red-50 hover:bg-red-100 text-red-600 font-medium px-2.5 py-1 rounded-lg transition-colors whitespace-nowrap">
                                                    <i class="fas fa-trash text-[10px]"></i> Delete
                                                </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-sm font-bold text-gray-700"
                                        x-text="`Total — ${orders.length} orders, ${orders.reduce((s,o) => s + o.items.reduce((ss,i) => ss + i.quantity, 0), 0)} items`"></td>
                                    <td colspan="3"></td>
                                    <td class="px-4 py-3 text-right font-bold text-lg text-primary-700"
                                        x-text="'Rs. ' + fmt(orders.reduce((s,o) => s + o.total, 0))"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- ======== RETURNS TABLE ======== --}}
                <div x-show="activeTab === 'returns'" class="p-4">

                    {{-- Returns totals --}}
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="bg-red-50 border border-red-100 rounded-xl px-4 py-3 text-center">
                            <div class="text-lg font-bold text-red-700" x-text="'Rs. ' + fmt(stats.total_refunded)"></div>
                            <div class="text-xs text-red-600">Total Refunded</div>
                        </div>
                        <div class="bg-orange-50 border border-orange-100 rounded-xl px-4 py-3 text-center">
                            <div class="text-lg font-bold text-orange-700" x-text="stats.return_count"></div>
                            <div class="text-xs text-orange-600">Return Transactions</div>
                        </div>
                    </div>

                    <div x-show="activityLoading" class="text-center py-16 text-gray-300">
                        <i class="fas fa-spinner fa-spin text-4xl mb-3"></i>
                        <p class="text-sm text-gray-400">Loading...</p>
                    </div>
                    <div x-show="!activityLoading && returns.length === 0" class="text-center py-16 text-gray-300">
                        <i class="fas fa-undo text-4xl mb-3"></i>
                        <p class="text-sm text-gray-400">No returns today</p>
                    </div>
                    <div x-show="!activityLoading && returns.length > 0" class="overflow-x-auto rounded-xl border border-gray-100">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                                <tr>
                                    <th class="text-left px-4 py-2.5 font-semibold">Time</th>
                                    <th class="text-left px-4 py-2.5 font-semibold">Return #</th>
                                    <th class="text-left px-4 py-2.5 font-semibold">Orig. Order</th>
                                    <th class="text-left px-4 py-2.5 font-semibold">Items Returned</th>
                                    <th class="text-left px-4 py-2.5 font-semibold">Reason</th>
                                    <th class="text-right px-4 py-2.5 font-semibold">Refund</th>
                                    <th class="px-4 py-2.5 font-semibold"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <template x-for="(r, idx) in returns" :key="idx">
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap" x-text="r.time"></td>
                                        <td class="px-4 py-3">
                                            <span class="font-mono text-xs text-gray-700" x-text="r.return_number"></span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="font-mono text-xs text-gray-500" x-text="r.order_number"></span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="space-y-0.5">
                                                <template x-if="r.items.length === 0">
                                                    <span class="text-xs text-gray-400 italic">No items</span>
                                                </template>
                                                <template x-for="(ri, j) in r.items" :key="j">
                                                    <div class="text-xs text-gray-700 flex items-center gap-1">
                                                        <span class="inline-block w-5 h-5 bg-red-100 text-red-600 rounded-full text-center leading-5 font-bold shrink-0 text-[10px]" x-text="ri.quantity"></span>
                                                        <span x-text="ri.product_name"></span>
                                                        <span class="text-gray-400 ml-auto whitespace-nowrap" x-text="'× Rs.' + fmt(ri.unit_price)"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-500 max-w-xs" x-text="r.reason"></td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="font-bold text-red-600" x-text="'Rs. ' + fmt(r.refund_amount)"></div>
                                            <div class="text-xs text-gray-400 capitalize" x-text="r.refund_method.replace('_', ' ')"></div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <a :href="`/pos/return/${r.id}/receipt`" target="_blank"
                                               class="inline-flex items-center gap-1 text-xs bg-gray-50 hover:bg-gray-100 text-gray-600 font-medium px-2.5 py-1 rounded-lg transition-colors whitespace-nowrap">
                                                <i class="fas fa-receipt text-[10px]"></i> Receipt
                                            </a>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                <tr>
                                    <td colspan="4" class="px-4 py-3 text-sm font-bold text-gray-700"
                                        x-text="`Total — ${returns.length} returns, ${returns.reduce((s,r) => s + r.items.reduce((ss,i) => ss + i.quantity, 0), 0)} items`"></td>
                                    <td></td>
                                    <td class="px-4 py-3 text-right font-bold text-lg text-red-600"
                                        x-text="'Rs. ' + fmt(returns.reduce((s,r) => s + r.refund_amount, 0))"></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- ======== PAYMENTS TABLE ======== --}}
                <div x-show="activeTab === 'payments'" class="p-4">

                    {{-- Payments totals --}}
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="bg-yellow-50 border border-yellow-100 rounded-xl px-4 py-3 text-center">
                            <div class="text-lg font-bold text-yellow-700" x-text="'Rs. ' + fmt(stats.total_collected)"></div>
                            <div class="text-xs text-yellow-600">Collected Today</div>
                        </div>
                        <div class="bg-green-50 border border-green-100 rounded-xl px-4 py-3 text-center">
                            <div class="text-lg font-bold text-green-700" x-text="stats.payment_count"></div>
                            <div class="text-xs text-green-600">Transactions</div>
                        </div>
                    </div>

                    <div x-show="activityLoading" class="text-center py-16 text-gray-300">
                        <i class="fas fa-spinner fa-spin text-4xl mb-3"></i>
                        <p class="text-sm text-gray-400">Loading...</p>
                    </div>
                    <div x-show="!activityLoading && payments.length === 0" class="text-center py-16 text-gray-300">
                        <i class="fas fa-hand-holding-usd text-4xl mb-3"></i>
                        <p class="text-sm text-gray-400">No customer payments recorded today</p>
                    </div>
                    <div x-show="!activityLoading && payments.length > 0" class="overflow-x-auto rounded-xl border border-gray-100">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                                <tr>
                                    <th class="text-left px-4 py-2.5 font-semibold">Time</th>
                                    <th class="text-left px-4 py-2.5 font-semibold">Customer</th>
                                    <th class="text-left px-4 py-2.5 font-semibold">Description</th>
                                    <th class="text-left px-4 py-2.5 font-semibold">Recorded By</th>
                                    <th class="text-right px-4 py-2.5 font-semibold">Paid</th>
                                    <th class="text-right px-4 py-2.5 font-semibold">Balance After</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <template x-for="(p, idx) in payments" :key="idx">
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap" x-text="p.time"></td>
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-sm text-gray-800" x-text="p.customer_name"></div>
                                            <div class="text-xs text-gray-400" x-show="p.customer_phone" x-text="p.customer_phone"></div>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-600 max-w-xs">
                                            <span x-text="p.description || '—'"></span>
                                            <div x-show="p.reference" class="text-gray-400 font-mono" x-text="'Ref: ' + p.reference"></div>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-500" x-text="p.user_name"></td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="font-bold text-green-600 text-sm" x-text="'+ Rs. ' + fmt(p.amount)"></span>
                                        </td>
                                        <td class="px-4 py-3 text-right text-xs">
                                            <span class="font-semibold"
                                                  :class="p.balance_after < 0 ? 'text-red-500' : 'text-gray-600'"
                                                  x-text="p.balance_after < 0 ? '– Rs. ' + fmt(Math.abs(p.balance_after)) + ' owed' : 'Rs. ' + fmt(p.balance_after) + ' credit'">
                                            </span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-sm font-bold text-gray-700"
                                        x-text="`Total — ${payments.length} payment(s)`"></td>
                                    <td colspan="2"></td>
                                    <td class="px-4 py-3 text-right font-bold text-lg text-green-600"
                                        x-text="'+ Rs. ' + fmt(payments.reduce((s,p) => s + p.amount, 0))"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>{{-- end overflow-y-auto --}}
        </div>
    </div>
</div>


@php
$_posStats = [
    'order_count'     => $todaySales->order_count ?? 0,
    'total_revenue'   => $todaySales->total_revenue ?? 0,
    'cash_total'      => $todaySales->cash_total ?? 0,
    'bank_total'      => $todaySales->bank_total ?? 0,
    'return_count'    => $todayReturns->return_count ?? 0,
    'total_refunded'  => $todayReturns->total_refunded ?? 0,
    'payment_count'   => $todayPayments->payment_count ?? 0,
    'total_collected' => $todayPayments->total_collected ?? 0,
];
$_posCategories = $categories->map(fn($c) => [
    'id'       => $c->id,
    'name'     => $c->name,
    'image'    => $c->image ? \Illuminate\Support\Facades\Storage::url($c->image) : null,
    'children' => $c->children->map(fn($child) => [
        'id'    => $child->id,
        'name'  => $child->name,
        'image' => $child->image ? \Illuminate\Support\Facades\Storage::url($child->image) : null,
    ])->values()->all(),
])->values()->toArray();
@endphp
@push('scripts')
<script>
function posApp() {
    return {
        // State
        cart: [],
        searchQuery: '',
        displayProducts: [],
        loading: false,
        categories: @json($_posCategories),
        activeParent: null,
        selectedCategory: null,
        discountType: 'flat',
        discountValue: 0,
        discountAmount: 0,
        subtotal: 0,
        total: 0,
        paymentMethod: 'cash',
        cashReceived: 0,
        partialAmountPaid: 0,
        promiseDate: '',
        splitCash: 0,
        splitBank: 0,
        bankAccountId: null,
        quickCash: [500, 1000, 2000, 5000],
        orderNotes: '',
        processingOrder: false,
        showCostPrice: false,
        colorPickerProduct: null,

        // Serial prompt (shown when a serialized product tile is clicked)
        serialPromptProduct: null,
        serialPromptInput:   '',
        serialPromptError:   '',
        serialPromptLoading: false,

        // Customer
        customerSearch: '',
        customerResults: [],
        selectedCustomer: null,
        showNewCustomer: false,
        newCustomer: { name: '', phone: '', address: '' },

        // Held orders (parked carts)
        heldOrders: [],
        showHeldOrders: false,

        // Session
        showOpenSession: false,
        showCloseSession: false,

        async init() {
            this.$refs.barcodeInput?.focus();
            // Load held orders from localStorage
            try { this.heldOrders = JSON.parse(localStorage.getItem('pos_held_orders') || '[]'); } catch(e) { this.heldOrders = []; }
            // Start on category grid — no products loaded until category selected or searched
            window.addEventListener('pos:sale-deleted', () => {
                if (this.searchQuery.length > 0) {
                    this.searchProducts();
                } else if (this.selectedCategory) {
                    this.loadProducts();
                }
                // If in subcategory/category grid, no reload needed (no products shown)
            });
        },

        async loadProducts() {
            this.loading = true;
            try {
                let url = '/pos/product/search?q=';
                if (this.selectedCategory) url += `&category=${this.selectedCategory.id}`;
                const res = await fetch(url);
                this.displayProducts = await res.json();
            } catch(e) { console.error(e); }
            this.loading = false;
        },

        selectCategory(cat) {
            if (cat.children && cat.children.length > 0) {
                // Parent with children → show subcategory grid
                this.activeParent = cat;
            } else {
                // Leaf category (or childless parent) → load products directly
                this.selectedCategory = cat;
                this.loadProducts();
            }
        },

        selectSubcategory(sub) {
            this.selectedCategory = sub;
            this.loadProducts();
        },

        clearCategory() {
            if (this.selectedCategory && this.activeParent) {
                // In product view reached via subcategory → go back to subcategory grid
                this.selectedCategory = null;
                this.displayProducts = [];
            } else if (this.activeParent && !this.selectedCategory) {
                // In subcategory grid → go back to parent grid
                this.activeParent = null;
            } else {
                // Back to root
                this.activeParent = null;
                this.selectedCategory = null;
                this.displayProducts = [];
            }
            this.$refs.barcodeInput?.focus();
        },

        async searchProducts() {
            if (this.searchQuery.length < 1) {
                if (this.selectedCategory) {
                    this.loadProducts(); // reload category products
                } else {
                    this.displayProducts = [];
                }
                return;
            }
            this.loading = true;
            try {
                // Search across ALL products (ignore category filter when searching)
                const res = await fetch(`/pos/product/search?q=${encodeURIComponent(this.searchQuery)}`);
                this.displayProducts = await res.json();
            } catch(e) {}
            this.loading = false;
        },

        async handleBarcodeEnter() {
            if (!this.searchQuery) return;
            try {
                const res = await fetch(`/pos/product/barcode/${encodeURIComponent(this.searchQuery)}`);
                if (res.ok) {
                    const product = await res.json();
                    if (product && product.id) {
                        if (product.is_serialized && product.serial_number) {
                            this.addSerializedToCart(product, product.serial_number, product.serial_id);
                        } else {
                            this.addToCart(product);
                        }
                        this.searchQuery = '';
                        if (this.selectedCategory) this.loadProducts();
                        return;
                    }
                } else {
                    // Show the error from the server (e.g. "IMEI not registered")
                    const data = await res.json().catch(() => ({}));
                    if (data.error) {
                        alert(data.error);
                        this.searchQuery = '';
                        return;
                    }
                }
            } catch(e) {}
            this.searchProducts();
        },

        addSerializedToCart(product, serialNumber, serialId) {
            // Check if this exact serial is already in cart
            const existing = this.cart.find(i => i.serial_number === serialNumber);
            if (existing) {
                alert(`Serial number ${serialNumber} is already in the cart.`);
                return;
            }
            // Use serial's own selling_price if available, fall back to product price
            const price = (product.selling_price && parseFloat(product.selling_price) > 0)
                          ? parseFloat(product.selling_price)
                          : parseFloat(product.price);
            this.cart.push({
                _key:             `${product.id}_s${serialNumber}`,
                product_id:       product.id,
                color_id:         null,
                color_name:       null,
                name:             product.name,
                image:            product.image,
                price:            price,
                cost_price:       parseFloat(product.cost_price) || 0,
                quantity:         1,
                stock:            1,
                serial_number:    serialNumber,
                serial_id:        serialId || null,
                is_serialized:    true,
                attributes:       product.attributes || {},
                exchange_eligible: product.exchange_eligible || false,
            });
            this.recalculate();
        },

        addToCart(product) {
            if (product.stock <= 0) return;

            // IMEI search result — serial already known, add directly without prompt
            if (product.is_serialized && product.serial_number) {
                this.addSerializedToCart(product, product.serial_number, product.serial_id);
                this.searchQuery    = '';
                this.displayProducts = [];
                if (this.selectedCategory) this.loadProducts();
                this.$refs.barcodeInput?.focus();
                return;
            }

            // Serialized product tile (no specific IMEI yet) — show IMEI prompt
            if (product.is_serialized) {
                this.serialPromptProduct = product;
                this.serialPromptInput   = '';
                this.serialPromptError   = '';
                this.serialPromptLoading = false;
                setTimeout(() => document.getElementById('serialPromptInput')?.focus(), 80);
                return;
            }

            // If product has colors, show color picker first
            if (product.colors && product.colors.length > 0) {
                this.colorPickerProduct = product;
                return;
            }
            this.doAddToCart(product, null, null, product.stock);
        },

        async confirmSerialPrompt() {
            const sn = (this.serialPromptInput || '').trim();
            if (!sn) {
                this.serialPromptError = 'Please enter or scan the IMEI / serial number.';
                return;
            }
            this.serialPromptLoading = true;
            this.serialPromptError   = '';
            try {
                const res  = await fetch(`/pos/product/barcode/${encodeURIComponent(sn)}`);
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.is_serialized && data.serial_number) {
                    // Verify the serial belongs to the product the salesman clicked
                    if (data.id !== this.serialPromptProduct.id) {
                        this.serialPromptError = `This serial belongs to a different product: ${data.name}. Please scan the correct IMEI.`;
                    } else {
                        this.addSerializedToCart(data, data.serial_number, data.serial_id);
                        this.serialPromptProduct = null;
                        this.serialPromptInput   = '';
                        this.$refs.barcodeInput?.focus();
                    }
                } else {
                    this.serialPromptError = data.error || 'Serial not found or already sold. Register it in a purchase first.';
                }
            } catch(e) {
                this.serialPromptError = 'Network error. Please try again.';
            }
            this.serialPromptLoading = false;
        },

        selectColorAndAdd(color) {
            const product = this.colorPickerProduct;
            this.colorPickerProduct = null;
            if (!product || color.stock_quantity <= 0) return;
            this.doAddToCart(product, color.id, color.name, color.stock_quantity);
        },

        doAddToCart(product, colorId, colorName, maxStock) {
            const cartKey  = colorId ? `${product.id}_c${colorId}` : `${product.id}`;
            const existing = this.cart.find(i => i._key === cartKey);
            if (existing) {
                if (existing.quantity < maxStock) existing.quantity++;
            } else {
                const label = product.name + (colorName ? ` — ${colorName}` : '');
                this.cart.push({
                    _key:             cartKey,
                    product_id:       product.id,
                    color_id:         colorId || null,
                    color_name:       colorName || null,
                    name:             label,
                    image:            product.image,
                    price:            parseFloat(product.price),
                    cost_price:       parseFloat(product.cost_price) || 0,
                    quantity:         1,
                    stock:            maxStock,
                    exchange_eligible: product.exchange_eligible || false,
                });
            }
            this.recalculate();
        },

        removeFromCart(index) {
            this.cart.splice(index, 1);
            this.recalculate();
        },

        increaseQty(index) {
            if (this.cart[index].quantity < this.cart[index].stock) {
                this.cart[index].quantity++;
                this.recalculate();
            }
        },

        decreaseQty(index) {
            if (this.cart[index].quantity > 1) {
                this.cart[index].quantity--;
            } else {
                this.cart.splice(index, 1);
            }
            this.recalculate();
        },

        updateQty(index) {
            const item = this.cart[index];
            if (item.quantity < 1) item.quantity = 1;
            if (item.quantity > item.stock) item.quantity = item.stock;
            this.recalculate();
        },

        clearCart() {
            if (this.cart.length === 0) return;
            this.cart = [];
            this.discountType  = 'flat';
            this.discountValue  = 0;
            this.discountAmount = 0;
            this.cashReceived = 0;
            this.splitCash = 0;
            this.splitBank = 0;
            this.bankAccountId = null;
            this.partialAmountPaid = 0;
            this.promiseDate = '';
            this.recalculate();
        },

        recalculate() {
            this.subtotal = this.cart.reduce((sum, i) => sum + i.price * i.quantity, 0);
            if (this.discountType === 'percent') {
                const pct = Math.min(100, Math.max(0, this.discountValue || 0));
                this.discountAmount = Math.round(this.subtotal * pct / 100);
            } else {
                this.discountAmount = Math.max(0, this.discountValue || 0);
            }
            this.total = Math.max(0, this.subtotal - this.discountAmount);
            if (this.paymentMethod === 'cash') {
                this.cashReceived = this.total;
            }
        },

        calcChange() {},

        setPayment(method) {
            this.paymentMethod = method;
            if (method === 'cash') {
                this.cashReceived = Math.ceil(this.total / 100) * 100;
                this.promiseDate = '';
            } else if (method === 'partial') {
                this.partialAmountPaid = 0;
            } else if (method === 'split') {
                this.splitCash = Math.ceil(this.total / 100) * 100;
                this.splitBank = 0;
                this.promiseDate = '';
            } else if (method === 'bank_transfer') {
                this.promiseDate = '';
            }
        },

        calcPartialKhata() {
            if (this.partialAmountPaid > this.total) this.partialAmountPaid = this.total;
            if (this.partialAmountPaid < 0) this.partialAmountPaid = 0;
        },

        calcSplitBank() {
            if (this.splitCash < 0) this.splitCash = 0;
            this.splitBank = Math.max(0, this.total - this.splitCash);
        },

        calcSplitCash() {
            if (this.splitBank < 0) this.splitBank = 0;
            this.splitCash = Math.max(0, this.total - this.splitBank);
        },

        async searchCustomers() {
            if (this.customerSearch.length < 2) { this.customerResults = []; return; }
            try {
                const res = await fetch(`/pos/customer/search?q=${encodeURIComponent(this.customerSearch)}`);
                this.customerResults = await res.json();
            } catch(e) {}
        },

        selectCustomer(cust) {
            this.selectedCustomer = cust;
            this.customerSearch = '';
            this.customerResults = [];
        },

        async createCustomer() {
            if (!this.newCustomer.name || !this.newCustomer.phone) return;
            try {
                const res = await fetch('/pos/customer', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify(this.newCustomer)
                });
                const data = await res.json();
                if (data.id) {
                    this.selectCustomer(data);
                    this.showNewCustomer = false;
                    this.newCustomer = { name: '', phone: '', address: '' };
                }
            } catch(e) {}
        },

        async placeOrder() {
            if (this.cart.length === 0 || this.processingOrder) return;
            this.processingOrder = true;
            try {
                const res = await fetch('/pos/order', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({
                        items: this.cart.map(i => ({ product_id: i.product_id, quantity: i.quantity, unit_price: i.price, color_id: i.color_id || null, serial_number: i.serial_number || null })),
                        discount: this.discountAmount,
                        payment_method: this.paymentMethod,
                        amount_paid: this.paymentMethod === 'partial' ? this.partialAmountPaid : null,
                        cash_amount: this.paymentMethod === 'split' ? this.splitCash : null,
                        bank_amount: this.paymentMethod === 'split' ? this.splitBank : null,
                        bank_account_id: ['bank_transfer', 'split'].includes(this.paymentMethod) ? this.bankAccountId : null,
                        customer_id: this.selectedCustomer?.id || null,
                        notes: this.orderNotes || null,
                        cash_received: this.cashReceived,
                        promise_date: ['khata','partial'].includes(this.paymentMethod) ? (this.promiseDate || null) : null,
                        exchange_item_name: null,
                        exchange_value: 0,
                    })
                });
                const data = await res.json();
                if (data.success && data.order_id) {
                    this.clearCart();
                    this.selectedCustomer = null;
                    this.orderNotes = '';
                    this.paymentMethod = 'cash';
                    window.open(`/pos/receipt/${data.order_id}`, '_blank', 'width=400,height=700');
                    if (this.selectedCategory) this.loadProducts(); // reload same category
                    window.dispatchEvent(new CustomEvent('pos:order-placed'));
                } else {
                    alert(data.error || data.message || 'Order failed. Please try again.');
                }
            } catch(e) { alert('Network error. Please try again.'); }
            this.processingOrder = false;
        },

        async openSession(cash) {
            try {
                const res = await fetch('/pos/session/open', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ opening_cash: parseFloat(cash) || 0 })
                });
                if (res.ok) { location.reload(); }
            } catch(e) {}
        },

        async closeSession() {
            try {
                const res = await fetch('/pos/session/close', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                });
                if (res.ok) { location.reload(); }
            } catch(e) {}
        },

        // ── Hold Order ──────────────────────────────────────────────────────
        holdOrder() {
            if (this.cart.length === 0) return;
            const label = this.selectedCustomer?.name
                || (this.orderNotes?.trim() ? this.orderNotes.substring(0, 24) : null)
                || `Hold ${this.heldOrders.length + 1}`;
            this.heldOrders.push({
                id:           Date.now(),
                label:        label,
                cart:         JSON.parse(JSON.stringify(this.cart)),
                customer:     this.selectedCustomer ? { ...this.selectedCustomer } : null,
                discountType: this.discountType,
                discountValue: this.discountValue,
                paymentMethod: this.paymentMethod,
                orderNotes:   this.orderNotes,
                total:        this.total,
                subtotal:     this.subtotal,
                itemCount:    this.cart.reduce((s, i) => s + i.quantity, 0),
                heldAt:       new Date().toLocaleTimeString('en-PK', { hour: '2-digit', minute: '2-digit' }),
            });
            this._saveHeldOrders();
            // Clear POS for the new customer
            this.cart              = [];
            this.selectedCustomer  = null;
            this.discountType      = 'flat';
            this.discountValue     = 0;
            this.orderNotes        = '';
            this.paymentMethod     = 'cash';
            this.recalculate();
            this.$refs.barcodeInput?.focus();
        },

        resumeHeldOrder(idx) {
            // If something is already in the cart, auto-hold it first
            if (this.cart.length > 0) this.holdOrder();
            const held           = this.heldOrders[idx];
            this.cart            = held.cart;
            this.selectedCustomer = held.customer;
            this.discountType    = held.discountType  || 'flat';
            this.discountValue   = held.discountValue || 0;
            this.paymentMethod   = held.paymentMethod || 'cash';
            this.orderNotes      = held.orderNotes    || '';
            this.heldOrders.splice(idx, 1);
            this._saveHeldOrders();
            this.showHeldOrders = false;
            this.recalculate();
            this.$refs.barcodeInput?.focus();
        },

        deleteHeldOrder(idx) {
            if (!confirm('Remove this held order? It cannot be recovered.')) return;
            this.heldOrders.splice(idx, 1);
            this._saveHeldOrders();
        },

        _saveHeldOrders() {
            try { localStorage.setItem('pos_held_orders', JSON.stringify(this.heldOrders)); } catch(e) {}
        },
    };
}

function posStats() {
    return {
        showDailyModal: false,
        activeTab: 'sales',
        stats: @json($_posStats),
        orders: [],
        returns: [],
        payments: [],
        activityLoading: false,

        get netRevenue() {
            return this.stats.total_revenue - this.stats.total_refunded;
        },

        init() {
            this.$watch('showDailyModal', val => { if (val) this.loadActivity(); });
            window.addEventListener('pos:order-placed', () => { this.refresh(); this.loadActivity(); });
        },

        async refresh() {
            try {
                const res = await fetch('/pos/stats');
                if (res.ok) this.stats = await res.json();
            } catch(e) { console.error('Stats refresh failed', e); }
        },

        async loadActivity() {
            this.activityLoading = true;
            try {
                const res = await fetch('/pos/today-activity');
                if (res.ok) {
                    const data = await res.json();
                    this.orders   = data.orders   ?? [];
                    this.returns  = data.returns  ?? [];
                    this.payments = data.payments ?? [];
                }
            } catch(e) { console.error('Activity load failed', e); }
            this.activityLoading = false;
        },

        pmLabel(method) {
            const map = {
                cash:          { label: 'Cash',    cls: 'bg-green-100 text-green-700' },
                bank_transfer: { label: 'Bank',    cls: 'bg-blue-100 text-blue-700' },
                khata:         { label: 'Khata',   cls: 'bg-red-100 text-red-700' },
                partial:       { label: 'Partial', cls: 'bg-orange-100 text-orange-700' },
                split:         { label: 'Split',   cls: 'bg-teal-100 text-teal-700' },
            };
            return map[method] || { label: method, cls: 'bg-gray-100 text-gray-600' };
        },

        async deleteSale(orderId, orderNumber, idx) {
            if (!confirm(`Delete sale ${orderNumber}?\n\nThis will restore all stock and reverse any Khata entries. This cannot be undone.`)) return;
            try {
                const res = await fetch(`/pos/orders/${orderId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept':       'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    this.orders.splice(idx, 1);
                    await this.refresh();
                    window.dispatchEvent(new CustomEvent('pos:sale-deleted'));
                } else {
                    alert(data.error || 'Delete failed.');
                }
            } catch(e) {
                alert('Network error. Please try again.');
            }
        },

        fmt(n) {
            return Number(n || 0).toLocaleString();
        },
    };
}
</script>
@endpush
@endsection
