<?php

namespace App\Http\Controllers\Ecom;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerDashboardController extends Controller
{
    private function account()
    {
        return Auth::guard('customer')->user();
    }

    public function index()
    {
        $account  = $this->account();
        $customer = $account->customer;

        $recentOrders = Order::where('customer_id', $customer->id)
            ->where('source', 'ecommerce')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('account.dashboard', compact('account', 'customer', 'recentOrders'));
    }

    public function orders()
    {
        $account  = $this->account();
        $customer = $account->customer;

        $orders = Order::where('customer_id', $customer->id)
            ->where('source', 'ecommerce')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('account.orders', compact('account', 'customer', 'orders'));
    }

    public function showOrder(Order $order)
    {
        $account  = $this->account();
        $customer = $account->customer;

        // Ensure this order belongs to this customer and is an ecommerce order
        if ($order->customer_id !== $customer->id || $order->source !== 'ecommerce') {
            abort(403);
        }

        $order->load('items');

        return view('account.order-detail', compact('account', 'customer', 'order'));
    }

    public function editProfile()
    {
        $account  = $this->account();
        $customer = $account->customer;
        return view('account.profile', compact('account', 'customer'));
    }

    public function updateProfile(Request $request)
    {
        $account  = $this->account();
        $customer = $account->customer;

        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'phone'            => 'required|string|max:20',
            'address'          => 'nullable|string|max:500',
            'city'             => 'nullable|string|max:100',
            'current_password' => 'nullable|string',
            'new_password'     => 'nullable|string|min:6|confirmed',
        ]);

        // If changing password, verify current one first
        if ($request->filled('new_password')) {
            if (!$request->filled('current_password') || !Hash::check($request->current_password, $account->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.'])->withInput();
            }
            $account->update(['password' => $request->new_password]);
        }

        $customer->update([
            'name'    => $data['name'],
            'phone'   => $data['phone'],
            'address' => $data['address'],
            'city'    => $data['city'],
        ]);

        return back()->with('success', 'Profile updated successfully.');
    }
}
