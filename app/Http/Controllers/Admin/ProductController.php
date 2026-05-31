<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Deal;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductImage;
use App\Models\ProductSocialLink;
use App\Models\ProductVideo;
use App\Services\BarcodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::active()->withCount('products')->orderBy('sort_order')->orderBy('name')->get();
        $brands     = Brand::all();

        // If no category is selected, show the category grid instead of products
        if (!$request->filled('category') && !$request->filled('q') && !$request->filled('brand') && !$request->filled('status')) {
            $uncategorizedCount = Product::whereNull('category_id')->count();
            return view('admin.products.index', compact('categories', 'brands', 'uncategorizedCount'));
        }

        // Category (or filter) selected — show products
        $query = Product::with(['category', 'brand'])->latest();

        if ($request->filled('q')) {
            $s = $request->q;
            $query->where(fn($q) => $q->where('name', 'like', "%{$s}%")
                                      ->orWhere('barcode', 'like', "%{$s}%")
                                      ->orWhere('sku', 'like', "%{$s}%"));
        }

        if ($request->filled('category')) {
            if ($request->category === 'uncategorized') {
                $query->whereNull('category_id');
            } else {
                $query->where('category_id', $request->category);
            }
        }
        if ($request->filled('brand'))  $query->where('brand_id', $request->brand);
        if ($request->filled('status')) $query->where('is_active', $request->status === 'active');

        $products        = $query->paginate(30)->withQueryString();
        $selectedCat     = $request->category === 'uncategorized'
                            ? (object)['id' => 'uncategorized', 'name' => 'Uncategorized']
                            : ($request->filled('category') ? $categories->firstWhere('id', $request->category) : null);
        $uncategorizedCount = 0;

        return view('admin.products.index', compact('products', 'categories', 'brands', 'selectedCat', 'uncategorizedCount'));
    }

    public function create()
    {
        $categories = Category::active()->whereNull('parent_id')->get();
        $brands     = Brand::all();
        $deals      = Deal::active()->get();
        return view('admin.products.create', compact('categories', 'brands', 'deals'));
    }

    public function subcategories(Category $category)
    {
        $subs = $category->children()->active()->orderBy('name')->get(['id', 'name']);
        return response()->json($subs);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'short_description'   => 'nullable|string|max:500',
            'description'         => 'nullable|string',
            'price'               => 'required|numeric|min:0',
            'cost_price'          => 'required|numeric|min:0',
            'compare_price'       => 'nullable|numeric|min:0',
            'stock_quantity'      => 'nullable|integer|min:0',
            'low_stock_threshold' => 'required|integer|min:0',
            'category_id'         => 'nullable|exists:categories,id',
            'subcategory_id'      => 'nullable|exists:categories,id',
            'brand_id'            => 'nullable|exists:brands,id',
            'barcode'             => 'nullable|string|max:50|unique:products',
            'sku'                 => 'nullable|string|max:100|unique:products',
            'is_active'           => 'boolean',
            'is_featured'         => 'boolean',
            'show_in_ecom'        => 'boolean',
            'track_inventory'     => 'boolean',
            'free_delivery'       => 'boolean',
            'images'              => 'nullable|array',
            'images.*'            => 'image|max:5120',
            'video_embed_url'     => 'nullable|string',
            'video_file'          => 'nullable|file|mimetypes:video/mp4,video/webm|max:102400',
        ]);

        $data['is_active']       = $request->boolean('is_active');
        $data['is_featured']     = $request->boolean('is_featured');
        $data['show_in_ecom']    = $request->boolean('show_in_ecom', true);
        $data['track_inventory'] = $request->boolean('track_inventory', true);
        $data['free_delivery']   = $request->boolean('free_delivery');
        $data['stock_quantity']  = $data['stock_quantity'] ?? 0;

        // Generate barcode if not provided
        if (empty($data['barcode'])) {
            $data['barcode'] = BarcodeService::generate($data['category_id'] ?? null);
        }

        $product = Product::create(\Arr::except($data, ['images', 'video_embed_url', 'video_file']));

        // Save uploaded images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $i => $file) {
                $product->images()->create([
                    'path'       => $file->store('products', 'public'),
                    'sort_order' => $i,
                    'is_primary' => $i === 0,
                ]);
            }
        }

        // Save embed video
        if ($request->filled('video_embed_url')) {
            $embedInput = trim($request->video_embed_url);
            if (str_contains($embedInput, '<iframe')) {
                preg_match('/src=["\']([^"\']+)["\']/', $embedInput, $matches);
                $embedInput = $matches[1] ?? $embedInput;
            }
            $product->videos()->create([
                'type'       => 'embed',
                'url'        => $embedInput,
                'sort_order' => 0,
            ]);
        }

        // Save uploaded video file
        if ($request->hasFile('video_file')) {
            $product->videos()->create([
                'type'       => 'upload',
                'path'       => $request->file('video_file')->store('product-videos', 'public'),
                'sort_order' => $product->videos()->max('sort_order') + 1,
            ]);
        }

        $this->saveSocialLinks($product, $request);
        $this->saveColors($product, $request);

        return redirect()->route('admin.products.show', $product)->with('success', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        $product->load(['images', 'videos', 'socialLinks', 'colors', 'category', 'brand']);
        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $product->load(['images', 'videos', 'socialLinks', 'colors']);
        $categories    = Category::active()->whereNull('parent_id')->get();
        $brands        = Brand::all();
        $subcategories = $product->category_id
            ? Category::active()->where('parent_id', $product->category_id)->orderBy('name')->get()
            : collect();
        return view('admin.products.edit', compact('product', 'categories', 'brands', 'subcategories'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'short_description'   => 'nullable|string|max:500',
            'description'         => 'nullable|string',
            'price'               => 'required|numeric|min:0',
            'cost_price'          => 'required|numeric|min:0',
            'compare_price'       => 'nullable|numeric|min:0',
            'stock_quantity'      => 'nullable|integer|min:0',
            'low_stock_threshold' => 'required|integer|min:0',
            'category_id'         => 'nullable|exists:categories,id',
            'subcategory_id'      => 'nullable|exists:categories,id',
            'brand_id'            => 'nullable|exists:brands,id',
            'barcode'             => 'nullable|string|max:50|unique:products,barcode,' . $product->id,
            'sku'                 => 'nullable|string|max:100|unique:products,sku,' . $product->id,
            'is_active'           => 'boolean',
            'is_featured'         => 'boolean',
            'show_in_ecom'        => 'boolean',
            'track_inventory'     => 'boolean',
            'free_delivery'       => 'boolean',
            'images'              => 'nullable|array',
            'images.*'            => 'image|max:5120',
            'video_embed_url'     => 'nullable|string',
            'video_file'          => 'nullable|file|mimetypes:video/mp4,video/webm|max:102400',
        ]);

        $data['is_active']       = $request->boolean('is_active');
        $data['is_featured']     = $request->boolean('is_featured');
        $data['show_in_ecom']    = $request->boolean('show_in_ecom', true);
        $data['track_inventory'] = $request->boolean('track_inventory', true);
        $data['free_delivery']   = $request->boolean('free_delivery');

        // Strip non-model fields before updating
        $product->update(\Arr::except($data, ['images', 'video_embed_url', 'video_file']));

        // Save uploaded images
        if ($request->hasFile('images')) {
            $sortStart = $product->images()->max('sort_order') + 1;
            $isPrimary = $product->images()->count() === 0;
            foreach ($request->file('images') as $i => $file) {
                $product->images()->create([
                    'path'       => $file->store('products', 'public'),
                    'sort_order' => $sortStart + $i,
                    'is_primary' => $isPrimary && $i === 0,
                ]);
            }
        }

        // Save embed video URL (supports both raw URL and full <iframe> embed code)
        if ($request->filled('video_embed_url')) {
            $embedInput = trim($request->video_embed_url);
            // Extract src= from iframe embed code if necessary
            if (str_contains($embedInput, '<iframe')) {
                preg_match('/src=["\']([^"\']+)["\']/', $embedInput, $matches);
                $embedInput = $matches[1] ?? $embedInput;
            }
            $product->videos()->create([
                'type'       => 'embed',
                'url'        => $embedInput,
                'sort_order' => $product->videos()->max('sort_order') + 1,
            ]);
        }

        // Save uploaded video file
        if ($request->hasFile('video_file')) {
            $product->videos()->create([
                'type'       => 'upload',
                'path'       => $request->file('video_file')->store('product-videos', 'public'),
                'sort_order' => $product->videos()->max('sort_order') + 1,
            ]);
        }

        $this->saveSocialLinks($product, $request);
        $this->saveColors($product, $request);

        return redirect()->route('admin.products.show', $product)->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }

    public function uploadImages(Request $request, Product $product)
    {
        $request->validate([
            'images'   => 'required|array',
            'images.*' => 'image|max:5120',
        ]);

        $sortStart = $product->images()->max('sort_order') + 1;
        $isPrimary = $product->images()->count() === 0;

        foreach ($request->file('images') as $i => $file) {
            $path = $file->store('products', 'public');

            $product->images()->create([
                'path'       => $path,
                'sort_order' => $sortStart + $i,
                'is_primary' => $isPrimary && $i === 0,
            ]);
        }

        return back()->with('success', count($request->file('images')) . ' image(s) uploaded.');
    }

    public function deleteImage(ProductImage $image)
    {
        Storage::disk('public')->delete($image->path);
        $wasPrimary = $image->is_primary;
        $productId  = $image->product_id;
        $image->delete();

        if ($wasPrimary) {
            ProductImage::where('product_id', $productId)->oldest()->first()?->update(['is_primary' => true]);
        }

        return back()->with('success', 'Image deleted.');
    }

    public function setPrimaryImage(ProductImage $image)
    {
        ProductImage::where('product_id', $image->product_id)->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);
        return back()->with('success', 'Primary image updated.');
    }

    public function uploadVideo(Request $request, Product $product)
    {
        $request->validate([
            'type'      => 'required|in:embed,upload',
            'url'       => 'required_if:type,embed|nullable|string',
            'video'     => 'required_if:type,upload|nullable|file|mimetypes:video/mp4,video/webm|max:102400',
            'title'     => 'nullable|string|max:150',
        ]);

        $videoData = [
            'type'       => $request->type,
            'title'      => $request->title,
            'sort_order' => $product->videos()->max('sort_order') + 1,
        ];

        if ($request->type === 'embed') {
            // Accept full <iframe> embed code or a plain URL
            $embedInput = trim($request->url ?? '');
            if (str_contains($embedInput, '<iframe')) {
                preg_match('/src=["\']([^"\']+)["\']/', $embedInput, $matches);
                $embedInput = $matches[1] ?? $embedInput;
            }
            $videoData['url'] = $embedInput;
        } else {
            $videoData['path'] = $request->file('video')->store('product-videos', 'public');
        }

        $product->videos()->create($videoData);

        return back()->with('success', 'Video added.');
    }

    public function deleteVideo(ProductVideo $video)
    {
        if ($video->path) {
            Storage::disk('public')->delete($video->path);
        }
        $video->delete();
        return back()->with('success', 'Video removed.');
    }

    private function saveSocialLinks(Product $product, Request $request): void
    {
        $product->socialLinks()->delete();
        $allowed = ['facebook', 'tiktok', 'instagram', 'youtube'];
        foreach ($request->input('social_links', []) as $entry) {
            $platform = $entry['platform'] ?? '';
            $url      = trim($entry['url'] ?? '');
            if ($url && in_array($platform, $allowed)) {
                $product->socialLinks()->create(['platform' => $platform, 'url' => $url]);
            }
        }
    }

    private function saveColors(Product $product, Request $request): void
    {
        $submitted    = collect($request->input('colors', []));
        $submittedIds = $submitted->pluck('id')->filter()->map(fn($id) => (int) $id);

        // Delete colors removed from the form
        $product->colors()->whereNotIn('id', $submittedIds)->delete();

        foreach ($submitted as $i => $colorData) {
            $name = trim($colorData['name'] ?? '');
            if (!$name) continue;

            $data = [
                'name'           => $name,
                'hex_code'       => trim($colorData['hex_code'] ?? '') ?: null,
                'stock_quantity' => max(0, (int) ($colorData['stock_quantity'] ?? 0)),
                'sort_order'     => $i,
            ];

            if (!empty($colorData['id'])) {
                $product->colors()->where('id', (int) $colorData['id'])->update($data);
            } else {
                $product->colors()->create($data);
            }
        }

        // Keep product.stock_quantity in sync with total color stock (if colors exist)
        if ($product->colors()->count() > 0) {
            $product->update(['stock_quantity' => $product->colors()->sum('stock_quantity')]);
        }
    }

    public function generateBarcodeAjax(Request $request): \Illuminate\Http\JsonResponse
    {
        $categoryId = $request->query('category_id') ? (int) $request->query('category_id') : null;
        return response()->json(['barcode' => BarcodeService::generate($categoryId)]);
    }

    public function printBarcode(Product $product)
    {
        return view('admin.products.print-barcode', compact('product'));
    }
}
