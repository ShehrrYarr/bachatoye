<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use BelongsToShop;

    protected $fillable = [
        'shop_id', 'label', 'bank_name', 'account_title', 'account_number', 'iban',
        'is_active', 'sort_order', 'opening_balance',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /** Short display string for dropdowns */
    public function getDisplayNameAttribute(): string
    {
        return $this->label . ' — ' . $this->account_title;
    }
}
