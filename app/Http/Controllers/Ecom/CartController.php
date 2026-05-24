<?php

namespace App\Http\Controllers\Ecom;

use App\Http\Controllers\Controller;
use App\Models\Product;
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

        $deliveryCharge = (float) \App\Models\Setting::get('delivery_charge', 150);
        $freeAbove      = (float) \App\Models\Setting::get('free_delivery_above', 5000);
        if ($subtotal >= $freeAbove) $deliveryCharge = 0;

        $total = $subtotal + $deliveryCharge;

        return view('ecom.cart', compact('items', 'subtotal', 'deliveryCharge', 'total'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1|max:100',
        ]);

        $product = Product::active()->inStock()->findOrFail($request->product_id);

        $cart = $this->cart();
        $key  = "product_{$product->id}";

        $currentQty = $cart[$key]['quantity'] ?? 0;
        $newQty     = $currentQty + $request->quantity;

        if ($product->track_inventory && $newQty > $product->stock_quantity) {
            $newQty = $product->stock_quantity;
        }

        $cart[$key] = [
            'product_id' => $product->id,
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
        return back()->with('success', 'Cart cleared.');
    }

    private function hydrateCart(array $cart): array
    {
        $productIds = array_column($cart, 'product_id');
        $products   = Product::whereIn('id', $productIds)->with('images')->get()->keyBy('id');

        return collect($cart)->map(function ($item, $rowId) use ($products) {
            $product = $products[$item['product_id']] ?? null;
            if (!$product) return null;

            return [
                'row_id'     => $rowId,
                'product'    => $product,
                'quantity'   => $item['quantity'],
                'price'      => $item['price'],
                'line_total' => $item['price'] * $item['quantity'],
            ];
        })->filter()->values()->toArray();
    }
}
