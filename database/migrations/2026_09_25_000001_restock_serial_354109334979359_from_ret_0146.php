<?php

use App\Models\Product;
use App\Models\ReturnOrder;
use App\Models\SerialNumber;
use App\Services\ShopStockService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data fix: IPHONE SE 3, IMEI 354109334979359, was returned on RET-0146 with
 * "Restock returned items" unticked by mistake. The serial was left as
 * status=returned, which the POS never offers and no screen can undo.
 *
 * Brings the unit back exactly as a restocked return would have: serial
 * in_stock with its sale link cleared, +1 stock at the shop it was sold
 * from (with a stock movement), and the return flagged restock=1 so profit
 * reporting stops counting the unit's cost against ORD-1268.
 */
return new class extends Migration
{
    private const SERIAL = '354109334979359';
    private const RETURN_NUMBER = 'RET-0146';

    public function up(): void
    {
        $return = ReturnOrder::with('items', 'order')->where('return_number', self::RETURN_NUMBER)->first();
        $serial = SerialNumber::where('serial_number', self::SERIAL)->first();

        if (!$return || !$serial) {
            echo "  [skip] serial or return not found\n";
            return;
        }

        if ($serial->status !== 'returned' || (int) $serial->return_order_id !== (int) $return->id) {
            echo "  [skip] serial is '{$serial->status}', not a pending unrestocked return\n";
            return;
        }

        if ($return->items->count() !== 1) {
            echo "  [skip] return has {$return->items->count()} items — expected only the phone\n";
            return;
        }

        DB::transaction(function () use ($return, $serial) {
            $product = Product::findOrFail($serial->product_id);
            $shopId  = $return->order?->shop_id;

            app(ShopStockService::class)->adjust(
                $shopId, $product, $return->items->first()->orderItem?->color_id, 1,
                'return', $return->return_number, 'Restocked after the fact — restock was unticked on return'
            );

            $note = 'Restocked on ' . now()->format('Y-m-d') . ' — ' . $return->return_number
                  . ' was processed with restock unticked by mistake';

            $serial->update([
                'status'        => 'in_stock',
                'order_id'      => null,
                'order_item_id' => null,
                'notes'         => $serial->notes ? $serial->notes . "\n" . $note : $note,
            ]);

            $return->update(['restock' => true]);
        });

        echo '  [fixed] ' . self::SERIAL . ' back in stock (' . self::RETURN_NUMBER . " now restocked)\n";
    }

    public function down(): void
    {
        // Data correction — not reversible.
    }
};
