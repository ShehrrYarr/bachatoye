<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Model;

class Buyback extends Model
{
    use BelongsToShop;

    protected $fillable = [
        'buyback_number',
        'shop_id',
        'seller_name',
        'seller_phone',
        'seller_cnic',
        'seller_customer_id',
        'seller_vendor_id',
        'amount_total',
        'payment_method',
        'bank_account_id',
        'notes',
        'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'amount_total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Buyback $buyback) {
            if (empty($buyback->buyback_number)) {
                $next = (static::max('id') ?? 0) + 1;
                $buyback->buyback_number = 'BB-' . str_pad($next, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function sellerCustomer()
    {
        return $this->belongsTo(Customer::class, 'seller_customer_id');
    }

    public function sellerVendor()
    {
        return $this->belongsTo(Vendor::class, 'seller_vendor_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function items()
    {
        return $this->hasMany(BuybackItem::class);
    }
}
