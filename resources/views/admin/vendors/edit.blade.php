@extends('layouts.admin')
@section('title', 'Edit Vendor')

@section('content')
@php $rPrefix = auth()->user()->panelPrefix(); @endphp
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route("{$rPrefix}.vendors.show", $vendor) }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Edit Vendor</h1>
    <span class="text-gray-400 text-sm">{{ $vendor->name }}</span>
</div>

<div class="max-w-2xl">
    <form method="POST" action="{{ route("{$rPrefix}.vendors.update", $vendor) }}">
        @csrf @method('PUT')
        <div class="card">
            <div class="card-header"><h2 class="font-semibold text-gray-800">Vendor Information</h2></div>
            <div class="card-body space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" value="{{ old('name', $vendor->name) }}" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Company / Brand</label>
                        <input type="text" name="company" value="{{ old('company', $vendor->company) }}" class="form-input">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $vendor->phone) }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ old('email', $vendor->email) }}" class="form-input">
                    </div>
                </div>
                <div>
                    <label class="form-label">Address</label>
                    <textarea name="address" rows="2" class="form-textarea">{{ old('address', $vendor->address) }}</textarea>
                </div>
                <div>
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="2" class="form-textarea">{{ old('notes', $vendor->notes) }}</textarea>
                </div>
                <div>
                    <label class="form-label">Opening Balance (Rs.)</label>
                    <input type="number" name="opening_balance" value="{{ old('opening_balance', $vendor->balance) }}" step="0.01" class="form-input">
                    <p class="form-hint">Positive = you owe vendor &middot; Negative = vendor owes you. Changing this records a balance adjustment.</p>
                </div>
                <div class="flex items-start gap-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                    <input type="hidden" name="khata_enabled" value="0">
                    <input type="checkbox" name="khata_enabled" id="khata_enabled" value="1"
                           {{ old('khata_enabled', $vendor->khata_enabled) ? 'checked' : '' }}
                           class="mt-0.5 w-4 h-4 text-amber-600 rounded border-amber-300 focus:ring-amber-500">
                    <div>
                        <label for="khata_enabled" class="text-sm font-semibold text-gray-800 cursor-pointer">Enable Khata / Ledger</label>
                        <p class="text-xs text-gray-500 mt-0.5">Allow credit transactions and ledger entries for this vendor.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex gap-3 mt-5">
            <button type="submit" class="btn-primary btn-lg"><i class="fas fa-save mr-2"></i> Update</button>
            <a href="{{ route("{$rPrefix}.vendors.show", $vendor) }}" class="btn-outline btn-lg">Cancel</a>
        </div>
    </form>
</div>
@endsection
