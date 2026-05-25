<?php

namespace App\Http\Controllers\Ecom;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function apply(Request $request)
    {
        $request->validate(['coupon_code' => 'required|string|max:50']);

        $coupon = Coupon::with(['products', 'categories'])
            ->where('code', strtoupper(trim($request->coupon_code)))
            ->first();

        if (!$coupon || !$coupon->isActive()) {
            return back()->withErrors(['coupon_code' => 'This coupon code is invalid or has expired.'])->withInput();
        }

        // Hydrate cart to calculate eligible subtotal
        $cart = session('cart', []);
        if (empty($cart)) {
            return back()->withErrors(['coupon_code' => 'Your cart is empty.'])->withInput();
        }

        $productIds = array_column($cart, 'product_id');
        $products   = Product::whereIn('id', $productIds)->get(['id', 'category_id'])->keyBy('id');

        $cartItems = collect($cart)->map(function ($item) use ($products) {
            $product = $products[$item['product_id']] ?? null;
            if (!$product) return null;
            return [
                'product_id'  => $item['product_id'],
                'category_id' => $product->category_id,
                'quantity'    => $item['quantity'],
                'price'       => $item['price'],
                'line_total'  => $item['price'] * $item['quantity'],
            ];
        })->filter()->values();

        $fullSubtotal = $cartItems->sum('line_total');

        // Minimum order check
        if ($coupon->min_order_amount && $fullSubtotal < $coupon->min_order_amount) {
            return back()
                ->withErrors(['coupon_code' => 'Minimum order of Rs. ' . number_format($coupon->min_order_amount) . ' required for this coupon.'])
                ->withInput();
        }

        $discount = $coupon->calculateDiscount($cartItems);

        if ($discount <= 0) {
            return back()
                ->withErrors(['coupon_code' => 'This coupon does not apply to any items in your cart.'])
                ->withInput();
        }

        // Increment usage counter
        $coupon->increment('times_used');

        session([
            'coupon_id'       => $coupon->id,
            'coupon_code'     => $coupon->code,
            'coupon_name'     => $coupon->name,
            'coupon_discount' => $discount,
        ]);

        return back()->with('success', 'Coupon applied! You save Rs. ' . number_format($discount) . '.');
    }

    public function remove()
    {
        // Reverse the usage counter if a coupon was applied
        $couponId = session('coupon_id');
        if ($couponId) {
            Coupon::where('id', $couponId)->decrement('times_used');
        }

        session()->forget(['coupon_id', 'coupon_code', 'coupon_name', 'coupon_discount']);
        return back()->with('success', 'Coupon removed.');
    }
}
