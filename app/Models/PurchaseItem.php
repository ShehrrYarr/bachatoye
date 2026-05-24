<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = ['purchase_id', 'product_id', 'product_name', 'quantity', 'unit_cost', 'line_total'];

    protected function casts(): array
    {
        return [
            'unit_cost'  => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
