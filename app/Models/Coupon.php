<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'name', 'description', 'type', 'value',
        'min_order_amount', 'applies_to', 'is_active',
        'starts_at', 'expires_at', 'times_used',
    ];

    protected function casts(): array
    {
        return [
            'value'            => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'is_active'        => 'boolean',
            'starts_at'        => 'datetime',
            'expires_at'       => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function products()
    {
        return $this->belongsToMany(Product::class, 'coupon_products');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'coupon_categories');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        if (!$this->is_active) return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        return true;
    }

    /** Human-readable discount label, e.g. "10% OFF" or "Rs. 200 OFF" */
    public function getBadgeLabelAttribute(): string
    {
        return $this->type === 'percentage'
            ? number_format($this->value, 0) . '% OFF'
            : 'Rs. ' . number_format($this->value, 0) . ' OFF';
    }

    /**
     * Calculate the discount amount for a collection of hydrated cart items.
     *
     * Each item must have: product_id, category_id, line_total
     *
     * Returns the discount in Rs. (never more than the eligible subtotal).
     */
    public function calculateDiscount(\Illuminate\Support\Collection $cartItems): float
    {
        if ($this->applies_to === 'products') {
            $ids = $this->products->pluck('id')->toArray();
            $eligible = $cartItems->filter(fn($i) => in_array($i['product_id'], $ids))->sum('line_total');
        } elseif ($this->applies_to === 'categories') {
            $ids = $this->categories->pluck('id')->toArray();
            $eligible = $cartItems->filter(fn($i) => in_array($i['category_id'], $ids))->sum('line_total');
        } else {
            $eligible = $cartItems->sum('line_total');
        }

        if ($eligible <= 0) return 0.0;

        return $this->type === 'percentage'
            ? round($eligible * ($this->value / 100), 2)
            : (float) min($this->value, $eligible);
    }

    /** Which products/categories this coupon is scoped to (for display) */
    public function getScopeDescriptionAttribute(): string
    {
        if ($this->applies_to === 'products' && $this->products->isNotEmpty()) {
            return $this->products->count() . ' product(s)';
        }
        if ($this->applies_to === 'categories' && $this->categories->isNotEmpty()) {
            return $this->categories->count() . ' categor' . ($this->categories->count() === 1 ? 'y' : 'ies');
        }
        return 'All products';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }
}
