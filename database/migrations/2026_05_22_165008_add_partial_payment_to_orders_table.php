<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('amount_paid', 12, 2)->nullable()->after('total');
        });

        // Extend payment_method enum to include 'partial'
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('cash','bank_transfer','khata','partial') NOT NULL DEFAULT 'cash'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('cash','bank_transfer','khata') NOT NULL DEFAULT 'cash'");

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('amount_paid');
        });
    }
};
