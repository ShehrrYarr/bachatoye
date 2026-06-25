<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeldOrder extends Model
{
    protected $fillable = [
        'label', 'created_by', 'cart_data', 'customer_data',
        'discount_type', 'discount_value', 'payment_method',
        'order_notes', 'total', 'section_ids',
    ];

    protected function casts(): array
    {
        return [
            'cart_data'     => 'array',
            'customer_data' => 'array',
            'section_ids'   => 'array',
            'total'         => 'decimal:2',
            'discount_value'=> 'decimal:2',
            'created_by'    => 'integer',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
