<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAttributePrice;
use App\Models\SerialAttributeDefinition;
use Illuminate\Http\Request;

class ProductAttributePriceController extends Controller
{
    /**
     * Save (or clear) which attribute definition is the primary store selector for this product.
     */
    public function savePrimaryAttribute(Request $request, Product $product)
    {
        $request->validate([
            'primary_serial_attribute_id' => 'nullable|exists:serial_attribute_definitions,id',
        ]);

        $product->update([
            'primary_serial_attribute_id' => $request->input('primary_serial_attribute_id') ?: null,
        ]);

        return back()->with('attr_price_success', 'Store attribute updated.');
    }

    /**
     * Save per-option prices for this product's chosen primary attribute.
     */
    public function save(Request $request, Product $product)
    {
        $attr = $product->is_serialized ? $product->primarySerialAttribute : null;

        if (!$attr) {
            return back()->withErrors(['attr' => 'No store attribute selected for this product.']);
        }

        $request->validate([
            'prices'   => 'nullable|array',
            'prices.*' => 'nullable|numeric|min:0',
        ]);

        foreach ($attr->options as $option) {
            $price = $request->input("prices.{$option}");

            if ($price === null || $price === '') {
                ProductAttributePrice::where('product_id', $product->id)
                    ->where('serial_attribute_definition_id', $attr->id)
                    ->where('option_value', $option)
                    ->delete();
            } else {
                ProductAttributePrice::updateOrCreate(
                    [
                        'product_id'                     => $product->id,
                        'serial_attribute_definition_id' => $attr->id,
                        'option_value'                   => $option,
                    ],
                    ['price' => $price]
                );
            }
        }

        return back()->with('attr_price_success', 'Attribute prices saved.');
    }
}
