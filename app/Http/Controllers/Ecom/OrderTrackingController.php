<?php

namespace App\Http\Controllers\Ecom;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
    public function index()
    {
        return view('ecom.order-tracking');
    }

    public function track(Request $request)
    {
        $request->validate([
            'order_number' => 'required|string',
            'phone'        => 'required|string',
        ]);

        $order = Order::where('order_number', $request->order_number)
                      ->where('customer_phone', $request->phone)
                      ->with(['items'])
                      ->first();

        if (!$order) {
            return back()->withErrors(['order_number' => 'No order found with these details.']);
        }

        return view('ecom.order-tracking', compact('order'));
    }
}
