<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::withCount(['products', 'categories'])->latest()->paginate(20);
        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        $products   = Product::active()->get(['id', 'name']);
        $categories = Category::active()->get(['id', 'name']);
        return view('admin.coupons.create', compact('products', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'             => 'required|string|max:50|unique:coupons|alpha_dash',
            'name'             => 'required|string|max:150',
            'description'      => 'nullable|string|max:500',
            'type'             => 'required|in:percentage,flat',
            'value'            => 'required|numeric|min:0.01',
            'min_order_amount' => 'nullable|numeric|min:0',
            'applies_to'       => 'required|in:all,products,categories',
            'is_active'        => 'boolean',
            'starts_at'        => 'nullable|date',
            'expires_at'       => 'nullable|date|after_or_equal:starts_at',
            'product_ids'      => 'array',
            'product_ids.*'    => 'exists:products,id',
            'category_ids'     => 'array',
            'category_ids.*'   => 'exists:categories,id',
        ]);

        $data['code']      = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active', true);

        $coupon = Coupon::create($data);

        if ($data['applies_to'] === 'products' && !empty($request->product_ids)) {
            $coupon->products()->sync($request->product_ids);
        }
        if ($data['applies_to'] === 'categories' && !empty($request->category_ids)) {
            $coupon->categories()->sync($request->category_ids);
        }

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created successfully.');
    }

    public function edit(Coupon $coupon)
    {
        $coupon->load(['products', 'categories']);
        $products   = Product::active()->get(['id', 'name']);
        $categories = Category::active()->get(['id', 'name']);
        return view('admin.coupons.edit', compact('coupon', 'products', 'categories'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $data = $request->validate([
            'code'             => 'required|string|max:50|unique:coupons,code,' . $coupon->id . '|alpha_dash',
            'name'             => 'required|string|max:150',
            'description'      => 'nullable|string|max:500',
            'type'             => 'required|in:percentage,flat',
            'value'            => 'required|numeric|min:0.01',
            'min_order_amount' => 'nullable|numeric|min:0',
            'applies_to'       => 'required|in:all,products,categories',
            'is_active'        => 'boolean',
            'starts_at'        => 'nullable|date',
            'expires_at'       => 'nullable|date|after_or_equal:starts_at',
            'product_ids'      => 'array',
            'product_ids.*'    => 'exists:products,id',
            'category_ids'     => 'array',
            'category_ids.*'   => 'exists:categories,id',
        ]);

        $data['code']      = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active', true);

        $coupon->update($data);

        $coupon->products()->sync(
            $data['applies_to'] === 'products' ? ($request->product_ids ?? []) : []
        );
        $coupon->categories()->sync(
            $data['applies_to'] === 'categories' ? ($request->category_ids ?? []) : []
        );

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted.');
    }

    public function toggle(Coupon $coupon)
    {
        $coupon->update(['is_active' => !$coupon->is_active]);
        return back()->with('success', $coupon->is_active ? 'Coupon activated.' : 'Coupon deactivated.');
    }
}
