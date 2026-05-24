<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorLedger;
use Illuminate\Http\Request;

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

        $vendor = Vendor::create($data);
        return redirect()->route('admin.vendors.show', $vendor)->with('success', 'Vendor created.');
    }

    public function show(Vendor $vendor)
    {
        $vendor->load('purchases');
        $ledger = $vendor->ledgerEntries()->with('createdBy')->latest()->paginate(25);
        return view('admin.vendors.show', compact('vendor', 'ledger'));
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

        $vendor->update($data);
        return redirect()->route('admin.vendors.show', $vendor)->with('success', 'Vendor updated.');
    }

    public function destroy(Vendor $vendor)
    {
        $vendor->delete();
        return redirect()->route('admin.vendors.index')->with('success', 'Vendor deleted.');
    }

    public function addLedgerEntry(Request $request, Vendor $vendor)
    {
        $data = $request->validate([
            'type'        => 'required|in:credit,debit',
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $newBalance = $vendor->balance + ($data['type'] === 'credit' ? $data['amount'] : -$data['amount']);

        VendorLedger::create([
            'vendor_id'    => $vendor->id,
            'type'         => $data['type'],
            'amount'       => $data['amount'],
            'balance_after' => $newBalance,
            'description'  => $data['description'] ?? 'Manual entry',
            'created_by'   => auth()->id(),
        ]);

        $vendor->update(['balance' => $newBalance]);

        return back()->with('success', 'Ledger entry added.');
    }
}
