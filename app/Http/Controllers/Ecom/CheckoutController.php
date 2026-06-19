<?php

namespace App\Http\Controllers\Ecom;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function index()
    {
        $cart = session('cart', []);
        if (empty($cart)) return redirect()->route('cart.index')->with('error', 'Your cart is empty.');

        $items = $this->hydrateCart($cart);

        $subtotal       = collect($items)->sum('line_total');
        $deliveryCharge = $this->resolveDelivery($items, $subtotal);

        $couponCode     = session('coupon_code');
        $couponDiscount = (float) session('coupon_discount', 0);
        $total = max(0, $subtotal + $deliveryCharge - $couponDiscount);

        $codAvailable        = collect($items)->every(fn($item) => $item['product']->cod_enabled);
        $allBankFreeDelivery = $deliveryCharge > 0
            && collect($items)->every(fn($item) => $item['product']->bank_free_delivery);

        $cartProductIds = collect($items)->pluck('product.id')->all();
        $triggeredDeals = Deal::active()
            ->where('type', 'bundle_free')
            ->with(['buyProducts', 'freeProducts'])
            ->get()
            ->filter(fn($deal) => $deal->isTriggeredBy($cartProductIds))
            ->values();

        return view('ecom.checkout', compact('items', 'subtotal', 'deliveryCharge', 'couponCode', 'couponDiscount', 'total', 'codAvailable', 'allBankFreeDelivery', 'triggeredDeals'));
    }

    public function store(Request $request)
    {
        $cart = session('cart', []);
        if (empty($cart)) return redirect()->route('cart.index');

        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'phone'          => 'required|string|max:20',
            'address'        => 'required|string',
            'city'           => 'required|string|max:100',
            'notes'          => 'nullable|string',
            'payment_method' => 'required|in:cash,bank_transfer',
            'payment_proof'  => 'required_if:payment_method,bank_transfer|nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);

        $items    = $this->hydrateCart($cart);
        $subtotal = collect($items)->sum('line_total');

        $deliveryCharge = $this->resolveDelivery($items, $subtotal, $data['payment_method']);

        $couponId       = session('coupon_id');
        $couponDiscount = (float) session('coupon_discount', 0);
        $total          = max(0, $subtotal + $deliveryCharge - $couponDiscount);

        $codAvailable = collect($items)->every(fn($item) => $item['product']->cod_enabled);
        if (!$codAvailable && $data['payment_method'] === 'cash') {
            return back()->withErrors(['payment_method' => 'Cash on Delivery is not available for one or more items in your cart.'])->withInput();
        }

        DB::beginTransaction();
        try {
            // Find or create customer by phone
            $customer = Customer::where('phone', $data['phone'])->first();
            if (!$customer) {
                $customer = Customer::create([
                    'name'          => $data['name'],
                    'phone'         => $data['phone'],
                    'city'          => $data['city'],
                    'source'        => 'online',
                    'khata_enabled' => false,
                ]);
            }

            $proofPath = null;
            if ($request->hasFile('payment_proof')) {
                $proofPath = $request->file('payment_proof')->store('payment-proofs', 'public');
            }

            $order = Order::create([
                'source'           => 'ecommerce',
                'customer_id'      => $customer->id,
                'customer_name'    => $data['name'],
                'customer_phone'   => $data['phone'],
                'delivery_address' => $data['address'],
                'city'             => $data['city'],
                'delivery_notes'   => $data['notes'] ?? null,
                'subtotal'         => $subtotal,
                'delivery_charge'  => $deliveryCharge,
                'coupon_id'        => $couponId,
                'coupon_discount'  => $couponDiscount,
                'total'            => $total,
                'payment_method'   => $data['payment_method'],
                'payment_status'   => 'pending',
                'payment_proof'    => $proofPath,
                'status'           => 'pending',
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id'        => $order->id,
                    'product_id'      => $item['product']->id,
                    'product_name'    => $item['product']->name,
                    'color_name'      => $item['color_name'] ?? null,
                    'product_barcode' => $item['product']->barcode,
                    'unit_price'      => $item['price'],
                    'cost_price'      => $item['product']->cost_price,
                    'quantity'        => $item['quantity'],
                    'line_total'      => $item['line_total'],
                ]);
                // Stock is deducted when order status changes to "Delivered" in OrderController
            }

            // Add free items from triggered bundle_free deals
            $cartProductIds = collect($items)->pluck('product.id')->all();
            $triggeredDeals = Deal::active()
                ->where('type', 'bundle_free')
                ->with(['buyProducts', 'freeProducts'])
                ->get()
                ->filter(fn($deal) => $deal->isTriggeredBy($cartProductIds));

            $addedFreeProductIds = [];
            foreach ($triggeredDeals as $deal) {
                foreach ($deal->freeProducts as $freeProduct) {
                    if (in_array($freeProduct->id, $addedFreeProductIds)) continue;
                    $addedFreeProductIds[] = $freeProduct->id;
                    OrderItem::create([
                        'order_id'        => $order->id,
                        'product_id'      => $freeProduct->id,
                        'product_name'    => $freeProduct->name . ' (Free — ' . $deal->name . ')',
                        'color_name'      => null,
                        'product_barcode' => $freeProduct->barcode,
                        'unit_price'      => 0,
                        'cost_price'      => $freeProduct->cost_price,
                        'quantity'        => 1,
                        'line_total'      => 0,
                    ]);
                }
            }

            DB::commit();
            session()->forget(['cart', 'coupon_id', 'coupon_code', 'coupon_name', 'coupon_discount']);

            return redirect()->route('checkout.success', $order->order_number);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Order failed. Please try again.']);
        }
    }

    public function success(string $order)
    {
        $order = Order::where('order_number', $order)->firstOrFail();
        return view('ecom.checkout-success', compact('order'));
    }

    public function uploadProof(Request $request, string $order)
    {
        $request->validate(['proof' => 'required|image|max:5120']);
        $orderModel = Order::where('order_number', $order)->firstOrFail();
        $path = $request->file('proof')->store('payment-proofs', 'public');
        $orderModel->update(['payment_proof' => $path]);
        return back()->with('success', 'Payment proof uploaded. We will verify and confirm your order.');
    }

    /**
     * Resolve delivery charge for the given cart items and subtotal.
     * Free delivery if:
     *   - subtotal >= free_delivery_above threshold, OR
     *   - any cart item's product has free_delivery = true, OR
     *   - any cart item's product belongs to a category with free_delivery = true, OR
     *   - payment is bank_transfer AND every cart item has bank_free_delivery = true
     */
    private function resolveDelivery(array $items, float $subtotal, ?string $paymentMethod = null): float
    {
        $charge    = (float) Setting::get('delivery_charge', 150);
        $freeAbove = (float) Setting::get('free_delivery_above', 5000);

        if ($subtotal >= $freeAbove) return 0;

        foreach ($items as $item) {
            $product = $item['product'];
            if ($product->free_delivery) return 0;
            if ($product->category && $product->category->free_delivery) return 0;
        }

        if ($paymentMethod === 'bank_transfer'
            && collect($items)->every(fn($item) => $item['product']->bank_free_delivery)) {
            return 0;
        }

        return $charge;
    }

    private function hydrateCart(array $cart): array
    {
        $productIds = array_column($cart, 'product_id');
        $products   = Product::with('category')->whereIn('id', $productIds)->get()->keyBy('id');

        return collect($cart)->map(function ($item) use ($products) {
            $product = $products[$item['product_id']] ?? null;
            if (!$product) return null;
            return [
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
