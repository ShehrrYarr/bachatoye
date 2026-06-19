<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformPayout extends Model
{
    protected $fillable = [
        'delivery_platform_id', 'bank_account_id', 'amount',
        'payout_date', 'reference', 'notes', 'created_by',
    ];

    protected $casts = ['payout_date' => 'date'];

    public function platform()
    {
        return $this->belongsTo(DeliveryPlatform::class, 'delivery_platform_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'platform_payout_orders')
                    ->withPivot('order_amount')
                    ->withTimestamps();
    }
}
