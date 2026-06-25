<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountLedger;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\CustomerAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        // POS / Walk-in: source = pos (set when created from POS or admin panel)
        $posQuery = (clone $base)->where('source', 'pos');

        if ($request->balance === 'outstanding') {
            $posQuery->where('credit_balance', '<', 0);
        } elseif ($request->balance === 'credit') {
            $posQuery->where('credit_balance', '>', 0);
        }

        $posCustomers = $posQuery
            ->withCount('orders')
            ->with('account')
            ->latest()
            ->paginate(20, ['*'], 'pos_page')
            ->withQueryString();

        // Online: source = online (set when customer checks out on ecommerce)
        $onlineCustomers = (clone $base)
            ->where('source', 'online')
            ->withCount('orders')
            ->with('account')
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
            'photo'   => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('customers', 'public');
        }

        $data['created_by']   = Auth::id();
        $data['source']        = 'pos';
        $data['khata_enabled'] = $request->boolean('khata_enabled');
        $customer = Customer::create($data);

        // Auto-create portal login account
        $plainPassword = Str::random(10);
        CustomerAccount::create([
            'customer_id'    => $customer->id,
            'email'          => $customer->email ?: null,
            'phone'          => $customer->phone,
            'password'       => Hash::make($plainPassword),
            'plain_password' => $plainPassword,
            'is_active'      => true,
        ]);

        return redirect()->route(Auth::user()->hasRole('admin') ? 'admin.customers.index' : 'salesman.customers.index')->with('success', 'Customer added.');
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'orders.items',
            'returns.items',
            'returns.order',
            'ledgerEntries.user',
        ]);
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
            'name'         => 'required|string|max:100',
            'phone'        => 'required|string|max:20|unique:customers,phone,' . $customer->id,
            'email'        => 'nullable|email',
            'address'      => 'nullable|string',
            'city'         => 'nullable|string|max:100',
            'is_active'    => 'boolean',
            'photo'        => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'remove_photo' => 'nullable|boolean',
        ]);

        if ($request->hasFile('photo')) {
            if ($customer->photo) Storage::disk('public')->delete($customer->photo);
            $data['photo'] = $request->file('photo')->store('customers', 'public');
        } elseif ($request->boolean('remove_photo')) {
            if ($customer->photo) Storage::disk('public')->delete($customer->photo);
            $data['photo'] = null;
        }

        $data['is_active']    = $request->boolean('is_active');
        $data['khata_enabled'] = $request->boolean('khata_enabled');
        $customer->update($data);
        return redirect()->route(Auth::user()->hasRole('admin') ? 'admin.customers.index' : 'salesman.customers.index')->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->photo) Storage::disk('public')->delete($customer->photo);
        $customer->delete();
        return redirect()->route(Auth::user()->hasRole('admin') ? 'admin.customers.index' : 'salesman.customers.index')->with('success', 'Customer removed.');
    }

    public function ledger(Customer $customer)
    {
        $entries      = $customer->ledgerEntries()->with(['user', 'bankAccount'])->latest()->paginate(30);
        $bankAccounts = BankAccount::active()->orderBy('sort_order')->get();
        return view('admin.customers.ledger', compact('customer', 'entries', 'bankAccounts'));
    }

    public function ledgerPrint(Request $request, Customer $customer)
    {
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        $query = $customer->ledgerEntries()->with(['user', 'bankAccount'])->oldest();
        if ($dateFrom) $query->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('created_at', '<=', $dateTo);

        $entries      = $query->get();
        $storeName    = \App\Models\Setting::get('shop_name', config('app.name'));
        $storePhone   = \App\Models\Setting::get('shop_phone', '');
        $storeAddress = \App\Models\Setting::get('shop_address', '');
        return view('admin.customers.ledger-print', compact(
            'customer', 'entries', 'storeName', 'storePhone', 'storeAddress', 'dateFrom', 'dateTo'
        ));
    }

    public function addLedgerEntry(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'type'            => 'required|in:debit,credit',
            'payment_method'  => 'required_if:type,credit|in:cash,bank_transfer',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'amount'          => 'required|numeric|min:0.01',
            'description'     => 'required|string|max:255',
            'reference'       => 'nullable|string|max:100',
        ]);

        $isCredit       = $data['type'] === 'credit';
        $paymentMethod  = $isCredit ? ($data['payment_method'] ?? null) : null;
        $bankAccountId  = ($isCredit && $paymentMethod === 'bank_transfer')
                            ? ($data['bank_account_id'] ?? null)
                            : null;

        DB::transaction(function () use ($data, $customer, $paymentMethod, $bankAccountId) {
            $delta  = $data['type'] === 'debit' ? -$data['amount'] : $data['amount'];
            $newBal = $customer->credit_balance + $delta;

            AccountLedger::create([
                'customer_id'     => $customer->id,
                'type'            => $data['type'],
                'payment_method'  => $paymentMethod,
                'bank_account_id' => $bankAccountId,
                'amount'          => $data['amount'],
                'balance_after'   => $newBal,
                'description'     => $data['description'],
                'reference'       => $data['reference'] ?? null,
                'user_id'         => Auth::id(),
            ]);

            $customer->update(['credit_balance' => $newBal]);
        });

        return back()->with('success', 'Ledger entry added.');
    }

    public function dismissPromise(AccountLedger $entry)
    {
        $entry->update(['promise_date' => null]);
        return response()->json(['success' => true]);
    }
}
