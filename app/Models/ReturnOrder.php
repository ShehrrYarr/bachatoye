<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnOrder extends Model
{
    protected $table = 'returns';

    protected $fillable = [
        'return_number', 'order_id', 'customer_id', 'reason',
        'refund_amount', 'refund_method', 'status', 'restock', 'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'refund_amount' => 'decimal:2',
            'restock'       => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ReturnOrder $return) {
            if (!$return->return_number) {
                $return->return_number = 'RET-' . strtoupper(uniqid());
            }
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function items()
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }
}
