<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data fix: the purchase form accepted payment_method=bank_transfer with no
 * bank account selected (the bank list was empty — no accounts existed yet),
 * so ~128 purchases were saved with bank_account_id NULL. Those payments were
 * invisible to the cash/bank balance engine: neither counted as cash-out nor
 * attributed to any bank.
 *
 * Per the owner, all of them were paid from one real bank account. This
 * creates that account (if it doesn't exist yet) and assigns every orphaned
 * bank_transfer purchase to it. Rename the account and set its opening
 * balance afterwards in Admin → Bank Accounts.
 */
return new class extends Migration
{
    private const BANK_LABEL = 'Bank Account';

    public function up(): void
    {
        $orphaned = DB::table('purchases')
            ->where('payment_method', 'bank_transfer')
            ->whereNull('bank_account_id');

        if ((clone $orphaned)->count() === 0) {
            echo "  [skip] no orphaned bank purchases found\n";
            return;
        }

        // Reuse the account if it exists (main-shop scope), else create it
        $bankId = DB::table('bank_accounts')
            ->whereNull('shop_id')
            ->where('label', self::BANK_LABEL)
            ->value('id');

        if (!$bankId) {
            $bankId = DB::table('bank_accounts')->insertGetId([
                'shop_id'         => null,
                'label'           => self::BANK_LABEL,
                'bank_name'       => self::BANK_LABEL,
                'account_title'   => self::BANK_LABEL,
                'is_active'       => true,
                'sort_order'      => 0,
                'opening_balance' => 0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
            echo "  [created] bank account '" . self::BANK_LABEL . "' (id {$bankId}) — set its opening balance in Admin → Bank Accounts\n";
        }

        $count = (clone $orphaned)->count();
        $total = (clone $orphaned)->sum('amount_paid');
        $orphaned->update(['bank_account_id' => $bankId]);

        echo "  [fixed] {$count} purchases (Rs. " . number_format($total) . ") assigned to bank account id {$bankId}\n";
    }

    public function down(): void
    {
        // Data correction — not reversible.
    }
};
