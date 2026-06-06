<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'product_name', 'color_name', 'color_id', 'product_barcode',
        'unit_price', 'cost_price', 'quantity', 'free_quantity',
        'discount_amount', 'line_total', 'serial_number_id',
    ];

    protected function casts(): array
    {
        return [
            'unit_price'      => 'decimal:2',
            'cost_price'      => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'line_total'      => 'decimal:2',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function serialNumber()
    {
        return $this->belongsTo(SerialNumber::class);
    }

    public function returnItems()
    {
        return $this->hasMany(ReturnItem::class);
    }
}
