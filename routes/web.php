<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Ecom;
use App\Http\Controllers\Pos;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Ecommerce Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [Ecom\HomeController::class, 'index'])->name('home');
Route::get('/products', [Ecom\ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [Ecom\ProductController::class, 'show'])->name('products.show');
Route::get('/category/{slug}', [Ecom\CategoryController::class, 'show'])->name('category.show');
Route::get('/brand/{slug}', [Ecom\BrandController::class, 'show'])->name('brand.show');
Route::get('/deals', [Ecom\DealController::class, 'index'])->name('deals.index');
Route::get('/search', [Ecom\ProductController::class, 'search'])->name('products.search');

// Cart
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [Ecom\CartController::class, 'index'])->name('index');
    Route::post('/add', [Ecom\CartController::class, 'add'])->name('add');
    Route::patch('/update/{rowId}', [Ecom\CartController::class, 'update'])->name('update');
    Route::delete('/remove/{rowId}', [Ecom\CartController::class, 'remove'])->name('remove');
    Route::delete('/clear', [Ecom\CartController::class, 'clear'])->name('clear');
    // Coupon
    Route::post('/coupon', [Ecom\CouponController::class, 'apply'])->name('coupon.apply');
    Route::delete('/coupon', [Ecom\CouponController::class, 'remove'])->name('coupon.remove');
});

// Checkout
Route::prefix('checkout')->name('checkout.')->middleware('customer.checkout')->group(function () {
    Route::get('/', [Ecom\CheckoutController::class, 'index'])->name('index');
    Route::post('/', [Ecom\CheckoutController::class, 'store'])->name('store');
    Route::get('/success/{order}', [Ecom\CheckoutController::class, 'success'])->name('success')->withoutMiddleware('customer.checkout');
    Route::post('/upload-proof/{order}', [Ecom\CheckoutController::class, 'uploadProof'])->name('upload_proof')->withoutMiddleware('customer.checkout');
});

// Order tracking (public — no login needed)
Route::get('/track-order', [Ecom\OrderTrackingController::class, 'index'])->name('order.track');
Route::post('/track-order', [Ecom\OrderTrackingController::class, 'track'])->name('order.track.result');

