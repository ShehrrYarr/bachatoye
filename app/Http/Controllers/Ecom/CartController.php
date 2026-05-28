<?php

namespace App\Http\Controllers\Ecom;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductColor;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private function cart(): array
    {
        return session('cart', []);
    }

    private function saveCart(array $cart): void
    {
        session(['cart' => $cart]);
    }

    public function index()
    {
        $cart     = $this->cart();
        $items    = $this->hydrateCart($cart);
        $subtotal = collect($items)->sum('line_total');

        $deliveryCharge = $this->resolveDelivery($items, $subtotal);

        // Coupon from session
        $couponCode     = session('coupon_code');
        $couponName     = session('coupon_name');
        $couponDiscount = (float) session('coupon_discount', 0);

        $total = max(0, $subtotal + $deliveryCharge - $couponDiscount);

        return view('ecom.cart', compact(
            'items', 'subtotal', 'deliveryCharge', 'total',
            'couponCode', 'couponName', 'couponDiscount'
        ));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1|max:100',
            'color_id'   => 'nullable|exists:product_colors,id',
        ]);

        $product = Product::active()->inStock()->with('colors')->findOrFail($request->product_id);

        // Resolve color selection
        $color     = null;
        $colorName = null;

        if ($product->colors->isNotEmpty()) {
            if ($request->filled('color_id')) {
                $color = $product->colors->find($request->color_id);
            }
            if (!$color) {
                return back()->withErrors(['color' => 'Please select a color before adding to cart.']);
            }
            if ($color->stock_quantity <= 0) {
                return back()->withErrors(['color' => "Sorry, {$color->name} is out of stock."]);
            }
            $colorName = $color->name;
        }

        $cart = $this->cart();
        $key  = $color ? "product_{$product->id}_color_{$color->id}" : "product_{$product->id}";

        $currentQty = $cart[$key]['quantity'] ?? 0;
        $newQty     = $currentQty + $request->quantity;

        if ($color && $newQty > $color->stock_quantity) {
            $newQty = $color->stock_quantity;
        } elseif (!$color && $product->track_inventory && $newQty > $product->stock_quantity) {
            $newQty = $product->stock_quantity;
        }

        $cart[$key] = [
            'product_id' => $product->id,
            'color_id'   => $color?->id,
            'color_name' => $colorName,
            'quantity'   => $newQty,
            'price'      => $product->getDiscountedPrice(),
        ];

        $this->saveCart($cart);

        if ($request->wantsJson()) {
            return response()->json(['count' => array_sum(array_column($cart, 'quantity'))]);
        }

        if ($request->boolean('buy_now')) {
            return redirect()->route('checkout.index');
        }

        return back()->with('success', "{$product->name} added to cart.");
    }

    public function update(Request $request, string $rowId)
    {
        $request->validate(['quantity' => 'required|integer|min:0']);
        $cart = $this->cart();

        if ($request->quantity === 0) {
            unset($cart[$rowId]);
        } else {
            if (isset($cart[$rowId])) {
                $cart[$rowId]['quantity'] = $request->quantity;
            }
        }

        $this->saveCart($cart);
        return back();
    }

    public function remove(string $rowId)
    {
        $cart = $this->cart();
        unset($cart[$rowId]);
        $this->saveCart($cart);
        return back()->with('success', 'Item removed.');
    }

    public function clear()
    {
        $this->saveCart([]);
        // Also clear any applied coupon
        session()->forget(['coupon_id', 'coupon_code', 'coupon_name', 'coupon_discount']);
        return back()->with('success', 'Cart cleared.');
    }

    private function resolveDelivery(array $items, float $subtotal): float
    {
        $charge    = (float) \App\Models\Setting::get('delivery_charge', 150);
        $freeAbove = (float) \App\Models\Setting::get('free_delivery_above', 5000);

        if ($subtotal >= $freeAbove) return 0;

        foreach ($items as $item) {
            $product = $item['product'];
            if ($product->free_delivery) return 0;
            if ($product->category && $product->category->free_delivery) return 0;
        }

        return $charge;
    }

    private function hydrateCart(array $cart): array
    {
        $productIds = array_column($cart, 'product_id');
        $products   = Product::whereIn('id', $productIds)->with(['images', 'category'])->get()->keyBy('id');

        return collect($cart)->map(function ($item, $rowId) use ($products) {
            $product = $products[$item['product_id']] ?? null;
            if (!$product) return null;

            return [
                'row_id'     => $rowId,
                'product'    => $product,
                'color_id'   => $item['color_id'] ?? null,
                'color_name' => $item['color_name'] ?? null,
                'quantity'   => $item['quantity'],
                'price'      => $item['price'],
                'line_total' => $item['price'] * $item['quantity'],
            ];
        })->filter()->values()->toArray();
    }
}
