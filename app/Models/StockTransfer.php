<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $fillable = [
        'transfer_number',
        'from_shop_id',
        'to_shop_id',
        'note',
        'total_items',
        'total_qty',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'from_shop_id' => 'integer',
            'to_shop_id'   => 'integer',
            'created_by'   => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (StockTransfer $transfer) {
            if (empty($transfer->transfer_number)) {
                $next = (static::max('id') ?? 0) + 1;
                $transfer->transfer_number = 'TRF-' . str_pad($next, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function fromShop()
    {
        return $this->belongsTo(Shop::class, 'from_shop_id');
    }

    public function toShop()
    {
        return $this->belongsTo(Shop::class, 'to_shop_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function getFromLabelAttribute(): string
    {
        return $this->fromShop?->name ?? 'Main Shop';
    }

    public function getToLabelAttribute(): string
    {
        return $this->toShop?->name ?? 'Main Shop';
    }
}
