<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Buyback;
use App\Models\BuybackItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\SerialNumber;
use App\Services\ShopStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosBuybackController extends Controller
{
    public function index()
    {
        $bankAccounts  = BankAccount::active()->forShop(Auth::user()->shopId())->orderBy('sort_order')->orderBy('id')->get();
        $subcategories = Category::active()->whereNotNull('parent_id')->orderBy('name')->get(['id', 'name']);

        return view('pos.buyback', compact('bankAccounts', 'subcategories'));
    }

    /**
     * Look up a serial number for buyback. Shows the unit's full sale history
     * (who bought it, when, for how much) so staff can use their own judgment —
     * the seller does not have to match the original buyer.
     */
    public function lookup(Request $request)
    {
        $q = trim($request->get('serial', ''));
        if (!$q) {
            return response()->json(['error' => 'Please enter a serial number.'], 422);
        }

        $serial = SerialNumber::where('serial_number', $q)
            ->with(['product.subcategory', 'order.customer', 'orderItem'])
            ->first();

        if (!$serial) {
            return response()->json(['error' => 'No unit found with that serial number.'], 404);
        }

        if ($serial->status === 'in_stock') {
            return response()->json(['error' => 'This unit is already in stock — no buyback needed.'], 422);
        }

        if ($serial->status === 'returned') {
            return response()->json(['error' => 'This unit is flagged "returned" (not restocked). Please check with admin before buying it back.'], 422);
        }

        $order     = $serial->order;
        $orderItem = $serial->orderItem;

        return response()->json([
            'serial_number_id'       => $serial->id,
            'serial_number'          => $serial->serial_number,
            'product_id'             => $serial->product_id,
            'product_name'           => $serial->product?->name,
            'product_color_id'       => $orderItem?->color_id,
            'color_name'             => $orderItem?->color_name,
            'current_subcategory_id' => $serial->subcategory_id,
            'current_cost_price'     => (float) $serial->cost_price,
            'current_selling_price'  => (float) $serial->selling_price,
            'sale' => $order ? [
                'order_number'   => $order->order_number,
                'customer_name'  => $order->customer_name ?: ($order->customer?->name ?: 'Walk-in'),
                'customer_phone' => $order->customer_phone ?: $order->customer?->phone,
                'date'           => $order->created_at->format('d M Y'),
                'price'          => $orderItem ? (float) $orderItem->unit_price : null,
            ] : null,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'serial_number_id'   => 'required|exists:serial_numbers,id',
            'seller_name'        => 'required|string|max:255',
            'seller_phone'       => 'nullable|string|max:30',
            'seller_cnic'        => 'nullable|string|max:30',
            'seller_customer_id' => 'nullable|exists:customers,id',
            'seller_vendor_id'   => 'nullable|exists:vendors,id',
            'amount_paid'        => 'required|numeric|min:0',
            'payment_method'     => 'required|in:cash,bank_transfer',
            'bank_account_id'    => 'nullable|exists:bank_accounts,id',
            'new_subcategory_id' => 'nullable|exists:categories,id',
            'new_selling_price'  => 'nullable|numeric|min:0',
            'notes'              => 'nullable|string|max:1000',
        ]);

        $shopId = Auth::user()->shopId();

        if ($request->payment_method === 'bank_transfer') {
            if (!$request->filled('bank_account_id')) {
                return response()->json(['error' => 'Please select a bank account.'], 422);
            }
            if (!BankAccount::forShop($shopId)->where('id', $request->bank_account_id)->exists()) {
                return response()->json(['error' => 'The selected bank account belongs to a different shop.'], 422);
            }
        }

        DB::beginTransaction();
        try {
            $serial = SerialNumber::lockForUpdate()->find($request->serial_number_id);

            if (!$serial || $serial->status !== 'sold') {
                DB::rollBack();
                return response()->json([
                    'error' => 'This unit is no longer eligible for buyback — it may have just been sold, returned, or already bought back.',
                ], 422);
            }

            $product = Product::find($serial->product_id);
            if (!$product) {
                DB::rollBack();
                return response()->json(['error' => 'The product for this serial no longer exists.'], 422);
            }

            // Snapshot the last sale before we touch anything
            $orderItem = $serial->orderItem;
            $order     = $serial->order;
            $colorId   = $orderItem?->color_id;
            $colorName = $orderItem?->color_name;
            $amount    = (float) $request->amount_paid;

            $buyback = Buyback::create([
                'shop_id'            => $shopId,
                'seller_name'        => $request->seller_name,
                'seller_phone'       => $request->seller_phone,
                'seller_cnic'        => $request->seller_cnic,
                'seller_customer_id' => $request->seller_customer_id,
                'seller_vendor_id'   => $request->seller_vendor_id,
                'amount_total'       => $amount,
                'payment_method'     => $request->payment_method,
                'bank_account_id'    => $request->payment_method === 'bank_transfer' ? $request->bank_account_id : null,
                'notes'              => $request->notes,
                'processed_by'       => Auth::id(),
            ]);

            BuybackItem::create([
                'buyback_id'             => $buyback->id,
                'product_id'             => $product->id,
                'product_name'           => $product->name,
                'product_color_id'       => $colorId,
                'color_name'             => $colorName,
                'serial_number_id'       => $serial->id,
                'original_order_item_id' => $orderItem?->id,
                'price_paid'             => $amount,
            ]);

            $note = "Bought back ({$buyback->buyback_number}) from {$request->seller_name}"
                  . ($request->seller_phone ? " ({$request->seller_phone})" : '')
                  . ' on ' . now()->format('d M Y') . ' for Rs. ' . number_format($amount) . '.';

            if ($order) {
                $note .= ' Originally sold to ' . ($order->customer_name ?: ($order->customer?->name ?: 'Walk-in'))
                       . ' on ' . $order->created_at->format('d M Y')
                       . ($orderItem ? ' for Rs. ' . number_format((float) $orderItem->unit_price) : '')
                       . ", order {$order->order_number}.";
            }

            $serialUpdate = [
                'status'        => 'in_stock',
                'shop_id'       => $shopId,
                'cost_price'    => $amount,
                'order_id'      => null,
                'order_item_id' => null,
                'notes'         => $serial->notes ? $serial->notes . "\n" . $note : $note,
            ];

            if ($request->filled('new_subcategory_id')) {
                $serialUpdate['subcategory_id'] = $request->new_subcategory_id;
            }
            if ($request->filled('new_selling_price')) {
                $serialUpdate['selling_price'] = $request->new_selling_price;
            }

            $serial->update($serialUpdate);

            app(ShopStockService::class)->adjust(
                $shopId, $product, $colorId, 1, 'buyback', $buyback->buyback_number
            );

            DB::commit();
            return response()->json([
                'success'        => true,
                'buyback_id'     => $buyback->id,
                'buyback_number' => $buyback->buyback_number,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function receipt(Buyback $buyback)
    {
        $buyback->load(['items.product', 'items.productColor', 'items.serialNumber', 'processedBy', 'bankAccount', 'shop']);

        $user = Auth::user();
        abort_if($user->isSubshop() && $buyback->shop_id !== $user->shopId(), 404);

        $shop     = $buyback->shop;
        $settings = [
            'shop_name'    => $shop?->name ?? Setting::get('shop_name', 'MobileHub'),
            'shop_phone'   => $shop?->phone ?? Setting::get('shop_phone'),
            'shop_address' => $shop?->address ?? Setting::get('shop_address'),
            'footer'       => $shop?->receipt_footer ?? Setting::get('receipt_footer'),
        ];

        return view('pos.buyback-receipt', compact('buyback', 'settings'));
    }
}
