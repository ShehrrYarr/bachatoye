<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopStock extends Model
{
    protected $fillable = [
        'shop_id',
        'product_id',
        'product_color_id',
        'quantity',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function color()
    {
        return $this->belongsTo(ProductColor::class, 'product_color_id');
    }
}
