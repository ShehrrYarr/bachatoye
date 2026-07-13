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
        'low_stock_threshold', 'low_stock_dismissed', 'category_id', 'subcategory_id', 'brand_id',
        'is_active', 'is_featured', 'is_new_arrival', 'show_in_ecom', 'track_inventory', 'is_serialized', 'free_delivery', 'bank_free_delivery', 'cod_enabled',
        'primary_serial_attribute_id', 'serial_attribute_ids', 'attribute_display_mode',
    ];

    protected function casts(): array
    {
        return [
            'price'                  => 'decimal:2',
            'cost_price'             => 'decimal:2',
            'compare_price'          => 'decimal:2',
            'is_active'              => 'boolean',
            'is_featured'            => 'boolean',
            'is_new_arrival'         => 'boolean',
            'show_in_ecom'           => 'boolean',
            'track_inventory'        => 'boolean',
            'is_serialized'          => 'boolean',
            'low_stock_dismissed'    => 'boolean',
            'free_delivery'          => 'boolean',
            'bank_free_delivery'     => 'boolean',
            'cod_enabled'            => 'boolean',
            'serial_attribute_ids'   => 'array',
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

    public function subcategory()
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
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

    public function serialNumbers()
    {
        return $this->hasMany(SerialNumber::class);
    }

    public function shopStocks()
    {
        return $this->hasMany(ShopStock::class);
    }

    /**
     * Stock quantity at a location. NULL shop = main shop
     * (products/product_colors columns); sub shop = shop_stocks rows.
     */
    public function stockForShop(?int $shopId, ?int $colorId = null): int
    {
        if ($shopId === null) {
            if ($colorId !== null) {
                return (int) $this->colors->firstWhere('id', $colorId)?->stock_quantity;
            }
            return (int) $this->stock_quantity;
        }

        $query = $this->shopStocks()->where('shop_id', $shopId);

        return (int) ($colorId !== null
            ? $query->where('product_color_id', $colorId)->value('quantity')
            : $query->sum('quantity'));
    }

    /** The attribute definition chosen as the primary selector on the store page. */
    public function primarySerialAttribute()
    {
        return $this->belongsTo(SerialAttributeDefinition::class, 'primary_serial_attribute_id');
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

    public function scopeEcomVisible($query)
    {
        return $query->where('show_in_ecom', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where(fn($q) => $q->where('track_inventory', false)->orWhere('stock_quantity', '>', 0));
    }

    /**
     * In-stock at a location. NULL shop = main shop (existing inStock behavior).
     */
    public function scopeInStockForShop($query, ?int $shopId)
    {
        if ($shopId === null) {
            return $query->inStock();
        }

        return $query->where(fn($q) => $q
            ->whereHas('shopStocks', fn($s) => $s->where('shop_id', $shopId)->where('quantity', '>', 0))
            ->orWhereHas('serialNumbers', fn($s) => $s->where('shop_id', $shopId)->where('status', 'in_stock'))
        );
    }
}
