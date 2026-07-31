<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountLedger extends Model
{
    protected $table = 'accounts_ledger';

    protected $fillable = [
        'customer_id', 'order_id', 'type', 'is_opening_balance', 'payment_method', 'bank_account_id', 'amount', 'balance_after',
        'description', 'promise_date', 'reference', 'user_id', 'return_id', 'edited_at', 'edited_by',
    ];

    protected function casts(): array
    {
        return [
            'amount'             => 'decimal:2',
            'balance_after'      => 'decimal:2',
            'is_opening_balance' => 'boolean',
            'promise_date'       => 'date',
            'edited_at'          => 'datetime',
        ];
    }

    /**
     * Manual entries carry no sale/return link and may be edited or deleted
     * via the generic ledger UI. The opening-balance row is excluded — it's
     * a single anchor row managed only through the customer edit form, so
     * it stays findable and never gets duplicated or orphaned.
     */
    public function isManual(): bool
    {
        return is_null($this->order_id) && is_null($this->return_id) && !$this->is_opening_balance;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function returnOrder()
    {
        return $this->belongsTo(\App\Models\ReturnOrder::class, 'return_id');
    }

    public function editedBy()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
