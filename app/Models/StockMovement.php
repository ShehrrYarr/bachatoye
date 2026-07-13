<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use BelongsToShop;

    protected $fillable = [
        'product_id', 'shop_id', 'type', 'quantity', 'before_quantity',
        'after_quantity', 'reference', 'note', 'user_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
