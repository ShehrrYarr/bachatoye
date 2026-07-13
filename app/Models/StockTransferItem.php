<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'product_name',
        'product_color_id',
        'color_name',
        'serial_number_id',
        'serial_code',
        'quantity',
    ];

    public function transfer()
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function serialNumber()
    {
        return $this->belongsTo(SerialNumber::class);
    }
}
