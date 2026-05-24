<?php

namespace App\Http\Controllers\Ecom;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Deal;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $heroBanners    = Banner::active()->hero()->get();
        $promoBanners   = Banner::active()->where('position', 'promo')->orderBy('sort_order')->get();
        $categories     = Category::active()->whereNull('parent_id')->withCount('products')->orderBy('sort_order')->take(8)->get();
        $featuredProducts = Product::active()->inStock()->featured()->with(['images', 'category'])->take(8)->get();
        $newArrivals    = Product::active()->inStock()->with(['images', 'category'])->latest()->take(8)->get();
        $activeDeals    = Deal::active()->take(4)->get();

        $dealProductChunks = Product::active()->inStock()
            ->whereHas('deals', fn($q) => $q->where('is_active', true)
                ->where(fn($q2) => $q2->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn($q2) => $q2->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            )
            ->with(['images', 'category', 'deals'])
            ->get()
            ->chunk(6);

        return view('ecom.home', compact(
            'heroBanners', 'promoBanners', 'categories',
            'featuredProducts', 'newArrivals', 'activeDeals', 'dealProductChunks'
        ));
    }
}
