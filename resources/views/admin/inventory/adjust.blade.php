@extends('layouts.admin')
@section('title', 'Adjust Stock: ' . $product->name)

@section('content')
@php $rPrefix = auth()->user()->panelPrefix(); @endphp
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route("{$rPrefix}.inventory.index") }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Adjust Stock</h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Product info --}}
    <div class="card p-5">
        <div class="flex items-center gap-4 mb-4">
            <img src="{{ $product->primary_image_url }}" loading="lazy" class="w-16 h-16 object-cover rounded-xl bg-gray-100 shrink-0">
            <div>
                <h2 class="font-bold text-gray-900">{{ $product->name }}</h2>
                @if($product->sku) <p class="text-xs text-gray-500 font-mono">SKU: {{ $product->sku }}</p> @endif
                @if($product->barcode) <p class="text-xs text-gray-500 font-mono">Barcode: {{ $product->barcode }}</p> @endif
            </div>
        </div>

        @if($product->colors->count() > 0)
            {{-- Per-color stock breakdown --}}
            <div class="space-y-2 mb-4">
                @foreach($product->colors as $color)
                <div class="flex items-center justify-between bg-gray-50 rounded-xl px-3 py-2">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded-full border border-gray-300 shrink-0"
                             style="{{ $color->hex_code ? 'background:'.$color->hex_code : 'background:#e5e7eb' }}"></div>
                        <span class="text-sm font-medium text-gray-700">{{ $color->name }}</span>
                    </div>
                    <span class="text-sm font-bold {{ $color->stock_quantity <= 0 ? 'text-red-600' : 'text-gray-800' }}">
                        {{ $color->stock_quantity }} units
                    </span>
                </div>
                @endforeach
                <div class="flex justify-between border-t border-gray-200 pt-2 px-1">
                    <span class="text-xs text-gray-500">Total stock</span>
                    <span class="text-sm font-bold text-gray-800">{{ $product->colors->sum('stock_quantity') }} units</span>
                </div>
            </div>
        @else
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="bg-gray-50 rounded-xl p-3">
                    <div class="text-gray-500 text-xs mb-1">Current Stock</div>
                    <div class="text-2xl font-bold {{ $product->stock_quantity <= 0 ? 'text-red-600' : ($product->isLowStock() ? 'text-orange-600' : 'text-gray-900') }}">
                        {{ number_format($product->stock_quantity) }}
                    </div>
                </div>
                <div class="bg-gray-50 rounded-xl p-3">
                    <div class="text-gray-500 text-xs mb-1">Low Stock Alert</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $product->low_stock_threshold }}</div>
                </div>
            </div>
        @endif
    </div>

    {{-- Adjust form --}}
    <div class="card p-5" x-data="{ selectedColor: '{{ old('color_id') }}' }">
        <h2 class="font-semibold text-gray-800 mb-4">Stock Adjustment</h2>
        <form method="POST" action="{{ route("{$rPrefix}.inventory.adjust", $product) }}">
            @csrf @method('PATCH')
            <div class="space-y-4">

                {{-- Color picker (only for color products) --}}
                @if($product->colors->count() > 0)
                <div>
                    <label class="form-label">Select Color Variant *</label>
                    @error('color_id') <p class="form-error mb-2">{{ $message }}</p> @enderror
                    <div class="space-y-2">
                        @foreach($product->colors as $color)
                        <label class="flex items-center justify-between gap-3 px-3 py-2.5 border-2 rounded-xl cursor-pointer transition-all"
                               :class="selectedColor === '{{ $color->id }}' ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300'">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="color_id" value="{{ $color->id }}"
                                       x-model="selectedColor"
                                       class="w-4 h-4 text-primary-600">
                                <div class="w-5 h-5 rounded-full border border-gray-300 shrink-0"
                                     style="{{ $color->hex_code ? 'background:'.$color->hex_code : 'background:#e5e7eb' }}"></div>
                                <span class="text-sm font-medium text-gray-800">{{ $color->name }}</span>
                            </div>
                            <span class="text-sm font-semibold {{ $color->stock_quantity <= 0 ? 'text-red-500' : 'text-green-700' }} shrink-0">
                                {{ $color->stock_quantity }} in stock
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif

                <div>
                    <label class="form-label">Adjustment Type *</label>
                    <select name="type" class="form-select" required>
                        <option value="purchase">Purchase / Stock In (+)</option>
                        <option value="adjustment">Manual Adjustment</option>
                        <option value="damage">Damage / Loss (–)</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Quantity *</label>
                    <input type="number" name="quantity" value="{{ old('quantity') }}"
                           class="form-input @error('quantity') border-red-500 @enderror"
                           placeholder="Enter quantity (positive or negative)" required>
                    <p class="form-hint">Use negative value to reduce stock (e.g. -5 for damage)</p>
                    @error('quantity') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Notes</label>
                    <textarea name="note" rows="2" class="form-textarea" placeholder="Reason for adjustment...">{{ old('note') }}</textarea>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="btn-primary">Apply Adjustment</button>
                    <a href="{{ route("{$rPrefix}.inventory.index") }}" class="btn-outline">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Movement history --}}
<div class="card mt-6">
    <div class="card-header">
        <h2 class="font-semibold text-gray-800">Movement History</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Qty Change</th>
                    <th>Notes</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $m)
                <tr>
                    <td class="text-xs">{{ $m->created_at->format('d M Y H:i') }}</td>
                    <td>
                        <span class="badge
                            @if($m->type === 'purchase') bg-green-100 text-green-700
                            @elseif($m->type === 'sale') bg-blue-100 text-blue-700
                            @elseif($m->type === 'return') bg-purple-100 text-purple-700
                            @elseif($m->type === 'damage') bg-red-100 text-red-700
                            @else bg-gray-100 text-gray-600 @endif">
                            {{ ucfirst($m->type) }}
                        </span>
                    </td>
                    <td class="{{ $m->quantity > 0 ? 'text-green-600' : 'text-red-600' }} font-semibold">
                        {{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}
                    </td>
                    <td class="text-sm text-gray-500">{{ $m->notes ?? '—' }}</td>
                    <td class="text-sm text-gray-500">{{ $m->user?->name ?? 'System' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-8 text-gray-400">No movement history yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
