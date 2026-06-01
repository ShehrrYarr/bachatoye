<?php

namespace App\Http\Controllers\Ecom;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

class CategoryController extends Controller
{
    public function show(string $slug)
    {
        $category = Category::active()->where('slug', $slug)->with('children')->firstOrFail();

        $query = Product::active()->inStock()->ecomVisible()
                         ->where(fn($q) => $q->where('category_id', $category->id)
                                             ->orWhere('subcategory_id', $category->id))
                         ->with(['images', 'brand', 'colors']);

        if (request()->filled('brand')) {
            $query->where('brand_id', request('brand'));
        }
        if (request()->filled('min_price')) {
            $query->where('price', '>=', request('min_price'));
        }
        if (request()->filled('max_price')) {
            $query->where('price', '<=', request('max_price'));
        }

        $sort = request('sort', 'latest');
        match ($sort) {
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name'       => $query->orderBy('name', 'asc'),
            default      => $query->latest(),
        };

        $products = $query->paginate(20)->withQueryString();

        $brands = Brand::whereHas('products', fn($q) =>
            $q->active()->inStock()->ecomVisible()
              ->where(fn($q2) => $q2->where('category_id', $category->id)
                                    ->orWhere('subcategory_id', $category->id))
        )->orderBy('name')->get();

        return view('ecom.category', compact('category', 'products', 'brands'));
    }
}
