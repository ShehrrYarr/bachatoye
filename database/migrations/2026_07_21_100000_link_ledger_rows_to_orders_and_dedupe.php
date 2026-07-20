<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every fully-paid POS sale with a customer writes an informational
 * debit + credit ("Sale" / "Payment Received") pair into the customer's
 * khata. Those credits carry a payment method, so every khata-collection
 * query counted them — double-counting each such sale (once via the order,
 * once as a "collection"). Worse, editing a sale's payment method or
 * deleting the sale left the old pair behind, stacking phantom
 * cash/bank collections in the reports.
 *
 * This migration:
 *  1. adds accounts_ledger.order_id and backfills it for machine-written
 *     sale rows (queries now exclude order-linked rows from collections)
 *  2. removes stale rows: everything on deleted orders, all pairs on
 *     orders that are now khata, and all but the newest pair on paid
 *     orders — adjusting customer balances by the net of removed rows
 *     (matched pairs net to zero)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('accounts_ledger', 'order_id')) {
            Schema::table('accounts_ledger', function (Blueprint $table) {
                $table->foreignId('order_id')->nullable()->after('customer_id')
                    ->constrained('orders')->nullOnDelete();
            });
        }

        // ── 1. Backfill: link machine-written sale rows to their orders ──
        $linked = DB::update("
            UPDATE accounts_ledger al
            JOIN orders o ON o.order_number = al.reference AND o.source = 'pos'
            SET al.order_id = o.id
            WHERE al.return_id IS NULL
              AND al.order_id IS NULL
              AND (al.description LIKE 'Sale —%'
                OR al.description LIKE 'Edited Sale —%'
                OR al.description LIKE 'Payment Received —%'
                OR al.description LIKE 'POS Sale —%'
                OR al.description LIKE 'Partial Payment —%')
        ");
        echo "  [backfill] linked {$linked} ledger row(s) to their orders\n";

        // ── 2. Cleanup of stale rows ─────────────────────────────────────
        $deletedRows = 0;
        $balanceFixes = 0;

        $orderIds = DB::table('accounts_ledger')
            ->whereNotNull('order_id')->whereNull('return_id')
            ->distinct()->pluck('order_id');

        foreach ($orderIds as $oid) {
            $order = DB::table('orders')->find($oid);
            $rows  = collect(DB::table('accounts_ledger')
                ->where('order_id', $oid)->whereNull('return_id')
                ->orderBy('id')->get());

            $toDelete = collect();

            if (!$order || $order->deleted_at) {
                // Deleted sale — nothing of it should remain in the khata
                $toDelete = $rows;
            } else {
                // Match "Payment Received" credits to their sale-debit partners
                $credits      = $rows->filter(fn($r) => str_starts_with($r->description ?? '', 'Payment Received —'));
                $pairDebitIds = [];
                foreach ($credits as $c) {
                    $partner = $rows->first(fn($r) => $r->type === 'debit'
                        && !in_array($r->id, $pairDebitIds)
                        && (str_starts_with($r->description ?? '', 'Sale —') || str_starts_with($r->description ?? '', 'Edited Sale —'))
                        && abs((float) $r->amount - (float) $c->amount) < 0.01
                        && abs(strtotime($r->created_at) - strtotime($c->created_at)) <= 2);
                    if ($partner) {
                        $pairDebitIds[$c->id] = $partner->id;
                    }
                }

                $currentlyPaid = in_array($order->payment_method, ['cash', 'bank_transfer', 'split'])
                    || ($order->payment_method === 'partial' && $order->payment_status === 'paid');

                // Keep the newest pair only when the order is currently fully
                // paid; a khata order's pairs are all leftovers from before the
                // payment-method change. Credits without a matched partner are
                // left untouched (deleting one side would shift the balance).
                $keepCreditId = $currentlyPaid
                    ? optional($credits->filter(fn($c) => isset($pairDebitIds[$c->id]))->sortByDesc('id')->first())->id
                    : null;

                foreach ($credits as $c) {
                    if (!isset($pairDebitIds[$c->id])) continue;
                    if ($keepCreditId !== null && $c->id === $keepCreditId) continue;
                    $toDelete->push($c);
                    $toDelete->push($rows->firstWhere('id', $pairDebitIds[$c->id]));
                }
            }

            if ($toDelete->isEmpty()) continue;

            $net = (float) $toDelete->where('type', 'debit')->sum('amount')
                 - (float) $toDelete->where('type', 'credit')->sum('amount');

            DB::table('accounts_ledger')->whereIn('id', $toDelete->pluck('id'))->delete();
            $deletedRows += $toDelete->count();

            if (abs($net) > 0.001 && $order && $order->customer_id) {
                DB::table('customers')->where('id', $order->customer_id)
                    ->increment('credit_balance', $net);
                $balanceFixes++;
            }
        }

        echo "  [cleanup] removed {$deletedRows} stale ledger row(s); adjusted {$balanceFixes} customer balance(s)\n";
    }

    public function down(): void
    {
        Schema::table('accounts_ledger', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
        });
    }
};
