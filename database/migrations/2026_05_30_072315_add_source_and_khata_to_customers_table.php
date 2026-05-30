<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // 'pos' = in-store / khata customer, 'online' = ecommerce checkout customer
            $table->enum('source', ['pos', 'online'])->default('pos')->after('is_active');
            $table->boolean('khata_enabled')->default(false)->after('source');
        });

        // Back-fill: customers with POS orders or a non-zero credit balance are in-store
        DB::statement("
            UPDATE customers
            SET source = 'pos', khata_enabled = 1
            WHERE id IN (
                SELECT customer_id FROM orders WHERE source = 'pos' AND customer_id IS NOT NULL
            ) OR credit_balance != 0
        ");

        // Remaining customers (ecommerce-only, zero balance) → mark as online
        DB::statement("
            UPDATE customers
            SET source = 'online', khata_enabled = 0
            WHERE source = 'pos'
              AND credit_balance = 0
              AND id NOT IN (
                SELECT customer_id FROM orders WHERE source = 'pos' AND customer_id IS NOT NULL
              )
              AND id IN (
                SELECT customer_id FROM orders WHERE source = 'ecommerce' AND customer_id IS NOT NULL
              )
        ");
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['source', 'khata_enabled']);
        });
    }
};
