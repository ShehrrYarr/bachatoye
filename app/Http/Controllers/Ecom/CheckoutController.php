<?php

namespace App\Http\Controllers\Ecom;

use App\Http\Controllers\Controller;
use App\Models\Customer;
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
        $deliveryCharge = (float) Setting::get('delivery_charge', 150);
        $freeAbove      = (float) Setting::get('free_delivery_above', 5000);
        if ($subtotal >= $freeAbove) $deliveryCharge = 0;
        $total = $subtotal + $deliveryCharge;

        return view('ecom.checkout', compact('items', 'subtotal', 'deliveryCharge', 'total'));
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
        ]);

        $items    = $this->hydrateCart($cart);
        $subtotal = collect($items)->sum('line_total');

        $deliveryCharge = (float) Setting::get('delivery_charge', 150);
        $freeAbove      = (float) Setting::get('free_delivery_above', 5000);
        if ($subtotal >= $freeAbove) $deliveryCharge = 0;
        $total = $subtotal + $deliveryCharge;

        DB::beginTransaction();
        try {
            // Find or create customer by phone
            $customer = Customer::where('phone', $data['phone'])->first();
            if (!$customer) {
                $customer = Customer::create([
                    'name'  => $data['name'],
                    'phone' => $data['phone'],
                    'city'  => $data['city'],
                ]);
            }

            $order = Order::create([
                'source'          => 'ecommerce',
                'customer_id'     => $customer->id,
                'customer_name'   => $data['name'],
                'customer_phone'  => $data['phone'],
                'delivery_address' => $data['address'],
                'city'            => $data['city'],
                'delivery_notes'  => $data['notes'] ?? null,
                'subtotal'        => $subtotal,
                'delivery_charge' => $deliveryCharge,
                'total'           => $total,
                'payment_method'  => $data['payment_method'],
                'payment_status'  => 'pending',
                'status'          => 'pending',
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id'       => $order->id,
                    'product_id'     => $item['product']->id,
                    'product_name'   => $item['product']->name,
                    'product_barcode' => $item['product']->barcode,
                    'unit_price'     => $item['price'],
                    'cost_price'     => $item['product']->cost_price,
                    'quantity'       => $item['quantity'],
                    'line_total'     => $item['line_total'],
                ]);
                // Stock is deducted when order status changes to "Delivered" in OrderController
            }

            DB::commit();
            session()->forget('cart');

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

    private function hydrateCart(array $cart): array
    {
        $productIds = array_column($cart, 'product_id');
        $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');

        return collect($cart)->map(function ($item) use ($products) {
            $product = $products[$item['product_id']] ?? null;
            if (!$product) return null;
            return [
                'product'    => $product,
                'quantity'   => $item['quantity'],
                'price'      => $item['price'],
                'line_total' => $item['price'] * $item['quantity'],
            ];
        })->filter()->values()->toArray();
    }
}
