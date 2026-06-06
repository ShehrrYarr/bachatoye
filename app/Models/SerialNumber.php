<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SerialNumber extends Model
{
    protected $fillable = [
        'product_id',
        'serial_number',
        'status',
        'purchase_id',
        'purchase_item_id',
        'order_id',
        'order_item_id',
        'return_order_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function product()
    {
        return $this->belongsTo(Product::class);
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
