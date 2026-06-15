<?php

namespace App\Http\Controllers\Ecom;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttributePrice;
use App\Models\Section;
use App\Models\SerialAttributeDefinition;
use App\Models\SerialNumber;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::active()->inStock()->ecomVisible()->with(['images', 'category', 'brand', 'colors']);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn($sq) => $sq->where('name', 'like', "%{$q}%")
                                        ->orWhere('short_description', 'like', "%{$q}%"));
        }

        // Section filter — used by "View All" links on per-section new arrivals
        if ($request->filled('section')) {
            $section = Section::where('slug', $request->section)->first();
            if ($section) {
                $catIds = Category::active()->where('section_id', $section->id)->pluck('id');
                $query->whereIn('category_id', $catIds);
            }
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('brand')) {
            $query->where('brand_id', $request->brand);
        }
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'price_asc'  => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'popular'    => $query->withCount('items')->orderByDesc('items_count'),
            default      => $query->latest(),
        };

        $products   = $query->paginate(20)->withQueryString();
        $categories = Category::active()->whereNull('parent_id')->get();
        $brands     = Brand::all();

        return view('ecom.products.index', compact('products', 'categories', 'brands'));
    }

    public function show(string $slug)
    {
        $product = Product::active()->inStock()->ecomVisible()->with([
            'images', 'videos', 'socialLinks', 'category', 'brand', 'primarySerialAttribute',
        ])->where('slug', $slug)->firstOrFail();

        $related = Product::active()->inStock()->ecomVisible()
                           ->where('category_id', $product->category_id)
                           ->where('id', '!=', $product->id)
                           ->with('images')
                           ->take(4)
                           ->get();

        $deal = $product->getActiveDeal();

        // Per-product primary attribute options for the store page
        $primaryAttr = null;
        $attrOptions = collect(); // [ ['value'=>'8 GB', 'price'=>35000, 'in_stock'=>true], ... ]

        if ($product->is_serialized) {
            $primaryAttr = $product->primarySerialAttribute; // already eager-loaded

            if ($primaryAttr) {
                $prices = ProductAttributePrice::where('product_id', $product->id)
                    ->where('serial_attribute_definition_id', $primaryAttr->id)
                    ->pluck('price', 'option_value');

                foreach ($primaryAttr->options as $opt) {
                    if ($prices->has($opt)) {
                        // Admin-configured attribute price takes priority
                        $price = (float) $prices->get($opt);
                    } else {
                        // Fall back to the min selling_price of serial numbers with this attribute value
                        $price = (float) (SerialNumber::where('product_id', $product->id)
                            ->where("attributes->{$primaryAttr->name}", $opt)
                            ->whereNotNull('selling_price')
                            ->where('selling_price', '>', 0)
                            ->min('selling_price') ?? 0);
                    }

                    $inStock = SerialNumber::where('product_id', $product->id)
                        ->where('status', 'in_stock')
                        ->where("attributes->{$primaryAttr->name}", $opt)
                        ->exists();

                    $serialWithImage = SerialNumber::where('product_id', $product->id)
                        ->where('status', 'in_stock')
                        ->where("attributes->{$primaryAttr->name}", $opt)
                        ->whereNotNull('image')
                        ->value('image');

                    $attrOptions->push([
                        'value'    => $opt,
                        'price'    => $price,
                        'in_stock' => $inStock,
                        'image'    => $serialWithImage ? \Illuminate\Support\Facades\Storage::disk('public')->url($serialWithImage) : null,
                    ]);
                }
            }
        }

        return view('ecom.products.show', compact('product', 'related', 'deal', 'primaryAttr', 'attrOptions'));
    }

    public function search(Request $request)
    {
        $request->validate(['q' => 'required|string|min:2']);
        return redirect()->route('products.index', ['q' => $request->q]);
    }

    public function liveSearch(Request $request)
    {
        $q = $request->input('q', '');
        if (strlen($q) < 2) return response()->json([]);

        $products = Product::active()->inStock()->ecomVisible()
                            ->where('name', 'like', "%{$q}%")
                            ->with('images')
                            ->limit(6)
                            ->get()
                            ->map(fn($p) => [
                                'name'  => $p->name,
                                'slug'  => $p->slug,
                                'price' => $p->getDiscountedPrice(),
                                'image' => $p->primary_image_url,
                            ]);

        return response()->json($products);
    }
}
