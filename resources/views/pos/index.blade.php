@extends('layouts.pos')

@section('content')
<div class="pos-grid no-print" x-data="posApp()" x-init="init()" @keydown.f1.window.prevent="canViewCost && (showCostPrice = !showCostPrice)">

    {{-- ===== BOOT LOADER (covers the Alpine FOUC while the POS initialises) ===== --}}
    {{-- No x-cloak: this must be visible on first paint, before Alpine boots. --}}
    <div x-show="loading"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="z-index: 200"
         class="fixed inset-0 bg-white flex flex-col items-center justify-center gap-4">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center"
             style="background: linear-gradient(135deg, var(--app-primary, #e11d48), var(--app-secondary, #be123c))">
            <i class="fas fa-cash-register text-white text-xl"></i>
        </div>
        <i class="fas fa-spinner fa-spin text-2xl" style="color: var(--app-primary, #e11d48)"></i>
        <p class="text-sm text-gray-400 font-medium">Loading POS…</p>
    </div>

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

            {{-- Session info (desktop) --}}
            <div class="shrink-0 hidden md:flex items-center gap-2">
                {{-- Mode indicator (click to view offline data / queued sales) --}}
                <button @click="showOfflineModal = true"
                        class="text-xs px-2.5 py-1.5 rounded-lg font-semibold flex items-center gap-1.5 shrink-0 transition-colors"
                        :class="offlineMode ? 'bg-red-100 text-red-700 hover:bg-red-200' : (isOnline ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-amber-100 text-amber-700 hover:bg-amber-200')"
                        title="View offline data & queued sales">
                    <span class="w-2 h-2 rounded-full"
                          :class="offlineMode ? 'bg-red-500 animate-pulse' : (isOnline ? 'bg-green-500' : 'bg-amber-500 animate-pulse')"></span>
                    <span x-text="offlineMode ? 'Offline Mode' : (isOnline ? 'Online' : 'No Internet')"></span>
                </button>

                {{-- Go Offline / Go Online toggle --}}
                <template x-if="!offlineMode">
                    <button @click="goOffline()" :disabled="preparingOffline"
                            class="text-xs bg-indigo-100 hover:bg-indigo-200 text-indigo-700 px-3 py-1.5 rounded-lg font-semibold transition-colors flex items-center gap-1.5 disabled:opacity-60 disabled:cursor-wait">
                        <i class="fas" :class="preparingOffline ? 'fa-spinner fa-spin' : 'fa-download'"></i>
                        <span x-text="preparingOffline ? 'Downloading…' : 'Go Offline'"></span>
                    </button>
                </template>
                <template x-if="offlineMode">
                    <button @click="goOnline()" :disabled="syncing"
                            class="text-xs bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg font-semibold transition-colors flex items-center gap-1.5 disabled:opacity-70 disabled:cursor-wait">
                        <i class="fas" :class="syncing ? 'fa-spinner fa-spin' : 'fa-cloud-upload-alt'"></i>
                        <span x-text="syncing ? 'Syncing…' : ('Go Online' + (offlineOrders.length > 0 ? ' (' + offlineOrders.length + ')' : ''))"></span>
                    </button>
                </template>

                {{-- Review queued offline sales --}}
                <button x-show="offlineOrders.length > 0" @click="showOfflineModal = true"
                        class="relative text-xs bg-blue-100 hover:bg-blue-200 text-blue-700 px-2.5 py-1.5 rounded-lg font-medium transition-colors flex items-center gap-1.5"
                        title="Review queued offline sales">
                    <i class="fas fa-receipt"></i>
                    <span x-text="offlineOrders.length"></span>
                </button>

                {{-- View toggle --}}
                <button @click="showViewModal = true"
                        class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 px-2.5 py-1.5 rounded-lg font-medium transition-colors flex items-center gap-1.5"
                        :title="posView === 'category' ? 'Category View — click to switch' : 'Product View — click to switch'">
                    <i class="fas" :class="posView === 'category' ? 'fa-th-large' : 'fa-boxes'"></i>
                    <span class="hidden xl:inline" x-text="posView === 'category' ? 'Categories' : 'Products'"></span>
                </button>

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
                <a href="{{ route(auth()->user()->panelPrefix() . '.dashboard') }}"
                   class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg font-medium transition-colors">
                    <i class="fas fa-th-large mr-1"></i>Dashboard
                </a>
            </div>

            {{-- Hamburger menu (mobile) --}}
            <div class="md:hidden relative shrink-0">
                <button @click="showMobileMenu = !showMobileMenu"
                        class="w-9 h-9 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors relative">
                    <i class="fas fa-bars"></i>
                    {{-- Pending offline orders dot (blue) takes priority over held (amber) --}}
                    <span x-show="offlineOrders.length > 0"
                          class="absolute -top-1 -right-1 w-3 h-3 bg-blue-500 rounded-full border-2 border-white"></span>
                    <span x-show="offlineOrders.length === 0 && heldOrders.length > 0"
                          class="absolute -top-1 -right-1 w-3 h-3 bg-amber-500 rounded-full border-2 border-white"></span>
                </button>
                <div x-show="showMobileMenu" @click.outside="showMobileMenu = false" x-transition
                     class="absolute right-0 top-full mt-2 w-52 bg-white border border-gray-200 rounded-xl shadow-xl z-30 overflow-hidden py-1">
                    {{-- Mode + offline controls (mobile) --}}
                    <div class="px-4 py-2 flex items-center gap-2 border-b border-gray-100">
                        <span class="w-2 h-2 rounded-full"
                              :class="offlineMode ? 'bg-red-500 animate-pulse' : (isOnline ? 'bg-green-500' : 'bg-amber-500 animate-pulse')"></span>
                        <span class="text-xs font-semibold"
                              :class="offlineMode ? 'text-red-600' : (isOnline ? 'text-green-600' : 'text-amber-600')"
                              x-text="offlineMode ? 'Offline Mode' : (isOnline ? 'Online' : 'No Internet')"></span>
                    </div>
                    <button x-show="!offlineMode" @click="goOffline()" :disabled="preparingOffline"
                            class="w-full text-left px-4 py-2.5 text-sm text-indigo-700 hover:bg-indigo-50 flex items-center gap-2.5 disabled:opacity-60">
                        <i class="fas w-4 text-center" :class="preparingOffline ? 'fa-spinner fa-spin' : 'fa-download'"></i>
                        <span x-text="preparingOffline ? 'Downloading…' : 'Go Offline'"></span>
                    </button>
                    <button x-show="offlineMode" @click="goOnline()" :disabled="syncing"
                            class="w-full text-left px-4 py-2.5 text-sm text-green-700 hover:bg-green-50 flex items-center gap-2.5 disabled:opacity-60">
                        <i class="fas w-4 text-center" :class="syncing ? 'fa-spinner fa-spin' : 'fa-cloud-upload-alt'"></i>
                        <span x-text="syncing ? 'Syncing…' : ('Go Online' + (offlineOrders.length > 0 ? ' (' + offlineOrders.length + ')' : ''))"></span>
                    </button>
                    <button x-show="offlineOrders.length > 0" @click="showOfflineModal = true; showMobileMenu = false"
                            class="w-full text-left px-4 py-2.5 text-sm text-blue-700 hover:bg-blue-50 flex items-center gap-2.5">
                        <i class="fas fa-receipt w-4 text-center"></i>
                        <span x-text="'Queued Sales (' + offlineOrders.length + ')'"></span>
                    </button>
                    <button @click="showViewModal = true; showMobileMenu = false"
                            class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2.5 border-b border-gray-100">
                        <i class="fas w-4 text-center" :class="posView === 'category' ? 'fa-th-large' : 'fa-boxes'"></i>
                        <span x-text="posView === 'category' ? 'Category View' : 'Product View'"></span>
                    </button>
                    @if($session)
                    <div class="px-4 py-2 text-xs text-gray-400 border-b border-gray-100">
                        Session: <span class="font-semibold text-green-600">Open</span>
                    </div>
                    <button @click="showCloseSession = true; showMobileMenu = false"
                            class="w-full text-left px-4 py-2.5 text-sm text-red-700 hover:bg-red-50 flex items-center gap-2.5">
                        <i class="fas fa-door-closed w-4 text-center"></i> Close Session
                    </button>
                    @else
                    <button @click="showOpenSession = true; showMobileMenu = false"
                            class="w-full text-left px-4 py-2.5 text-sm text-green-700 hover:bg-green-50 flex items-center gap-2.5">
                        <i class="fas fa-door-open w-4 text-center"></i> Open Session
                    </button>
                    @endif
                    <button x-show="heldOrders.length > 0" @click="showHeldOrders = true; showMobileMenu = false"
                            class="w-full text-left px-4 py-2.5 text-sm text-amber-700 hover:bg-amber-50 flex items-center gap-2.5">
                        <i class="fas fa-layer-group w-4 text-center"></i>
                        <span x-text="'Held Orders (' + heldOrders.length + ')'"></span>
                    </button>
                    <a href="{{ route('pos.return.index') }}"
                       class="block px-4 py-2.5 text-sm text-purple-700 hover:bg-purple-50 flex items-center gap-2.5">
                        <i class="fas fa-undo w-4 text-center"></i> Return
                    </a>
                    <a href="{{ route('pos.exchange.index') }}"
                       class="block px-4 py-2.5 text-sm text-indigo-700 hover:bg-indigo-50 flex items-center gap-2.5">
                        <i class="fas fa-sync-alt w-4 text-center"></i> Exchange
                    </a>
                    <a href="{{ route(auth()->user()->panelPrefix() . '.dashboard') }}"
                       class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2.5 border-t border-gray-100">
                        <i class="fas fa-th-large w-4 text-center"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>

        {{-- Offline mode banner --}}
        <div x-show="offlineMode" x-cloak
             class="bg-red-600 text-white px-4 py-2 flex items-center justify-between gap-3 text-sm">
            <div class="flex items-center gap-2 min-w-0">
                <i class="fas fa-wifi-slash shrink-0"></i>
                <span class="font-semibold">Offline Mode</span>
                <span class="hidden sm:inline text-red-100 text-xs truncate">
                    — sales are saved on this device. Click <strong>Go Online</strong> to sync.
                </span>
            </div>
            <button @click="goOnline()" :disabled="syncing"
                    class="shrink-0 bg-white text-red-700 hover:bg-red-50 font-semibold text-xs px-3 py-1 rounded-lg transition-colors disabled:opacity-70 disabled:cursor-wait flex items-center gap-1.5">
                <i class="fas" :class="syncing ? 'fa-spinner fa-spin' : 'fa-cloud-upload-alt'"></i>
                <span x-text="syncing ? 'Syncing…' : ('Go Online' + (offlineOrders.length > 0 ? ' (' + offlineOrders.length + ')' : ''))"></span>
            </button>
        </div>

        {{-- Parent category grid (shown when no search + no parent selected + no product category + category view) --}}
        <div class="p-4 flex-1 overflow-y-auto" x-show="!searchQuery && !activeParent && !selectedCategory && posView === 'category'">
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

        {{-- Subcategory grid (shown when a parent is active but no leaf category selected + category view) --}}
        <div class="flex-1 flex flex-col overflow-hidden" x-show="!searchQuery && activeParent && !selectedCategory && posView === 'category'">
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

                    {{-- "All [Parent]" tile --}}
                    <div @click="selectedCategory = { id: activeParent.id, name: 'All ' + activeParent.name }; loadProducts()"
                         class="cursor-pointer group bg-white rounded-xl border-2 border-dashed shadow-sm hover:shadow-md transition-all overflow-hidden flex flex-col"
                         style="border-color: var(--app-primary, #e11d48);">
                        <div class="aspect-square overflow-hidden flex items-center justify-center" style="background: #fff5f7;">
                            <i class="fas fa-layer-group text-3xl transition-colors" style="color: var(--app-primary, #e11d48);"></i>
                        </div>
                        <div class="px-2 py-1.5">
                            <div class="text-xs font-semibold leading-tight truncate" style="color: var(--app-primary, #e11d48);" x-text="'All ' + activeParent.name"></div>
                        </div>
                    </div>

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

        {{-- Product grid (shown when searching OR a leaf category is selected OR in product view) --}}
        <div class="flex-1 flex flex-col overflow-hidden" x-show="searchQuery || selectedCategory || posView === 'product'">
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
                            <div class="text-xs font-bold"
                                 :class="tilePriceLabel(product) === 'Out of stock' ? 'text-red-500' : 'text-primary-700'"
                                 x-text="tilePriceLabel(product)"></div>
                            <div class="text-xs mt-0.5" :class="product.stock <= 0 ? 'text-red-500' : 'text-gray-400'"
                                 x-text="product.stock <= 0 ? 'Out of Stock' : `Stock: ${product.stock}`"></div>
                        </div>
                    </template>
                    <template x-if="displayProducts.length === 0 && !loading">
                        <div class="col-span-5 text-center py-12 text-gray-400">
                            <i class="fas fa-box-open text-4xl mb-3"></i>
                            <p class="text-sm">No products found</p>
                            {{-- Offline diagnostic --}}
                            <template x-if="effectiveOffline()">
                                <div class="mt-2 text-xs">
                                    <p x-show="catalog.length === 0" class="text-red-500">
                                        No products are cached for offline use. Go online and click <strong>Go Offline</strong> again to download them.
                                    </p>
                                    <p x-show="catalog.length > 0 && !catalogHasCategoryData" class="text-amber-600 max-w-sm mx-auto">
                                        <span x-text="catalog.length"></span> products cached, but without category info.
                                        Open the status pill → <strong>Refresh</strong> (online) to update the offline data.
                                    </p>
                                    <p x-show="catalog.length > 0 && catalogHasCategoryData" class="text-gray-400">
                                        <span x-text="catalog.length"></span> products cached — none in this category. Try another category or search by name.
                                    </p>
                                </div>
                            </template>
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
    <div class="pos-cart bg-white border-l border-gray-200" :class="showMobileCart ? 'mobile-open' : ''">

        {{-- Cart header --}}
        <div class="px-4 py-2 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                {{-- Back to products (mobile only) --}}
                <button @click="showMobileCart = false"
                        class="lg:hidden w-8 h-8 -ml-1 flex items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                </button>
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
                           placeholder="Search by name or phone number..."
                           class="w-full text-xs px-3 py-1.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary-500">
                    <div x-show="customerSearch.length >= 2"
                         class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-20 overflow-hidden">
                        <template x-for="cust in customerResults" :key="cust.type + '_' + cust.id">
                            <div @click="selectCustomer(cust)"
                                 class="px-3 py-2 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0 flex items-center justify-between gap-2">
                                <div>
                                    <div class="text-xs font-semibold text-gray-800" x-text="cust.name"></div>
                                    <div class="text-xs text-gray-500" x-text="cust.phone"></div>
                                </div>
                                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded shrink-0"
                                      :class="cust.type === 'vendor' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                                      x-text="cust.type === 'vendor' ? 'Vendor' : 'Customer'"></span>
                            </div>
                        </template>
                        <div x-show="customerResults.length === 0"
                             class="px-3 py-2.5 flex items-center gap-2 text-xs text-gray-400 border-b border-gray-100">
                            <i class="fas fa-search"></i>
                            <span>No customer found for "<span class="font-medium text-gray-600" x-text="customerSearch"></span>"</span>
                        </div>
                        <div @click="showNewCustomer = true; customerResults = []; customerSearch = ''"
                             class="px-3 py-2 hover:bg-primary-50 cursor-pointer text-xs text-primary-600 font-medium flex items-center gap-1.5">
                            <i class="fas fa-user-plus"></i> Add new customer
                        </div>
                    </div>
                </div>
            </div>
            <div x-show="selectedCustomer" class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-1.5">
                        <div class="text-xs font-semibold text-gray-800" x-text="selectedCustomer?.name"></div>
                        <span class="text-[9px] font-bold px-1 py-0.5 rounded"
                              :class="selectedCustomer?.type === 'vendor' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                              x-text="selectedCustomer?.type === 'vendor' ? 'Vendor' : 'Customer'"></span>
                    </div>
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
                            {{-- Stock error --}}
                            <template x-if="item.stockError">
                                <p class="text-[10px] text-red-500 font-semibold mt-1 flex items-center gap-1">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <span>Cannot sell more — only <span x-text="item.stock"></span> unit(s) in stock</span>
                                </p>
                            </template>
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
        <div class="pos-cart-totals border-t border-gray-200 px-3 py-2 space-y-2">
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

            {{-- Khata-not-enabled warning --}}
            <div x-show="khataBlockMsg" x-transition
                 class="flex items-center gap-2 text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                <i class="fas fa-lock text-amber-500 shrink-0"></i>
                <span x-text="khataBlockMsg"></span>
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
            <div x-show="paymentMethod === 'partial'" class="space-y-1.5 bg-orange-50 border border-orange-200 rounded-xl p-2">
                <div class="text-xs font-semibold text-orange-700 mb-1">
                    <i class="fas fa-code-branch mr-1"></i> Partial Payment
                    (<span x-text="partialPayVia === 'bank' ? 'Bank' : 'Cash'"></span> + Khata)
                </div>

                {{-- Cash / Bank toggle --}}
                <div class="flex gap-1.5">
                    <button @click="partialPayVia = 'cash'; partialBankId = ''"
                            :class="partialPayVia === 'cash' ? 'bg-green-500 text-white border-green-500' : 'bg-white text-gray-600 border-gray-300 hover:border-green-300'"
                            class="flex-1 text-xs font-semibold border rounded-lg py-1 transition-colors">
                        <i class="fas fa-money-bill-wave mr-1"></i> Cash
                    </button>
                    <button @click="partialPayVia = 'bank'"
                            :class="partialPayVia === 'bank' ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-gray-600 border-gray-300 hover:border-blue-300'"
                            class="flex-1 text-xs font-semibold border rounded-lg py-1 transition-colors">
                        <i class="fas fa-university mr-1"></i> Bank
                    </button>
                </div>

                {{-- Bank account selector --}}
                @if($bankAccounts->count())
                <div x-show="partialPayVia === 'bank'" x-transition>
                    <select x-model="partialBankId"
                            class="w-full text-sm border border-blue-300 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-blue-400 bg-white font-medium">
                        <option value="">— Choose bank account —</option>
                        @foreach($bankAccounts as $bank)
                        <option value="{{ $bank->id }}">
                            {{ $bank->label }} — {{ $bank->bank_name }}{{ $bank->account_number ? ' · '.$bank->account_number : '' }}
                        </option>
                        @endforeach
                    </select>
                    <p x-show="!partialBankId && partialPayVia === 'bank'" class="text-xs text-orange-500 font-medium mt-0.5">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Please select a bank account.
                    </p>
                </div>
                @endif

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
                        <span x-text="partialPayVia === 'bank' ? 'Bank Received:' : 'Cash Received:'"></span>
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

    {{-- ===== MOBILE FLOATING CART BAR (above the daily summary bar) ===== --}}
    <div class="lg:hidden fixed left-0 right-0 z-40 no-print" style="bottom: 36px;">
        <button @click="showMobileCart = true"
                class="w-full h-14 px-4 flex items-center justify-between text-white shadow-[0_-4px_12px_rgba(0,0,0,0.15)]"
                style="background: var(--app-gradient, linear-gradient(135deg, #f43f5e 0%, #be123c 100%));">
            <span class="flex items-center gap-3 text-sm font-semibold">
                <span class="relative">
                    <i class="fas fa-shopping-cart text-lg"></i>
                    <span x-show="cart.length > 0"
                          class="absolute -top-2 -right-2.5 min-w-[18px] h-[18px] px-1 bg-white text-primary-700 text-[10px] font-bold rounded-full flex items-center justify-center"
                          x-text="cart.reduce((s, i) => s + i.quantity, 0)"></span>
                </span>
                <span x-text="cart.length === 0 ? 'Cart is empty' : cart.reduce((s, i) => s + i.quantity, 0) + ' item(s)'"></span>
            </span>
            <span class="flex items-center gap-2 text-base font-bold">
                <span x-text="`Rs. ${total.toLocaleString()}`"></span>
                <i class="fas fa-chevron-up text-xs opacity-80"></i>
            </span>
        </button>
    </div>

    {{-- Serial Prompt Modal — appears when a serialized product tile is clicked --}}
    <template x-teleport="body">
        <div x-show="serialPromptProduct" x-transition
             class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
             @keydown.window.escape="serialPromptProduct = null">
            <div class="bg-white rounded-2xl shadow-2xl flex flex-col w-[480px] max-w-full" style="max-height: 85vh;">

                {{-- Header --}}
                <div class="flex items-center gap-3 p-5 border-b border-gray-100 shrink-0">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center shrink-0">
                        <i class="fas fa-barcode text-indigo-600 text-lg"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-bold text-gray-900 text-base truncate" x-text="serialPromptProduct?.name"></h3>
                        <p class="text-xs text-gray-500">
                            <span x-text="serialPromptSerials.length"></span> unit<span x-show="serialPromptSerials.length !== 1">s</span> in stock
                        </p>
                    </div>
                    <button @click="serialPromptProduct = null" class="text-gray-400 hover:text-gray-600 transition-colors p-1 shrink-0">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>

                {{-- Search / scan input --}}
                <div class="px-5 pt-4 shrink-0">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="text"
                               id="serialPromptInput"
                               x-model="serialPromptInput"
                               @keydown.enter.prevent="confirmSerialPrompt()"
                               @keydown.escape="serialPromptProduct = null"
                               @input="serialPromptError = ''"
                               placeholder="Scan barcode or type IMEI to filter..."
                               autocomplete="off"
                               class="w-full border border-gray-300 rounded-xl pl-9 pr-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div x-show="serialPromptError" class="mt-2 flex items-start gap-1.5 text-xs text-red-600">
                        <i class="fas fa-exclamation-circle mt-0.5 shrink-0"></i>
                        <span x-text="serialPromptError"></span>
                    </div>
                </div>

                {{-- Serial list --}}
                <div class="flex-1 overflow-y-auto px-5 pt-3 pb-2 min-h-0">

                    {{-- Loading state --}}
                    <template x-if="serialPromptLoading && serialPromptSerials.length === 0">
                        <div class="flex items-center justify-center py-10 text-gray-400 gap-2">
                            <i class="fas fa-spinner fa-spin"></i>
                            <span class="text-sm">Loading serials...</span>
                        </div>
                    </template>

                    {{-- Empty state --}}
                    <template x-if="!serialPromptLoading && serialPromptSerials.length === 0">
                        <div class="flex flex-col items-center justify-center py-10 text-gray-400 gap-2">
                            <i class="fas fa-inbox text-3xl"></i>
                            <span class="text-sm">No units in stock</span>
                        </div>
                    </template>

                    {{-- Filtered list --}}
                    <template x-if="serialPromptSerials.length > 0">
                        <div class="space-y-2">
                            <template x-for="s in serialPromptFilteredSerials" :key="s.id">
                                <button type="button"
                                        @click="pickSerial(s)"
                                        :class="cart.find(i => i.serial_number === s.serial_number) ? 'opacity-40 cursor-not-allowed' : 'hover:bg-indigo-50 hover:border-indigo-400 cursor-pointer'"
                                        :disabled="!!cart.find(i => i.serial_number === s.serial_number)"
                                        class="w-full flex items-center gap-3 p-3 rounded-xl border border-gray-200 bg-white transition-all text-left group">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                                        <i class="fas fa-fingerprint text-indigo-500 text-sm"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-mono font-semibold text-gray-800 truncate" x-text="s.serial_number"></div>
                                        <template x-if="Object.keys(s.attributes || {}).length > 0">
                                            <div class="flex flex-wrap gap-1 mt-0.5">
                                                <template x-for="[k, v] in Object.entries(s.attributes || {})" :key="k">
                                                    <span class="text-[10px] bg-gray-100 text-gray-600 rounded px-1.5 py-0.5 font-medium"
                                                          x-text="`${k}: ${v}`"></span>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="cart.find(i => i.serial_number === s.serial_number)">
                                            <span class="text-[10px] text-orange-600 font-semibold mt-0.5 block">Already in cart</span>
                                        </template>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="text-sm font-bold text-indigo-700" x-text="`Rs. ${Number(s.selling_price).toLocaleString()}`"></div>
                                        <div class="text-[10px] text-gray-400 group-hover:text-indigo-500">click to add</div>
                                    </div>
                                </button>
                            </template>

                            {{-- No match for filter --}}
                            <template x-if="serialPromptFilteredSerials.length === 0 && serialPromptInput.trim()">
                                <div class="text-center py-6 text-gray-400 text-sm">
                                    No serial matching "<span x-text="serialPromptInput.trim()" class="font-mono"></span>"
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                {{-- Footer --}}
                <div class="px-5 py-4 border-t border-gray-100 shrink-0">
                    <button @click="serialPromptProduct = null"
                            class="w-full py-2.5 px-4 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- Color Picker Modal — kept inside posApp() so it can access colorPickerProduct --}}
    <template x-teleport="body">
        <div x-show="colorPickerProduct" x-transition class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl p-6 w-80 max-w-full" @click.outside="colorPickerProduct = null">
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

    {{-- ── Offline Orders / Sync Modal ───────────────────────────────────── --}}
    <template x-teleport="body">
        <div x-show="showOfflineModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
             @keydown.window.escape="showOfflineModal = false"
             @click.self="showOfflineModal = false">

            <div class="bg-white rounded-2xl shadow-2xl flex flex-col"
                 style="width:480px; max-width:100%; max-height:85vh; min-height:0;">

                {{-- Header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100" style="flex-shrink:0;">
                    <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                        <span class="w-7 h-7 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-cloud-upload-alt text-blue-600 text-sm"></i>
                        </span>
                        Offline Orders
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-semibold"
                              x-text="offlineOrders.length"></span>
                    </h3>
                    <button @click="showOfflineModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>

                {{-- Connectivity + sync action bar --}}
                <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between gap-3" style="flex-shrink:0;">
                    <div class="flex items-center gap-2 text-sm">
                        <span class="w-2 h-2 rounded-full" :class="isOnline ? 'bg-green-500' : 'bg-red-500 animate-pulse'"></span>
                        <span class="font-medium" :class="isOnline ? 'text-green-700' : 'text-red-600'"
                              x-text="isOnline ? 'Online — ready to sync' : 'Offline — connect to sync'"></span>
                    </div>
                    <button @click="syncOfflineOrders()"
                            :disabled="syncing || offlineOrders.length === 0 || !isOnline"
                            class="btn-primary btn-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!syncing"><i class="fas fa-sync-alt mr-1.5"></i> Sync Now</span>
                        <span x-show="syncing"><i class="fas fa-spinner fa-spin mr-1.5"></i> Syncing...</span>
                    </button>
                </div>

                {{-- Last sync report --}}
                <template x-if="lastSyncReport">
                    <div class="px-5 py-2.5 text-xs border-b border-gray-100" style="flex-shrink:0;"
                         :class="lastSyncReport.failed > 0 ? 'bg-amber-50 text-amber-800' : 'bg-green-50 text-green-800'">
                        <i class="fas" :class="lastSyncReport.failed > 0 ? 'fa-exclamation-triangle' : 'fa-check-circle'"></i>
                        <span x-text="lastSyncReport.success + ' synced'"></span>
                        <template x-if="lastSyncReport.failed > 0">
                            <span>· <span x-text="lastSyncReport.failed"></span> still pending</span>
                        </template>
                        <template x-if="lastSyncReport.networkAborted">
                            <span> — connection lost during sync</span>
                        </template>
                    </div>
                </template>

                {{-- Scrollable body --}}
                <div style="flex:1 1 0%; overflow-y:auto; padding:12px 16px;">

                    {{-- Empty state --}}
                    <div x-show="offlineOrders.length === 0" class="text-center py-12 text-gray-400">
                        <i class="fas fa-cloud text-4xl mb-3"></i>
                        <p class="text-sm font-medium">No offline orders</p>
                        <p class="text-xs mt-1" style="color:#d1d5db;">Sales made while offline will queue here for syncing</p>
                    </div>

                    {{-- Offline order cards --}}
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <template x-for="(ord, idx) in offlineOrders" :key="ord.ref">
                            <div :style="ord.error
                                    ? 'border:1px solid #fecaca; background:#fef2f2; border-radius:12px; padding:14px;'
                                    : 'border:1px solid #bfdbfe; background:#eff6ff; border-radius:12px; padding:14px;'">

                                {{-- Top row: label + total --}}
                                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:8px; margin-bottom:6px;">
                                    <div style="min-width:0;">
                                        <div style="font-weight:600; font-size:14px; color:#1f2937; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" x-text="ord.label"></div>
                                        <div style="font-size:11px; color:#6b7280; display:flex; align-items:center; gap:6px; margin-top:2px; flex-wrap:wrap;">
                                            <i class="fas fa-clock" style="font-size:9px;"></i>
                                            <span x-text="ord.createdLabel"></span>
                                            <span style="color:#d1d5db;">·</span>
                                            <span x-text="ord.itemCount + (ord.itemCount === 1 ? ' item' : ' items')"></span>
                                            <span style="color:#d1d5db;">·</span>
                                            <span style="text-transform:capitalize;" x-text="ord.payment_method"></span>
                                        </div>
                                    </div>
                                    <div style="font-weight:700; font-size:14px; color:#1d4ed8; flex-shrink:0;"
                                         x-text="'Rs. ' + Number(ord.total).toLocaleString()"></div>
                                </div>

                                {{-- Item preview --}}
                                <div style="margin-bottom:10px; padding-left:6px; border-left:2px solid #bfdbfe;">
                                    <template x-for="(item, j) in ord.items.slice(0, 3)" :key="j">
                                        <div style="font-size:11px; color:#4b5563; display:flex; align-items:center; gap:5px; padding:1px 0; overflow:hidden;">
                                            <span style="display:inline-flex; align-items:center; justify-content:center; min-width:16px; height:16px; background:white; border:1px solid #bfdbfe; color:#2563eb; border-radius:50%; font-weight:700; font-size:9px; flex-shrink:0;"
                                                  x-text="item.qty"></span>
                                            <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" x-text="item.name"></span>
                                            <template x-if="item.serial">
                                                <span style="font-family:monospace; font-size:10px; color:#6366f1;" x-text="item.serial"></span>
                                            </template>
                                        </div>
                                    </template>
                                    <div x-show="ord.items.length > 3" style="font-size:10px; color:#9ca3af; padding-top:2px;"
                                         x-text="'+ ' + (ord.items.length - 3) + ' more'"></div>
                                </div>

                                {{-- Error message (sync failure) --}}
                                <template x-if="ord.error">
                                    <div style="background:white; border:1px solid #fecaca; border-radius:8px; padding:8px 10px; margin-bottom:10px; font-size:11px; color:#b91c1c; display:flex; gap:6px;">
                                        <i class="fas fa-exclamation-circle" style="margin-top:1px; flex-shrink:0;"></i>
                                        <span x-text="ord.error"></span>
                                    </div>
                                </template>

                                {{-- Actions --}}
                                <div style="display:flex; gap:8px;">
                                    <button @click="printOfflineReceipt(ord)"
                                            style="flex:1; font-size:12px; font-weight:600; padding:7px; border-radius:8px; border:1px solid #bfdbfe; background:white; color:#1d4ed8;">
                                        <i class="fas fa-print mr-1"></i> Reprint
                                    </button>
                                    <button @click="deleteOfflineOrder(idx)"
                                            style="flex:1; font-size:12px; font-weight:600; padding:7px; border-radius:8px; border:1px solid #fecaca; background:white; color:#b91c1c;">
                                        <i class="fas fa-trash-alt mr-1"></i> Discard
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-5 py-3 border-t border-gray-100 space-y-2" style="flex-shrink:0;">
                    {{-- Cached offline data diagnostic --}}
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500">
                            <i class="fas fa-database mr-1 text-gray-400"></i>
                            Offline data cached:
                            <span class="font-semibold text-gray-700" x-text="catalog.length + ' products'"></span>
                            ·
                            <span class="font-semibold text-gray-700" x-text="offlineCustomers.length + ' customers'"></span>
                        </span>
                        <button @click="preparingOffline = true; Promise.all([refreshCatalog(), refreshCustomers()]).then(() => { preparingOffline = false; })"
                                :disabled="preparingOffline"
                                class="text-blue-600 hover:text-blue-800 font-semibold disabled:opacity-50">
                            <i class="fas mr-1" :class="preparingOffline ? 'fa-spinner fa-spin' : 'fa-rotate'"></i>Refresh
                        </button>
                    </div>
                    <template x-if="catalogError">
                        <p class="text-xs text-red-600">
                            <i class="fas fa-exclamation-triangle mr-1"></i><span x-text="catalogError"></span>
                        </p>
                    </template>
                    {{-- Warn if the cached catalog has no category data (stale cache / outdated server) --}}
                    <template x-if="catalog.length > 0 && !catalogHasCategoryData">
                        <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-2 py-1.5">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Cached products have no category info, so category browsing won't work offline.
                            Click <strong>Refresh</strong> above (while online). If it stays this way, the server needs the latest update + caches cleared.
                        </p>
                    </template>
                    <p class="text-xs text-gray-400 text-center">
                        <i class="fas fa-info-circle mr-1"></i>
                        Synced orders post to the live database with their original sale time. Failed ones stay here for review.
                    </p>
                </div>
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

                    {{-- Loading spinner --}}
                    <div x-show="holdsLoading" class="text-center py-8 text-gray-400">
                        <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                        <p class="text-xs">Loading held orders...</p>
                    </div>

                    {{-- Empty state --}}
                    <div x-show="!holdsLoading && heldOrders.length === 0" class="text-center py-12 text-gray-400">
                        <i class="fas fa-inbox text-4xl mb-3"></i>
                        <p class="text-sm font-medium">No held orders</p>
                        <p class="text-xs mt-1" style="color:#d1d5db;">Press <strong style="color:#9ca3af;">Hold</strong> in the cart to park an order</p>
                    </div>

                    {{-- Held order cards --}}
                    <div x-show="!holdsLoading" style="display:flex; flex-direction:column; gap:12px;">
                        <template x-for="held in heldOrders" :key="held.id">
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
                                            <template x-if="!held.isOwn">
                                                <span style="display:flex; align-items:center; gap:3px; color:#7c3aed;">
                                                    <span style="color:#d1d5db;">·</span>
                                                    <i class="fas fa-user-tag" style="font-size:9px;"></i>
                                                    <span x-text="held.createdBy"></span>
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
                                    <button @click="resumeHeldOrder(held.id)"
                                            style="flex:1; background:#f59e0b; color:white; font-size:12px; font-weight:600; padding:8px 0; border-radius:10px; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; transition:background 0.15s;"
                                            onmouseover="this.style.background='#d97706'" onmouseout="this.style.background='#f59e0b'">
                                        <i class="fas fa-play" style="font-size:10px;"></i> Resume
                                    </button>
                                    <button @click="deleteHeldOrder(held.id)"
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
    <div x-show="showOpenSession" x-transition class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" x-data="{ cash: '' }">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-80 max-w-full" @click.outside="showOpenSession = false">
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
    <div x-show="showCloseSession" x-transition class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-80 max-w-full" @click.outside="showCloseSession = false">
            <h3 class="font-bold text-gray-900 text-lg mb-2">Close POS Session</h3>
            <p class="text-sm text-gray-500 mb-4">This will close the current session and generate a summary.</p>
            <div class="flex gap-3">
                <button @click="closeSession()" class="btn-danger flex-1 justify-center">Close Session</button>
                <button @click="showCloseSession = false" class="btn-outline flex-1 justify-center">Cancel</button>
            </div>
        </div>
    </div>

    {{-- View Picker Modal (shown on every POS load) --}}
    <div x-show="showViewModal" x-cloak
         class="fixed inset-0 bg-black/60 z-[60] flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-8">
            <div class="text-center mb-7">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background: linear-gradient(135deg, var(--app-primary, #e11d48), var(--app-secondary, #be123c))">
                    <i class="fas fa-cash-register text-white text-xl"></i>
                </div>
                <h2 class="text-lg font-bold text-gray-900">How do you want to browse?</h2>
                <p class="text-sm text-gray-400 mt-1">Choose your preferred product view</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <button @click="switchView('category')"
                        class="flex flex-col items-center gap-3 p-5 rounded-xl border-2 border-gray-200 hover:border-primary-400 hover:bg-primary-50 transition-all group cursor-pointer">
                    <div class="w-12 h-12 bg-gray-100 group-hover:bg-primary-100 rounded-xl flex items-center justify-center transition-colors">
                        <i class="fas fa-th-large text-gray-400 group-hover:text-primary-600 text-xl transition-colors"></i>
                    </div>
                    <div class="text-center">
                        <div class="font-semibold text-gray-800 text-sm">Category View</div>
                        <div class="text-xs text-gray-400 mt-0.5">Browse by category</div>
                    </div>
                </button>
                <button @click="switchView('product')"
                        class="flex flex-col items-center gap-3 p-5 rounded-xl border-2 border-gray-200 hover:border-primary-400 hover:bg-primary-50 transition-all group cursor-pointer">
                    <div class="w-12 h-12 bg-gray-100 group-hover:bg-primary-100 rounded-xl flex items-center justify-center transition-colors">
                        <i class="fas fa-boxes text-gray-400 group-hover:text-primary-600 text-xl transition-colors"></i>
                    </div>
                    <div class="text-center">
                        <div class="font-semibold text-gray-800 text-sm">Product View</div>
                        <div class="text-xs text-gray-400 mt-0.5">See all products</div>
                    </div>
                </button>
            </div>
        </div>
    </div>

    {{-- New Customer Modal --}}
    <div x-show="showNewCustomer" x-transition class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-96 max-w-full" @click.outside="showNewCustomer = false">
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
                <div class="flex items-start gap-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                    <input type="checkbox" id="pos_khata_enabled" x-model="newCustomer.khata_enabled"
                           class="mt-0.5 w-4 h-4 text-amber-600 rounded border-amber-300 focus:ring-amber-500">
                    <div>
                        <label for="pos_khata_enabled" class="text-sm font-semibold text-gray-800 cursor-pointer">Enable Khata / Ledger</label>
                        <p class="text-xs text-gray-500 mt-0.5">Allow khata and partial payment for this customer.</p>
                    </div>
                </div>
            </div>
            <div class="flex gap-3 mt-4">
                <button @click="createCustomer()" class="btn-primary flex-1 justify-center">Save Customer</button>
                <button @click="showNewCustomer = false; newCustomer = { name: '', phone: '', address: '', khata_enabled: false }" class="btn-outline flex-1 justify-center">Cancel</button>
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

        {{-- Vendor payments chip (only shown when there are any) --}}
        <template x-if="stats.vendor_pay_total > 0">
            <div class="flex items-center gap-1.5 shrink-0">
                <div class="w-px h-4 bg-gray-700 shrink-0"></div>
                <button @click="activeTab = 'vendors'; showDailyModal = true"
                        class="flex items-center gap-1.5 bg-orange-900/60 hover:bg-orange-800 text-orange-300 px-2.5 py-1 rounded-lg transition-colors">
                    <i class="fas fa-handshake"></i>
                    <span class="font-semibold" x-text="'– Rs. ' + fmt(stats.vendor_pay_total)"></span>
                    <span class="text-orange-400" x-text="stats.vendor_pay_count + ' vendor'"></span>
                </button>
            </div>
        </template>

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
                <template x-if="stats.vendor_pay_count > 0">
                    <button @click="activeTab = 'vendors'"
                            :class="activeTab === 'vendors' ? 'border-b-2 border-orange-500 text-orange-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                            class="py-2.5 px-4 text-sm transition-colors">
                        <i class="fas fa-handshake mr-1.5"></i>
                        Vendor Paid <span class="ml-1 text-xs bg-orange-100 text-orange-700 px-1.5 py-0.5 rounded-full font-semibold" x-text="stats.vendor_pay_count"></span>
                    </button>
                </template>
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

                {{-- ======== VENDOR PAYMENTS TABLE ======== --}}
                <div x-show="activeTab === 'vendors'" class="p-4">

                    <div class="grid grid-cols-3 gap-3 mb-4">
                        <div class="bg-orange-50 border border-orange-100 rounded-xl px-4 py-3 text-center">
                            <div class="text-lg font-bold text-orange-700" x-text="'Rs. ' + fmt(stats.vendor_pay_total)"></div>
                            <div class="text-xs text-orange-600">Total Paid Out</div>
                        </div>
                        <div class="bg-green-50 border border-green-100 rounded-xl px-4 py-3 text-center">
                            <div class="text-lg font-bold text-green-700" x-text="'Rs. ' + fmt(stats.vendor_pay_cash)"></div>
                            <div class="text-xs text-green-600">Cash Paid</div>
                        </div>
                        <div class="bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 text-center">
                            <div class="text-lg font-bold text-blue-700" x-text="'Rs. ' + fmt(stats.vendor_pay_bank)"></div>
                            <div class="text-xs text-blue-600">Bank Paid</div>
                        </div>
                    </div>

                    <div x-show="activityLoading" class="text-center py-16 text-gray-300">
                        <i class="fas fa-spinner fa-spin text-4xl mb-3"></i>
                        <p class="text-sm text-gray-400">Loading...</p>
                    </div>
                    <div x-show="!activityLoading && vendor_payments.length === 0" class="text-center py-16 text-gray-300">
                        <i class="fas fa-handshake text-4xl mb-3"></i>
                        <p class="text-sm text-gray-400">No vendor payments recorded today</p>
                    </div>
                    <div x-show="!activityLoading && vendor_payments.length > 0" class="overflow-x-auto rounded-xl border border-gray-100">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                                <tr>
                                    <th class="text-left px-4 py-2.5 font-semibold">Time</th>
                                    <th class="text-left px-4 py-2.5 font-semibold">Vendor</th>
                                    <th class="text-left px-4 py-2.5 font-semibold">Description</th>
                                    <th class="text-left px-4 py-2.5 font-semibold">Method</th>
                                    <th class="text-right px-4 py-2.5 font-semibold">Paid</th>
                                    <th class="text-right px-4 py-2.5 font-semibold">Balance After</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <template x-for="(v, idx) in vendor_payments" :key="idx">
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap" x-text="v.time"></td>
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-sm text-gray-800" x-text="v.vendor_name"></div>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-600 max-w-xs" x-text="v.description || '—'"></td>
                                        <td class="px-4 py-3 text-xs">
                                            <span x-show="v.payment_method === 'cash'"
                                                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-medium">
                                                <i class="fas fa-money-bill-wave text-[10px]"></i> Cash
                                            </span>
                                            <span x-show="v.payment_method === 'bank_transfer'"
                                                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-medium">
                                                <i class="fas fa-university text-[10px]"></i>
                                                <span x-text="v.bank_label || 'Bank'"></span>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="font-bold text-orange-600 text-sm" x-text="'– Rs. ' + fmt(v.amount)"></span>
                                        </td>
                                        <td class="px-4 py-3 text-right text-xs">
                                            <span class="font-semibold"
                                                  :class="v.balance_after > 0 ? 'text-red-500' : 'text-gray-500'"
                                                  x-text="v.balance_after > 0 ? 'Rs. ' + fmt(v.balance_after) + ' owed' : 'Settled'">
                                            </span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-sm font-bold text-gray-700"
                                        x-text="`Total — ${vendor_payments.length} payment(s)`"></td>
                                    <td colspan="2"></td>
                                    <td class="px-4 py-3 text-right font-bold text-lg text-orange-600"
                                        x-text="'– Rs. ' + fmt(vendor_payments.reduce((s,v) => s + v.amount, 0))"></td>
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
    'order_count'      => $todaySales->order_count ?? 0,
    'total_revenue'    => $todaySales->total_revenue ?? 0,
    'cash_total'       => $todaySales->cash_total ?? 0,
    'bank_total'       => $todaySales->bank_total ?? 0,
    'return_count'     => $todayReturns->return_count ?? 0,
    'total_refunded'   => $todayReturns->total_refunded ?? 0,
    'payment_count'    => $todayPayments->payment_count ?? 0,
    'total_collected'  => $todayPayments->total_collected ?? 0,
    'vendor_pay_count' => $todayVendorPayments->vendor_pay_count ?? 0,
    'vendor_pay_total' => $todayVendorPayments->vendor_pay_total ?? 0,
    'vendor_pay_cash'  => $todayVendorPayments->vendor_pay_cash ?? 0,
    'vendor_pay_bank'  => $todayVendorPayments->vendor_pay_bank ?? 0,
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
// Shop details for the client-side offline receipt (sub shop identity when applicable)
window.__posShop = {
    name:    @json($currentShop?->name ?? \App\Models\Setting::get('shop_name', 'MobileHub')),
    phone:   @json($currentShop?->phone ?? \App\Models\Setting::get('shop_phone')),
    address: @json($currentShop?->address ?? \App\Models\Setting::get('shop_address')),
};
// Offline caches are keyed per shop so one browser can't sell from the wrong shop's cache
const POS_CACHE_KEY = @json($currentShop?->code ?? 'main');
const posCacheKey = (name) => `${name}_${POS_CACHE_KEY}`;
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

        // Mobile UI state
        showMobileCart: false,
        showMobileMenu: false,
        discountType: 'flat',
        discountValue: 0,
        discountAmount: 0,
        subtotal: 0,
        total: 0,
        paymentMethod: 'cash',
        cashReceived: 0,
        partialAmountPaid: 0,
        partialPayVia: 'cash',
        partialBankId: '',
        promiseDate: '',
        splitCash: 0,
        splitBank: 0,
        bankAccountId: null,
        quickCash: [500, 1000, 2000, 5000],
        orderNotes: '',
        processingOrder: false,
        canViewCost: {{ $canViewCost ? 'true' : 'false' }},
        showCostPrice: false,
        colorPickerProduct: null,

        // Serial prompt (shown when a serialized product tile is clicked)
        serialPromptProduct:  null,
        serialPromptInput:    '',
        serialPromptError:    '',
        serialPromptLoading:  false,
        serialPromptSerials:  [],  // all in-stock serials for the product
        get serialPromptFilteredSerials() {
            const q = (this.serialPromptInput || '').trim().toLowerCase();
            if (!q) return this.serialPromptSerials;
            return this.serialPromptSerials.filter(s =>
                s.serial_number.toLowerCase().includes(q) ||
                Object.values(s.attributes || {}).some(v => String(v).toLowerCase().includes(q))
            );
        },

        // Customer
        customerSearch: '',
        customerResults: [],
        selectedCustomer: null,
        showNewCustomer: false,
        newCustomer: { name: '', phone: '', address: '', khata_enabled: false },
        khataBlockMsg: '',

        // Boot loader — masks the Alpine init flash until the page is ready
        loading: true,

        // View picker (category grid vs all products — asked on every page load)
        posView: null,
        showViewModal: true,

        // Held orders (parked carts — stored in DB)
        heldOrders: [],
        showHeldOrders: false,
        holdsLoading: false,

        // Offline mode + sync queue
        isOnline: navigator.onLine,   // live connectivity (heartbeat)
        offlineMode: false,           // manual offline mode (user-controlled)
        preparingOffline: false,      // downloading data for offline use
        offlineOrders: [],
        showOfflineModal: false,
        syncing: false,
        lastSyncReport: null,   // { success, failed, networkAborted }
        catalog: [],            // full product catalog cached for offline browsing
        catalogReady: false,
        catalogError: '',       // last catalog-download error (for diagnostics)
        offlineCustomers: [],   // customers + vendors cached for offline use

        // Session
        showOpenSession: false,
        showCloseSession: false,

        async init() {
            this.focusBarcode();
            // Load held orders from server (background — badge updates when done)
            this._fetchHolds();
            this.$watch('showHeldOrders', v => { if (v) this._fetchHolds(); });
            // Load any offline orders pending sync
            try { this.offlineOrders = JSON.parse(localStorage.getItem(posCacheKey('pos_offline_orders')) || '[]'); } catch(e) { this.offlineOrders = []; }
            // Load the cached product catalog + customers (for offline browsing)
            try { this.catalog = JSON.parse(localStorage.getItem(posCacheKey('pos_catalog')) || '[]'); } catch(e) { this.catalog = []; }
            try { this.offlineCustomers = JSON.parse(localStorage.getItem(posCacheKey('pos_offline_customers')) || '[]'); } catch(e) { this.offlineCustomers = []; }
            this.catalogReady = this.catalog.length > 0;
            // Restore manual offline mode so a refresh keeps the POS offline
            try { this.offlineMode = localStorage.getItem(posCacheKey('pos_offline_mode')) === '1'; } catch(e) { this.offlineMode = false; }
            // Restore last-used product view — skip the picker if already chosen
            const savedView = localStorage.getItem('pos_view');
            if (savedView === 'category' || savedView === 'product') {
                this.posView = savedView;
                this.showViewModal = false;
                if (savedView === 'product') this.loadProducts();
            }

            // The view decision is made — reveal the page (and the view picker if
            // it's a first-time load). $nextTick ensures Alpine has applied the
            // showViewModal state before we fade the loader out, so nothing flashes.
            this.$nextTick(() => { this.loading = false; });

            // Connectivity monitoring — browser events + a real heartbeat to the server
            window.addEventListener('online',  () => { this.checkConnection(); });
            window.addEventListener('offline', () => { this.isOnline = false; });
            this.checkConnection();
            setInterval(() => this.checkConnection(), 20000);

            // Poll held orders every 10 s so section-mates' holds appear without a refresh
            setInterval(() => this._pollHolds(), 10000);

            // Keep the offline cache fresh on every load. These fail gracefully
            // when genuinely offline (the existing cache is kept), and crucially
            // they are NOT gated by the heartbeat, so a missing /pos/ping route
            // can't prevent the catalog from downloading.
            // Also force a refresh if the cached catalog is in an old format that
            // lacks category data (self-heals a stale cache that can't browse).
            if (!this.offlineMode || (this.catalog.length > 0 && !this.catalogHasCategoryData)) {
                this.refreshCatalog();
                this.refreshCustomers();
            }

            // If we reloaded straight into offline mode, render products from cache
            if (this.offlineMode && this.selectedCategory) this.loadProducts();

            // Start on category grid — no products loaded until category selected or searched
            window.addEventListener('pos:sale-deleted', () => {
                if (this.searchQuery.length > 0) {
                    this.searchProducts();
                } else if (this.selectedCategory || this.posView === 'product') {
                    this.loadProducts();
                }
                // If in subcategory/category grid, no reload needed (no products shown)
            });
        },

        // Ping the server to confirm real connectivity (navigator.onLine only
        // reports the network interface, not whether the server is reachable).
        async checkConnection() {
            try {
                const res = await fetch('/pos/ping', { method: 'GET', cache: 'no-store' });
                this.isOnline = res.ok;
            } catch(e) {
                this.isOnline = false;
            }
        },

        // Behave offline when the user manually chose offline mode OR the
        // connection genuinely dropped (safety net so a sale is never lost).
        effectiveOffline() {
            return this.offlineMode || !this.isOnline;
        },

        // Download the full catalog and cache it for offline browsing.
        // NOT gated by the heartbeat — we just try the request, so a missing
        // /pos/ping route (e.g. stale route cache) can't block the download.
        // Returns true on success. Fails gracefully (keeps existing cache).
        async refreshCatalog() {
            try {
                const res = await fetch('/pos/catalog', { cache: 'no-store' });
                if (!res.ok) { this.catalogError = 'Server returned HTTP ' + res.status + ' for /pos/catalog'; return false; }
                const data = await res.json();
                if (Array.isArray(data)) {
                    this.catalog = data;
                    this.catalogReady = data.length > 0;
                    this.catalogError = '';
                    try { localStorage.setItem(posCacheKey('pos_catalog'), JSON.stringify(data)); } catch(e) { this.catalogError = 'Browser storage is full — could not save catalog.'; return false; }
                    return true;
                }
                this.catalogError = 'Unexpected catalog response.';
                return false;
            } catch(e) {
                this.catalogError = 'No connection while downloading catalog.';
                return false;
            }
        },

        // Download customers + vendors and cache them for offline use.
        async refreshCustomers() {
            try {
                const res = await fetch('/pos/customers-cache', { cache: 'no-store' });
                if (!res.ok) return false;
                const data = await res.json();
                if (Array.isArray(data)) {
                    this.offlineCustomers = data;
                    try { localStorage.setItem(posCacheKey('pos_offline_customers'), JSON.stringify(data)); } catch(e) {}
                    return true;
                }
                return false;
            } catch(e) { return false; }
        },

        // ── Manual offline mode ────────────────────────────────────────────────
        // Download everything needed and switch the POS into offline mode.
        async goOffline() {
            this.showMobileMenu = false;
            this.catalogError = '';

            // Always TRY to download the latest data (don't trust the heartbeat —
            // a missing /pos/ping route shouldn't block this).
            this.preparingOffline = true;
            const [catOk] = await Promise.all([this.refreshCatalog(), this.refreshCustomers()]);
            this.preparingOffline = false;

            if (!catOk || !this.catalogReady) {
                // Couldn't fetch fresh data — fall back to whatever is already cached
                if (this.catalog.length === 0) {
                    alert(
                        'Could not download product data, and nothing is cached yet.\n\n' +
                        (this.catalogError || 'Please check your internet connection') + '\n\n' +
                        'Make sure you are online and try again. If this keeps happening after a deploy, the server route cache may need clearing.'
                    );
                    return;
                }
                if (!confirm('Could not download the latest data (' + (this.catalogError || 'connection issue') + ').\n\nUse the ' + this.catalog.length + ' product(s) already cached and go offline anyway?')) {
                    return;
                }
            }

            // Ask the service worker to store the latest page shell so a refresh
            // while offline still loads the POS.
            try { navigator.serviceWorker?.controller?.postMessage('cache-shell'); } catch(e) {}

            this.offlineMode = true;
            this._saveOfflineMode();
            // Refresh whatever is on screen from the cache
            if (this.searchQuery.length > 0) this.searchProducts();
            else if (this.selectedCategory) this.loadProducts();

            alert('Offline mode is ON.\n\nSaved for offline use:\n• ' + this.catalog.length + ' product(s)\n• ' + this.offlineCustomers.length + ' customer(s)/vendor(s)\n\nYou can refresh the page and keep selling. Click "Go Online" to sync your sales.');
        },

        // Sync all queued sales and switch back to online mode.
        async goOnline() {
            this.showMobileMenu = false;
            await this.checkConnection();
            if (!this.isOnline) {
                alert('Still no internet connection. Connect to the internet first, then click Go Online.');
                return;
            }

            if (this.offlineOrders.length > 0) {
                await this.syncOfflineOrders();
            }

            this.offlineMode = false;
            this._saveOfflineMode();
            await this.refreshCatalog();   // freshen data now that we're back online

            // If some sales failed to sync, surface them for review
            if (this.offlineOrders.length > 0) {
                this.showOfflineModal = true;
            }
        },

        _saveOfflineMode() {
            try { localStorage.setItem(posCacheKey('pos_offline_mode'), this.offlineMode ? '1' : '0'); } catch(e) {}
        },

        // True only if the cached catalog actually carries category info. A
        // stale cache (or an outdated server /pos/catalog) lacks category_id,
        // which makes category browsing return nothing offline.
        get catalogHasCategoryData() {
            return this.catalog.length > 0 && this.catalog[0].category_id !== undefined;
        },

        // Build the set of category ids that count as "inside" the selected one:
        // the category itself plus (if it's a parent) all of its subcategory ids.
        // Returned as strings so number/string id mismatches never break matching.
        _categoryMatchIds(catId) {
            const ids = new Set([String(catId)]);
            for (const c of this.categories) {
                if (String(c.id) === String(catId) && Array.isArray(c.children)) {
                    c.children.forEach(ch => ids.add(String(ch.id)));
                }
            }
            return ids;
        },

        // Filter the cached catalog by selected category (offline equivalent of loadProducts)
        catalogByCategory() {
            if (!this.selectedCategory) return this.posView === 'product' ? this.catalog : [];
            const ids = this._categoryMatchIds(this.selectedCategory.id);
            const matched = this.catalog.filter(p =>
                ids.has(String(p.category_id)) || ids.has(String(p.subcategory_id))
            );
            // Diagnostic for the case the user is hitting now
            if (matched.length === 0) {
                console.warn('[POS offline] No products matched category', this.selectedCategory,
                    '| cached:', this.catalog.length,
                    '| sample item keys:', this.catalog[0] ? Object.keys(this.catalog[0]) : 'none',
                    '| has category_id:', this.catalog[0] ? (this.catalog[0].category_id !== undefined) : 'n/a');
            }
            return matched;
        },

        // Filter the cached catalog by a search query (offline equivalent of searchProducts)
        catalogBySearch(q) {
            const needle = q.toLowerCase();
            const matched = this.catalog.filter(p =>
                (p.name && p.name.toLowerCase().includes(needle)) ||
                (p.barcode && String(p.barcode).toLowerCase().includes(needle)) ||
                (p.sku && String(p.sku).toLowerCase().includes(needle))
            );
            // Also match individual serials (so scanning/typing an IMEI works offline)
            const serialHits = [];
            for (const p of this.catalog) {
                if (!p.is_serialized || !Array.isArray(p.serials)) continue;
                for (const s of p.serials) {
                    if (s.serial_number.toLowerCase().includes(needle)) {
                        serialHits.push({
                            ...p,
                            _key:          's_' + s.id,
                            price:         s.selling_price,
                            cost_price:    s.cost_price,
                            stock:         1,
                            serial_number: s.serial_number,
                            serial_id:     s.id,
                            attributes:    s.attributes || {},
                            colors:        [],
                        });
                    }
                }
            }
            return matched.concat(serialHits);
        },

        // Focus the barcode input — desktop only (on mobile it pops the keyboard)
        focusBarcode() {
            if (window.innerWidth >= 1024) this.$refs.barcodeInput?.focus();
        },

        async loadProducts() {
            // Offline → serve from the cached catalog
            if (this.effectiveOffline()) {
                this.displayProducts = this.catalogByCategory();
                this.loading = false;
                return;
            }
            this.loading = true;
            try {
                let url = '/pos/product/search?q=';
                if (this.selectedCategory) url += `&category=${this.selectedCategory.id}`;
                const res = await fetch(url);
                this.displayProducts = await res.json();
            } catch(e) {
                // Network dropped mid-request — fall back to the cached catalog
                this.isOnline = false;
                this.displayProducts = this.catalogByCategory();
            }
            this.loading = false;
        },

        switchView(view) {
            this.posView = view;
            this.selectedCategory = null;
            this.activeParent = null;
            this.searchQuery = '';
            this.displayProducts = [];
            this.showViewModal = false;
            localStorage.setItem('pos_view', view);
            if (view === 'product') this.loadProducts();
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
            this.focusBarcode();
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
            // Offline → search the cached catalog
            if (this.effectiveOffline()) {
                this.displayProducts = this.catalogBySearch(this.searchQuery);
                this.loading = false;
                return;
            }
            this.loading = true;
            try {
                // Search across ALL products (ignore category filter when searching)
                const res = await fetch(`/pos/product/search?q=${encodeURIComponent(this.searchQuery)}`);
                this.displayProducts = await res.json();
            } catch(e) {
                this.isOnline = false;
                this.displayProducts = this.catalogBySearch(this.searchQuery);
            }
            this.loading = false;
        },

        async handleBarcodeEnter() {
            if (!this.searchQuery) return;

            // Offline → resolve the scan against the cached catalog
            if (this.effectiveOffline()) {
                this.handleOfflineScan(this.searchQuery.trim());
                return;
            }

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
            } catch(e) {
                // Network dropped — resolve the scan offline instead
                this.isOnline = false;
                this.handleOfflineScan(this.searchQuery.trim());
                return;
            }
            this.searchProducts();
        },

        // Resolve a scanned/typed code against the cached catalog while offline.
        handleOfflineScan(code) {
            if (!code) return;
            const lc = code.toLowerCase();

            // 1) Exact product barcode / SKU match
            const product = this.catalog.find(p =>
                (p.barcode && String(p.barcode).toLowerCase() === lc) ||
                (p.sku && String(p.sku).toLowerCase() === lc)
            );
            if (product) {
                this.addToCart(product);   // serialized → opens picker; else adds
                this.searchQuery = '';
                return;
            }

            // 2) Exact serial / IMEI match across serialized products
            for (const p of this.catalog) {
                if (!p.is_serialized || !Array.isArray(p.serials)) continue;
                const s = p.serials.find(x => x.serial_number.toLowerCase() === lc);
                if (s) {
                    this.addSerializedToCart(
                        { ...p, price: s.selling_price, cost_price: s.cost_price, attributes: s.attributes },
                        s.serial_number,
                        s.id
                    );
                    this.searchQuery = '';
                    return;
                }
            }

            // 3) No exact hit → show partial matches in the grid
            this.searchProducts();
        },

        // Price text for a product tile. Serialized products have no meaningful
        // product-level price — show "From Rs. X" based on the cheapest in-stock
        // serial (matching the ecom site), or "Out of stock" when none remain.
        tilePriceLabel(p) {
            if (p.is_serialized && !p.serial_number && !(Number(p.price) > 0)) {
                // price_from comes from the server; fall back to cached serials offline
                let from = Number(p.price_from) || 0;
                if (!from && Array.isArray(p.serials) && p.serials.length) {
                    from = Math.min(...p.serials.map(s => Number(s.selling_price) || 0).filter(v => v > 0));
                    if (!isFinite(from)) from = 0;
                }
                return from > 0 ? `From Rs. ${from.toLocaleString()}` : 'Out of stock';
            }
            return `Rs. ${Number(p.price).toLocaleString()}`;
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
                this.focusBarcode();
                return;
            }

            // Serialized product tile (no specific IMEI yet) — show serial picker modal
            if (product.is_serialized) {
                this.serialPromptProduct = product;
                this.serialPromptInput   = '';
                this.serialPromptError   = '';
                this.serialPromptSerials = [];
                setTimeout(() => document.getElementById('serialPromptInput')?.focus(), 80);

                // Offline → use the serials embedded in the cached catalog
                if (this.effectiveOffline()) {
                    const cat = this.catalog.find(p => p.id === product.id);
                    this.serialPromptSerials = product.serials || cat?.serials || [];
                    this.serialPromptLoading = false;
                    return;
                }

                this.serialPromptLoading = true;
                fetch(`/pos/product/${product.id}/serials`)
                    .then(r => r.json())
                    .then(data => { this.serialPromptSerials = data.serials || []; })
                    .catch(() => {
                        // Fall back to cached serials if the request fails
                        const cat = this.catalog.find(p => p.id === product.id);
                        this.serialPromptSerials = product.serials || cat?.serials || [];
                        if (this.serialPromptSerials.length === 0) {
                            this.serialPromptError = 'Could not load serials. Try scanning directly.';
                        }
                    })
                    .finally(() => { this.serialPromptLoading = false; });
                return;
            }

            // If product has colors, show color picker first
            if (product.colors && product.colors.length > 0) {
                this.colorPickerProduct = product;
                return;
            }
            this.doAddToCart(product, null, null, product.stock);
        },

        // Click a serial row in the list → add directly
        pickSerial(s) {
            if (this.cart.find(i => i.serial_number === s.serial_number)) return;
            const p = this.serialPromptProduct;
            this.addSerializedToCart(
                { ...p, price: s.selling_price, cost_price: s.cost_price, attributes: s.attributes },
                s.serial_number,
                s.id
            );
            this.serialPromptProduct = null;
            this.serialPromptInput   = '';
            this.serialPromptSerials = [];
            this.focusBarcode();
        },

        // Scan / type IMEI and press Enter — validates against server then adds
        async confirmSerialPrompt() {
            const sn = (this.serialPromptInput || '').trim();
            if (!sn) {
                this.serialPromptError = 'Please enter or scan the IMEI / serial number.';
                return;
            }
            // If the typed value matches a listed serial, pick it directly without a server round-trip
            const listed = this.serialPromptSerials.find(s => s.serial_number.toLowerCase() === sn.toLowerCase());
            if (listed) {
                this.pickSerial(listed);
                return;
            }
            this.serialPromptLoading = true;
            this.serialPromptError   = '';
            try {
                const res  = await fetch(`/pos/product/barcode/${encodeURIComponent(sn)}`);
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.is_serialized && data.serial_number) {
                    if (data.id !== this.serialPromptProduct.id) {
                        this.serialPromptError = `This serial belongs to a different product: ${data.name}. Please scan the correct IMEI.`;
                    } else {
                        this.addSerializedToCart(data, data.serial_number, data.serial_id);
                        this.serialPromptProduct = null;
                        this.serialPromptInput   = '';
                        this.serialPromptSerials = [];
                        this.focusBarcode();
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
                if (existing.quantity < existing.stock) {
                    existing.quantity++;
                    existing.stockError = false;
                } else if (existing.track_inventory) {
                    existing.stockError = true;
                }
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
                    track_inventory:  product.track_inventory !== undefined ? !!product.track_inventory : true,
                    stockError:       false,
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
            const item = this.cart[index];
            if (item.quantity < item.stock) {
                item.quantity++;
                item.stockError = false;
                this.recalculate();
            } else if (item.track_inventory) {
                item.stockError = true;
            }
        },

        decreaseQty(index) {
            const item = this.cart[index];
            if (item.quantity > 1) {
                item.quantity--;
                item.stockError = false;
            } else {
                this.cart.splice(index, 1);
            }
            this.recalculate();
        },

        updateQty(index) {
            const item = this.cart[index];
            if (item.quantity < 1) item.quantity = 1;
            if (item.track_inventory && item.quantity > item.stock) {
                item.stockError = true;
                item.quantity   = item.stock || 1;
            } else {
                item.stockError = false;
            }
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
            if (['khata', 'partial'].includes(method) && this.selectedCustomer && !this.selectedCustomer.khata_enabled) {
                this.khataBlockMsg = 'Khata is not enabled for this customer. Enable it from their profile first.';
                setTimeout(() => { this.khataBlockMsg = ''; }, 4000);
                return;
            }
            this.khataBlockMsg = '';
            this.paymentMethod = method;
            if (method === 'cash') {
                this.cashReceived = Math.ceil(this.total / 100) * 100;
                this.promiseDate = '';
            } else if (method === 'partial') {
                this.partialAmountPaid = 0;
                this.partialPayVia = 'cash';
                this.partialBankId = '';
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
            // Offline → filter the cached customers + vendors
            if (this.effectiveOffline()) {
                const q = this.customerSearch.toLowerCase();
                this.customerResults = this.offlineCustomers.filter(c =>
                    (c.name && c.name.toLowerCase().includes(q)) ||
                    (c.phone && String(c.phone).includes(this.customerSearch))
                ).slice(0, 13);
                return;
            }
            try {
                const res = await fetch(`/pos/customer/search?q=${encodeURIComponent(this.customerSearch)}`);
                this.customerResults = await res.json();
            } catch(e) {
                // Network dropped — fall back to the cached list
                const q = this.customerSearch.toLowerCase();
                this.customerResults = this.offlineCustomers.filter(c =>
                    (c.name && c.name.toLowerCase().includes(q)) ||
                    (c.phone && String(c.phone).includes(this.customerSearch))
                ).slice(0, 13);
            }
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
                    this.newCustomer = { name: '', phone: '', address: '', khata_enabled: false };
                }
            } catch(e) {}
        },

        // Build the order payload sent to the server (shared by online + offline paths)
        buildOrderPayload() {
            return {
                items: this.cart.map(i => ({ product_id: i.product_id, quantity: i.quantity, unit_price: i.price, color_id: i.color_id || null, serial_number: i.serial_number || null })),
                discount: this.discountAmount,
                payment_method: this.paymentMethod,
                amount_paid: this.paymentMethod === 'partial' ? this.partialAmountPaid : null,
                partial_pay_via: this.paymentMethod === 'partial' ? this.partialPayVia : null,
                partial_bank_account_id: (this.paymentMethod === 'partial' && this.partialPayVia === 'bank') ? this.partialBankId : null,
                cash_amount: this.paymentMethod === 'split' ? this.splitCash : null,
                bank_amount: this.paymentMethod === 'split' ? this.splitBank : null,
                bank_account_id: ['bank_transfer', 'split'].includes(this.paymentMethod) ? this.bankAccountId : null,
                customer_id: (this.selectedCustomer?.type === 'customer') ? (this.selectedCustomer?.id || null) : null,
                vendor_id:   (this.selectedCustomer?.type === 'vendor')   ? (this.selectedCustomer?.id || null) : null,
                notes: this.orderNotes || null,
                cash_received: this.cashReceived,
                promise_date: ['khata','partial'].includes(this.paymentMethod) ? (this.promiseDate || null) : null,
                exchange_item_name: null,
                exchange_value: 0,
            };
        },

        resetAfterSale() {
            this.clearCart();
            this.selectedCustomer = null;
            this.orderNotes = '';
            this.paymentMethod = 'cash';
            this.showMobileCart = false;
            if (this.selectedCategory) this.loadProducts(); // reload same category
        },

        async placeOrder() {
            if (this.cart.length === 0 || this.processingOrder) return;
            this.processingOrder = true;

            const payload = this.buildOrderPayload();

            // Offline → queue locally and print an interim receipt
            if (this.effectiveOffline()) {
                this.queueOfflineOrder(payload);
                this.processingOrder = false;
                return;
            }

            try {
                const res = await fetch('/pos/order', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success && data.order_id) {
                    window.open(`/pos/receipt/${data.order_id}`, '_blank', 'width=400,height=700');
                    this.resetAfterSale();
                    window.dispatchEvent(new CustomEvent('pos:order-placed'));
                } else {
                    // Server reachable but rejected the sale (stock/serial/validation) — surface it
                    alert(data.error || data.message || 'Order failed. Please try again.');
                }
            } catch(e) {
                // Network dropped mid-request — fall back to offline queue so the sale isn't lost
                this.isOnline = false;
                this.queueOfflineOrder(payload);
            }
            this.processingOrder = false;
        },

        // ── Offline queue ──────────────────────────────────────────────────────
        queueOfflineOrder(payload) {
            const ref     = 'OFF-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
            const nowIso  = new Date().toISOString();
            const queued  = {
                ref,
                payload: { ...payload, offline_ref: ref, offline_created_at: nowIso },
                // Display snapshot (for the modal + interim receipt)
                label:     this.selectedCustomer?.name || 'Walk-in Customer',
                items:     this.cart.map(i => ({ name: i.name, qty: i.quantity, price: i.price, serial: i.serial_number || null })),
                total:     this.total,
                subtotal:  this.subtotal,
                discount:  this.discountAmount,
                payment_method: this.paymentMethod,
                itemCount: this.cart.reduce((s, i) => s + i.quantity, 0),
                createdAt: nowIso,
                createdLabel: new Date().toLocaleString('en-PK', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }),
                error: null,
            };
            this.offlineOrders.push(queued);
            this._saveOfflineOrders();
            this.printOfflineReceipt(queued);
            this.resetAfterSale();
        },

        async syncOfflineOrders() {
            if (this.syncing || this.offlineOrders.length === 0) return;
            await this.checkConnection();
            if (!this.isOnline) {
                this.lastSyncReport = { success: 0, failed: this.offlineOrders.length, networkAborted: true };
                return;
            }
            this.syncing = true;
            let success = 0;
            let networkAborted = false;

            for (const ord of this.offlineOrders) {
                try {
                    const res  = await fetch('/pos/order', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify(ord.payload)
                    });
                    const data = await res.json().catch(() => ({}));
                    if (res.ok && data.success) {
                        ord._synced = true;
                        ord.error   = null;
                        success++;
                    } else {
                        ord.error = data.error || data.message || 'Rejected by server.';
                    }
                } catch(e) {
                    // Connection dropped again — stop and keep the rest queued
                    this.isOnline   = false;
                    networkAborted  = true;
                    break;
                }
            }

            // Drop the ones that synced; keep failures (with their error) in the queue
            this.offlineOrders = this.offlineOrders.filter(o => !o._synced);
            this._saveOfflineOrders();
            this.syncing = false;
            this.lastSyncReport = { success, failed: this.offlineOrders.length, networkAborted };

            if (success > 0) window.dispatchEvent(new CustomEvent('pos:order-placed'));
        },

        deleteOfflineOrder(idx) {
            if (!confirm('Discard this offline sale? It will NOT be synced and cannot be recovered.')) return;
            this.offlineOrders.splice(idx, 1);
            this._saveOfflineOrders();
        },

        _saveOfflineOrders() {
            try { localStorage.setItem(posCacheKey('pos_offline_orders'), JSON.stringify(this.offlineOrders)); } catch(e) {}
        },

        // Minimal client-side receipt printed at the time of an offline sale
        printOfflineReceipt(ord) {
            const shop = window.__posShop || {};
            const money = n => 'Rs. ' + Number(n || 0).toLocaleString();
            const rows = ord.items.map(it =>
                `<tr><td>${it.name}${it.serial ? `<br><span class="sn">${it.serial}</span>` : ''}</td>`
                + `<td style="text-align:center">${it.qty}</td>`
                + `<td style="text-align:right">${money(it.price * it.qty)}</td></tr>`
            ).join('');
            const html = `
                <html><head><title>Offline Sale</title><style>
                    *{font-family:monospace;font-size:12px;margin:0;padding:0}
                    body{padding:10px;width:280px}
                    h2{text-align:center;font-size:15px;margin-bottom:2px}
                    .ctr{text-align:center}.muted{color:#555}
                    .banner{background:#000;color:#fff;text-align:center;padding:4px;margin:8px 0;font-weight:bold}
                    table{width:100%;border-collapse:collapse;margin-top:6px}
                    td{padding:2px 0;vertical-align:top}
                    .sn{font-size:10px;color:#555}
                    .tot{border-top:1px dashed #000;margin-top:6px;padding-top:6px}
                    .row{display:flex;justify-content:space-between}
                    .big{font-size:14px;font-weight:bold}
                </style></head><body>
                    <h2>${shop.name || 'MobileHub'}</h2>
                    ${shop.phone ? `<div class="ctr muted">${shop.phone}</div>` : ''}
                    ${shop.address ? `<div class="ctr muted">${shop.address}</div>` : ''}
                    <div class="banner">OFFLINE SALE — PENDING SYNC</div>
                    <div class="muted">Ref: ${ord.ref}</div>
                    <div class="muted">Date: ${ord.createdLabel}</div>
                    <div class="muted">Customer: ${ord.label}</div>
                    <div class="muted">Payment: ${ord.payment_method}</div>
                    <table>${rows}</table>
                    <div class="tot">
                        <div class="row"><span>Subtotal</span><span>${money(ord.subtotal)}</span></div>
                        ${ord.discount > 0 ? `<div class="row"><span>Discount</span><span>- ${money(ord.discount)}</span></div>` : ''}
                        <div class="row big"><span>TOTAL</span><span>${money(ord.total)}</span></div>
                    </div>
                    <div class="ctr muted" style="margin-top:10px">This sale will sync once the shop is back online.</div>
                </body></html>`;
            const w = window.open('', '_blank', 'width=320,height=600');
            if (w) { w.document.write(html); w.document.close(); w.focus(); setTimeout(() => w.print(), 200); }
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
        async holdOrder() {
            if (this.cart.length === 0) return;
            const label = this.selectedCustomer?.name
                || (this.orderNotes?.trim() ? this.orderNotes.substring(0, 24) : null)
                || `Hold ${this.heldOrders.length + 1}`;
            const payload = {
                label:          label,
                cart:           JSON.parse(JSON.stringify(this.cart)),
                customer:       this.selectedCustomer ? { ...this.selectedCustomer } : null,
                discount_type:  this.discountType,
                discount_value: this.discountValue,
                payment_method: this.paymentMethod,
                order_notes:    this.orderNotes,
                total:          this.total,
            };
            // Clear POS immediately for responsiveness
            this.cart             = [];
            this.selectedCustomer = null;
            this.discountType     = 'flat';
            this.discountValue    = 0;
            this.orderNotes       = '';
            this.paymentMethod    = 'cash';
            this.showMobileCart   = false;
            this.recalculate();
            this.focusBarcode();
            // Persist to server
            try {
                const r = await fetch('{{ route('pos.holds.store') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify(payload),
                });
                if (r.ok) this.heldOrders.unshift(await r.json());
            } catch(e) {}
        },

        async resumeHeldOrder(id) {
            const idx = this.heldOrders.findIndex(h => h.id === id);
            if (idx === -1) return;
            // If something is already in the cart, auto-hold it first
            if (this.cart.length > 0) await this.holdOrder();
            const held            = this.heldOrders[idx];
            this.cart             = held.cart;
            this.selectedCustomer = held.customer;
            this.discountType     = held.discountType  || 'flat';
            this.discountValue    = held.discountValue || 0;
            this.paymentMethod    = held.paymentMethod || 'cash';
            this.orderNotes       = held.orderNotes    || '';
            // Delete from server then remove from local list
            try {
                await fetch(`/pos/holds/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                });
            } catch(e) {}
            this.heldOrders.splice(this.heldOrders.findIndex(h => h.id === id), 1);
            this.showHeldOrders = false;
            // On mobile, open the cart so the resumed order is visible
            if (window.innerWidth < 1024) this.showMobileCart = true;
            this.recalculate();
            this.focusBarcode();
        },

        async deleteHeldOrder(id) {
            if (!confirm('Remove this held order? It cannot be recovered.')) return;
            try {
                const r = await fetch(`/pos/holds/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                });
                if (r.ok) {
                    const idx = this.heldOrders.findIndex(h => h.id === id);
                    if (idx !== -1) this.heldOrders.splice(idx, 1);
                }
            } catch(e) {}
        },

        async _fetchHolds() {
            this.holdsLoading = true;
            try {
                const r = await fetch('{{ route('pos.holds.index') }}', { headers: { 'Accept': 'application/json' } });
                if (r.ok) this.heldOrders = await r.json();
            } catch(e) {}
            this.holdsLoading = false;
        },

        async _pollHolds() {
            try {
                const r = await fetch('{{ route('pos.holds.index') }}', { headers: { 'Accept': 'application/json' } });
                if (r.ok) this.heldOrders = await r.json();
            } catch(e) {}
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
        vendor_payments: [],
        activityLoading: false,

        get netRevenue() {
            return this.stats.total_revenue - this.stats.total_refunded - this.stats.vendor_pay_total;
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
                    this.orders          = data.orders          ?? [];
                    this.returns         = data.returns         ?? [];
                    this.payments        = data.payments        ?? [];
                    this.vendor_payments = data.vendor_payments ?? [];
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