/*
|--------------------------------------------------------------------------
| Customer Account Routes
|--------------------------------------------------------------------------
*/
Route::prefix('account')->name('account.')->group(function () {

    // Guest-only (redirect logged-in customers to dashboard)
    Route::middleware('guest:customer')->group(function () {
        Route::get('/login', [Ecom\CustomerAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [Ecom\CustomerAuthController::class, 'login'])->name('login.post');
        Route::get('/register', [Ecom\CustomerAuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [Ecom\CustomerAuthController::class, 'register'])->name('register.post');
        Route::get('/forgot-password', [Ecom\CustomerPasswordController::class, 'showForgot'])->name('password.forgot');
        Route::post('/forgot-password/question', [Ecom\CustomerPasswordController::class, 'getQuestion'])->name('password.get-question');
        Route::post('/reset-password', [Ecom\CustomerPasswordController::class, 'reset'])->name('password.reset');
    });

    // Auth-required
    Route::middleware('customer.auth')->group(function () {
        Route::get('/dashboard', [Ecom\CustomerDashboardController::class, 'index'])->name('dashboard');
        Route::get('/orders', [Ecom\CustomerDashboardController::class, 'orders'])->name('orders');
        Route::get('/orders/{order}', [Ecom\CustomerDashboardController::class, 'showOrder'])->name('orders.show');
        Route::get('/profile', [Ecom\CustomerDashboardController::class, 'editProfile'])->name('profile');
        Route::post('/profile', [Ecom\CustomerDashboardController::class, 'updateProfile'])->name('profile.update');
        Route::post('/logout', [Ecom\CustomerAuthController::class, 'logout'])->name('logout');
    });
});

// Live product search (AJAX)
Route::get('/api/products/search', [Ecom\ProductController::class, 'liveSearch'])->name('api.products.search');

/*
|--------------------------------------------------------------------------
| Auth Routes (Admin & Salesman only)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest:web');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('guest:web');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Admin Panel Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {

    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/today-report', [Admin\DashboardController::class, 'todayReportPrint'])->name('dashboard.today-report');
    Route::get('/date-range-report', [Admin\DashboardController::class, 'dateRangeReport'])->name('date-range-report');

    // Products
    Route::get('products/generate-barcode', [Admin\ProductController::class, 'generateBarcodeAjax'])->name('products.generate_barcode');
    Route::get('categories/{category}/subcategories', [Admin\ProductController::class, 'subcategories'])->name('categories.subcategories');
    Route::resource('products', Admin\ProductController::class);
    Route::post('products/{product}/images', [Admin\ProductController::class, 'uploadImages'])->name('products.images.upload');
    Route::delete('products/images/{image}', [Admin\ProductController::class, 'deleteImage'])->name('products.images.delete');
    Route::patch('products/images/{image}/primary', [Admin\ProductController::class, 'setPrimaryImage'])->name('products.images.primary');
    Route::post('products/{product}/videos', [Admin\ProductController::class, 'uploadVideo'])->name('products.videos.upload');
    Route::delete('products/videos/{video}', [Admin\ProductController::class, 'deleteVideo'])->name('products.videos.delete');
    Route::get('products/{product}/print-barcode', [Admin\ProductController::class, 'printBarcode'])->name('products.print-barcode');

    // Sections
    Route::resource('sections', Admin\SectionController::class);

    // Categories & Brands
    Route::resource('categories', Admin\CategoryController::class);
    Route::resource('brands', Admin\BrandController::class);

    // POS Bank Accounts & Cash Balance
    Route::post('bank-accounts/cash-opening', [Admin\BankAccountController::class, 'updateCashOpening'])->name('bank-accounts.cash-opening');
    Route::resource('bank-accounts', Admin\BankAccountController::class)->except(['create', 'edit', 'show']);

    // Inventory
    Route::get('inventory', [Admin\InventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/{product}/adjust', [Admin\InventoryController::class, 'adjustForm'])->name('inventory.adjust.form');
    Route::patch('inventory/{product}/adjust', [Admin\InventoryController::class, 'adjust'])->name('inventory.adjust');
    Route::get('inventory/{product}/history', [Admin\InventoryController::class, 'history'])->name('inventory.history');
    Route::get('inventory/barcode-scan', [Admin\InventoryController::class, 'barcodeScan'])->name('inventory.barcode_scan');
    Route::post('inventory/barcode-assign', [Admin\InventoryController::class, 'assignBarcode'])->name('inventory.barcode_assign');
    Route::get('inventory/barcode-print', [Admin\InventoryController::class, 'printLabels'])->name('inventory.barcode_print');
    Route::get('inventory/low-stock', [Admin\InventoryController::class, 'lowStock'])->name('inventory.low_stock');
    Route::patch('inventory/{product}/dismiss', [Admin\InventoryController::class, 'dismissLowStock'])->name('inventory.dismiss');

    // Banners
    Route::resource('banners', Admin\BannerController::class);
    Route::patch('banners/{banner}/toggle', [Admin\BannerController::class, 'toggle'])->name('banners.toggle');

    // Deals
    Route::resource('deals', Admin\DealController::class);
    Route::patch('deals/{deal}/toggle', [Admin\DealController::class, 'toggle'])->name('deals.toggle');

    // Coupons
    Route::resource('coupons', Admin\CouponController::class);
    Route::patch('coupons/{coupon}/toggle', [Admin\CouponController::class, 'toggle'])->name('coupons.toggle');

    // Bank Free Delivery
    Route::get('bank-free-delivery', [Admin\BankFreeDeliveryController::class, 'index'])->name('bank-free-delivery.index');
    Route::patch('bank-free-delivery/{product}/toggle', [Admin\BankFreeDeliveryController::class, 'toggle'])->name('bank-free-delivery.toggle');

    // Orders
    Route::get('orders', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/deleted', [Admin\OrderController::class, 'deletedSales'])->name('orders.deleted');
    Route::get('orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/status', [Admin\OrderController::class, 'updateStatus'])->name('orders.status');
    Route::patch('orders/{order}/payment-status', [Admin\OrderController::class, 'updatePaymentStatus'])->name('orders.payment_status');
    Route::get('orders/{order}/invoice', [Admin\OrderController::class, 'invoice'])->name('orders.invoice');
    Route::get('orders/{order}/invoice/pdf', [Admin\OrderController::class, 'invoicePdf'])->name('orders.invoice_pdf');

    // Returns
    Route::get('returns', [Admin\ReturnController::class, 'index'])->name('returns.index');
    Route::get('returns/{return}', [Admin\ReturnController::class, 'show'])->name('returns.show');
    Route::patch('returns/{return}/approve', [Admin\ReturnController::class, 'approve'])->name('returns.approve');
    Route::patch('returns/{return}/reject', [Admin\ReturnController::class, 'reject'])->name('returns.reject');

    // Customers
    Route::resource('customers', Admin\CustomerController::class);
    Route::get('customers/{customer}/ledger', [Admin\CustomerController::class, 'ledger'])->name('customers.ledger');
    // Customer Online Accounts
    Route::get('customer-accounts', [Admin\CustomerAccountController::class, 'index'])->name('customer-accounts.index');
    Route::patch('customer-accounts/{account}/toggle', [Admin\CustomerAccountController::class, 'toggle'])->name('customer-accounts.toggle');
    Route::get('customers/{customer}/ledger/print', [Admin\CustomerController::class, 'ledgerPrint'])->name('customers.ledger.print');
    Route::post('customers/{customer}/ledger', [Admin\CustomerController::class, 'addLedgerEntry'])->name('customers.ledger.add');

    // Vendors & Purchases
    Route::resource('vendors', Admin\VendorController::class);
    Route::get('vendors/{vendor}/khata', [Admin\VendorController::class, 'khata'])->name('vendors.khata');
    Route::get('vendors/{vendor}/khata/print', [Admin\VendorController::class, 'khataPrint'])->name('vendors.khata.print');
    Route::post('vendors/{vendor}/ledger', [Admin\VendorController::class, 'addLedgerEntry'])->name('vendors.ledger.add');
    Route::get('api/vendors/{vendor}/balance', fn(\App\Models\Vendor $vendor) => response()->json(['balance' => $vendor->balance]))->name('api.vendor.balance');
    Route::get('api/serials/check', function (\Illuminate\Http\Request $request) {
        $serial = trim($request->input('serial', ''));
        if ($serial === '') return response()->json(['exists' => false]);
        $exists = \App\Models\SerialNumber::where('serial_number', $serial)->exists();
        return response()->json(['exists' => $exists]);
    })->name('api.serials.check');
    Route::post('api/products/quick-create', [Admin\PurchaseController::class, 'quickCreateProduct'])->name('api.products.quick-create');
    Route::get('api/products/search', function (\Illuminate\Http\Request $request) {
        $q = $request->input('q', '');
        if (strlen($q) < 2) return response()->json([]);
        $allDefs = \App\Models\SerialAttributeDefinition::activeOrdered()
            ->map(fn($d) => ['id' => $d->id, 'name' => $d->name, 'options' => $d->options]);
        $products = \App\Models\Product::where('name', 'like', "%{$q}%")
            ->orWhere('sku', 'like', "%{$q}%")
            ->orderBy('name')->limit(10)
            ->with(['colors', 'category', 'subcategory'])
            ->get(['id', 'name', 'sku', 'cost_price', 'is_serialized', 'serial_attribute_ids', 'category_id', 'subcategory_id']);
        return response()->json($products->map(fn($p) => [
            'id'               => $p->id,
            'name'             => $p->name,
            'sku'              => $p->sku,
            'cost_price'       => $p->cost_price,
            'is_serialized'    => (bool) $p->is_serialized,
            'serial_attr_defs' => $p->is_serialized
                ? ($p->serial_attribute_ids && count($p->serial_attribute_ids)
                    ? $allDefs->filter(fn($d) => in_array($d['id'], $p->serial_attribute_ids))->values()
                    : $allDefs->values())
                : [],
            'colors'           => $p->colors->map(fn($c) => [
                'id'       => $c->id,
                'name'     => $c->name,
                'hex_code' => $c->hex_code,
            ])->values(),
            'category'    => $p->category?->name,
            'subcategory' => $p->subcategory?->name,
        ]));
    })->name('admin.api.products.search');
    Route::get('purchases', [Admin\PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('purchases/create', [Admin\PurchaseController::class, 'create'])->name('purchases.create');
    Route::post('purchases', [Admin\PurchaseController::class, 'store'])->name('purchases.store');
    Route::post('purchases/temp-serial-image', [Admin\PurchaseController::class, 'tempSerialImage'])->name('purchases.temp-serial-image');
    Route::get('purchases/{purchase}', [Admin\PurchaseController::class, 'show'])->name('purchases.show');
    Route::get('purchases/{purchase}/edit', [Admin\PurchaseController::class, 'edit'])->name('purchases.edit');
    Route::put('purchases/{purchase}', [Admin\PurchaseController::class, 'update'])->name('purchases.update');
    Route::delete('purchases/{purchase}', [Admin\PurchaseController::class, 'destroy'])->name('purchases.destroy');
    Route::get('reports/purchases', [Admin\PurchaseController::class, 'report'])->name('reports.purchases');
    // Serial number routes
    Route::get('purchases/{purchase}/serials', [Admin\SerialController::class, 'showForPurchase'])->name('purchases.serials');
    Route::post('purchases/{purchase}/serials', [Admin\SerialController::class, 'storeForPurchase'])->name('purchases.serials.store');
    Route::get('serials/lookup', [Admin\SerialController::class, 'lookup'])->name('serials.lookup');
    // Serial attribute definitions
    Route::get('serials/attributes', [Admin\SerialAttributeController::class, 'index'])->name('serials.attributes.index');
    Route::post('serials/attributes', [Admin\SerialAttributeController::class, 'store'])->name('serials.attributes.store');
    Route::put('serials/attributes/{serialAttribute}', [Admin\SerialAttributeController::class, 'update'])->name('serials.attributes.update');
    Route::delete('serials/attributes/{serialAttribute}', [Admin\SerialAttributeController::class, 'destroy'])->name('serials.attributes.destroy');

    // Expenses
    Route::resource('expenses', Admin\ExpenseController::class);
    Route::resource('expense-categories', Admin\ExpenseCategoryController::class);

    // POS Sessions
    Route::get('pos-sessions', [Admin\PosSessionController::class, 'index'])->name('pos_sessions.index');

    // Salesmen / User management
    Route::get('salesmen', [Admin\SalesmanController::class, 'index'])->name('salesmen.index');
    Route::get('salesmen/create', [Admin\SalesmanController::class, 'create'])->name('salesmen.create');
    Route::post('salesmen', [Admin\SalesmanController::class, 'store'])->name('salesmen.store');
    Route::get('salesmen/{user}', [Admin\SalesmanController::class, 'show'])->name('salesmen.show');
    Route::get('salesmen/{user}/edit', [Admin\SalesmanController::class, 'edit'])->name('salesmen.edit');
    Route::put('salesmen/{user}', [Admin\SalesmanController::class, 'update'])->name('salesmen.update');
    Route::delete('salesmen/{user}', [Admin\SalesmanController::class, 'destroy'])->name('salesmen.destroy');
    Route::patch('salesmen/{user}/permissions', [Admin\SalesmanController::class, 'updatePermissions'])->name('salesmen.permissions');
    Route::patch('salesmen/{user}/toggle', [Admin\SalesmanController::class, 'toggleActive'])->name('salesmen.toggle');
    Route::get('salesmen/{user}/login-log', [Admin\SalesmanController::class, 'loginLog'])->name('salesmen.login_log');

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/sales', [Admin\ReportController::class, 'sales'])->name('sales');
        Route::get('/profit-loss', [Admin\ReportController::class, 'profitLoss'])->name('profit_loss');
        Route::get('/inventory', [Admin\ReportController::class, 'inventory'])->name('inventory');
        Route::get('/salesman-performance', [Admin\ReportController::class, 'salesmanPerformance'])->name('salesman_performance');
        Route::get('/expenses', [Admin\ReportController::class, 'expenses'])->name('expenses');
        Route::get('/accounts', [Admin\ReportController::class, 'accounts'])->name('accounts');
        Route::get('/export/{type}', [Admin\ReportController::class, 'export'])->name('export');
    });

    // Settings
    Route::get('settings', [Admin\SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    Route::post('settings/logo', [Admin\SettingController::class, 'uploadLogo'])->name('settings.logo');
    Route::post('settings/account', [Admin\SettingController::class, 'updateAccount'])->name('settings.account');
    Route::post('settings/sections', [Admin\SettingController::class, 'updateSectionPermissions'])->name('settings.sections');

    // Per-product store attribute selector + pricing + serial entry field selection
    Route::post('products/{product}/primary-attribute', [Admin\ProductAttributePriceController::class, 'savePrimaryAttribute'])->name('products.primary-attribute.save');
    Route::post('products/{product}/serial-attributes', [Admin\ProductAttributePriceController::class, 'saveSerialAttributes'])->name('products.serial-attributes.save');
    Route::post('products/{product}/attribute-prices', [Admin\ProductAttributePriceController::class, 'save'])->name('products.attribute-prices.save');
    Route::get('products/{product}/stickers', [Admin\ProductController::class, 'printStickers'])->name('products.stickers');

    // System tools (migrate + git pull) — admin only
    Route::post('system/migrate',  [Admin\SettingController::class, 'runMigrate'])->name('system.migrate');
    Route::post('system/git-pull', [Admin\SettingController::class, 'runGitPull'])->name('system.git-pull');
    Route::post('system/optimize', [Admin\SettingController::class, 'runOptimize'])->name('system.optimize');
});

/*
|--------------------------------------------------------------------------
| Salesman Panel Routes
|--------------------------------------------------------------------------
*/
Route::prefix('salesman')->name('salesman.')->middleware(['auth', 'role:salesman'])->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'salesmanDashboard'])->name('dashboard');
    Route::get('/dashboard/today-report', [Admin\DashboardController::class, 'salesmanTodayReportPrint'])->name('dashboard.today-report');

    Route::get('orders', [Admin\OrderController::class, 'index'])
        ->middleware('permission:orders.view')->name('orders.index');
    Route::get('orders/{order}', [Admin\OrderController::class, 'show'])
        ->middleware('permission:orders.view')->name('orders.show');

    Route::get('customers', [Admin\CustomerController::class, 'index'])
        ->middleware('permission:customers.view')->name('customers.index');
    Route::get('customers/{customer}', [Admin\CustomerController::class, 'show'])
        ->middleware('permission:customers.view')->name('customers.show');
    Route::get('customers/{customer}/ledger', [Admin\CustomerController::class, 'ledger'])
        ->middleware('permission:accounts.view')->name('customers.ledger');
    Route::get('customers/{customer}/ledger/print', [Admin\CustomerController::class, 'ledgerPrint'])
        ->middleware('permission:accounts.view')->name('customers.ledger.print');
    Route::post('customers/{customer}/ledger', [Admin\CustomerController::class, 'addLedgerEntry'])
        ->middleware('permission:accounts.manage')->name('customers.ledger.add');

    Route::get('inventory', [Admin\InventoryController::class, 'index'])
        ->middleware('permission:inventory.view')->name('inventory.index');
    Route::get('inventory/low-stock', [Admin\InventoryController::class, 'lowStock'])
        ->middleware('permission:inventory.view')->name('inventory.low_stock');
    Route::get('inventory/{product}/history', [Admin\InventoryController::class, 'history'])
        ->middleware('permission:inventory.view')->name('inventory.history');
    Route::get('inventory/{product}/adjust', [Admin\InventoryController::class, 'adjustForm'])
        ->middleware('permission:inventory.adjust')->name('inventory.adjust.form');
    Route::patch('inventory/{product}/adjust', [Admin\InventoryController::class, 'adjust'])
        ->middleware('permission:inventory.adjust')->name('inventory.adjust');
    Route::patch('inventory/{product}/dismiss', [Admin\InventoryController::class, 'dismissLowStock'])
        ->middleware('permission:inventory.adjust')->name('inventory.dismiss');

    Route::get('expenses', [Admin\ExpenseController::class, 'index'])
        ->middleware('permission:expenses.view')->name('expenses.index');
    Route::get('expenses/create', [Admin\ExpenseController::class, 'create'])
        ->middleware('permission:expenses.manage')->name('expenses.create');
    Route::post('expenses', [Admin\ExpenseController::class, 'store'])
        ->middleware('permission:expenses.manage')->name('expenses.store');
    Route::get('expenses/{expense}/edit', [Admin\ExpenseController::class, 'edit'])
        ->middleware('permission:expenses.manage')->name('expenses.edit');
    Route::put('expenses/{expense}', [Admin\ExpenseController::class, 'update'])
        ->middleware('permission:expenses.manage')->name('expenses.update');
    Route::delete('expenses/{expense}', [Admin\ExpenseController::class, 'destroy'])
        ->middleware('permission:expenses.manage')->name('expenses.destroy');

    Route::get('reports/sales', [Admin\ReportController::class, 'sales'])
        ->middleware('permission:reports.view')->name('reports.sales');

    Route::get('purchases', [Admin\PurchaseController::class, 'index'])
        ->middleware('permission:purchases.view')->name('purchases.index');
    Route::get('purchases/create', [Admin\PurchaseController::class, 'create'])
        ->middleware('permission:purchases.manage')->name('purchases.create');
    Route::post('purchases', [Admin\PurchaseController::class, 'store'])
        ->middleware('permission:purchases.manage')->name('purchases.store');
    Route::post('purchases/temp-serial-image', [Admin\PurchaseController::class, 'tempSerialImage'])
        ->middleware('permission:purchases.manage')->name('purchases.temp-serial-image');
    Route::get('purchases/{purchase}', [Admin\PurchaseController::class, 'show'])
        ->middleware('permission:purchases.view')->name('purchases.show');
    // Serial number routes for salesman
    Route::get('purchases/{purchase}/serials', [Admin\SerialController::class, 'showForPurchase'])
        ->middleware('permission:purchases.manage')->name('purchases.serials');
    Route::post('purchases/{purchase}/serials', [Admin\SerialController::class, 'storeForPurchase'])
        ->middleware('permission:purchases.manage')->name('purchases.serials.store');
    Route::get('serials/lookup', [Admin\SerialController::class, 'lookup'])
        ->middleware('permission:inventory.view')->name('serials.lookup');

    // Purchase-related API helpers (mirrors admin endpoints)
    Route::get('api/products/search', function (\Illuminate\Http\Request $request) {
        $q = $request->input('q', '');
        if (strlen($q) < 2) return response()->json([]);
        $allDefs = \App\Models\SerialAttributeDefinition::activeOrdered()
            ->map(fn($d) => ['id' => $d->id, 'name' => $d->name, 'options' => $d->options]);
        $products = \App\Models\Product::where('name', 'like', "%{$q}%")
            ->orWhere('sku', 'like', "%{$q}%")
            ->orderBy('name')->limit(10)->with(['colors', 'category', 'subcategory'])
            ->get(['id', 'name', 'sku', 'cost_price', 'is_serialized', 'serial_attribute_ids', 'category_id', 'subcategory_id']);
        return response()->json($products->map(fn($p) => [
            'id'               => $p->id,
            'name'             => $p->name,
            'sku'              => $p->sku,
            'cost_price'       => $p->cost_price,
            'is_serialized'    => (bool) $p->is_serialized,
            'serial_attr_defs' => $p->is_serialized
                ? ($p->serial_attribute_ids && count($p->serial_attribute_ids)
                    ? $allDefs->filter(fn($d) => in_array($d['id'], $p->serial_attribute_ids))->values()
                    : $allDefs->values())
                : [],
            'colors'           => $p->colors->map(fn($c) => [
                'id' => $c->id, 'name' => $c->name, 'hex_code' => $c->hex_code,
            ])->values(),
            'category'    => $p->category?->name,
            'subcategory' => $p->subcategory?->name,
        ]));
    })->middleware('permission:purchases.manage')->name('api.products.search');

    Route::post('api/products/quick-create', [Admin\PurchaseController::class, 'quickCreateProduct'])
        ->middleware('permission:purchases.manage')->name('api.products.quick-create');

    Route::get('api/serials/check', function (\Illuminate\Http\Request $request) {
        $serial = trim($request->input('serial', ''));
        if ($serial === '') return response()->json(['exists' => false]);
        $exists = \App\Models\SerialNumber::where('serial_number', $serial)->exists();
        return response()->json(['exists' => $exists]);
    })->middleware('permission:purchases.manage')->name('api.serials.check');

    Route::get('products/generate-barcode', [Admin\ProductController::class, 'generateBarcodeAjax'])
        ->middleware('permission:purchases.manage')->name('products.generate_barcode');
});

