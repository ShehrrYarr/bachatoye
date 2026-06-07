<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SerialAttributeDefinition extends Model
{
    protected $fillable = ['name', 'options', 'is_active', 'is_primary', 'sort_order'];

    protected $casts = [
        'options'    => 'array',
        'is_active'  => 'boolean',
        'is_primary' => 'boolean',
    ];

    public static function activeOrdered()
    {
        return static::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
    }

    /** The one attribute definition currently marked as primary, or null. */
    public static function primary(): ?self
    {
        return static::where('is_primary', true)->first();
    }

    /** Mark this definition as primary and unmark all others. */
    public function makePrimary(): void
    {
        static::where('id', '!=', $this->id)->update(['is_primary' => false]);
        $this->update(['is_primary' => true]);
    }

    /** Remove the primary flag from whatever definition currently holds it. */
    public static function clearPrimary(): void
    {
        static::where('is_primary', true)->update(['is_primary' => false]);
    }
}
