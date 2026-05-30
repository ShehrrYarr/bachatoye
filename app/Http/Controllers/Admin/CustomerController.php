<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountLedger;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $base = Customer::query();

        if ($request->filled('q')) {
            $s = $request->q;
            $base->where(fn($q) => $q->where('name', 'like', "%{$s}%")
                                     ->orWhere('phone', 'like', "%{$s}%")
                                     ->orWhere('email', 'like', "%{$s}%"));
        }

        // POS / Walk-in: has at least one POS order
        $posQuery = (clone $base)
            ->whereHas('orders', fn($q) => $q->where('source', 'pos'));

        if ($request->balance === 'outstanding') {
            $posQuery->where('credit_balance', '<', 0);
        } elseif ($request->balance === 'credit') {
            $posQuery->where('credit_balance', '>', 0);
        }

        $posCustomers = $posQuery
            ->withCount('orders')
            ->latest()
            ->paginate(20, ['*'], 'pos_page')
            ->withQueryString();

        // Online: no POS orders at all
        $onlineCustomers = (clone $base)
            ->whereDoesntHave('orders', fn($q) => $q->where('source', 'pos'))
            ->withCount('orders')
            ->latest()
            ->paginate(20, ['*'], 'online_page')
            ->withQueryString();

        return view('admin.customers.index', compact('posCustomers', 'onlineCustomers'));
    }

    public function create()
    {
        return view('admin.customers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:100',
            'phone'   => 'required|string|max:20|unique:customers',
            'email'   => 'nullable|email',
            'address' => 'nullable|string',
            'city'    => 'nullable|string|max:100',
        ]);

        $data['created_by']   = Auth::id();
        $data['source']        = 'pos';
        $data['khata_enabled'] = true;
        Customer::create($data);

        return redirect()->route('admin.customers.index')->with('success', 'Customer added.');
    }

    public function show(Customer $customer)
    {
        $customer->load(['orders.items', 'ledgerEntries.user']);
        $ledger = $customer->ledgerEntries()->latest()->paginate(20);
        $hasPosActivity = $customer->orders->where('source', 'pos')->count() > 0
                       || $customer->credit_balance != 0;
        return view('admin.customers.show', compact('customer', 'ledger', 'hasPosActivity'));
    }

    public function edit(Customer $customer)
    {
        return view('admin.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'phone'     => 'required|string|max:20|unique:customers,phone,' . $customer->id,
            'email'     => 'nullable|email',
            'address'   => 'nullable|string',
            'city'      => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $customer->update($data);
        return redirect()->route('admin.customers.index')->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('admin.customers.index')->with('success', 'Customer removed.');
    }

    public function ledger(Customer $customer)
    {
        $entries = $customer->ledgerEntries()->with('user')->latest()->paginate(30);
        return view('admin.customers.ledger', compact('customer', 'entries'));
    }

    public function ledgerPrint(Customer $customer)
    {
        $entries = $customer->ledgerEntries()->with('user')->oldest()->get();
        $storeName = \App\Models\Setting::get('shop_name', config('app.name'));
        $storePhone = \App\Models\Setting::get('shop_phone', '');
        $storeAddress = \App\Models\Setting::get('shop_address', '');
        return view('admin.customers.ledger-print', compact('customer', 'entries', 'storeName', 'storePhone', 'storeAddress'));
    }

    public function addLedgerEntry(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'type'           => 'required|in:debit,credit',
            'payment_method' => 'nullable|in:cash,bank_transfer',
            'amount'         => 'required|numeric|min:0.01',
            'description'    => 'required|string|max:255',
            'reference'      => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($data, $customer) {
            $delta  = $data['type'] === 'debit' ? -$data['amount'] : $data['amount'];
            $newBal = $customer->credit_balance + $delta;

            AccountLedger::create([
                'customer_id'    => $customer->id,
                'type'           => $data['type'],
                'payment_method' => $data['type'] === 'credit' ? ($data['payment_method'] ?? null) : null,
                'amount'         => $data['amount'],
                'balance_after'  => $newBal,
                'description'    => $data['description'],
                'reference'      => $data['reference'] ?? null,
                'user_id'        => Auth::id(),
            ]);

            $customer->update(['credit_balance' => $newBal]);
        });

        return back()->with('success', 'Ledger entry added.');
    }
}
