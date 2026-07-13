<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Model;

class PosSession extends Model
{
    use BelongsToShop;

    protected $fillable = [
        'shop_id', 'user_id', 'opening_cash', 'closing_cash',
        'total_sales', 'total_orders', 'opened_at', 'closed_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_cash' => 'decimal:2',
            'closing_cash' => 'decimal:2',
            'total_sales'  => 'decimal:2',
            'opened_at'    => 'datetime',
            'closed_at'    => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }
}
