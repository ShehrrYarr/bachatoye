<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'company', 'address', 'balance', 'notes'];

    protected function casts(): array
    {
        return ['balance' => 'decimal:2'];
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
