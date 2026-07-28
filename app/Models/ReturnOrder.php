<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnOrder extends Model
{
    protected $table = 'returns';

    protected $fillable = [
        'return_number', 'order_id', 'customer_id', 'vendor_id', 'reason',
        'refund_amount', 'refund_method', 'bank_account_id', 'status', 'restock', 'processed_by',
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
                $max  = (int) static::max('id');
                $next = str_pad($max + 1, 4, '0', STR_PAD_LEFT);
                $return->return_number = 'RET-' . $next;
            }
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
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
