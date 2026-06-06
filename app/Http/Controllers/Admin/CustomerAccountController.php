<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerAccount;
use Illuminate\Http\Request;

class CustomerAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerAccount::with('customer')
            ->orderByDesc('created_at');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qb) use ($q) {
                $qb->where('email', 'like', "%{$q}%")
                   ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active' ? 1 : 0);
        }

        $accounts = $query->paginate(20)->withQueryString();

        return view('admin.customer-accounts.index', compact('accounts'));
    }

    public function toggle(CustomerAccount $account)
    {
        $account->update(['is_active' => !$account->is_active]);
        $status = $account->is_active ? 'enabled' : 'disabled';
        return back()->with('success', "Account {$status} successfully.");
    }
}
