@extends('layouts.admin')
@section('title', 'Print Barcode Labels')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.inventory.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-xl font-bold text-gray-900">Print Barcode Labels</h1>
    </div>
    <button onclick="window.print()" class="btn-primary no-print">
        <i class="fas fa-print mr-2"></i> Print Labels
    </button>
</div>

{{-- Print selection form (hidden on print) --}}
<div class="card p-5 mb-6 no-print">
    <form method="GET" action="{{ route('admin.inventory.barcode_print') }}" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="form-label text-sm">Category</label>
            <select name="category" class="form-select text-sm">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label text-sm">Labels per product</label>
            <input type="number" name="copies" value="{{ request('copies', 1) }}" min="1" max="10" class="form-input w-20 text-sm">
        </div>
        <button type="submit" class="btn-outline btn-sm">Apply</button>
    </form>
</div>

{{-- Label grid (printable area) --}}
<style>
@media print {
    .no-print { display: none !important; }
    body { background: white !important; }
    .label-grid { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 2px !important; }
    .label-item { border: 1px solid #ccc !important; page-break-inside: avoid !important; }
}
</style>

<div class="label-grid grid grid-cols-2 sm:grid-cols-4 gap-3">
    @foreach($products as $product)
    @php $copies = (int) request('copies', 1); @endphp
    @for($c = 0; $c < $copies; $c++)
    <div class="label-item border border-gray-300 rounded-lg p-3 text-center bg-white">
        <div class="text-xs font-bold text-gray-800 leading-tight mb-1 truncate">
            {{ \App\Models\Setting::get('shop_name', 'MobileHub') }}
        </div>
        <div class="text-xs text-gray-600 leading-tight mb-1 line-clamp-2" style="font-size:9px">{{ $product->name }}</div>
        @if($product->barcode)
        <div class="font-mono text-xs tracking-widest my-1" style="font-size:8px; letter-spacing: 2px;">
            ||| {{ $product->barcode }} |||
        </div>
        <div class="font-mono" style="font-size:8px;">{{ $product->barcode }}</div>
        @endif
        <div class="font-bold text-primary-700 text-sm mt-1">Rs. {{ number_format($product->price) }}</div>
        @if($product->sku)
        <div class="text-gray-400 mt-0.5" style="font-size:8px;">SKU: {{ $product->sku }}</div>
        @endif
    </div>
    @endfor
    @endforeach
</div>

@if($products->count() === 0)
<div class="text-center py-20 text-gray-400">
    <i class="fas fa-barcode text-5xl mb-4"></i>
    <p>No products with barcodes found.</p>
    <p class="text-sm mt-1">Assign barcodes to products in the inventory section.</p>
</div>
@endif
@endsection
