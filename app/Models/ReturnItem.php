<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnItem extends Model
{
    protected $table = 'return_items';

    protected $fillable = [
        'return_id', 'order_item_id', 'product_id',
        'product_name', 'quantity', 'unit_price', 'line_total', 'refund_amount',
    ];

    protected function casts(): array
    {
        return [
            'unit_price'    => 'decimal:2',
            'line_total'    => 'decimal:2',
            'refund_amount' => 'decimal:2',
        ];
    }

    /**
     * Allocate an order-level refund total across return lines, weighted by
     * each line's original line_total. The last line absorbs the rounding
     * remainder so the parts always sum exactly to $totalRefund.
     *
     * @param  array<int, array{line_total: float}>  $lineItems
     * @return array<int, array>  same rows with 'refund_amount' added
     */
    public static function allocateRefunds(array $lineItems, float $totalRefund): array
    {
        $count = count($lineItems);
        if ($count === 0) {
            return [];
        }

        $sumLineTotal = array_sum(array_column($lineItems, 'line_total'));
        $allocated    = 0.0;

        foreach ($lineItems as $i => &$row) {
            if ($i === $count - 1) {
                // Last line takes whatever remains, avoiding rounding drift.
                $row['refund_amount'] = round($totalRefund - $allocated, 2);
                continue;
            }

            $share = $sumLineTotal > 0
                ? round($totalRefund * ($row['line_total'] / $sumLineTotal), 2)
                : round($totalRefund / $count, 2);

            $row['refund_amount'] = $share;
            $allocated += $share;
        }
        unset($row);

        return $lineItems;
    }

    public function returnOrder()
    {
        return $this->belongsTo(ReturnOrder::class, 'return_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
