@extends('layouts.admin')
@section('title', 'Stock History: ' . $product->name)

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.inventory.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Stock History</h1>
    <span class="text-gray-400 text-sm">{{ $product->name }}</span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <div class="stat-icon bg-blue-100"><i class="fas fa-box text-blue-600"></i></div>
        <div>
            <div class="text-2xl font-extrabold {{ $product->stock_quantity <= 0 ? 'text-red-600' : ($product->isLowStock() ? 'text-orange-600' : 'text-gray-900') }}">
                {{ number_format($product->stock_quantity) }}
            </div>
            <div class="text-sm text-gray-500">Current Stock</div>
        </div>
    </div>
    <div class="stat-card lg:col-span-3">
        <div class="flex items-center gap-4">
            <img src="{{ $product->primary_image_url }}" class="w-12 h-12 object-cover rounded-xl bg-gray-100 shrink-0">
            <div>
                <div class="font-bold text-gray-900">{{ $product->name }}</div>
                @if($product->sku)<div class="text-xs text-gray-500 font-mono">SKU: {{ $product->sku }}</div>@endif
                @if($product->barcode)<div class="text-xs text-gray-500 font-mono">Barcode: {{ $product->barcode }}</div>@endif
            </div>
            <a href="{{ route('admin.inventory.adjust.form', $product) }}" class="btn-primary btn-sm ml-auto">
                <i class="fas fa-edit mr-1"></i> Adjust Stock
            </a>
        </div>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="card-header">
        <h2 class="font-semibold text-gray-800">Movement History</h2>
        <span class="text-sm text-gray-500">{{ $movements->total() }} records</span>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Type</th>
                    <th class="text-center">Change</th>
                    <th class="text-center">Before</th>
                    <th class="text-center">After</th>
                    <th>Notes</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $m)
                <tr>
                    <td class="text-xs text-gray-500 whitespace-nowrap">{{ $m->created_at->format('d M Y H:i') }}</td>
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
                    <td class="text-center font-bold {{ $m->quantity > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}
                    </td>
                    <td class="text-center text-sm text-gray-500">{{ number_format($m->before_quantity) }}</td>
                    <td class="text-center text-sm font-semibold text-gray-800">{{ number_format($m->after_quantity) }}</td>
                    <td class="text-sm text-gray-500">{{ $m->note ?? '—' }}</td>
                    <td class="text-sm text-gray-500">{{ $m->user?->name ?? 'System' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-12 text-gray-400">No movement history yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-gray-200">{{ $movements->links() }}</div>
</div>
@endsection
