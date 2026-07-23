<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Setting;
use App\Models\Vendor;
use App\Models\VendorLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::withCount('purchases')->latest();

        if ($request->filled('q')) {
            $s = $request->q;
            $query->where(fn($q) => $q->where('name', 'like', "%{$s}%")
                                      ->orWhere('phone', 'like', "%{$s}%")
                                      ->orWhere('company', 'like', "%{$s}%"));
        }

        if ($request->balance === 'outstanding') {
            $query->where('balance', '>', 0);
        }

        $vendors = $query->paginate(20)->withQueryString();
        return view('admin.vendors.index', compact('vendors'));
    }

    public function create()
    {
        return view('admin.vendors.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:150',
            'phone'   => 'nullable|string|max:30',
            'email'   => 'nullable|email|max:150',
            'company' => 'nullable|string|max:150',
            'address' => 'nullable|string|max:400',
            'notes'   => 'nullable|string|max:1000',
        ]);

        $data['khata_enabled'] = $request->boolean('khata_enabled');

        $vendor = Vendor::create($data);
        $rPrefix = auth()->user()->panelPrefix();
        return redirect()->route("{$rPrefix}.vendors.show", $vendor)->with('success', 'Vendor created.');
    }

    public function show(Vendor $vendor)
    {
        $vendor->load('purchases');
        $bankAccounts = BankAccount::active()->orderBy('sort_order')->orderBy('id')->get();
        return view('admin.vendors.show', compact('vendor', 'bankAccounts'));
    }

    public function khata(Vendor $vendor)
    {
        $entries      = $vendor->ledgerEntries()->with(['createdBy', 'bankAccount', 'editedBy'])->latest()->paginate(30);
        $bankAccounts = BankAccount::active()->orderBy('sort_order')->orderBy('id')->get();
        return view('admin.vendors.khata', compact('vendor', 'entries', 'bankAccounts'));
    }

    public function khataPrint(Request $request, Vendor $vendor)
    {
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        $query = $vendor->ledgerEntries()->with(['createdBy', 'bankAccount'])->oldest();
        if ($dateFrom) $query->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('created_at', '<=', $dateTo);

        $entries      = $query->get();
        $storeName    = Setting::get('shop_name', config('app.name'));
        $storePhone   = Setting::get('shop_phone', '');
        $storeAddress = Setting::get('shop_address', '');
        return view('admin.vendors.khata-print', compact(
            'vendor', 'entries', 'storeName', 'storePhone', 'storeAddress', 'dateFrom', 'dateTo'
        ));
    }

    public function edit(Vendor $vendor)
    {
        return view('admin.vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:150',
            'phone'   => 'nullable|string|max:30',
            'email'   => 'nullable|email|max:150',
            'company' => 'nullable|string|max:150',
            'address' => 'nullable|string|max:400',
            'notes'   => 'nullable|string|max:1000',
        ]);

        $data['khata_enabled'] = $request->boolean('khata_enabled');
        $vendor->update($data);
        $rPrefix = auth()->user()->panelPrefix();
        return redirect()->route("{$rPrefix}.vendors.show", $vendor)->with('success', 'Vendor updated.');
    }

    public function destroy(Vendor $vendor)
    {
        $vendor->delete();
        return redirect()->route('admin.vendors.index')->with('success', 'Vendor deleted.');
    }

    public function addLedgerEntry(Request $request, Vendor $vendor)
    {
        $data = $request->validate([
            'type'            => 'required|in:credit,debit',
            'amount'          => 'required|numeric|min:0.01',
            'description'     => 'nullable|string|max:255',
            'payment_method'  => 'required_if:type,debit|nullable|in:cash,bank_transfer',
            'bank_account_id' => 'required_if:payment_method,bank_transfer|nullable|exists:bank_accounts,id',
        ]);

        $isDebit   = $data['type'] === 'debit';
        $payMethod = $data['payment_method'] ?? null;
        $bankAccId = ($payMethod === 'bank_transfer') ? ($data['bank_account_id'] ?? null) : null;

        DB::transaction(function () use ($data, $vendor, $payMethod, $bankAccId, $isDebit) {
            $newBalance = $vendor->balance + ($isDebit ? -$data['amount'] : $data['amount']);

            VendorLedger::create([
                'vendor_id'       => $vendor->id,
                'type'            => $data['type'],
                'amount'          => $data['amount'],
                'balance_after'   => $newBalance,
                'description'     => $data['description'] ?? 'Manual entry',
                'payment_method'  => $payMethod,
                'bank_account_id' => $bankAccId,
                'created_by'      => auth()->id(),
            ]);

            $vendor->update(['balance' => $newBalance]);
        });

        return back()->with('success', 'Ledger entry added.');
    }

    /**
     * Edit a MANUAL vendor ledger entry. Auto rows (linked to a purchase or
     * order) are refused. Shifts this row's balance and every later row (by id)
     * plus the vendor's current balance by the delta difference.
     */
    public function updateLedgerEntry(Request $request, Vendor $vendor, VendorLedger $entry)
    {
        abort_unless((int) $entry->vendor_id === (int) $vendor->id, 404);
        abort_unless($entry->isManual(), 403, 'Only manual entries can be edited.');

        $data = $request->validate([
            'type'            => 'required|in:credit,debit',
            'amount'          => 'required|numeric|min:0.01',
            'description'     => 'nullable|string|max:255',
            'payment_method'  => 'required_if:type,debit|nullable|in:cash,bank_transfer',
            'bank_account_id' => 'required_if:payment_method,bank_transfer|nullable|exists:bank_accounts,id',
        ]);

        $isDebit   = $data['type'] === 'debit';
        $payMethod = $data['payment_method'] ?? null;
        $bankAccId = ($payMethod === 'bank_transfer') ? ($data['bank_account_id'] ?? null) : null;

        DB::transaction(function () use ($data, $vendor, $entry, $isDebit, $payMethod, $bankAccId) {
            $oldDelta = $entry->type === 'debit' ? -$entry->amount : $entry->amount;
            $newDelta = $isDebit ? -$data['amount'] : $data['amount'];
            $diff     = round($newDelta - $oldDelta, 2);

            $entry->update([
                'type'            => $data['type'],
                'amount'          => $data['amount'],
                'balance_after'   => round($entry->balance_after + $diff, 2),
                'description'     => $data['description'] ?? 'Manual entry',
                'payment_method'  => $payMethod,
                'bank_account_id' => $bankAccId,
                'edited_at'       => now(),
                'edited_by'       => auth()->id(),
            ]);

            if (abs($diff) > 0.0001) {
                VendorLedger::where('vendor_id', $vendor->id)
                    ->where('id', '>', $entry->id)
                    ->update(['balance_after' => DB::raw('balance_after + (' . $diff . ')')]);

                $vendor->update(['balance' => round($vendor->balance + $diff, 2)]);
            }
        });

        return back()->with('success', 'Ledger entry updated.');
    }

    /** Delete a MANUAL vendor ledger entry and roll it out of the balance chain. */
    public function deleteLedgerEntry(Vendor $vendor, VendorLedger $entry)
    {
        abort_unless((int) $entry->vendor_id === (int) $vendor->id, 404);
        abort_unless($entry->isManual(), 403, 'Only manual entries can be deleted.');

        DB::transaction(function () use ($vendor, $entry) {
            $oldDelta = $entry->type === 'debit' ? -$entry->amount : $entry->amount;
            $diff     = round(-$oldDelta, 2);

            VendorLedger::where('vendor_id', $vendor->id)
                ->where('id', '>', $entry->id)
                ->update(['balance_after' => DB::raw('balance_after + (' . $diff . ')')]);

            $vendor->update(['balance' => round($vendor->balance + $diff, 2)]);
            $entry->delete();
        });

        return back()->with('success', 'Ledger entry deleted.');
    }
}
