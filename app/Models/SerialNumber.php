<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Model;

class SerialNumber extends Model
{
    use BelongsToShop;

    protected $fillable = [
        'product_id',
        'shop_id',
        'subcategory_id',
        'serial_number',
        'cost_price',
        'selling_price',
        'attributes',
        'status',
        'purchase_id',
        'purchase_item_id',
        'order_id',
        'order_item_id',
        'return_order_id',
        'notes',
        'image',
    ];

    protected function casts(): array
    {
        return [
            'status'        => 'string',
            'cost_price'    => 'decimal:2',
            'selling_price' => 'decimal:2',
            'attributes'    => 'array',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(\App\Models\Category::class, 'subcategory_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function returnOrder()
    {
        return $this->belongsTo(ReturnOrder::class);
    }

    /** All order items across this serial's lifetime (survives buyback/return clearing the current order_id). */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /** All buyback events this serial has ever been part of. */
    public function buybackItems()
    {
        return $this->hasMany(BuybackItem::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeInStock($query)
    {
        return $query->where('status', 'in_stock');
    }

    public function scopeSold($query)
    {
        return $query->where('status', 'sold');
    }

    public function scopeReturned($query)
    {
        return $query->where('status', 'returned');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'in_stock' => '<span class="badge bg-green-100 text-green-700">In Stock</span>',
            'sold'     => '<span class="badge bg-blue-100 text-blue-700">Sold</span>',
            'returned' => '<span class="badge bg-orange-100 text-orange-700">Returned</span>',
            default    => '<span class="badge bg-gray-100 text-gray-700">' . ucfirst($this->status) . '</span>',
        };
    }
}
