<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Historical 'partial' orders never populated cash_amount/bank_amount
     * (only 'split' did) — derive them from amount_paid + whether a bank
     * account was attached, so cash-in-hand/bank reporting can read these
     * two columns uniformly for split and partial alike.
     */
    public function up(): void
    {
        DB::table('orders')
            ->where('payment_method', 'partial')
            ->whereNull('cash_amount')
            ->whereNull('bank_amount')
            ->whereNotNull('bank_account_id')
            ->update([
                'cash_amount' => 0,
                'bank_amount' => DB::raw('amount_paid'),
            ]);

        DB::table('orders')
            ->where('payment_method', 'partial')
            ->whereNull('cash_amount')
            ->whereNull('bank_amount')
            ->whereNull('bank_account_id')
            ->update([
                'cash_amount' => DB::raw('amount_paid'),
                'bank_amount' => 0,
            ]);
    }

    public function down(): void
    {
        DB::table('orders')
            ->where('payment_method', 'partial')
            ->update([
                'cash_amount' => null,
                'bank_amount' => null,
            ]);
    }
};
