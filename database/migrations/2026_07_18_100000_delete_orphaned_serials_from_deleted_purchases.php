<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data fix: deleting a purchase used to leave its serial numbers behind —
 * the FK set purchase_id to NULL but the units stayed in_stock, so the POS
 * kept offering serials that were never really stocked. destroy() now
 * deletes them; this removes the orphans that already exist.
 *
 * Serials only ever enter the system through purchases (owner confirmed),
 * so an in_stock serial with no purchase link can only be such an orphan.
 * Sold/returned serials are never touched — they carry sales history.
 */
return new class extends Migration
{
    public function up(): void
    {
        $orphans = DB::table('serial_numbers')
            ->whereNull('purchase_id')
            ->where('status', 'in_stock')
            ->get(['id', 'serial_number', 'product_id']);

        if ($orphans->isEmpty()) {
            echo "  [skip] no orphaned serials found\n";
            return;
        }

        DB::table('serial_numbers')->whereIn('id', $orphans->pluck('id'))->delete();

        echo '  [fixed] deleted ' . $orphans->count() . " orphaned in-stock serial(s):\n";
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
