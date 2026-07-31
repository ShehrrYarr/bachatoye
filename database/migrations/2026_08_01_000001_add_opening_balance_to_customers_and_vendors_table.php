<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A separate, permanent record of what a customer/vendor's balance was
     * when they were added — distinct from credit_balance/balance, which is
     * the live running total after every sale/purchase/payment since. All
     * pre-existing rows default to 0 (they never had a recorded opening
     * balance; their current balance is entirely from real transactions).
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('opening_balance', 12, 2)->default(0)->after('credit_balance');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->decimal('opening_balance', 12, 2)->default(0)->after('balance');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('opening_balance');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('opening_balance');
        });
    }
};
