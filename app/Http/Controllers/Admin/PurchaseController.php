<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Vendor;
use App\Models\VendorLedger;
use App\Services\BarcodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::with('vendor')->latest('purchase_date');

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
        $bankAccounts = BankAccount::active()->orderBy('sort_order')->get();
        return view('admin.purchases.create', compact('vendors', 'products', 'categories', 'brands', 'bankAccounts'));
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

    public function store(Request $request)
    {
        $request->validate([
            'purchase_date'       => 'required|date',
            'vendor_id'           => 'required|exists:vendors,id',
            'reference'           => 'nullable|string|max:100',
            'payment_method'      => 'required|in:cash,bank_transfer,credit,partial',
            'bank_account_id'     => 'nullable|exists:bank_accounts,id',
            'amount_paid'         => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string|max:1000',
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'required|exists:products,id',
            'items.*.quantity'    => 'required|integer|min:1',
            'items.*.unit_cost'   => 'required|numeric|min:0',
            'items.*.color_id'    => 'nullable|exists:product_colors,id',
            'items.*.color_name'  => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($request) {
            $items   = $request->items;
            $subtotal = collect($items)->sum(fn($i) => $i['quantity'] * $i['unit_cost']);
            $total    = $subtotal;

            $payMethod    = $request->payment_method;
            $bankAccountId = in_array($payMethod, ['bank_transfer']) ? ($request->bank_account_id ?: null) : null;
            $amountPaid = match ($payMethod) {
                'cash'          => $total,
                'bank_transfer' => $total,
                'credit'        => 0,
                'partial'       => min((float) ($request->amount_paid ?? 0), $total),
            };
            $payStatus = match (true) {
                $amountPaid >= $total => 'paid',
                $amountPaid > 0       => 'partial',
                default               => 'unpaid',
            };

            $purchase = Purchase::create([
                'reference'      => $request->reference,
                'vendor_id'      => $request->vendor_id,
                'purchase_date'  => $request->purchase_date,
                'subtotal'       => $subtotal,
                'total'          => $total,
                'payment_method'  => $payMethod,
                'bank_account_id' => $bankAccountId,
                'amount_paid'     => $amountPaid,
                'payment_status' => $payStatus,
                'notes'          => $request->notes,
                'created_by'     => auth()->id(),
            ]);

            foreach ($items as $row) {
                $product = Product::find($row['product_id']);
                $lineTotal = $row['quantity'] * $row['unit_cost'];
                $colorId   = !empty($row['color_id']) ? (int) $row['color_id'] : null;
                $colorName = !empty($row['color_name']) ? $row['color_name'] : null;

                $purchase->items()->create([
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'color_id'     => $colorId,
                    'color_name'   => $colorName,
                    'quantity'     => $row['quantity'],
                    'unit_cost'    => $row['unit_cost'],
                    'line_total'   => $lineTotal,
                ]);

                // If a color is specified, increment its stock too
                if ($colorId) {
                    ProductColor::where('id', $colorId)->increment('stock_quantity', $row['quantity']);
                }

                // Record stock movement (capture before/after)
                $before = (int) $product->stock_quantity;
                $after  = $before + $row['quantity'];

                $product->increment('stock_quantity', $row['quantity']);

                $updates = ['cost_price' => $row['unit_cost']];
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
            if ($request->vendor_id && $payStatus !== 'paid') {
                $vendor    = Vendor::find($request->vendor_id);
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
        });

        $rPrefix = auth()->user()->hasRole('admin') ? 'admin' : 'salesman';
        return redirect()->route("{$rPrefix}.purchases.index")->with('success', 'Purchase recorded and stock updated.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['vendor', 'items.product', 'createdBy']);
        return view('admin.purchases.show', compact('purchase'));
    }

    public function report(Request $request)
    {
        $query = Purchase::with('vendor')->latest('purchase_date');

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

        $purchases = $query->get();
        $vendors   = Vendor::orderBy('name')->get(['id', 'name']);

        $summary = [
            'total_spent'  => $purchases->sum('total'),
            'total_paid'   => $purchases->sum('amount_paid'),
            'total_unpaid' => $purchases->sum(fn($p) => $p->total - $p->amount_paid),
            'count'        => $purchases->count(),
        ];

        return view('admin.reports.purchases', compact('purchases', 'vendors', 'summary'));
    }
}
