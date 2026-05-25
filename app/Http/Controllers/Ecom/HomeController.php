<?php

namespace App\Http\Controllers\Ecom;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Deal;
use App\Models\Product;
use App\Models\Section;

class HomeController extends Controller
{
    public function index()
    {
        $heroBanners    = Banner::active()->hero()->get();
        $promoBanners   = Banner::active()->where('position', 'promo')->orderBy('sort_order')->get();
        $categories     = Category::active()->whereNull('parent_id')
                            ->withCount('products')
                            ->with(['children' => fn($q) => $q->active()->withCount('products')])
                            ->orderBy('sort_order')->take(8)->get();
        $featuredProducts = Product::active()->inStock()->ecomVisible()->featured()->with(['images', 'category'])->take(8)->get();
        $activeDeals    = Deal::active()->take(4)->get();

        $dealProductChunks = Product::active()->inStock()->ecomVisible()
            ->whereHas('deals', fn($q) => $q->where('is_active', true)
                ->where(fn($q2) => $q2->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn($q2) => $q2->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            )
            ->with(['images', 'category', 'deals'])
            ->get()
            ->chunk(6);

        // ── Per-section new arrivals ──────────────────────────────────────────
        // 1 query: all sections ordered
        $sections = Section::orderBy('sort_order')->get();

        // 1 query: all active category IDs grouped by section_id
        $categoryIdsBySection = Category::active()
            ->whereIn('section_id', $sections->pluck('id'))
            ->get(['id', 'section_id'])
            ->groupBy('section_id');

        // N queries (one per section): latest 8 in-stock ecom-visible products
        $sectionNewArrivals = $sections->map(function (Section $section) use ($categoryIdsBySection) {
            $catIds = $categoryIdsBySection->get($section->id, collect())->pluck('id');
            if ($catIds->isEmpty()) return null;

            $products = Product::active()->inStock()->ecomVisible()
                ->whereIn('category_id', $catIds)
                ->with(['images', 'category'])
                ->latest()
                ->take(8)
                ->get();

            return $products->isNotEmpty()
                ? ['section' => $section, 'products' => $products]
                : null;
        })->filter()->values();

        return view('ecom.home', compact(
            'heroBanners', 'promoBanners', 'categories',
            'featuredProducts', 'sectionNewArrivals',
            'activeDeals', 'dealProductChunks'
        ));
    }
}
