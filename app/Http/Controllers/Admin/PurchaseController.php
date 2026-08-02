<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\Purchase;
use App\Models\SerialAttributeDefinition;
use App\Models\SerialNumber;
use App\Models\StockMovement;
use App\Models\Vendor;
use App\Models\VendorLedger;
use App\Services\BarcodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::with('vendor')->orderByDesc('purchase_date')->orderByDesc('id');

        if ($request->filled('product')) {
            $search = $request->product;
            $query->where(function ($q) use ($search) {
                $q->whereHas('items', function ($i) use ($search) {
                    $i->where('product_name', 'like', "%{$search}%")
                      ->orWhereHas('product', fn($p) => $p->where('barcode', $search));
                })->orWhereHas('serialNumbers', fn($s) => $s->where('serial_number', $search));
            });
        }
        if ($request->filled('vendor')) {
            $query->where('vendor_id', $request->vendor);
        }
        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }
        if ($request->filled('from')) {
            $query->whereDate('purchase_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('purchase_date', '<=', $request->to);
        }

        $purchases = $query->paginate(25)->withQueryString();
        $vendors   = Vendor::orderBy('name')->get(['id', 'name']);

        return view('admin.purchases.index', compact('purchases', 'vendors'));
    }

    public function create()
    {
        $vendors    = Vendor::orderBy('name')->get();
        $products   = Product::orderBy('name')->get(['id', 'name', 'cost_price', 'sku']);
        $categories = Category::active()->whereNull('parent_id')
            ->with(['children' => fn($q) => $q->active()->orderBy('name')->select('id', 'name', 'parent_id')])
            ->orderBy('name')->get(['id', 'name']);
        $brands       = Brand::orderBy('name')->get(['id', 'name']);
        $bankAccounts      = BankAccount::active()->orderBy('sort_order')->get();
        $serialAttributeDefs = SerialAttributeDefinition::activeOrdered();
        $draft = session('purchase_draft'); // pending review draft — restores the form state
        return view('admin.purchases.create', compact('vendors', 'products', 'categories', 'brands', 'bankAccounts', 'serialAttributeDefs', 'draft'));
    }

    public function quickCreateProduct(Request $request)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'sku'                 => 'nullable|string|max:100|unique:products,sku',
            'category_id'         => 'nullable|exists:categories,id',
            'subcategory_id'      => 'nullable|exists:categories,id',
            'brand_id'            => 'nullable|exists:brands,id',
            'short_description'   => 'nullable|string|max:500',
            'barcode'             => 'nullable|string|max:50|unique:products,barcode',
            'cost_price'          => 'required|numeric|min:0',
            'price'               => 'required|numeric|min:0',
            'compare_price'       => 'nullable|numeric|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'show_in_ecom'        => 'nullable|boolean',
            'images.*'            => 'nullable|image|max:5120',
            'video_embed_url'     => 'nullable|string',
            'colors'              => 'nullable|array',
            'colors.*.name'       => 'required_with:colors|string|max:100',
            'colors.*.hex_code'   => 'nullable|string|max:7',
        ]);

        // Generate unique slug
        $slug = Str::slug($data['name']);
        $base = $slug; $n = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n++;
        }

        // Auto-generate barcode using section prefix (same logic as the product create form)
        // Use parent category_id for prefix lookup — sections live on parent categories
        if (empty($data['barcode'])) {
            $data['barcode'] = BarcodeService::generate(
                isset($data['category_id']) ? (int) $data['category_id'] : null
            );
        }

        $product = Product::create([
            'name'                => $data['name'],
            'slug'                => $slug,
            'sku'                 => $data['sku'] ?? null,
            'category_id'         => $data['category_id'] ?? null,
            'subcategory_id'      => $data['subcategory_id'] ?? null,
            'brand_id'            => $data['brand_id'] ?? null,
            'short_description'   => $data['short_description'] ?? null,
            'barcode'             => $data['barcode'],
            'cost_price'          => $data['cost_price'],
            'price'               => $data['price'],
            'compare_price'       => $data['compare_price'] ?? null,
            'stock_quantity'      => 0,
            'low_stock_threshold' => $data['low_stock_threshold'] ?? 5,
            'track_inventory'     => true,
            'is_active'           => true,
            'show_in_ecom'        => (bool) ($data['show_in_ecom'] ?? false),
            'created_by'          => auth()->id(),
        ]);

        // Save images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $i => $file) {
                $product->images()->create([
                    'path'       => $file->store('products', 'public'),
                    'sort_order' => $i,
                    'is_primary' => $i === 0,
                ]);
            }
        }

        // Save embed video
        if (!empty($data['video_embed_url'])) {
            $embed = trim($data['video_embed_url']);
            if (str_contains($embed, '<iframe')) {
                preg_match('/src=["\']([^"\']+)["\']/', $embed, $m);
                $embed = $m[1] ?? $embed;
            }
            $product->videos()->create(['type' => 'embed', 'url' => $embed, 'sort_order' => 0]);
        }

        // Save colors
        $createdColors = [];
        if (!empty($data['colors'])) {
            foreach ($data['colors'] as $i => $c) {
                $name = trim($c['name'] ?? '');
                if (!$name) continue;
                $color = $product->colors()->create([
                    'name'           => $name,
                    'hex_code'       => $c['hex_code'] ?? null,
                    'stock_quantity' => 0,
                    'sort_order'     => $i,
                ]);
                $createdColors[] = ['id' => $color->id, 'name' => $color->name, 'hex_code' => $color->hex_code];
            }
        }

        return response()->json([
            'id'         => $product->id,
            'name'       => $product->name,
            'sku'        => $product->sku,
            'cost_price' => $product->cost_price,
            'colors'     => $createdColors,
        ]);
    }

    /** Friendly messages for the bank-selection rules. */
    private function purchaseValidationMessages(): array
    {
        return [
            'bank_account_id.required_if'      => 'Select a bank account for the bank transfer payment.',
            'partial_bank_account_id.required' => 'Select a bank account for the bank portion of the partial payment.',
        ];
    }

    /**
     * Validation rules shared by store() (direct save) and review() (draft).
     */
    private function purchaseValidationRules(): array
    {
        return [
            'purchase_date'           => 'required|date',
            'vendor_id'               => 'required|exists:vendors,id',
            'reference'               => 'nullable|string|max:100',
            'payment_method'          => 'required|in:cash,bank_transfer,credit,partial',
            'bank_account_id'         => ['required_if:payment_method,bank_transfer', 'nullable', 'exists:bank_accounts,id'],
            'partial_pay_via'         => 'nullable|in:cash,bank',
            'partial_bank_account_id' => [
                Rule::requiredIf(fn() => request('payment_method') === 'partial' && request('partial_pay_via') === 'bank'),
                'nullable', 'exists:bank_accounts,id',
            ],
            'amount_paid'             => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string|max:1000',
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'required|exists:products,id',
            'items.*.quantity'    => 'required|integer|min:1',
            'items.*.unit_cost'   => 'required|numeric|min:0',
            'items.*.color_id'    => 'nullable|exists:product_colors,id',
            'items.*.color_name'  => 'nullable|string|max:100',
            'items.*.serials'                   => 'nullable|array',
            'items.*.serials.*.id'              => 'nullable|integer',
            'items.*.serials.*.serial'          => 'nullable|string|max:100',
            'items.*.serials.*.cost_price'      => 'nullable|numeric|min:0',
            'items.*.serials.*.selling_price'   => 'nullable|numeric|min:0',
            'items.*.serials.*.attributes'      => 'nullable|array',
            'items.*.serials.*.attributes.*'    => 'nullable|string|max:200',
            'items.*.serials.*.image_path'      => 'nullable|string|max:500',
        ];
    }

    /**
     * Server-side serial validation for serialized products.
     * Returns an error message, or null when everything is fine.
     */
    private function serialConflictError(array $items, ?int $excludePurchaseId = null): ?string
    {
        // Pass 1: collect all serials across all items and check for cross-item duplicates
        $allSerialMap = []; // uppercase → original value
        foreach ($items as $row) {
            $product = Product::find($row['product_id']);
            if (!$product || !$product->is_serialized) continue;

            $serials = array_values(array_filter(
                array_map(fn($s) => trim($s['serial'] ?? ''), $row['serials'] ?? []),
                fn($s) => $s !== ''
            ));

            // Quantity check
            if (count($serials) < (int) $row['quantity']) {
                return "Please enter all {$row['quantity']} serial number(s) for: {$product->name}.";
            }

            foreach ($serials as $sn) {
                $upper = strtoupper($sn);
                if (isset($allSerialMap[$upper])) {
                    return "Serial number '{$sn}' appears more than once in this purchase.";
                }
                $allSerialMap[$upper] = $sn;
            }
        }

        // Pass 2: check all collected serials against the database in one query
        // (when editing, this purchase's own serials are not conflicts)
        if (!empty($allSerialMap)) {
            $existing = SerialNumber::whereIn('serial_number', array_values($allSerialMap))
                ->when($excludePurchaseId, fn($q) => $q->where(
                    fn($qq) => $qq->whereNull('purchase_id')->orWhere('purchase_id', '!=', $excludePurchaseId)
                ))
                ->pluck('serial_number');
            if ($existing->isNotEmpty()) {
                return "Serial number(s) already registered in the system: " . $existing->implode(', ');
            }
        }

        return null;
    }

    /**
     * Products with color variants must be purchased against a specific color,
     * otherwise the product total and the per-color stock drift apart
     * (POS reads color stock; the website reads the product total).
     * Returns an error message, or null when everything is fine.
     */
    private function colorRequirementError(array $items): ?string
    {
        foreach ($items as $row) {
            $product = Product::with('colors')->find($row['product_id']);
            if (!$product || $product->is_serialized || $product->colors->isEmpty()) {
                continue;
            }

            $colorId = $row['color_id'] ?? null;
            if (!$colorId) {
                return "{$product->name} has color variants — please select which color you are purchasing.";
            }

            if (!$product->colors->contains('id', (int) $colorId)) {
                return "The selected color for {$product->name} does not belong to that product.";
            }
        }

        return null;
    }

    /**
     * Effective line cost: serialized products take the sum of their per-unit serial
     * cost prices (the top-level unit cost field is hidden for them); everyone else
     * uses quantity × unit_cost.
     */
    private function lineCost(array $row): float
    {
        $product = Product::find($row['product_id']);
        if ($product && $product->is_serialized) {
            return (float) collect($row['serials'] ?? [])
                ->sum(fn($s) => is_numeric($s['cost_price'] ?? null) ? (float) $s['cost_price'] : 0.0);
        }
        return (float) $row['quantity'] * (float) $row['unit_cost'];
    }

    /**
     * Record the purchase: purchase row, items, serials, stock, khata.
     * $data holds the same keys as the validated store() payload.
     */
    private function persistPurchase(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'];

            $subtotal = collect($items)->sum(fn($row) => $this->lineCost($row));
            $total    = $subtotal;

            $payMethod    = $data['payment_method'];
            $bankAccountId = $payMethod === 'bank_transfer'
                ? (($data['bank_account_id'] ?? null) ?: null)
                : ($payMethod === 'partial' && ($data['partial_pay_via'] ?? null) === 'bank'
                    ? (($data['partial_bank_account_id'] ?? null) ?: null)
                    : null);
            $amountPaid = match ($payMethod) {
                'cash'          => $total,
                'bank_transfer' => $total,
                'credit'        => 0,
                'partial'       => min((float) ($data['amount_paid'] ?? 0), $total),
            };
            $payStatus = match (true) {
                $amountPaid >= $total => 'paid',
                $amountPaid > 0       => 'partial',
                default               => 'unpaid',
            };

            $purchase = Purchase::create([
                'reference'      => $data['reference'] ?? null,
                'vendor_id'      => $data['vendor_id'],
                'purchase_date'  => $data['purchase_date'],
                'subtotal'       => $subtotal,
                'total'          => $total,
                'payment_method'  => $payMethod,
                'bank_account_id' => $bankAccountId,
                'amount_paid'     => $amountPaid,
                'payment_status' => $payStatus,
                'notes'          => $data['notes'] ?? null,
                'created_by'     => auth()->id(),
            ]);

            foreach ($items as $row) {
                $product = Product::find($row['product_id']);
                $lineTotal = $this->lineCost($row);
                // For serialized products derive the per-unit cost from the line total (the
                // top-level unit cost field is hidden, so $row['unit_cost'] is 0 for them).
                $unitCost  = ($product->is_serialized && (int) $row['quantity'] > 0)
                    ? round($lineTotal / (int) $row['quantity'], 2)
                    : (float) $row['unit_cost'];
                $colorId   = !empty($row['color_id']) ? (int) $row['color_id'] : null;
                $colorName = !empty($row['color_name']) ? $row['color_name'] : null;

                $purchaseItem = $purchase->items()->create([
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'color_id'     => $colorId,
                    'color_name'   => $colorName,
                    'quantity'     => $row['quantity'],
                    'unit_cost'    => $unitCost,
                    'line_total'   => $lineTotal,
                ]);

                // Save serial numbers for serialized products
                if ($product->is_serialized && !empty($row['serials'])) {
                    foreach ($row['serials'] as $snData) {
                        $sn = trim($snData['serial'] ?? '');
                        if ($sn === '') continue;
                        SerialNumber::create([
                            'product_id'       => $product->id,
                            'serial_number'    => $sn,
                            'cost_price'       => isset($snData['cost_price']) && is_numeric($snData['cost_price'])
                                                  ? $snData['cost_price'] : null,
                            'selling_price'    => isset($snData['selling_price']) && is_numeric($snData['selling_price'])
                                                  ? $snData['selling_price'] : null,
                            'attributes'       => !empty($snData['attributes']) ? $snData['attributes'] : null,
                            'image'            => $snData['image_path'] ?? null,
                            'status'           => 'in_stock',
                            'purchase_id'      => $purchase->id,
                            'purchase_item_id' => $purchaseItem->id,
                        ]);
                    }
                }

                // If a color is specified, increment its stock too
                if ($colorId) {
                    ProductColor::where('id', $colorId)->increment('stock_quantity', $row['quantity']);
                }

                // Record stock movement (capture before/after)
                $before = (int) $product->stock_quantity;
                $after  = $before + $row['quantity'];

                $product->increment('stock_quantity', $row['quantity']);

                $updates = ['cost_price' => $unitCost];
                // Auto-clear dismissal if restocked above threshold
                if ($after > $product->low_stock_threshold && $product->low_stock_dismissed) {
                    $updates['low_stock_dismissed'] = false;
                }
                $product->update($updates);

                StockMovement::create([
                    'product_id'      => $product->id,
                    'type'            => 'purchase',
                    'quantity'        => $row['quantity'],
                    'before_quantity' => $before,
                    'after_quantity'  => $after,
                    'reference'       => $purchase->reference ?? 'PUR-' . $purchase->id,
                    'note'            => 'Purchase from ' . ($purchase->vendor?->name ?? 'unknown vendor'),
                    'user_id'         => auth()->id(),
                ]);
            }

            // Update vendor ledger if payment is credit or partial
            if ($data['vendor_id'] && $payStatus !== 'paid') {
                $vendor    = Vendor::find($data['vendor_id']);
                $owed      = $total - $amountPaid;
                $newBal    = $vendor->balance + $owed;

                VendorLedger::create([
                    'vendor_id'    => $vendor->id,
                    'purchase_id'  => $purchase->id,
                    'type'         => 'credit',
                    'amount'       => $owed,
                    'balance_after' => $newBal,
                    'description'  => $payStatus === 'partial'
                        ? "Partial payment — Rs. {$amountPaid} paid, Rs. {$owed} on credit"
                        : "Purchase on credit",
                    'created_by'   => auth()->id(),
                ]);

                $vendor->update(['balance' => $newBal]);
            }

            return $purchase;
        });
    }

    /**
     * Direct save (legacy route) — kept as a thin wrapper around the shared pieces.
     */
    public function store(Request $request)
    {
        $request->validate($this->purchaseValidationRules(), $this->purchaseValidationMessages());

        if ($err = $this->serialConflictError($request->items)) {
            return back()->withErrors(['items' => $err])->withInput();
        }

        if ($err = $this->colorRequirementError($request->items)) {
            return back()->withErrors(['items' => $err])->withInput();
        }

        $purchase = $this->persistPurchase($request->all());

        $rPrefix = auth()->user()->panelPrefix();
        return redirect()->route("{$rPrefix}.purchases.show", $purchase)
            ->with('success', 'Purchase recorded — stock and serial numbers updated.');
    }

    /**
     * Step 1 — validate the submitted purchase and stash it in the session
     * as a draft. Nothing is written to the database yet.
     */
    public function review(Request $request)
    {
        $rPrefix = auth()->user()->panelPrefix();

        $request->validate($this->purchaseValidationRules() + [
            'ui_state' => 'nullable|string', // raw Alpine items JSON, used to rebuild the form
        ], $this->purchaseValidationMessages());

        // Stash first so the create page can restore state even when a serial conflicts
        session(['purchase_draft' => $request->only([
            'purchase_date', 'vendor_id', 'reference', 'payment_method', 'bank_account_id',
            'partial_pay_via', 'partial_bank_account_id', 'amount_paid', 'notes', 'items', 'ui_state',
        ])]);

        if ($err = $this->serialConflictError($request->items)) {
            return redirect()->route("{$rPrefix}.purchases.create")
                ->withErrors(['items' => $err]);
        }

        if ($err = $this->colorRequirementError($request->items)) {
            return redirect()->route("{$rPrefix}.purchases.create")
                ->withErrors(['items' => $err]);
        }

        return redirect()->route("{$rPrefix}.purchases.review.show");
    }

    /**
     * Step 2 — show the draft for review against the vendor's invoice.
     */
    public function reviewShow()
    {
        $rPrefix = auth()->user()->panelPrefix();

        $draft = session('purchase_draft');
        if (!$draft) {
            return redirect()->route("{$rPrefix}.purchases.create");
        }

        $vendor = Vendor::find($draft['vendor_id']);

        // Per-line display data using the exact same cost math as persistPurchase()
        $lines = collect($draft['items'])->map(function ($row) {
            $product   = Product::find($row['product_id']);
            $lineTotal = $this->lineCost($row);
            $qty       = (int) $row['quantity'];
            $serials   = collect($row['serials'] ?? [])
                ->filter(fn($s) => trim($s['serial'] ?? '') !== '')
                ->values();
            return [
                'product'      => $product,
                'product_name' => $product?->name ?? '(deleted product)',
                'color_name'   => $row['color_name'] ?? null,
                'quantity'     => $qty,
                'unit_cost'    => $product?->is_serialized && $qty > 0
                                  ? round($lineTotal / $qty, 2)
                                  : (float) $row['unit_cost'],
                'line_total'   => $lineTotal,
                'is_serialized' => (bool) $product?->is_serialized,
                'serials'      => $serials,
            ];
        });

        $total      = $lines->sum('line_total');
        $payMethod  = $draft['payment_method'];
        $amountPaid = match ($payMethod) {
            'cash'          => $total,
            'bank_transfer' => $total,
            'credit'        => 0,
            default         => min((float) ($draft['amount_paid'] ?? 0), $total),
        };
        $owed = max(0, $total - $amountPaid);

        $bankAccountId = $payMethod === 'bank_transfer'
            ? ($draft['bank_account_id'] ?? null)
            : ($payMethod === 'partial' && ($draft['partial_pay_via'] ?? null) === 'bank'
                ? ($draft['partial_bank_account_id'] ?? null)
                : null);
        $bankAccount = $bankAccountId ? BankAccount::find($bankAccountId) : null;

        return view('admin.purchases.review', [
            'draft'       => $draft,
            'vendor'      => $vendor,
            'lines'       => $lines,
            'total'       => $total,
            'amountPaid'  => $amountPaid,
            'owed'        => $owed,
            'payMethod'   => $payMethod,
            'bankAccount' => $bankAccount,
        ]);
    }

    /**
     * Step 3 — the user confirmed: actually record the draft.
     */
    public function confirm()
    {
        $rPrefix = auth()->user()->panelPrefix();

        $draft = session('purchase_draft');
        if (!$draft) {
            return redirect()->route("{$rPrefix}.purchases.create");
        }

        // Re-check: a serial could have been registered elsewhere since the review started
        if ($err = $this->serialConflictError($draft['items'])) {
            return redirect()->route("{$rPrefix}.purchases.create")
                ->withErrors(['items' => $err]);
        }

        if ($err = $this->colorRequirementError($draft['items'])) {
            return redirect()->route("{$rPrefix}.purchases.create")
                ->withErrors(['items' => $err]);
        }

        $purchase = $this->persistPurchase($draft);
        session()->forget('purchase_draft');

        return redirect()->route("{$rPrefix}.purchases.show", $purchase)
            ->with('success', 'Purchase recorded — stock and serial numbers updated.');
    }

    /**
     * Throw away the pending draft.
     */
    public function discardReview()
    {
        session()->forget('purchase_draft');
        $rPrefix = auth()->user()->panelPrefix();
        return redirect()->route("{$rPrefix}.purchases.create")
            ->with('info', 'Draft purchase discarded.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['vendor', 'items.product', 'createdBy']);

        // Redirect to serial registration if newly created purchase has serialized items
        // and serials haven't been registered yet
        return view('admin.purchases.show', compact('purchase'));
    }

    public function edit(Purchase $purchase)
    {
        $purchase->load(['items.product.colors', 'vendor']);
        $vendors      = Vendor::orderBy('name')->get();
        $products     = Product::orderBy('name')->get(['id', 'name', 'cost_price', 'sku']);
        $bankAccounts = BankAccount::active()->orderBy('sort_order')->get();
        $categories   = Category::active()->whereNull('parent_id')
            ->with(['children' => fn($q) => $q->active()->orderBy('name')->select('id', 'name', 'parent_id')])
            ->orderBy('name')->get(['id', 'name']);
        $brands = Brand::orderBy('name')->get(['id', 'name']);
        $serialAttributeDefs = SerialAttributeDefinition::activeOrdered();

        $allDefs = $serialAttributeDefs
            ->map(fn($d) => ['id' => $d->id, 'name' => $d->name, 'options' => $d->options])
            ->values();

        $serialsByItem = SerialNumber::where('purchase_id', $purchase->id)->get()->groupBy('purchase_item_id');

        // Rebuild the create form's Alpine item state (one entry per product,
        // per-color rows when colors are present, serial rows for serialized).
        $uid     = 0;
        $grouped = [];
        foreach ($purchase->items as $item) {
            $pid     = $item->product_id;
            $product = $item->product;

            if (!isset($grouped[$pid])) {
                $attrDefs = ($product?->is_serialized)
                    ? (($product->serial_attribute_ids && count($product->serial_attribute_ids))
                        ? $allDefs->filter(fn($d) => in_array($d['id'], $product->serial_attribute_ids))->values()->all()
                        : $allDefs->all())
                    : [];

                $grouped[$pid] = [
                    '_uid'             => ++$uid,
                    'id'               => $pid,
                    'name'             => $item->product_name,
                    'sku'              => $product?->sku ?? '',
                    'unit_cost'        => (float) $item->unit_cost,
                    'has_colors'       => false,
                    'is_serialized'    => (bool) ($product?->is_serialized),
                    'attrDefs'         => $attrDefs,
                    'attrMode'         => 'different',
                    'sharedAttributes' => (object) [],
                    'sharedCost'       => '',
                    'sharedSelling'    => '',
                    'colors'           => [],
                    'quantity'         => 0,
                    'serials'          => [],
                ];
            }

            $serialRows = $this->serialRowsForEdit(
                $serialsByItem->get($item->id) ?? collect(),
                $grouped[$pid]['attrDefs']
            );

            if ($item->color_id) {
                $grouped[$pid]['has_colors'] = true;
                $grouped[$pid]['colors'][]   = [
                    'id'               => $item->color_id,
                    'name'             => $item->color_name,
                    'hex_code'         => $product?->colors->find($item->color_id)?->hex_code ?? '',
                    'quantity'         => (int) $item->quantity,
                    'serials'          => $serialRows,
                    'sharedAttributes' => (object) [],
                    'sharedCost'       => '',
                    'sharedSelling'    => '',
                ];
                $grouped[$pid]['quantity'] += (int) $item->quantity;
            } else {
                $grouped[$pid]['quantity'] = (int) $item->quantity;
                $grouped[$pid]['serials']  = $serialRows;
            }
        }

        // Colored products: append the product's other colors with qty 0 so
        // more units can be added, matching the create form's color list.
        foreach ($grouped as &$g) {
            if (!$g['has_colors']) continue;
            $presentIds = array_column($g['colors'], 'id');
            $allColors  = $purchase->items->firstWhere('product_id', $g['id'])?->product?->colors ?? collect();
            foreach ($allColors as $c) {
                if (!in_array($c->id, $presentIds)) {
                    $g['colors'][] = [
                        'id'               => $c->id,
                        'name'             => $c->name,
                        'hex_code'         => $c->hex_code ?? '',
                        'quantity'         => 0,
                        'serials'          => [],
                        'sharedAttributes' => (object) [],
                        'sharedCost'       => '',
                        'sharedSelling'    => '',
                    ];
                }
            }
        }
        unset($g);

        $draft = [
            'vendor_id'               => $purchase->vendor_id,
            'purchase_date'           => $purchase->purchase_date->format('Y-m-d'),
            'reference'               => $purchase->reference,
            'notes'                   => $purchase->notes,
            'payment_method'          => $purchase->payment_method,
            'bank_account_id'         => $purchase->payment_method === 'bank_transfer' ? $purchase->bank_account_id : null,
            'partial_pay_via'         => $purchase->payment_method === 'partial' && $purchase->bank_account_id ? 'bank' : 'cash',
            'partial_bank_account_id' => $purchase->payment_method === 'partial' ? $purchase->bank_account_id : null,
            'amount_paid'             => (float) $purchase->amount_paid,
            'ui_state'                => json_encode(array_values($grouped)),
        ];

        $editPurchase = $purchase;

        return view('admin.purchases.create', compact(
            'vendors', 'products', 'categories', 'brands', 'bankAccounts',
            'serialAttributeDefs', 'draft', 'editPurchase'
        ));
    }

    /**
     * Map a purchase item's serial rows into the create form's Alpine shape.
     * Sold/returned/transferred units come first and are flagged locked so
     * the form renders them read-only and quantity cuts can't drop them.
     */
    private function serialRowsForEdit($serials, array $attrDefs): array
    {
        $defNames = array_column($attrDefs, 'name');

        return collect($serials)
            ->sortBy(fn($s) => ($s->status === 'in_stock' && $s->shop_id === null) ? 1 : 0)
            ->values()
            ->map(function ($s) use ($defNames) {
                $locked = $s->status !== 'in_stock' || $s->shop_id !== null;
                $attrs  = $s->attributes ?? [];
                $known  = array_filter($attrs, fn($k) => in_array($k, $defNames), ARRAY_FILTER_USE_KEY);
                $extra  = array_diff_key($attrs, $known);

                return [
                    'id'              => $s->id,
                    'serial'          => $s->serial_number,
                    'cost_price'      => $s->cost_price !== null ? (float) $s->cost_price : '',
                    'selling_price'   => $s->selling_price !== null ? (float) $s->selling_price : '',
                    'attributes'      => (object) $known,
                    'extraFields'     => collect($extra)->map(fn($v, $k) => ['key' => $k, 'value' => $v])->values()->all(),
                    'serialError'     => null,
                    'serialChecking'  => false,
                    'image_path'      => $s->image,
                    'imagePreviewUrl' => $s->image ? asset('storage/' . $s->image) : null,
                    'imageUploading'  => false,
                    'imageError'      => null,
                    'locked'          => $locked,
                    'lockedReason'    => $locked ? ($s->shop_id ? 'TRANSFERRED' : strtoupper($s->status)) : null,
                ];
            })->all();
    }

    public function update(Request $request, Purchase $purchase)
    {
        $request->validate($this->purchaseValidationRules(), $this->purchaseValidationMessages());

        // Serial checks: same as create, but this purchase's own serials don't conflict
        if ($err = $this->serialConflictError($request->items, $purchase->id)) {
            return back()->withErrors(['items' => $err])->withInput();
        }
        if ($err = $this->colorRequirementError($request->items)) {
            return back()->withErrors(['items' => $err])->withInput();
        }

        try {
            DB::transaction(function () use ($request, $purchase) {
                $purchase->load('items');

                // ── 0. Guard: sold/returned/transferred serials must stay ──
                // They're rendered locked on the form and must come back in
                // the payload — dropping one would corrupt sales history.
                $lockedSerials = SerialNumber::where('purchase_id', $purchase->id)
                    ->where(fn($q) => $q->where('status', '!=', 'in_stock')->orWhereNotNull('shop_id'))
                    ->get();
                $submittedIds = collect($request->items)
                    ->flatMap(fn($row) => collect($row['serials'] ?? [])->pluck('id'))
                    ->filter()->map(fn($v) => (int) $v)->all();
                $missing = $lockedSerials->reject(fn($s) => in_array($s->id, $submittedIds));
                if ($missing->isNotEmpty()) {
                    throw new \RuntimeException(
                        'Cannot update: serial(s) ' . $missing->pluck('serial_number')->take(5)->join(', ')
                        . ' are sold, returned or transferred and cannot be removed from this purchase.'
                    );
                }

                // ── 1. Guard: verify stock reversal won't go negative ─────
                foreach ($purchase->items as $old) {
                    $product = Product::find($old->product_id);
                    if (!$product) continue;

                    if ($old->color_id) {
                        $color = ProductColor::find($old->color_id);
                        if ($color && ($color->stock_quantity - $old->quantity) < 0) {
                            throw new \RuntimeException(
                                "Cannot update: reversing {$old->quantity} units of \"{$product->name}\" ({$old->color_name}) would make its color stock negative (current: {$color->stock_quantity})."
                            );
                        }
                    } elseif (($product->stock_quantity - $old->quantity) < 0) {
                        throw new \RuntimeException(
                            "Cannot update: reversing {$old->quantity} units of \"{$product->name}\" would make stock negative (current: {$product->stock_quantity})."
                        );
                    }
                }

                // ── 2. Reverse old stock ───────────────────────────────────
                foreach ($purchase->items as $old) {
                    $product = Product::find($old->product_id);
                    if (!$product) continue;

                    if ($old->color_id) {
                        ProductColor::where('id', $old->color_id)->decrement('stock_quantity', $old->quantity);
                    }
                    $before = (int) $product->stock_quantity;
                    $after  = $before - (int) $old->quantity;
                    $product->decrement('stock_quantity', $old->quantity);

                    StockMovement::create([
                        'product_id'      => $product->id,
                        'type'            => 'adjustment',
                        'quantity'        => -(int) $old->quantity,
                        'before_quantity' => $before,
                        'after_quantity'  => $after,
                        'reference'       => $purchase->reference ?? 'PUR-'.$purchase->id,
                        'note'            => 'Purchase edit — old stock reversed',
                        'user_id'         => auth()->id(),
                    ]);
                }

                // ── 3. Reverse old vendor ledger for this purchase ────────
                $oldCredit = VendorLedger::where('purchase_id', $purchase->id)->where('type', 'credit')->sum('amount');
                if ($oldCredit > 0) {
                    $oldVendor = Vendor::find($purchase->vendor_id);
                    $oldVendor?->decrement('balance', $oldCredit);
                }
                VendorLedger::where('purchase_id', $purchase->id)->delete();

                // ── 4. Delete old items ───────────────────────────────────
                $purchase->items()->delete();

                // ── 5. Recalculate totals from new items (serialized lines
                //       cost = sum of their per-unit serial cost prices) ────
                $newItems   = $request->items;
                $subtotal   = collect($newItems)->sum(fn($i) => $this->lineCost($i));
                $total      = $subtotal;
                $payMethod  = $request->payment_method;
                $bankAccId  = $payMethod === 'bank_transfer'
                    ? ($request->bank_account_id ?: null)
                    : ($payMethod === 'partial' && $request->partial_pay_via === 'bank'
                        ? ($request->partial_bank_account_id ?: null)
                        : null);
                $amountPaid = match ($payMethod) {
                    'cash', 'bank_transfer' => $total,
                    'credit'                => 0.0,
                    'partial'               => min((float)($request->amount_paid ?? 0), $total),
                };
                $payStatus = match (true) {
                    $amountPaid >= $total => 'paid',
                    $amountPaid > 0       => 'partial',
                    default               => 'unpaid',
                };

                // ── 6. Update purchase record ─────────────────────────────
                $purchase->update([
                    'reference'       => $request->reference,
                    'vendor_id'       => $request->vendor_id,
                    'purchase_date'   => $request->purchase_date,
                    'subtotal'        => $subtotal,
                    'total'           => $total,
                    'payment_method'  => $payMethod,
                    'bank_account_id' => $bankAccId,
                    'amount_paid'     => $amountPaid,
                    'payment_status'  => $payStatus,
                    'notes'           => $request->notes,
                ]);

                // ── 7. Apply new items (and reconcile serials) ────────────
                $keptSerialIds = [];
                foreach ($newItems as $row) {
                    $product   = Product::find($row['product_id']);
                    if (!$product) continue;
                    $colorId   = !empty($row['color_id'])   ? (int)$row['color_id']   : null;
                    $colorName = !empty($row['color_name']) ? $row['color_name']       : null;
                    $lineTotal = $this->lineCost($row);
                    $unitCost  = ($product->is_serialized && (int) $row['quantity'] > 0)
                        ? round($lineTotal / (int) $row['quantity'], 2)
                        : (float) $row['unit_cost'];

                    $purchaseItem = $purchase->items()->create([
                        'product_id'   => $product->id,
                        'product_name' => $product->name,
                        'color_id'     => $colorId,
                        'color_name'   => $colorName,
                        'quantity'     => $row['quantity'],
                        'unit_cost'    => $unitCost,
                        'line_total'   => $lineTotal,
                    ]);

                    // Serial reconciliation: rows with an id are this purchase's
                    // existing serials (updated in place, or just relinked when
                    // locked); rows without an id are new units.
                    if ($product->is_serialized && !empty($row['serials'])) {
                        foreach ($row['serials'] as $snData) {
                            $snVal = trim($snData['serial'] ?? '');
                            if ($snVal === '') continue;

                            $existing = !empty($snData['id'])
                                ? SerialNumber::where('id', (int) $snData['id'])
                                    ->where('purchase_id', $purchase->id)
                                    ->first()
                                : null;

                            if ($existing) {
                                $isLocked = $existing->status !== 'in_stock' || $existing->shop_id !== null;
                                if ($isLocked) {
                                    // Sold/transferred: only relink to the recreated item
                                    $existing->update(['purchase_item_id' => $purchaseItem->id]);
                                } else {
                                    $existing->update([
                                        'serial_number'    => $snVal,
                                        'cost_price'       => isset($snData['cost_price']) && is_numeric($snData['cost_price'])
                                                              ? $snData['cost_price'] : null,
                                        'selling_price'    => isset($snData['selling_price']) && is_numeric($snData['selling_price'])
                                                              ? $snData['selling_price'] : null,
                                        'attributes'       => !empty($snData['attributes']) ? $snData['attributes'] : null,
                                        'image'            => $snData['image_path'] ?? null,
                                        'product_id'       => $product->id,
                                        'purchase_item_id' => $purchaseItem->id,
                                    ]);
                                }
                                $keptSerialIds[] = $existing->id;
                            } else {
                                $new = SerialNumber::create([
                                    'product_id'       => $product->id,
                                    'serial_number'    => $snVal,
                                    'cost_price'       => isset($snData['cost_price']) && is_numeric($snData['cost_price'])
                                                          ? $snData['cost_price'] : null,
                                    'selling_price'    => isset($snData['selling_price']) && is_numeric($snData['selling_price'])
                                                          ? $snData['selling_price'] : null,
                                    'attributes'       => !empty($snData['attributes']) ? $snData['attributes'] : null,
                                    'image'            => $snData['image_path'] ?? null,
                                    'status'           => 'in_stock',
                                    'purchase_id'      => $purchase->id,
                                    'purchase_item_id' => $purchaseItem->id,
                                ]);
                                $keptSerialIds[] = $new->id;
                            }
                        }
                    }

                    if ($colorId) {
                        ProductColor::where('id', $colorId)->increment('stock_quantity', $row['quantity']);
                    }

                    $product->refresh();
                    $before = (int) $product->stock_quantity;
                    $after  = $before + (int) $row['quantity'];
                    $product->increment('stock_quantity', $row['quantity']);
                    $product->update(['cost_price' => $unitCost]);

                    StockMovement::create([
                        'product_id'      => $product->id,
                        'type'            => 'purchase',
                        'quantity'        => (int) $row['quantity'],
                        'before_quantity' => $before,
                        'after_quantity'  => $after,
                        'reference'       => $purchase->reference ?? 'PUR-'.$purchase->id,
                        'note'            => 'Purchase edit — new stock applied',
                        'user_id'         => auth()->id(),
                    ]);
                }

                // In-stock serials of this purchase that were dropped from the
                // form are removed (locked ones are guaranteed present above)
                SerialNumber::where('purchase_id', $purchase->id)
                    ->where('status', 'in_stock')
                    ->whereNull('shop_id')
                    ->whereNotIn('id', $keptSerialIds)
                    ->delete();

                // ── 8. New vendor ledger entry if on credit ───────────────
                if ($payStatus !== 'paid') {
                    $vendor  = Vendor::find($request->vendor_id);
                    $owed    = $total - $amountPaid;
                    $newBal  = (float)$vendor->balance + $owed;
                    VendorLedger::create([
                        'vendor_id'     => $vendor->id,
                        'purchase_id'   => $purchase->id,
                        'type'          => 'credit',
                        'amount'        => $owed,
                        'balance_after' => $newBal,
                        'description'   => $payStatus === 'partial'
                            ? "Partial payment — Rs.{$amountPaid} paid, Rs.{$owed} on credit (edited)"
                            : "Purchase on credit (edited)",
                        'created_by'    => auth()->id(),
                    ]);
                    $vendor->update(['balance' => $newBal]);
                }
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.purchases.show', $purchase)
            ->with('success', 'Purchase updated — stock and ledger adjusted.');
    }

    public function destroy(Purchase $purchase)
    {
        try {
            DB::transaction(function () use ($purchase) {
                $purchase->load('items');

                // ── 0. Guard: serials from this purchase must all be untouched ──
                // Sold/returned units belong to sales history; transferred units
                // sit at a sub shop — deleting the purchase would corrupt both.
                $blocked = SerialNumber::where('purchase_id', $purchase->id)
                    ->where(fn($q) => $q->where('status', '!=', 'in_stock')->orWhereNotNull('shop_id'))
                    ->get();
                if ($blocked->isNotEmpty()) {
                    $list = $blocked->take(5)
                        ->map(fn($s) => $s->serial_number . ' (' . ($s->shop_id ? 'transferred' : $s->status) . ')')
                        ->join(', ');
                    throw new \RuntimeException(
                        'Cannot delete: ' . $blocked->count() . ' serial(s) from this purchase are sold, returned or transferred — ' . $list . '. Handle those first.'
                    );
                }

                // ── 1. Guard: verify stock reversal won't go negative ─────
                foreach ($purchase->items as $item) {
                    $product = Product::find($item->product_id);
                    if (!$product) continue;

                    if ($item->color_id) {
                        $color = ProductColor::find($item->color_id);
                        if ($color && ($color->stock_quantity - $item->quantity) < 0) {
                            throw new \RuntimeException(
                                "Cannot delete: reversing {$item->quantity} units of \"{$product->name}\" ({$item->color_name}) would make its color stock negative (current: {$color->stock_quantity}). Adjust stock first."
                            );
                        }
                    } elseif (($product->stock_quantity - $item->quantity) < 0) {
                        throw new \RuntimeException(
                            "Cannot delete: reversing {$item->quantity} units of \"{$product->name}\" would make stock negative (current: {$product->stock_quantity}). Adjust stock first."
                        );
                    }
                }

                // ── 2. Reverse stock ──────────────────────────────────────
                foreach ($purchase->items as $item) {
                    $product = Product::find($item->product_id);
                    if (!$product) continue;

                    if ($item->color_id) {
                        ProductColor::where('id', $item->color_id)->decrement('stock_quantity', $item->quantity);
                    }
                    $before = (int) $product->stock_quantity;
                    $after  = $before - (int) $item->quantity;
                    $product->decrement('stock_quantity', $item->quantity);

                    StockMovement::create([
                        'product_id'      => $product->id,
                        'type'            => 'adjustment',
                        'quantity'        => -(int) $item->quantity,
                        'before_quantity' => $before,
                        'after_quantity'  => $after,
                        'reference'       => $purchase->reference ?? 'PUR-'.$purchase->id,
                        'note'            => 'Purchase deleted — stock reversed',
                        'user_id'         => auth()->id(),
                    ]);
                }

                // ── 3. Reverse vendor ledger ──────────────────────────────
                $creditTotal = VendorLedger::where('purchase_id', $purchase->id)->where('type', 'credit')->sum('amount');
                if ($creditTotal > 0 && $purchase->vendor_id) {
                    $vendor = Vendor::find($purchase->vendor_id);
                    $vendor?->decrement('balance', $creditTotal);
                }
                VendorLedger::where('purchase_id', $purchase->id)->delete();

                // ── 4. Hard delete (serials too — the guard above ensures
                //       they're all untouched in_stock units) ──────────────
                SerialNumber::where('purchase_id', $purchase->id)->delete();
                $purchase->items()->delete();
                $purchase->delete();
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['delete' => $e->getMessage()]);
        }

        return redirect()->route('admin.purchases.index')
            ->with('success', 'Purchase deleted — stock reversed and ledger updated.');
    }

    public function report(Request $request)
    {
        $query = Purchase::with(['vendor', 'items'])->latest('purchase_date');

        $productId = $request->filled('product') ? (int) $request->product : null;

        if ($request->filled('vendor')) {
            $query->where('vendor_id', $request->vendor);
        }
        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }
        if ($request->filled('from')) {
            $query->whereDate('purchase_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('purchase_date', '<=', $request->to);
        }
        if ($productId) {
            $query->whereHas('items', fn($q) => $q->where('product_id', $productId));
        }

        $purchases = $query->get();
        $vendors   = Vendor::orderBy('name')->get(['id', 'name']);
        $products  = Product::orderBy('name')->get(['id', 'name']);

        $summary = [
            'total_spent'  => $purchases->sum('total'),
            'total_paid'   => $purchases->sum('amount_paid'),
            'total_unpaid' => $purchases->sum(fn($p) => $p->total - $p->amount_paid),
            'count'        => $purchases->count(),
        ];

        // ── Product-wise breakdown of the filtered purchases ──────────────────
        $purchaseDates = $purchases->pluck('purchase_date', 'id');

        $byProduct = $purchases->flatMap->items
            ->when($productId, fn($items) => $items->where('product_id', $productId))
            ->groupBy('product_name')
            ->map(function ($items, $name) use ($purchaseDates) {
                $qty    = $items->sum('quantity');
                $spent  = $items->sum('line_total');
                $latest = $items->sortByDesc(fn($i) => $purchaseDates[$i->purchase_id] ?? null)->first();

                return [
                    'name'      => $name,
                    'qty'       => $qty,
                    'spent'     => $spent,
                    'avg_cost'  => $qty > 0 ? $spent / $qty : 0,
                    'last_cost' => (float) $latest->unit_cost,
                ];
            })
            ->sortByDesc('spent')
            ->values();

        return view('admin.reports.purchases', compact('purchases', 'vendors', 'products', 'summary', 'byProduct', 'productId'));
    }

    public function tempSerialImage(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['image' => 'required|image|max:4096']);
        $path = $request->file('image')->store('serials', 'public');
        return response()->json([
            'path' => $path,
            'url'  => Storage::disk('public')->url($path),
        ]);
    }
}
