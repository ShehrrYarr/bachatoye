<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Historical serialized sales recorded via the pre-fix createOrder()
     * used the product's generic cost_price instead of the specific unit's
     * actual cost (fixed in PosController::createOrder(), which now matches
     * updateOrder()'s already-correct `serial cost_price ?? product cost_price`
     * pattern). Backfill every existing order_items row whose cost_price
     * doesn't match its linked serial's cost_price, so historical COGS/
     * profit figures in the Sales and Profit & Loss reports (which read
     * order_items.cost_price directly) become correct.
     */
    public function up(): void
    {
        DB::table('order_items')
            ->join('serial_numbers', 'serial_numbers.id', '=', 'order_items.serial_number_id')
            ->whereColumn('order_items.cost_price', '!=', 'serial_numbers.cost_price')
            ->update([
                'order_items.cost_price' => DB::raw('serial_numbers.cost_price'),
            ]);
    }

    public function down(): void
    {
        // Irreversible: the pre-fix values were wrong (product cost instead
        // of the unit's actual cost), not a prior state worth restoring.
    }
};
