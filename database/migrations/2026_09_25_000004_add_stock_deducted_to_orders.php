<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Online orders only: whether this order's stock is currently taken off.
 * Set when first marked delivered, cleared when cancelled/returned, so
 * re-marking delivered can't deduct twice (see OrderController::syncEcomStock).
 * POS orders deduct at sale time and never use it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('stock_deducted')->default(false)->after('status');
        });

        // Existing online orders: deducted if their stock movements net out
        // negative (the old code wrote a 'sale' movement on every delivery and
        // never restored stock on cancel, so this matches actual stock).
        $deducted = DB::table('orders')
            ->join('stock_movements', 'stock_movements.reference', '=', 'orders.order_number')
            ->where('orders.source', 'ecommerce')
            ->groupBy('orders.id')
            ->havingRaw('SUM(stock_movements.quantity) < 0')
            ->pluck('orders.id');

        DB::table('orders')->whereIn('id', $deducted)->update(['stock_deducted' => true]);

        echo '  [backfill] ' . $deducted->count() . " online order(s) marked as stock deducted\n";
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('stock_deducted');
        });
    }
};
