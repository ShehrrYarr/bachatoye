<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuybackItem extends Model
{
    protected $fillable = [
        'buyback_id',
        'product_id',
        'product_name',
        'product_color_id',
        'color_name',
        'serial_number_id',
        'original_order_item_id',
        'price_paid',
    ];

    protected function casts(): array
    {
        return [
            'price_paid' => 'decimal:2',
        ];
    }

    public function buyback()
    {
        return $this->belongsTo(Buyback::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productColor()
    {
        return $this->belongsTo(ProductColor::class);
    }

    public function serialNumber()
    {
        return $this->belongsTo(SerialNumber::class);
    }

    public function originalOrderItem()
    {
        return $this->belongsTo(OrderItem::class, 'original_order_item_id');
    }
}
