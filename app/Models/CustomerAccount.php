<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class CustomerAccount extends Authenticatable
{
    const SECURITY_QUESTIONS = [
        "What was the name of your first pet?",
        "What was the name of the street you grew up on?",
        "What is your mother's maiden name?",
        "What was the name of your first school?",
        "What was the make and model of your first car?",
        "What city were you born in?",
        "What is the name of your childhood best friend?",
    ];

    protected $fillable = [
        'customer_id',
        'email',
        'password',
        'security_question',
        'security_answer',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = ['password', 'security_answer'];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'is_active'     => 'boolean',
            'password'      => 'hashed',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
