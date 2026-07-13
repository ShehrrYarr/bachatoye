<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'receipt_header',
        'receipt_footer',
        'cash_opening_balance',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'            => 'boolean',
            'cash_opening_balance' => 'decimal:2',
        ];
    }

    /** The single login user of this shop. */
    public function loginUser()
    {
        return $this->hasOne(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function shopStocks()
    {
        return $this->hasMany(ShopStock::class);
    }

    public function serialNumbers()
    {
        return $this->hasMany(SerialNumber::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
