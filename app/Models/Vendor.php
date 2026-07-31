<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'company', 'address', 'balance', 'opening_balance', 'notes', 'khata_enabled'];

    protected function casts(): array
    {
        return [
            'balance'         => 'decimal:2',
            'opening_balance' => 'decimal:2',
            'khata_enabled'   => 'boolean',
        ];
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(VendorLedger::class);
    }

    public function getOwedAttribute(): float
    {
        return max(0, (float) $this->balance);
    }
}
