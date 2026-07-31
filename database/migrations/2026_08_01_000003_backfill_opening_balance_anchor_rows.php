<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reconciles opening-balance ledger rows written before the anchor-row
     * design existed (one-off "Opening Balance"/"Opening Balance Adjustment"/
     * "Opening Balance Corrected" rows that were simply appended at whatever
     * position they were entered, rather than treated as preceding all other
     * transactions). For each customer/vendor with such a row: flags the most
     * recent one as the canonical anchor, shifts every other row's running
     * balance by the same delta it originally contributed (so the ledger
     * reads correctly in true chronological order regardless of display
     * order), and makes sure customers.opening_balance/vendors.opening_balance
     * matches. Running credit_balance/balance totals are untouched — this
     * only corrects historical row attribution and running-balance display.
     */
    public function up(): void
    {
        $this->reconcile(
            ledgerTable: 'accounts_ledger',
            ownerTable: 'customers',
            ownerColumn: 'customer_id',
        );

        $this->reconcile(
            ledgerTable: 'vendor_ledger',
            ownerTable: 'vendors',
            ownerColumn: 'vendor_id',
        );
    }

    private function reconcile(string $ledgerTable, string $ownerTable, string $ownerColumn): void
    {
        $candidates = DB::table($ledgerTable)
            ->whereIn('description', ['Opening Balance', 'Opening Balance Adjustment', 'Opening Balance Corrected'])
            ->where('is_opening_balance', false)
            ->orderByDesc('id')
            ->get();

        $seen = [];
        foreach ($candidates as $row) {
            $ownerId = $row->{$ownerColumn};
            if (isset($seen[$ownerId])) {
                continue; // keep only the most recent matching row as the anchor
            }
            $seen[$ownerId] = true;

            $delta = $row->type === 'debit' ? -(float) $row->amount : (float) $row->amount;
            $deltaSql = sprintf('%.2f', $delta);

            DB::table($ledgerTable)->where('id', $row->id)->update([
                'is_opening_balance' => true,
                'description'        => 'Opening Balance',
            ]);

            // The opening balance precedes every other row chronologically,
            // whenever it was actually entered — shift all of them by the delta.
            DB::table($ledgerTable)
                ->where($ownerColumn, $ownerId)
                ->where('id', '!=', $row->id)
                ->update(['balance_after' => DB::raw("balance_after + ({$deltaSql})")]);

            // The anchor's own balance_after becomes its standalone contribution.
            DB::table($ledgerTable)->where('id', $row->id)->update(['balance_after' => $delta]);

            DB::table($ownerTable)->where('id', $ownerId)->update(['opening_balance' => $delta]);
        }
    }

    public function down(): void
    {
        // Irreversible: this reconciles historical row attribution and
        // running-balance display, not a structural change worth un-doing.
    }
};
