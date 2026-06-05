@extends('layouts.admin')
@section('title', $product->name)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.products.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-xl font-bold text-gray-900 truncate max-w-md">{{ $product->name }}</h1>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('products.show', $product->slug) }}" target="_blank" class="btn-outline btn-sm">
            <i class="fas fa-external-link-alt mr-1"></i> View in Store
        </a>
        <a href="{{ route('admin.products.edit', $product) }}" class="btn-primary btn-sm">
            <i class="fas fa-edit mr-1"></i> Edit
        </a>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- Images + videos --}}
    <div class="xl:col-span-2 space-y-5">

        {{-- ===== IMAGE MANAGER ===== --}}
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-gray-800">Images</h2>
                <span class="text-xs text-gray-400">{{ $product->images->count() }} photo(s)</span>
            </div>
            <div class="card-body space-y-4">

                {{-- Existing images grid --}}
                @if($product->images->count())
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
                    @foreach($product->images as $img)
                    <div class="relative group rounded-xl overflow-hidden bg-gray-100 aspect-square">
                        <img src="{{ $img->url }}" alt="" class="w-full h-full object-cover">

                        {{-- Primary crown --}}
                        @if($img->is_primary)
                        <div class="absolute top-1 left-1 bg-yellow-400 text-yellow-900 text-xs font-bold px-1.5 py-0.5 rounded-md">
                            <i class="fas fa-star text-xs"></i> Primary
                        </div>
                        @endif

                        {{-- Hover action buttons --}}
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            @if(!$img->is_primary)
                            <form method="POST" action="{{ route('admin.products.images.primary', $img) }}">
                                @csrf @method('PATCH')
                                <button type="submit" title="Set as Primary"
                                        class="w-8 h-8 bg-yellow-400 hover:bg-yellow-300 text-yellow-900 rounded-lg flex items-center justify-center text-xs">
                                    <i class="fas fa-star"></i>
                                </button>
                            </form>
                            @endif
                            <form method="POST" action="{{ route('admin.products.images.delete', $img) }}"
                                  onsubmit="return confirm('Delete this image?')">
                                @csrf @method('DELETE')
                                <button type="submit" title="Delete"
                                        class="w-8 h-8 bg-red-500 hover:bg-red-400 text-white rounded-lg flex items-center justify-center text-xs">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Upload new images --}}
                <form method="POST" action="{{ route('admin.products.images.upload', $product) }}"
                      enctype="multipart/form-data"
                      x-data="{ files: [], dragging: false }"
                      @dragover.prevent="dragging = true"
                      @dragleave.prevent="dragging = false"
                      @drop.prevent="dragging = false; files = Array.from($event.dataTransfer.files); $refs.imgInput.files = $event.dataTransfer.files">
                    @csrf

                    <label :class="dragging ? 'border-primary-500 bg-primary-50' : 'border-gray-300 bg-gray-50 hover:bg-gray-100'"
                           class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-xl cursor-pointer transition-all">
                        <div class="text-center pointer-events-none">
                            <i class="fas fa-cloud-upload-alt text-2xl mb-1"
                               :class="dragging ? 'text-primary-500' : 'text-gray-400'"></i>
                            <p class="text-sm font-medium" :class="dragging ? 'text-primary-600' : 'text-gray-500'">
                                <span x-show="files.length === 0">Click or drag &amp; drop images here</span>
                                <span x-show="files.length > 0" x-text="files.length + ' file(s) selected'"></span>
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">JPG, PNG, WEBP — max 5MB each</p>
                        </div>
                        <input type="file" name="images[]" multiple accept="image/*" class="hidden"
                               x-ref="imgInput"
                               @change="files = Array.from($event.target.files)"
                               id="img-upload-input">
                    </label>

                    <div x-show="files.length > 0" class="mt-3 flex justify-end">
                        <button type="submit" class="btn-primary btn-sm">
                            <i class="fas fa-upload mr-1"></i>
                            Upload <span x-text="files.length"></span> Image(s)
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ===== VIDEOS ===== --}}
        <div class="card">
            <div class="card-header"><h2 class="font-semibold text-gray-800">Videos</h2></div>
            <div class="card-body space-y-3">

                @forelse($product->videos as $video)
                <div class="rounded-xl overflow-hidden relative group">
                    @if($video->type === 'upload')
                    <video src="{{ asset('storage/'.$video->path) }}" controls class="w-full rounded-xl"></video>
                    @else
                    <iframe src="{{ $video->embed_url }}" class="w-full aspect-video" allowfullscreen
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
                    @endif
                    <form method="POST" action="{{ route('admin.products.videos.delete', $video) }}"
                          onsubmit="return confirm('Remove this video?')"
                          class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        @csrf @method('DELETE')
                        <button class="bg-red-600 hover:bg-red-700 text-white text-xs px-2 py-1 rounded-lg">
                            <i class="fas fa-trash mr-1"></i>Remove
                        </button>
                    </form>
                </div>
                @empty
                <p class="text-sm text-gray-400">No videos yet.</p>
                @endforelse

                {{-- Add Video --}}
                <div class="border-t border-gray-100 pt-3" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="text-sm text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1.5">
                        <i class="fas fa-plus-circle"></i>
                        <span x-text="open ? 'Cancel' : 'Add Video'"></span>
                    </button>
                    <div x-show="open" x-transition class="mt-3">
                        <form method="POST" action="{{ route('admin.products.videos.upload', $product) }}"
                              enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            <input type="hidden" name="type" value="embed">
                            <div>
                                <label class="form-label text-sm">Embed Code</label>
                                <textarea name="url" rows="3" required
                                          class="form-textarea font-mono text-xs"
                                          placeholder="Paste the full YouTube/TikTok &lt;iframe&gt; embed code here"></textarea>
                                <p class="form-hint">YouTube → Share → Embed → copy the full &lt;iframe&gt; code</p>
                            </div>
                            <button type="submit" class="btn-primary btn-sm">
                                <i class="fas fa-save mr-1"></i> Save Video
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Description --}}
        @if($product->description)
        <div class="card">
            <div class="card-header"><h2 class="font-semibold text-gray-800">Description</h2></div>
            <div class="card-body prose prose-sm max-w-none text-gray-700">
                {!! nl2br(e($product->description)) !!}
            </div>
        </div>
        @endif

        {{-- Stock history --}}
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-gray-800">Recent Stock Movements</h2>
                <a href="{{ route('admin.inventory.history', $product) }}" class="text-sm text-primary-600 hover:underline">View All</a>
            </div>
            <div class="card-body">
                @if($product->stockMovements->count())
                <table class="data-table text-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Qty</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($product->stockMovements->take(10) as $m)
                        <tr>
                            <td class="text-xs">{{ $m->created_at->format('d M Y H:i') }}</td>
                            <td>
                                <span class="badge
                                    @if($m->type === 'purchase') bg-green-100 text-green-700
                                    @elseif($m->type === 'sale') bg-blue-100 text-blue-700
                                    @elseif($m->type === 'return') bg-purple-100 text-purple-700
                                    @else bg-gray-100 text-gray-600 @endif">
                                    {{ ucfirst($m->type) }}
                                </span>
                            </td>
                            <td class="{{ $m->quantity > 0 ? 'text-green-600' : 'text-red-600' }} font-semibold">
                                {{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}
                            </td>
                            <td class="text-gray-500 text-xs">{{ $m->notes ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p class="text-gray-400 text-sm">No stock movements yet.</p>
                @endif
            </div>
        </div>
        {{-- Purchase History --}}
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-gray-800">Purchase History</h2>
                @if($purchaseHistory->count())
                <span class="text-xs text-gray-400">{{ $purchaseHistory->count() }} purchase line(s)</span>
                @endif
            </div>
            <div class="card-body">
                @if($purchaseHistory->count())

                {{-- Summary row --}}
                @php
                    $totalUnitsBought = $purchaseHistory->sum('quantity');
                    $totalSpent       = $purchaseHistory->sum('line_total');
                    $vendorNames      = $purchaseHistory->map(fn($i) => $i->purchase?->vendor?->name)->filter()->unique()->values();
                @endphp
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div class="bg-blue-50 border border-blue-100 rounded-xl px-3 py-2.5 text-center">
                        <div class="text-lg font-bold text-blue-700">{{ number_format($totalUnitsBought) }}</div>
                        <div class="text-xs text-blue-500">Total Units In</div>
                    </div>
                    <div class="bg-green-50 border border-green-100 rounded-xl px-3 py-2.5 text-center">
                        <div class="text-lg font-bold text-green-700">Rs. {{ number_format($totalSpent) }}</div>
                        <div class="text-xs text-green-500">Total Cost</div>
                    </div>
                    <div class="bg-purple-50 border border-purple-100 rounded-xl px-3 py-2.5 text-center">
                        <div class="text-lg font-bold text-purple-700">{{ $vendorNames->count() }}</div>
                        <div class="text-xs text-purple-500">Vendor(s)</div>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-gray-100">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                            <tr>
                                <th class="text-left px-3 py-2.5 font-semibold">Date</th>
                                <th class="text-left px-3 py-2.5 font-semibold">Reference</th>
                                <th class="text-left px-3 py-2.5 font-semibold">Vendor</th>
                                <th class="text-left px-3 py-2.5 font-semibold">Color</th>
                                <th class="text-right px-3 py-2.5 font-semibold">Qty</th>
                                <th class="text-right px-3 py-2.5 font-semibold">Unit Cost</th>
                                <th class="text-right px-3 py-2.5 font-semibold">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($purchaseHistory as $item)
                            @php $purchase = $item->purchase; @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-3 py-2.5 text-xs text-gray-500 whitespace-nowrap">
                                    {{ $purchase?->purchase_date?->format('d M Y') ?? '—' }}
                                </td>
                                <td class="px-3 py-2.5">
                                    @if($purchase)
                                    <a href="{{ route('admin.purchases.show', $purchase) }}"
                                       class="font-mono text-xs text-primary-600 hover:underline">
                                        {{ $purchase->reference ?? 'PO-'.$purchase->id }}
                                    </a>
                                    @else
                                    <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-xs font-medium text-gray-700">
                                    {{ $purchase?->vendor?->name ?? '—' }}
                                </td>
                                <td class="px-3 py-2.5">
                                    @if($item->color_name)
                                    <span class="inline-flex items-center gap-1 text-xs text-gray-600">
                                        <span class="w-3 h-3 rounded-full border border-gray-300 shrink-0 inline-block"
                                              style="background: {{ optional(\App\Models\ProductColor::find($item->color_id))->hex_code ?? '#e5e7eb' }}"></span>
                                        {{ $item->color_name }}
                                    </span>
                                    @else
                                    <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-right">
                                    <span class="font-semibold text-blue-700">+{{ number_format($item->quantity) }}</span>
                                </td>
                                <td class="px-3 py-2.5 text-right text-xs text-gray-600">
                                    Rs. {{ number_format($item->unit_cost) }}
                                </td>
                                <td class="px-3 py-2.5 text-right font-semibold text-gray-800">
                                    Rs. {{ number_format($item->line_total) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                            <tr>
                                <td colspan="4" class="px-3 py-2.5 text-xs font-bold text-gray-600">
                                    {{ $purchaseHistory->count() }} lines · {{ $vendorNames->join(', ') ?: '—' }}
                                </td>
                                <td class="px-3 py-2.5 text-right font-bold text-blue-700">
                                    +{{ number_format($totalUnitsBought) }}
                                </td>
                                <td class="px-3 py-2.5"></td>
                                <td class="px-3 py-2.5 text-right font-bold text-gray-900">
                                    Rs. {{ number_format($totalSpent) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @else
                <p class="text-sm text-gray-400">No purchases recorded for this product yet.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Sidebar details --}}
    <div class="space-y-5">
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Product Details</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Status</dt>
                    <dd>
                        @if($product->is_active)
                            <span class="badge bg-green-100 text-green-700">Active</span>
                        @else
                            <span class="badge bg-gray-100 text-gray-500">Inactive</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Category</dt>
                    <dd class="font-medium">{{ $product->category?->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Brand</dt>
                    <dd class="font-medium">{{ $product->brand?->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between border-t border-gray-100 pt-3">
                    <dt class="text-gray-500">Selling Price</dt>
                    <dd class="font-bold text-primary-700">Rs. {{ number_format($product->price) }}</dd>
                </div>
                @if($product->cost_price)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Cost Price</dt>
                    <dd class="font-medium">Rs. {{ number_format($product->cost_price) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Margin</dt>
                    <dd class="font-medium text-green-600">
                        @php $margin = $product->cost_price > 0 ? round((($product->price - $product->cost_price) / $product->price) * 100, 1) : null; @endphp
                        {{ $margin !== null ? $margin.'%' : '—' }}
                    </dd>
                </div>
                @endif
                <div class="flex justify-between border-t border-gray-100 pt-3">
                    <dt class="text-gray-500">Stock</dt>
                    @php
                        $displayStock = $product->colors->count() > 0
                            ? $product->colors->sum('stock_quantity')
                            : $product->stock_quantity;
                    @endphp
                    <dd class="font-semibold {{ $displayStock <= 0 ? 'text-red-600' : ($product->isLowStock() ? 'text-orange-600' : 'text-gray-800') }}">
                        {{ $product->track_inventory ? number_format($displayStock) : 'Not tracked' }}
                    </dd>
                </div>
                @if($product->sku)
                <div class="flex justify-between">
                    <dt class="text-gray-500">SKU</dt>
                    <dd class="font-mono text-xs">{{ $product->sku }}</dd>
                </div>
                @endif
                @if($product->barcode)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Barcode</dt>
                    <dd class="font-mono text-xs">{{ $product->barcode }}</dd>
                </div>
                @endif
                <div class="flex justify-between border-t border-gray-100 pt-3">
                    <dt class="text-gray-500">Featured</dt>
                    <dd>{{ $product->is_featured ? 'Yes' : 'No' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">New Arrival</dt>
                    <dd>{{ $product->is_new_arrival ? 'Yes' : 'No' }}</dd>
                </div>
                <div class="flex justify-between border-t border-gray-100 pt-3">
                    <dt class="text-gray-500">Created</dt>
                    <dd class="text-xs">{{ $product->created_at->format('d M Y') }}</dd>
                </div>
            </dl>
        </div>

        @if($product->colors->count())
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Colors</h2>
            <div class="space-y-2">
                @foreach($product->colors as $color)
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-5 h-5 rounded-full border border-gray-300 shrink-0"
                             style="{{ $color->hex_code ? 'background:'.$color->hex_code : 'background:#e5e7eb' }}"></div>
                        <span class="text-sm font-medium text-gray-700">{{ $color->name }}</span>
                    </div>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                        {{ $color->stock_quantity <= 0 ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-700' }}">
                        {{ $color->stock_quantity }} units
                    </span>
                </div>
                @endforeach
                <div class="border-t border-gray-100 pt-2 flex justify-between text-xs text-gray-500">
                    <span>Total stock</span>
                    <span class="font-semibold text-gray-800">{{ $product->colors->sum('stock_quantity') }} units</span>
                </div>
            </div>
        </div>
        @endif

        @if($product->socialLinks->count())
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Social Links</h2>
            <div class="space-y-2">
                @foreach($product->socialLinks as $link)
                <a href="{{ $link->url }}" target="_blank" rel="noopener"
                   class="flex items-center gap-2 text-sm text-gray-600 hover:text-primary-600 transition-colors">
                    <i class="{{ $link->icon }} w-4 text-center"></i>
                    <span>{{ ucfirst($link->platform) }}</span>
                    <i class="fas fa-external-link-alt text-xs ml-auto text-gray-400"></i>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        <div class="flex flex-col gap-2">
            <a href="{{ route('admin.products.edit', $product) }}" class="btn-primary justify-center">
                <i class="fas fa-edit mr-2"></i> Edit Product
            </a>
            @if($product->barcode)
            <a href="{{ route('admin.products.print-barcode', $product) }}" target="_blank"
               class="btn-outline justify-center">
                <i class="fas fa-barcode mr-2"></i> Print Barcode
            </a>
            @endif
            <a href="{{ route('admin.inventory.adjust.form', $product) }}" class="btn-outline justify-center">
                <i class="fas fa-cubes mr-2"></i> Adjust Stock
            </a>
            <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                  onsubmit="return confirm('Delete this product permanently?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn-danger w-full justify-center">
                    <i class="fas fa-trash mr-2"></i> Delete
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
