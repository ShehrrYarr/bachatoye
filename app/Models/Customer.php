<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use BelongsToShop;

    protected $fillable = [
        'shop_id',
        'name',
        'phone',
        'email',
        'address',
        'city',
        'photo',
        'credit_balance',
        'opening_balance',
        'is_active',
        'source',
        'khata_enabled',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'credit_balance'  => 'decimal:2',
            'opening_balance' => 'decimal:2',
            'is_active'      => 'boolean',
            'khata_enabled'  => 'boolean',
        ];
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(AccountLedger::class);
    }

    public function returns()
    {
        return $this->hasMany(ReturnOrder::class);
    }

    public function account()
    {
        return $this->hasOne(CustomerAccount::class);
    }

    public function getOutstandingBalanceAttribute(): float
    {
        // negative balance means customer owes us (debit > credit)
        return (float) $this->credit_balance * -1;
    }

    public function scopeWithBalance($query)
    {
        return $query->where('credit_balance', '<', 0);
    }
}
