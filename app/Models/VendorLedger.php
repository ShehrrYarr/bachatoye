<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorLedger extends Model
{
    protected $table = 'vendor_ledger';

    protected $fillable = ['vendor_id', 'purchase_id', 'order_id', 'type', 'is_opening_balance', 'amount', 'balance_after', 'description', 'reference', 'payment_method', 'bank_account_id', 'created_by', 'edited_at', 'edited_by'];

    protected function casts(): array
    {
        return [
            'amount'             => 'decimal:2',
            'balance_after'      => 'decimal:2',
            'is_opening_balance' => 'boolean',
            'edited_at'          => 'datetime',
        ];
    }

    /**
     * Manual entries carry no purchase/order link and may be edited or
     * deleted via the generic ledger UI. The opening-balance row is
     * excluded — it's a single anchor row managed only through the vendor
     * edit form, so it stays findable and never gets duplicated or orphaned.
     */
    public function isManual(): bool
    {
        return is_null($this->purchase_id) && is_null($this->order_id) && !$this->is_opening_balance;
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function editedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'edited_by');
    }
}
