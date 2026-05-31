<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Extend the enum to include bank_transfer
        DB::statement("ALTER TABLE purchases MODIFY COLUMN payment_method ENUM('cash','bank_transfer','credit','partial') NOT NULL DEFAULT 'cash'");

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('bank_account_id')
                  ->nullable()
                  ->after('payment_method')
                  ->constrained('bank_accounts')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->dropColumn('bank_account_id');
        });

        DB::statement("ALTER TABLE purchases MODIFY COLUMN payment_method ENUM('cash','credit','partial') NOT NULL DEFAULT 'cash'");
    }
};
