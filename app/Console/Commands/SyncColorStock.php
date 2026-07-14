<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reconciles per-color stock with the product total (fed by purchases).
 *
 * The product total is authoritative — it is maintained by every flow.
 * Per-color rows drift when a purchase/delivery skipped the color, so we
 * rebuild each color from its own transaction history (main shop only:
 * purchases − delivered sales + restocked returns) and apply it when the
 * rebuilt numbers exactly account for the product total. Single-color
 * products fall back to color = product total. Anything else is reported
 * for a manual count instead of guessing.
 */
class SyncColorStock extends Command
{
    protected $signature = 'stock:sync-colors {--dry-run : Show what would change without saving}';

    protected $description = 'Reconcile per-color stock with product totals (purchases are the source of truth)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if ($dry) {
            $this->warn('DRY RUN — nothing will be saved.');
        }

        $fixed = 0;
        $manual = 0;

        $products = Product::where('is_active', true)
            ->where('is_serialized', false)
            ->has('colors')
            ->with('colors')
            ->get();

        foreach ($products as $product) {
            $colorsTotal = (int) $product->colors->sum('stock_quantity');
            if ($colorsTotal === (int) $product->stock_quantity) {
                continue; // already consistent
            }

            // Rebuild each color from its own history (main shop only)
            $nets = [];
            foreach ($product->colors as $color) {
                $purchased = (int) DB::table('purchase_items')
                    ->where('product_id', $product->id)
                    ->where('color_id', $color->id)
                    ->sum('quantity');

                $sold = (int) DB::table('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->where('order_items.product_id', $product->id)
                    ->where('order_items.color_id', $color->id)
                    ->whereNull('orders.deleted_at')
                    ->whereNull('orders.shop_id')
                    ->where('orders.status', 'delivered')
                    ->sum('order_items.quantity');

                $returned = (int) DB::table('return_items')
                    ->join('returns', 'returns.id', '=', 'return_items.return_id')
                    ->join('order_items', 'order_items.id', '=', 'return_items.order_item_id')
                    ->where('return_items.product_id', $product->id)
                    ->where('order_items.color_id', $color->id)
                    ->whereIn('returns.status', ['approved', 'completed'])
                    ->where('returns.restock', 1)
                    ->sum('return_items.quantity');

                $nets[$color->id] = $purchased - $sold + $returned;
            }

            $netsSum   = array_sum($nets);
            $allValid  = collect($nets)->every(fn($n) => $n >= 0);

            if ($allValid && $netsSum === (int) $product->stock_quantity) {
                // History fully explains the product total — apply per-color truth
                foreach ($product->colors as $color) {
                    $this->applyColor($product, $color, $nets[$color->id], $dry);
                }
                $fixed++;
            } elseif ($product->colors->count() === 1) {
                // Single color: the product total IS that color's stock
                $this->applyColor($product, $product->colors->first(), max(0, (int) $product->stock_quantity), $dry);
                $fixed++;
            } else {
                $this->error("MANUAL REVIEW — #{$product->id} {$product->name}: product={$product->stock_quantity}, "
                    . 'colors [' . $product->colors->map(fn($c) => "{$c->name}={$c->stock_quantity}")->join(', ') . '], '
                    . 'history suggests [' . $product->colors->map(fn($c) => "{$c->name}={$nets[$c->id]}")->join(', ') . '] '
                    . "(sum {$netsSum} ≠ total). Count the shelf and adjust manually.");
                $manual++;
            }
        }

        $this->info(($dry ? '[dry run] ' : '') . "Done — {$fixed} product(s) reconciled, {$manual} need manual review.");

        return self::SUCCESS;
    }

    private function applyColor(Product $product, $color, int $newQty, bool $dry): void
    {
        if ((int) $color->stock_quantity === $newQty) {
            return;
        }

        $this->line(($dry ? '[dry] ' : '') . "#{$product->id} {$product->name} — {$color->name}: {$color->stock_quantity} → {$newQty}");

        if (!$dry) {
            $color->update(['stock_quantity' => $newQty]);
        }
    }
}
