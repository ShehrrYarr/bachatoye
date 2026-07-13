@extends('layouts.admin')
@section('title', 'Low Stock Alert')

@section('content')
@php $rPrefix = auth()->user()->panelPrefix(); @endphp
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route("{$rPrefix}.inventory.index") }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Low Stock Alert</h1>
    <span class="badge bg-orange-100 text-orange-700">{{ $products->count() }} products</span>
</div>

@if($products->count())
<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th class="text-center">Current Stock</th>
                <th class="text-center">Threshold</th>
                <th class="text-right">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
            <tr>
                <td>
                    <div class="flex items-center gap-3">
                        <img src="{{ $product->primary_image_url }}" class="w-9 h-9 object-cover rounded-lg bg-gray-100 shrink-0">
                        <div>
                            <div class="font-medium text-gray-800 text-sm">{{ $product->name }}</div>
                            @if($product->barcode)<div class="text-xs text-gray-400 font-mono">{{ $product->barcode }}</div>@endif
                        </div>
                    </div>
                </td>
                <td class="text-sm text-gray-600">{{ $product->category?->name ?? '—' }}</td>
                <td class="text-center">
                    @if($product->stock_quantity <= 0)
                        <span class="badge bg-red-100 text-red-700">Out of Stock</span>
                    @else
                        <span class="badge bg-orange-100 text-orange-700">{{ $product->stock_quantity }}</span>
                    @endif
                </td>
                <td class="text-center text-sm text-gray-500">{{ $product->low_stock_threshold }}</td>
                <td class="text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route("{$rPrefix}.inventory.adjust.form", $product) }}" class="btn-primary btn-sm">
                            <i class="fas fa-plus mr-1"></i> Restock
                        </a>
                        <form method="POST" action="{{ route('admin.inventory.dismiss', $product) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn-outline btn-sm text-gray-500"
                                    title="Dismiss from low stock alerts">
                                <i class="fas fa-eye-slash mr-1"></i> Dismiss
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div class="text-center py-20 text-gray-400">
    <i class="fas fa-check-circle text-5xl text-green-400 mb-4"></i>
    <p class="font-medium text-green-600">All products are well stocked!</p>
</div>
@endif
@endsection
