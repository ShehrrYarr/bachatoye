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
            $table->text('notes')->nullable()->after('payment_notes');
            $table->decimal('cash_amount', 12, 2)->nullable()->after('amount_paid');
            $table->decimal('bank_amount', 12, 2)->nullable()->after('cash_amount');
        });

        // Extend payment_method enum to include 'split'
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('cash','bank_transfer','khata','partial','split') NOT NULL DEFAULT 'cash'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('cash','bank_transfer','khata','partial') NOT NULL DEFAULT 'cash'");

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['notes', 'cash_amount', 'bank_amount']);
        });
    }
};
