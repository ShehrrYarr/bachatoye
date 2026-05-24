<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use Sluggable;

    protected $fillable = [
        'name', 'slug', 'sku', 'barcode', 'short_description', 'description',
        'price', 'cost_price', 'compare_price', 'stock_quantity',
        'low_stock_threshold', 'category_id', 'brand_id',
        'is_active', 'is_featured', 'track_inventory',
    ];

    protected function casts(): array
    {
        return [
            'price'               => 'decimal:2',
            'cost_price'          => 'decimal:2',
            'compare_price'       => 'decimal:2',
            'is_active'           => 'boolean',
            'is_featured'         => 'boolean',
            'track_inventory'     => 'boolean',
        ];
    }

    public function sluggable(): array
    {
        return ['slug' => ['source' => 'name']];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function videos()
    {
        return $this->hasMany(ProductVideo::class)->orderBy('sort_order');
    }

    public function socialLinks()
    {
        return $this->hasMany(ProductSocialLink::class);
    }

    public function colors()
    {
        return $this->hasMany(ProductColor::class)->orderBy('sort_order');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function deals()
    {
        return $this->belongsToMany(Deal::class, 'deal_products');
    }

    public function getPrimaryImageUrlAttribute(): string
    {
        $img = $this->images->firstWhere('is_primary', true) ?? $this->images->first();
        return $img ? asset('storage/' . $img->path) : asset('images/product-placeholder.png');
    }

    public function isInStock(): bool
    {
        return !$this->track_inventory || $this->stock_quantity > 0;
    }

    public function isLowStock(): bool
    {
        return $this->track_inventory && $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function getActiveDeal(): ?Deal
    {
        return $this->deals()
            ->where('is_active', true)
            ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->first();
    }

    public function getDiscountedPrice(): float
    {
        $deal = $this->getActiveDeal();
        if (!$deal || $deal->type === 'buy_x_get_y') {
            return (float) $this->price;
        }
        if ($deal->type === 'percentage') {
            return round($this->price * (1 - $deal->value / 100), 2);
        }
        return max(0, $this->price - $deal->value);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where(fn($q) => $q->where('track_inventory', false)->orWhere('stock_quantity', '>', 0));
    }
}