/*
|--------------------------------------------------------------------------
| POS Routes (Admin + Salesmen with pos.access permission)
|--------------------------------------------------------------------------
*/
Route::prefix('pos')->name('pos.')->middleware(['auth', 'permission:pos.access'])->group(function () {
    Route::get('/', [Pos\PosController::class, 'index'])->name('index');
    Route::post('/session/open', [Pos\PosController::class, 'openSession'])->name('session.open');
    Route::post('/session/close', [Pos\PosController::class, 'closeSession'])->name('session.close');

    // Lightweight connectivity heartbeat for offline-mode detection
    Route::get('/ping', fn() => response()->json(['ok' => true]))->name('ping');

    // AJAX endpoints for POS
    Route::post('/order', [Pos\PosController::class, 'createOrder'])->name('order.create');
    Route::get('/product/search', [Pos\PosController::class, 'searchProduct'])->name('product.search');
    Route::get('/catalog', [Pos\PosController::class, 'catalog'])->name('catalog');
    Route::get('/customers-cache', [Pos\PosController::class, 'offlineCustomers'])->name('customers.cache');
    Route::get('/product/barcode/{barcode}', [Pos\PosController::class, 'getByBarcode'])->name('product.barcode');
    Route::get('/product/{productId}/serials', [Pos\PosController::class, 'getProductSerials'])->name('product.serials');
    Route::get('/customer/search', [Pos\PosController::class, 'searchCustomer'])->name('customer.search');
    Route::post('/customer', [Pos\PosController::class, 'createCustomer'])->name('customer.create');
    Route::get('/receipt/{order}', [Pos\PosController::class, 'receipt'])->name('receipt');
    Route::get('/stats', [Pos\PosController::class, 'stats'])->name('stats');
    Route::get('/today-activity', [Pos\PosController::class, 'todayActivity'])->name('today-activity');

    // Edit sale
    Route::get('/orders/{order}/edit', [Pos\PosController::class, 'editOrder'])
        ->middleware('permission:pos.edit_sale')->name('order.edit');
    Route::put('/orders/{order}', [Pos\PosController::class, 'updateOrder'])
        ->middleware('permission:pos.edit_sale')->name('order.update');

    // Delete sale
    Route::delete('/orders/{order}', [Pos\PosController::class, 'deleteOrder'])
        ->middleware('permission:pos.delete_sale')->name('order.delete');

    // Returns
    Route::get('/return', [Pos\PosReturnController::class, 'index'])
        ->middleware('permission:pos.process_returns')->name('return.index');
    Route::get('/return/order/{orderNumber}', [Pos\PosReturnController::class, 'findOrder'])
        ->middleware('permission:pos.process_returns')->name('return.find');
    Route::get('/return/search/sku', [Pos\PosReturnController::class, 'searchBySku'])
        ->middleware('permission:pos.process_returns')->name('return.search');
    Route::post('/return', [Pos\PosReturnController::class, 'process'])
        ->middleware('permission:pos.process_returns')->name('return.process');
    Route::get('/return/{return}/receipt', [Pos\PosReturnController::class, 'receipt'])
        ->middleware('permission:pos.process_returns')->name('return.receipt');

    // Exchanges
    Route::get('/exchange', [Pos\PosExchangeController::class, 'index'])->name('exchange.index');
    Route::get('/exchange/order/{orderNumber}', [Pos\PosExchangeController::class, 'findOrder'])->name('exchange.find');
    Route::post('/exchange', [Pos\PosExchangeController::class, 'processExchange'])->name('exchange.process');
});
