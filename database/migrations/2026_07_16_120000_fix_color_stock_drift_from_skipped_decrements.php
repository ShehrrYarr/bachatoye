<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data fix: between 2026-07-13 (sub-shops phase 3 deploy) and this deploy,
 * ShopStockService::adjustMain() silently skipped the product_colors
 * decrement on hosts where PDO returns integer columns as strings
 * (strict === comparison of product ids). Every POS color sale in that
 * window decremented products.stock_quantity but left the color row
 * untouched, so colors drifted above the product total.
 *
 * This recomputes the drift per color from actual POS sales/returns and
 * corrects the color rows. Products whose drift doesn't exactly match the
 * attributable sales are left untouched and reported for manual review.
 */
return new class extends Migration
{
    private const BUG_DEPLOYED_AT = '2026-07-13 00:00:00';

    public function up(): void
    {
        $mismatched = DB::select("
            SELECT p.id, p.name, p.stock_quantity, SUM(pc.stock_quantity) AS color_sum
            FROM products p
            JOIN product_colors pc ON pc.product_id = p.id
            GROUP BY p.id, p.name, p.stock_quantity
            HAVING color_sum <> p.stock_quantity
        ");

        foreach ($mismatched as $p) {
            $drift = (int) $p->color_sum - (int) $p->stock_quantity;

            // Net skipped decrement per color: POS color sales in the bug
            // window minus POS returns (whose color restore was equally
            // skipped, so they offset).
            $perColor = [];
            $netTotal = 0;

            foreach (DB::table('product_colors')->where('product_id', $p->id)->get() as $c) {
                $sold = (int) DB::table('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->where('order_items.color_id', $c->id)
                    ->where('orders.source', 'pos')
                    ->where('orders.created_at', '>=', self::BUG_DEPLOYED_AT)
                    ->where('orders.status', '!=', 'cancelled')
                    ->sum('order_items.quantity');

                $returned = (int) DB::table('return_items')
                    ->join('order_items as oi', 'oi.id', '=', 'return_items.order_item_id')
                    ->join('orders', 'orders.id', '=', 'oi.order_id')
                    ->join('returns', 'returns.id', '=', 'return_items.return_id')
                    ->where('oi.color_id', $c->id)
                    ->where('orders.source', 'pos')
                    ->where('returns.created_at', '>=', self::BUG_DEPLOYED_AT)
                    ->sum('return_items.quantity');

                $net = $sold - $returned;
                if ($net > 0) {
                    $perColor[$c->id] = $net;
                }
                $netTotal += $net;
            }

            if ($drift <= 0 || $netTotal !== $drift) {
                echo "  [skip] product {$p->id} ({$p->name}): drift {$drift} but attributable sales {$netTotal} — review manually\n";
                continue;
            }

            foreach ($perColor as $colorId => $net) {
                DB::table('product_colors')
                    ->where('id', $colorId)
                    ->update(['stock_quantity' => DB::raw("GREATEST(0, stock_quantity - {$net})")]);
            }

            echo "  [fixed] product {$p->id} ({$p->name}): colors reduced by {$drift} to match product stock {$p->stock_quantity}\n";
        }
    }

    public function down(): void
    {
        // Data correction — not reversible.
    }
};
