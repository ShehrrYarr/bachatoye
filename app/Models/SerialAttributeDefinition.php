<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SerialAttributeDefinition extends Model
{
    protected $fillable = ['name', 'options', 'is_active', 'sort_order'];

    protected $casts = [
        'options'   => 'array',
        'is_active' => 'boolean',
    ];

    public static function activeOrdered()
    {
        return static::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
    }
}
