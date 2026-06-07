<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAttributePrice extends Model
{
    protected $fillable = [
        'product_id',
        'serial_attribute_definition_id',
        'option_value',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeDefinition()
    {
        return $this->belongsTo(SerialAttributeDefinition::class, 'serial_attribute_definition_id');
    }
}
