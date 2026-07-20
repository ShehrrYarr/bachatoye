<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data fix: deleting a POS sale restored product-level stock but left the
 * sold serial numbers as status=sold pointing at the soft-deleted order —
 * so the unit vanished from the POS (which only offers in_stock serials)
 * while still appearing in serial lookup. deleteOrder() now resets them;
 * this restores the ones already orphaned.
 *
 * Product stock is NOT touched here: it was already incremented when the
 * sale was deleted. Only the serial rows are brought back in stock (they
 * keep their shop_id, so they reappear at the shop they were sold from).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Sold serials whose order is soft-deleted, or whose order row is gone
        $orphans = DB::table('serial_numbers')
            ->leftJoin('orders', 'orders.id', '=', 'serial_numbers.order_id')
            ->where('serial_numbers.status', 'sold')
            ->where(fn($q) => $q->whereNotNull('orders.deleted_at')->orWhereNull('orders.id'))
            ->get(['serial_numbers.id', 'serial_numbers.serial_number', 'serial_numbers.product_id']);

        if ($orphans->isEmpty()) {
            echo "  [skip] no orphaned sold serials found\n";
            return;
        }

        DB::table('serial_numbers')
            ->whereIn('id', $orphans->pluck('id'))
            ->update([
                'status'        => 'in_stock',
                'order_id'      => null,
                'order_item_id' => null,
            ]);

        echo '  [fixed] restored ' . $orphans->count() . " serial(s) from deleted sales back to stock:\n";
        foreach ($orphans->take(20) as $s) {
            echo "    - {$s->serial_number} (product {$s->product_id})\n";
        }
        if ($orphans->count() > 20) {
            echo '    … and ' . ($orphans->count() - 20) . " more\n";
        }
    }

    public function down(): void
    {
        // Data correction — not reversible.
    }
};
