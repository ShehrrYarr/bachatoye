<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ShopStock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Single primitive for mutating stock at a location.
 * NULL shop = main shop (products/product_colors columns, existing behavior);
 * a shop id = that shop's shop_stocks rows.
 *
 * Must be called inside a DB transaction — rows are locked for update.
 */
class ShopStockService
{
    /**
     * @param  int  $delta  positive = stock in, negative = stock out
     * @throws ValidationException when the location lacks enough stock
     */
    public function adjust(
        ?int $shopId,
        Product $product,
        ?int $colorId,
        int $delta,
        string $type,
        string $reference,
        ?string $note = null
    ): void {
        if ($delta === 0) {
            return;
        }

        if ($shopId === null) {
            $this->adjustMain($product, $colorId, $delta, $type, $reference, $note);
        } else {
            $this->adjustShop($shopId, $product, $colorId, $delta, $type, $reference, $note);
        }
    }

    private function adjustMain(Product $product, ?int $colorId, int $delta, string $type, string $reference, ?string $note): void
    {
        $locked = Product::lockForUpdate()->find($product->id);

        if ($delta < 0 && $locked->track_inventory && $locked->stock_quantity < -$delta) {
            throw ValidationException::withMessages([
                'stock' => "Not enough stock of {$locked->name} at Main Shop (available: {$locked->stock_quantity}, needed: " . (-$delta) . ").",
            ]);
        }

        if ($colorId) {
            $color = ProductColor::lockForUpdate()->find($colorId);
            // Cast both sides: some hosts' PDO returns integer columns as
            // strings, and a silent strict-mismatch here skips the color
            // decrement entirely (production bug, 2026-07).
            if ($color && (int) $color->product_id === (int) $locked->id) {
                if ($delta < 0 && $color->stock_quantity < -$delta) {
                    throw ValidationException::withMessages([
                        'stock' => "Not enough stock of {$locked->name} ({$color->name}) at Main Shop (available: {$color->stock_quantity}).",
                    ]);
                }
                $color->increment('stock_quantity', $delta);
            }
        }

        $before = $locked->stock_quantity;
        $locked->increment('stock_quantity', $delta);

        StockMovement::create([
            'product_id'      => $locked->id,
            'shop_id'         => null,
            'type'            => $type,
            'quantity'        => $delta,
            'before_quantity' => $before,
            'after_quantity'  => $before + $delta,
            'reference'       => $reference,
            'note'            => $note,
            'user_id'         => Auth::id(),
        ]);
    }

    private function adjustShop(int $shopId, Product $product, ?int $colorId, int $delta, string $type, string $reference, ?string $note): void
    {
        $row = ShopStock::firstOrCreate(
            ['shop_id' => $shopId, 'product_id' => $product->id, 'product_color_id' => $colorId],
            ['quantity' => 0]
        );
        $row = ShopStock::lockForUpdate()->find($row->id);

        if ($delta < 0 && $product->track_inventory && $row->quantity < -$delta) {
            throw ValidationException::withMessages([
                'stock' => "Not enough stock of {$product->name} at this shop (available: {$row->quantity}, needed: " . (-$delta) . ").",
            ]);
        }

        $before = (int) ShopStock::where('shop_id', $shopId)->where('product_id', $product->id)->sum('quantity');
        $row->increment('quantity', $delta);

        StockMovement::create([
            'product_id'      => $product->id,
            'shop_id'         => $shopId,
            'type'            => $type,
            'quantity'        => $delta,
            'before_quantity' => $before,
            'after_quantity'  => $before + $delta,
            'reference'       => $reference,
            'note'            => $note,
            'user_id'         => Auth::id(),
        ]);
    }
}
