<?php

namespace App\Http\Controllers\Ecom;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;

class BrandController extends Controller
{
    public function show(string $slug)
    {
        $brand    = Brand::where('slug', $slug)->firstOrFail();
        $products = Product::active()->inStock()->where('brand_id', $brand->id)->with(['images'])->paginate(20);
        return view('ecom.brand', compact('brand', 'products'));
    }
}
