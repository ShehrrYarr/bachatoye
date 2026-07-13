<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use BelongsToShop, SoftDeletes;

    protected $fillable = [
        'order_number', 'offline_ref', 'source', 'shop_id', 'customer_id', 'vendor_id', 'delivery_platform_id', 'cod_status', 'customer_name', 'customer_phone',
        'customer_email', 'delivery_address', 'city', 'delivery_notes',
        'subtotal', 'discount_amount', 'delivery_charge', 'total', 'amount_paid',
        'cash_amount', 'bank_amount', 'bank_account_id',
        'payment_method', 'payment_status', 'payment_proof', 'payment_notes',
        'notes', 'exchange_item_name', 'exchange_value',
        'status', 'tracking_number', 'served_by', 'deal_id',
        'coupon_id', 'coupon_discount', 'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'        => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'delivery_charge' => 'decimal:2',
            'coupon_discount' => 'decimal:2',
            'total'           => 'decimal:2',
            'amount_paid'     => 'decimal:2',
            'cash_amount'     => 'decimal:2',
            'bank_amount'     => 'decimal:2',
            'exchange_value'  => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (!$order->order_number) {
                $max  = (int) static::withTrashed()->max('id');
                $next = str_pad($max + 1, 4, '0', STR_PAD_LEFT);
                $order->order_number = 'ORD-' . $next;
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function servedBy()
    {
        return $this->belongsTo(User::class, 'served_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function returns()
    {
        return $this->hasMany(ReturnOrder::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function deliveryPlatform()
    {
        return $this->belongsTo(DeliveryPlatform::class);
    }

    public function platformPayouts()
    {
        return $this->belongsToMany(PlatformPayout::class, 'platform_payout_orders')
                    ->withPivot('order_amount')
                    ->withTimestamps();
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending'    => '<span class="badge bg-yellow-100 text-yellow-800">Pending</span>',
            'processing' => '<span class="badge bg-blue-100 text-blue-800">Processing</span>',
            'shipped'    => '<span class="badge bg-purple-100 text-purple-800">Shipped</span>',
            'delivered'  => '<span class="badge bg-green-100 text-green-800">Delivered</span>',
            'cancelled'  => '<span class="badge bg-red-100 text-red-800">Cancelled</span>',
            'returned'   => '<span class="badge bg-gray-100 text-gray-800">Returned</span>',
            default      => $this->status,
        };
    }
}
