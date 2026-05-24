<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountLedger extends Model
{
    protected $table = 'accounts_ledger';

    protected $fillable = [
        'customer_id', 'type', 'amount', 'balance_after',
        'description', 'reference', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'amount'        => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
