<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductColor extends Model
{
    protected $fillable = ['product_id', 'name', 'hex_code', 'stock_quantity', 'sort_order'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
