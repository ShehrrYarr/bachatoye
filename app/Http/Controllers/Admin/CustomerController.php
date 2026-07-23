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
    /** Sub shop logins may only touch their own shop's customers. */
    private function guardShop(Customer $customer): void
    {
        $user = Auth::user();
        abort_if($user->isSubshop() && $customer->shop_id !== $user->shopId(), 404);
    }

    public function index(Request $request)
    {
        $shopId = Auth::user()->shopId();
        $base   = Customer::query()->with('shop')
            // Sub shop sees only its own customers; admin/salesman see all shops
            // and can narrow with the ?shop= filter (''=all, 'main', or a shop id)
            ->when(Auth::user()->isSubshop(),
                fn($q) => $q->forShop($shopId),
                fn($q) => $q->forShopFilter($request->input('shop', '')));

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

        $shops = Auth::user()->isSubshop() ? collect() : \App\Models\Shop::orderBy('name')->get();

        return view('admin.customers.index', compact('posCustomers', 'onlineCustomers', 'shops'));
    }

    public function create()
    {
        return view('admin.customers.create');
    }

    public function store(Request $request)
    {
        $shopId = Auth::user()->shopId();

        $data = $request->validate([
            'name'    => 'required|string|max:100',
            'phone'   => [
                'required', 'string', 'max:20',
                \Illuminate\Validation\Rule::unique('customers', 'phone')->where(
                    fn($q) => $shopId ? $q->where('shop_id', $shopId) : $q->whereNull('shop_id')
                ),
            ],
            'email'   => 'nullable|email',
            'address' => 'nullable|string',
            'city'    => 'nullable|string|max:100',
            'photo'   => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('customers', 'public');
        }

        $data['shop_id']       = $shopId;
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

        return redirect()->route(Auth::user()->panelPrefix() . '.customers.index')->with('success', 'Customer added.');
    }

    public function show(Customer $customer)
    {
        $this->guardShop($customer);
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
        $this->guardShop($customer);
        return view('admin.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $this->guardShop($customer);
        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'phone'        => [
                'required', 'string', 'max:20',
                \Illuminate\Validation\Rule::unique('customers', 'phone')->ignore($customer->id)->where(
                    fn($q) => $customer->shop_id ? $q->where('shop_id', $customer->shop_id) : $q->whereNull('shop_id')
                ),
            ],
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
        return redirect()->route(Auth::user()->panelPrefix() . '.customers.index')->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        $this->guardShop($customer);
        if ($customer->photo) Storage::disk('public')->delete($customer->photo);
        $customer->delete();
        return redirect()->route(Auth::user()->panelPrefix() . '.customers.index')->with('success', 'Customer removed.');
    }

    public function ledger(Customer $customer)
    {
        $this->guardShop($customer);
        $entries      = $customer->ledgerEntries()->with(['user', 'bankAccount', 'editedBy'])->latest()->paginate(30);
        $bankAccounts = BankAccount::active()->forShop($customer->shop_id)->orderBy('sort_order')->get();
        return view('admin.customers.ledger', compact('customer', 'entries', 'bankAccounts'));
    }

    public function ledgerPrint(Request $request, Customer $customer)
    {
        $this->guardShop($customer);
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        $query = $customer->ledgerEntries()->with(['user', 'bankAccount'])->oldest();
        if ($dateFrom) $query->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('created_at', '<=', $dateTo);

        $entries      = $query->get();
        $shop         = $customer->shop;
        $storeName    = $shop?->name ?? \App\Models\Setting::get('shop_name', config('app.name'));
        $storePhone   = $shop?->phone ?? \App\Models\Setting::get('shop_phone', '');
        $storeAddress = $shop?->address ?? \App\Models\Setting::get('shop_address', '');
        return view('admin.customers.ledger-print', compact(
            'customer', 'entries', 'storeName', 'storePhone', 'storeAddress', 'dateFrom', 'dateTo'
        ));
    }

    public function addLedgerEntry(Request $request, Customer $customer)
    {
        $this->guardShop($customer);
        $data = $request->validate([
            'type'            => 'required|in:debit,credit',
            // Both directions move real money — cash or a specific bank
            'payment_method'  => 'required|in:cash,bank_transfer',
            'bank_account_id' => [
                'required_if:payment_method,bank_transfer',
                'nullable',
                \Illuminate\Validation\Rule::exists('bank_accounts', 'id')->where(
                    fn($q) => $customer->shop_id ? $q->where('shop_id', $customer->shop_id) : $q->whereNull('shop_id')
                ),
            ],
            'amount'          => 'required|numeric|min:0.01',
            'description'     => 'required|string|max:255',
            'reference'       => 'nullable|string|max:100',
        ], [
            'bank_account_id.required_if' => 'Select a bank account for the bank payment.',
        ]);

        $paymentMethod  = $data['payment_method'];
        $bankAccountId  = $paymentMethod === 'bank_transfer' ? ($data['bank_account_id'] ?? null) : null;

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

    /**
     * Edit a MANUAL ledger entry. Auto rows (linked to a sale or return) are
     * refused. Editing shifts this row's running balance and every later row
     * (by id) plus the customer's current balance by the delta difference — no
     * full replay needed since the balance chain is cumulative in id order.
     */
    public function updateLedgerEntry(Request $request, Customer $customer, AccountLedger $entry)
    {
        $this->guardShop($customer);
        abort_unless($entry->customer_id === $customer->id, 404);
        abort_unless($entry->isManual(), 403, 'Only manual entries can be edited.');

        $data = $request->validate([
            'type'            => 'required|in:debit,credit',
            'payment_method'  => 'required|in:cash,bank_transfer',
            'bank_account_id' => [
                'required_if:payment_method,bank_transfer',
                'nullable',
                \Illuminate\Validation\Rule::exists('bank_accounts', 'id')->where(
                    fn($q) => $customer->shop_id ? $q->where('shop_id', $customer->shop_id) : $q->whereNull('shop_id')
                ),
            ],
            'amount'          => 'required|numeric|min:0.01',
            'description'     => 'required|string|max:255',
            'reference'       => 'nullable|string|max:100',
        ], [
            'bank_account_id.required_if' => 'Select a bank account for the bank payment.',
        ]);

        $paymentMethod = $data['payment_method'];
        $bankAccountId = $paymentMethod === 'bank_transfer' ? ($data['bank_account_id'] ?? null) : null;

        DB::transaction(function () use ($data, $customer, $entry, $paymentMethod, $bankAccountId) {
            $oldDelta = $entry->type === 'debit' ? -$entry->amount : $entry->amount;
            $newDelta = $data['type'] === 'debit' ? -$data['amount'] : $data['amount'];
            $diff     = round($newDelta - $oldDelta, 2);

            $entry->update([
                'type'            => $data['type'],
                'payment_method'  => $paymentMethod,
                'bank_account_id' => $bankAccountId,
                'amount'          => $data['amount'],
                'balance_after'   => round($entry->balance_after + $diff, 2),
                'description'     => $data['description'],
                'reference'       => $data['reference'] ?? null,
                'edited_at'       => now(),
                'edited_by'       => Auth::id(),
            ]);

            if (abs($diff) > 0.0001) {
                AccountLedger::where('customer_id', $customer->id)
                    ->where('id', '>', $entry->id)
                    ->update(['balance_after' => DB::raw('balance_after + (' . $diff . ')')]);

                $customer->update(['credit_balance' => round($customer->credit_balance + $diff, 2)]);
            }
        });

        return back()->with('success', 'Ledger entry updated.');
    }

    /** Delete a MANUAL ledger entry and roll its effect out of the balance chain. */
    public function deleteLedgerEntry(Customer $customer, AccountLedger $entry)
    {
        $this->guardShop($customer);
        abort_unless($entry->customer_id === $customer->id, 404);
        abort_unless($entry->isManual(), 403, 'Only manual entries can be deleted.');

        DB::transaction(function () use ($customer, $entry) {
            $oldDelta = $entry->type === 'debit' ? -$entry->amount : $entry->amount;
            $diff     = round(-$oldDelta, 2);

            AccountLedger::where('customer_id', $customer->id)
                ->where('id', '>', $entry->id)
                ->update(['balance_after' => DB::raw('balance_after + (' . $diff . ')')]);

            $customer->update(['credit_balance' => round($customer->credit_balance + $diff, 2)]);
            $entry->delete();
        });

        return back()->with('success', 'Ledger entry deleted.');
    }

    public function dismissPromise(AccountLedger $entry)
    {
        $user = Auth::user();
        abort_if($user->isSubshop() && $entry->customer?->shop_id !== $user->shopId(), 404);

        $entry->update(['promise_date' => null]);
        return response()->json(['success' => true]);
    }
}
