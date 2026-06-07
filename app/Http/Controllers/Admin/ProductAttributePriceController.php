<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAttributePrice;
use App\Models\SerialAttributeDefinition;
use Illuminate\Http\Request;

class ProductAttributePriceController extends Controller
{
    public function save(Request $request, Product $product)
    {
        $attr = SerialAttributeDefinition::primary();

        if (!$attr || !$product->is_serialized) {
            return back()->withErrors(['attr' => 'No primary attribute defined or product is not serialized.']);
        }

        $request->validate([
            'prices'   => 'nullable|array',
            'prices.*' => 'nullable|numeric|min:0',
        ]);

        foreach ($attr->options as $option) {
            $price = $request->input("prices.{$option}");

            if ($price === null || $price === '') {
                // Remove price entry if blank
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
