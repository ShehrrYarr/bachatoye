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
            'name'            => 'required|string|max:150',
            'phone'           => 'nullable|string|max:30',
            'email'           => 'nullable|email|max:150',
            'company'         => 'nullable|string|max:150',
            'address'         => 'nullable|string|max:400',
            'notes'           => 'nullable|string|max:1000',
            'opening_balance' => 'nullable|numeric',
        ]);

        $openingBalance = (float) ($data['opening_balance'] ?? 0);
        $data['opening_balance'] = $openingBalance;
        $data['khata_enabled'] = $request->boolean('khata_enabled');

        $vendor = DB::transaction(function () use ($data, $openingBalance) {
            $vendor = Vendor::create($data);

            if (abs($openingBalance) > 0.0001) {
                VendorLedger::create([
                    'vendor_id'          => $vendor->id,
                    'type'               => $openingBalance >= 0 ? 'credit' : 'debit',
                    'is_opening_balance' => true,
                    'amount'             => abs($openingBalance),
                    'balance_after'      => $openingBalance,
                    'description'        => 'Opening Balance',
                    'created_by'         => auth()->id(),
                ]);
                $vendor->update(['balance' => $openingBalance]);
            }

            return $vendor;
        });

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
        // Opening balance always sorts last (oldest-conceptually), regardless
        // of when it was actually entered/corrected — everything else newest first.
        $entries      = $vendor->ledgerEntries()->with(['createdBy', 'bankAccount', 'editedBy'])
            ->orderBy('is_opening_balance')->latest()->paginate(30);
        $bankAccounts = BankAccount::active()->orderBy('sort_order')->orderBy('id')->get();
        return view('admin.vendors.khata', compact('vendor', 'entries', 'bankAccounts'));
    }

    public function khataPrint(Request $request, Vendor $vendor)
    {
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        // Opening balance always sorts first on a chronological statement,
        // regardless of when it was actually entered/corrected.
        $query = $vendor->ledgerEntries()->with(['createdBy', 'bankAccount'])
            ->orderByDesc('is_opening_balance')->oldest();
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
            'name'            => 'required|string|max:150',
            'phone'           => 'nullable|string|max:30',
            'email'           => 'nullable|email|max:150',
            'company'         => 'nullable|string|max:150',
            'address'         => 'nullable|string|max:400',
            'notes'           => 'nullable|string|max:1000',
            'opening_balance' => 'nullable|numeric',
        ]);

        // Opening balance is a separate historical figure from the live
        // balance (running total after every purchase/payment since).
        // Correcting it shifts the live balance by the same delta — it
        // never wipes out real transaction history the way overwriting
        // balance directly would.
        $newOpeningBalance = array_key_exists('opening_balance', $data) ? (float) $data['opening_balance'] : (float) $vendor->opening_balance;
        unset($data['opening_balance']);
        $data['khata_enabled'] = $request->boolean('khata_enabled');

        DB::transaction(function () use ($vendor, $data, $newOpeningBalance) {
            $vendor->update($data);

            $diff = round($newOpeningBalance - $vendor->opening_balance, 2);
            if (abs($diff) > 0.0001) {
                $newLiveBalance = round($vendor->balance + $diff, 2);

                // Reuse the single anchor row (never insert a new one per edit),
                // so it always stays findable and never duplicates in the ledger.
                $anchor = VendorLedger::where('vendor_id', $vendor->id)->where('is_opening_balance', true)->first();
                $anchorAttrs = [
                    'vendor_id'          => $vendor->id,
                    'type'               => $newOpeningBalance >= 0 ? 'credit' : 'debit',
                    'is_opening_balance' => true,
                    'amount'             => abs($newOpeningBalance),
                    // The anchor's own balance_after is its standalone contribution
                    // (it conceptually precedes every other transaction), not the
                    // live total — that keeps every row's running balance correct
                    // when read in true chronological order regardless of display order.
                    'balance_after'      => $newOpeningBalance,
                    'description'        => 'Opening Balance',
                ];

                if ($anchor) {
                    $anchor->update($anchorAttrs + ['edited_at' => now(), 'edited_by' => auth()->id()]);
                } else {
                    $anchor = VendorLedger::create($anchorAttrs + ['created_by' => auth()->id()]);
                }

                // The opening balance precedes every other row chronologically,
                // whenever it was actually entered — shift all of them by the delta.
                VendorLedger::where('vendor_id', $vendor->id)
                    ->where('id', '!=', $anchor->id)
                    ->update(['balance_after' => DB::raw('balance_after + (' . $diff . ')')]);

                $vendor->update(['opening_balance' => $newOpeningBalance, 'balance' => $newLiveBalance]);
            }
        });

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
