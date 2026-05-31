@extends('layouts.admin')
@section('title', 'Adjust Stock: ' . $product->name)

@section('content')
@php $rPrefix = auth()->user()->hasRole('admin') ? 'admin' : 'salesman'; @endphp
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route("{$rPrefix}.inventory.index") }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Adjust Stock</h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Product info --}}
    <div class="card p-5">
        <div class="flex items-center gap-4 mb-4">
            <img src="{{ $product->primary_image_url }}" class="w-16 h-16 object-cover rounded-xl bg-gray-100 shrink-0">
            <div>
                <h2 class="font-bold text-gray-900">{{ $product->name }}</h2>
                @if($product->sku) <p class="text-xs text-gray-500 font-mono">SKU: {{ $product->sku }}</p> @endif
                @if($product->barcode) <p class="text-xs text-gray-500 font-mono">Barcode: {{ $product->barcode }}</p> @endif
            </div>
        </div>
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
    </div>

    {{-- Adjust form --}}
    <div class="card p-5">
        <h2 class="font-semibold text-gray-800 mb-4">Stock Adjustment</h2>
        <form method="POST" action="{{ route("{$rPrefix}.inventory.adjust", $product) }}">
            @csrf @method('PATCH')
            <div class="space-y-4">
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
                    <input type="number" name="quantity" class="form-input @error('quantity') border-red-500 @enderror"
                           placeholder="Enter quantity (positive or negative)" required>
                    <p class="form-hint">Use negative value to reduce stock (e.g. -5 for damage)</p>
                    @error('quantity') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Notes</label>
                    <textarea name="note" rows="2" class="form-textarea" placeholder="Reason for adjustment..."></textarea>
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
