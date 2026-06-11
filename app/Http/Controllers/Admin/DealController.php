<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Deal;
use App\Models\Product;
use Illuminate\Http\Request;

class DealController extends Controller
{
    public function index()
    {
        $deals = Deal::withCount('products')->latest()->paginate(20);
        return view('admin.deals.index', compact('deals'));
    }

    public function create()
    {
        $products   = Product::active()->get(['id', 'name']);
        $categories = Category::active()->get(['id', 'name']);
        return view('admin.deals.create', compact('products', 'categories'));
    }

    public function store(Request $request)
    {
        if ($request->type === 'bundle_free') {
            return $this->storeBundleFree($request);
        }

        $request->merge([
            'value'      => $request->type === 'flat'
                                ? $request->flat_discount
                                : $request->discount_value,
            'applies_to' => $request->filled('product_ids')
                                ? 'products'
                                : ($request->filled('category_ids') ? 'categories' : 'all'),
        ]);

        $data = $request->validate([
            'name'         => 'required|string|max:150',
            'code'         => 'nullable|string|max:50|unique:deals',
            'type'         => 'required|in:percentage,flat,buy_x_get_y',
            'value'        => 'required_unless:type,buy_x_get_y|numeric|min:0',
            'buy_quantity' => 'required_if:type,buy_x_get_y|nullable|integer|min:1',
            'get_quantity' => 'required_if:type,buy_x_get_y|nullable|integer|min:1',
            'applies_to'   => 'required|in:all,products,categories',
            'is_active'    => 'boolean',
            'starts_at'    => 'nullable|date',
            'ends_at'      => 'nullable|date|after_or_equal:starts_at',
            'product_ids'  => 'array',
            'category_ids' => 'array',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $deal = Deal::create($data);

        if ($data['applies_to'] === 'products' && !empty($request->product_ids)) {
            $deal->products()->sync($request->product_ids);
        }
        if ($data['applies_to'] === 'categories' && !empty($request->category_ids)) {
            $deal->categories()->sync($request->category_ids);
        }

        return redirect()->route('admin.deals.index')->with('success', 'Deal created.');
    }

    private function storeBundleFree(Request $request, ?Deal $deal = null): \Illuminate\Http\RedirectResponse
    {
        $uniqueRule = $deal ? 'unique:deals,code,' . $deal->id : 'unique:deals';

        $data = $request->validate([
            'name'              => 'required|string|max:150',
            'code'              => 'nullable|string|max:50|' . $uniqueRule,
            'is_active'         => 'boolean',
            'starts_at'         => 'nullable|date',
            'ends_at'           => 'nullable|date|after_or_equal:starts_at',
            'buy_product_ids'   => 'required|array|min:1',
            'buy_product_ids.*' => 'exists:products,id',
            'free_product_ids'  => 'required|array|min:1',
            'free_product_ids.*'=> 'exists:products,id',
        ]);

        $payload = [
            'name'       => $data['name'],
            'code'       => $data['code'] ?? null,
            'type'       => 'bundle_free',
            'value'      => 0,
            'applies_to' => 'products',
            'is_active'  => $request->boolean('is_active', true),
            'starts_at'  => $data['starts_at'] ?? null,
            'ends_at'    => $data['ends_at'] ?? null,
        ];

        if ($deal) {
            $deal->update($payload);
        } else {
            $deal = Deal::create($payload);
        }

        // Sync buy products (role = buy) and free products (role = free)
        $buySync  = collect($request->buy_product_ids)->mapWithKeys(fn($id) => [$id => ['role' => 'buy']]);
        $freeSync = collect($request->free_product_ids)->mapWithKeys(fn($id) => [$id => ['role' => 'free']]);
        $deal->products()->sync($buySync->merge($freeSync));

        return redirect()->route('admin.deals.index')->with('success', 'Deal ' . ($deal->wasRecentlyCreated ? 'created' : 'updated') . '.');
    }

    public function edit(Deal $deal)
    {
        $deal->load(['products', 'buyProducts', 'freeProducts', 'categories']);
        $products   = Product::active()->get(['id', 'name']);
        $categories = Category::active()->get(['id', 'name']);
        return view('admin.deals.edit', compact('deal', 'products', 'categories'));
    }

    public function update(Request $request, Deal $deal)
    {
        if ($request->type === 'bundle_free') {
            return $this->storeBundleFree($request, $deal);
        }

        $request->merge([
            'value'      => $request->type === 'flat'
                                ? $request->flat_discount
                                : $request->discount_value,
            'applies_to' => $request->filled('product_ids')
                                ? 'products'
                                : ($request->filled('category_ids') ? 'categories' : 'all'),
        ]);

        $data = $request->validate([
            'name'         => 'required|string|max:150',
            'code'         => 'nullable|string|max:50|unique:deals,code,' . $deal->id,
            'type'         => 'required|in:percentage,flat,buy_x_get_y,bundle_free',
            'value'        => 'required_unless:type,buy_x_get_y|numeric|min:0',
            'buy_quantity' => 'required_if:type,buy_x_get_y|nullable|integer|min:1',
            'get_quantity' => 'required_if:type,buy_x_get_y|nullable|integer|min:1',
            'applies_to'   => 'required|in:all,products,categories',
            'is_active'    => 'boolean',
            'starts_at'    => 'nullable|date',
            'ends_at'      => 'nullable|date|after_or_equal:starts_at',
            'product_ids'  => 'array',
            'category_ids' => 'array',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $deal->update($data);

        $deal->products()->sync($data['applies_to'] === 'products' ? ($request->product_ids ?? []) : []);
        $deal->categories()->sync($data['applies_to'] === 'categories' ? ($request->category_ids ?? []) : []);

        return redirect()->route('admin.deals.index')->with('success', 'Deal updated.');
    }

    public function destroy(Deal $deal)
    {
        $deal->delete();
        return redirect()->route('admin.deals.index')->with('success', 'Deal deleted.');
    }

    public function toggle(Deal $deal)
    {
        $deal->update(['is_active' => !$deal->is_active]);
        return back()->with('success', $deal->is_active ? 'Deal activated.' : 'Deal deactivated.');
    }
}
