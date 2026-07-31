<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flags the single row (per customer/vendor) that represents their
     * opening balance, so it can be found and updated in place — instead of
     * inserting a new row every time the opening balance is corrected — and
     * pinned to the correct position in ledger listings regardless of when
     * the correction actually happened.
     */
    public function up(): void
    {
        Schema::table('accounts_ledger', function (Blueprint $table) {
            $table->boolean('is_opening_balance')->default(false)->after('type');
        });

        Schema::table('vendor_ledger', function (Blueprint $table) {
            $table->boolean('is_opening_balance')->default(false)->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('accounts_ledger', function (Blueprint $table) {
            $table->dropColumn('is_opening_balance');
        });

        Schema::table('vendor_ledger', function (Blueprint $table) {
            $table->dropColumn('is_opening_balance');
        });
    }
};
