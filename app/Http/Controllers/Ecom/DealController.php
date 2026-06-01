<?php

namespace App\Http\Controllers\Ecom;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Product;

class DealController extends Controller
{
    public function index()
    {
        $deals = Deal::active()->with(['products.images', 'products.colors', 'categories'])->get();
        return view('ecom.deals', compact('deals'));
    }
}
