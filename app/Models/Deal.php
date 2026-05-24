<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    protected $fillable = [
        'name', 'code', 'type', 'value', 'buy_quantity', 'get_quantity',
        'applies_to', 'is_active', 'starts_at', 'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'value'      => 'decimal:2',
            'is_active'  => 'boolean',
            'starts_at'  => 'datetime',
            'ends_at'    => 'datetime',
        ];
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'deal_products');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'deal_categories');
    }

    public function isActive(): bool
    {
        if (!$this->is_active) return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->ends_at && $this->ends_at->isPast()) return false;
        return true;
    }

    public function getBadgeLabelAttribute(): string
    {
        return match($this->type) {
            'percentage'  => number_format($this->value, 0) . '% OFF',
            'flat'        => 'Rs. ' . number_format($this->value, 0) . ' OFF',
            'buy_x_get_y' => "Buy {$this->buy_quantity} Get {$this->get_quantity} Free",
            default       => $this->name,
        };
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }
}
