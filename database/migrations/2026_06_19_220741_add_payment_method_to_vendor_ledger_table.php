<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vendor_ledger', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('reference');
            $table->foreignId('bank_account_id')->nullable()->after('payment_method')
                  ->constrained('bank_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vendor_ledger', function (Blueprint $table) {
            $table->dropForeignIfExists(['bank_account_id']);
            $table->dropColumnIfExists('bank_account_id');
            $table->dropColumnIfExists('payment_method');
        });
    }
};
