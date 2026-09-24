<?php

use App\Models\Order;
use App\Models\PosSession;
use App\Models\Product;
use App\Services\ShopStockService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data fix, two parts:
 *
 * 1. Duplicate offline sales. When two syncs of the same offline sale raced,
 *    both passed the idempotency check and the sale was saved twice (known:
 *    ORD-0658 = copy of ORD-0657, ORD-2617 = copy of ORD-2616), deducting
 *    stock and counting revenue twice. Each extra copy is removed the way
 *    "Delete sale" does it: stock restored, order soft-deleted. Copies with
 *    returns, khata/vendor ledger rows or serial numbers are left for manual
 *    review. Every copy's offline_ref is then made unique so the next
 *    migration can add the unique index that stops this happening again.
 *
 * 2. Split payments with change. The POS recorded cash handed over, not cash
 *    kept, so change was counted as cash in (known: ORD-2689, Rs. 480 cash +
 *    Rs. 1,500 bank on a Rs. 1,930 sale). The cash portion becomes
 *    total − bank, which is what createOrder() now stores.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->removeDuplicateOfflineSales();
        $this->fixSplitChange();
    }

    private function removeDuplicateOfflineSales(): void
    {
        $refs = DB::table('orders')
            ->whereNotNull('offline_ref')
            ->groupBy('offline_ref')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('offline_ref');

        if ($refs->isEmpty()) {
            echo "  [skip] no duplicate offline sales\n";
            return;
        }

        foreach ($refs as $ref) {
            $orders   = Order::withTrashed()->with('items')->where('offline_ref', $ref)->orderBy('id')->get();
            $original = $orders->shift();

            foreach ($orders as $copy) {
                DB::transaction(function () use ($copy, $original, $ref) {
                    if (!$copy->trashed()) {
                        if ($this->needsManualReview($copy)) {
                            echo "  [manual] {$copy->order_number} duplicates {$original->order_number} but has returns, ledger rows or serials — delete it from the POS\n";
                        } else {
                            $this->deleteCopy($copy, $original);
                            echo "  [fixed] removed {$copy->order_number} (duplicate of {$original->order_number}), stock restored\n";
                        }
                    }

                    // Keeps the ref traceable while freeing it for the unique index
                    DB::table('orders')->where('id', $copy->id)->update(['offline_ref' => "{$ref}#dup{$copy->id}"]);
                });
            }
        }
    }

    private function needsManualReview(Order $copy): bool
    {
        return DB::table('returns')->where('order_id', $copy->id)->exists()
            || DB::table('accounts_ledger')->where('order_id', $copy->id)->exists()
            || DB::table('vendor_ledger')->where('order_id', $copy->id)->exists()
            || $copy->items->contains(fn($i) => $i->serial_number_id);
    }

    private function deleteCopy(Order $copy, Order $original): void
    {
        foreach ($copy->items as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                app(ShopStockService::class)->adjust(
                    $copy->shop_id, $product, $item->color_id, $item->quantity,
                    'adjustment', $copy->order_number, "Duplicate of {$original->order_number} (offline sync) removed"
                );
            }
        }

        // Take it back out of the POS session it was counted in, if any
        $session = PosSession::where('user_id', $copy->served_by)
            ->where('opened_at', '<=', $copy->created_at)
            ->where(fn($q) => $q->whereNull('closed_at')->orWhere('closed_at', '>=', $copy->created_at))
            ->orderByDesc('opened_at')
            ->first();
        if ($session) {
            $session->decrement('total_sales', (float) $copy->total);
            $session->decrement('total_orders');
        }

        $note = "Removed automatically: duplicate of {$original->order_number} created by the offline sync";
        $copy->notes = $copy->notes ? $copy->notes . "\n" . $note : $note;
        $copy->save();
        $copy->delete();
    }

    private function fixSplitChange(): void
    {
        $orders = DB::table('orders')
            ->where('payment_method', 'split')
            ->whereNull('deleted_at')
            ->whereRaw('COALESCE(cash_amount, 0) + COALESCE(bank_amount, 0) > total + 0.01')
            ->whereRaw('COALESCE(bank_amount, 0) <= total')
            ->get(['id', 'order_number', 'total', 'cash_amount', 'bank_amount']);

        if ($orders->isEmpty()) {
            echo "  [skip] no split payments with change counted as cash\n";
            return;
        }

        foreach ($orders as $o) {
            $cash = round((float) $o->total - (float) $o->bank_amount, 2);
            DB::table('orders')->where('id', $o->id)->update(['cash_amount' => $cash, 'amount_paid' => $o->total]);
            echo "  [fixed] {$o->order_number}: cash Rs. " . number_format((float) $o->cash_amount)
                . ' → Rs. ' . number_format($cash) . ' (change was counted as cash)' . "\n";
        }
    }

    public function down(): void
    {
        // Data correction — not reversible.
    }
};
