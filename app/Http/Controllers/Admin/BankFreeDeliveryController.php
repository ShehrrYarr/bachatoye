<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class BankFreeDeliveryController extends Controller
{
    public function index(Request $request)
    {
        $search   = $request->get('search');
        $filter   = $request->get('filter', 'all'); // all | enabled | disabled

        $query = Product::with('category')->orderBy('name');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($filter === 'enabled') {
            $query->where('bank_free_delivery', true);
        } elseif ($filter === 'disabled') {
            $query->where('bank_free_delivery', false);
        }

        $products      = $query->paginate(30)->withQueryString();
        $enabledCount  = Product::where('bank_free_delivery', true)->count();

        return view('admin.bank-free-delivery.index', compact('products', 'search', 'filter', 'enabledCount'));
    }

    public function toggle(Product $product)
    {
        $product->update(['bank_free_delivery' => !$product->bank_free_delivery]);

        return response()->json([
            'enabled' => $product->bank_free_delivery,
        ]);
    }
}
