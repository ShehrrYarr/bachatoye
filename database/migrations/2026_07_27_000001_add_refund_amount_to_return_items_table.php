<?php

use App\Models\ReturnItem;
use App\Models\ReturnOrder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_items', function (Blueprint $table) {
            $table->decimal('refund_amount', 12, 2)->nullable()->after('line_total');
        });

        // Backfill: allocate each return's order-level refund_amount across its
        // lines proportionally by line_total, so historical returns get a real
        // per-item refund figure (needed for returns-aware profit reporting).
        ReturnOrder::with('items')->chunkById(200, function ($returns) {
            foreach ($returns as $return) {
                if ($return->items->isEmpty()) continue;

                $lineItems = $return->items->map(fn($item) => [
                    'id'         => $item->id,
                    'line_total' => (float) $item->line_total,
                ])->all();

                $allocated = ReturnItem::allocateRefunds($lineItems, (float) $return->refund_amount);

                foreach ($allocated as $row) {
                    ReturnItem::where('id', $row['id'])->update(['refund_amount' => $row['refund_amount']]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('return_items', function (Blueprint $table) {
            $table->dropColumn('refund_amount');
        });
    }
};
