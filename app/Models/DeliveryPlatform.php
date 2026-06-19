<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryPlatform extends Model
{
    protected $fillable = ['name', 'contact', 'notes', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function payouts()
    {
        return $this->hasMany(PlatformPayout::class);
    }

    public function pendingCodTotal(): float
    {
        return (float) $this->orders()
            ->where('cod_status', 'pending')
            ->where('status', 'delivered')
            ->sum('total');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
