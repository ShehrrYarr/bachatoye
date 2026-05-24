@extends('layouts.ecom')
@section('title', 'Deals & Offers')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">

    <nav class="text-sm text-gray-500 mb-6 flex items-center gap-2">
        <a href="{{ route('home') }}" class="hover:text-primary-600">Home</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-800 font-medium">Deals & Offers</span>
    </nav>

    {{-- Header --}}
    <div class="text-center mb-10">
        <div class="inline-flex items-center gap-2 bg-red-100 text-red-700 px-4 py-1.5 rounded-full text-sm font-semibold mb-3">
            <i class="fas fa-fire"></i> Limited Time Offers
        </div>
        <h1 class="text-3xl font-extrabold text-gray-900">Deals & Offers</h1>
        <p class="text-gray-500 mt-2">Grab the best deals before they're gone</p>
    </div>

    @if($deals->count())
    <div class="space-y-12">
        @foreach($deals as $deal)
        <section>
            {{-- Deal header --}}
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 bg-gradient-to-r from-red-500 to-pink-600 text-white px-4 py-2 rounded-xl">
                        <i class="fas
                            @if($deal->type === 'percentage') fa-percent
                            @elseif($deal->type === 'flat') fa-tag
                            @else fa-gift @endif"></i>
                        <span class="font-black text-lg">{{ $deal->badge_label }}</span>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900 text-lg">{{ $deal->name }}</h2>
                        @if($deal->ends_at)
                        <p class="text-xs text-gray-500 flex items-center gap-1">
                            <i class="fas fa-clock text-orange-400"></i>
                            Ends {{ $deal->ends_at->format('d M Y') }}
                        </p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Deal products --}}
            @php
                $products = $deal->products->count() ? $deal->products : \App\Models\Product::whereHas('category', function($q) use ($deal) {
                    $q->whereIn('id', $deal->categories->pluck('id'));
                })->active()->with(['images', 'category', 'brand'])->take(8)->get();
            @endphp

            @if($products->count())
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                @foreach($products as $product)
                    @include('ecom.partials.product-card', ['product' => $product])
                @endforeach
            </div>
            @else
            <div class="bg-gray-50 rounded-xl p-8 text-center text-gray-400">
                <i class="fas fa-box-open text-3xl mb-2"></i>
                <p class="text-sm">No products in this deal</p>
            </div>
            @endif

            @if(!$loop->last)
            <div class="border-b border-gray-200 mt-10"></div>
            @endif
        </section>
        @endforeach
    </div>

    @else
    <div class="text-center py-24">
        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-tag text-4xl text-gray-300"></i>
        </div>
        <h2 class="text-xl font-bold text-gray-700 mb-2">No Active Deals</h2>
        <p class="text-gray-500 mb-6">Check back soon for exciting offers!</p>
        <a href="{{ route('products.index') }}" class="btn-primary">Browse All Products</a>
    </div>
    @endif
</div>
@endsection
