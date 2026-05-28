@extends('layouts.admin')
@section('title', 'POS Bank Accounts')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-900">POS Bank Accounts</h1>
        <p class="text-sm text-gray-500 mt-0.5">These accounts appear as a dropdown when processing bank transfers on the POS.</p>
    </div>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">
    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

    {{-- Bank list --}}
    <div class="lg:col-span-3">
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-gray-800">All Bank Accounts</h2>
                <span class="text-xs text-gray-400">{{ $banks->count() }} account(s)</span>
            </div>
            @forelse($banks as $bank)
            <div class="px-5 py-4 border-b border-gray-100 last:border-0 flex items-center gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-0.5">
                        <span class="font-semibold text-gray-800">{{ $bank->label }}</span>
                        @if(!$bank->is_active)
                            <span class="badge bg-gray-100 text-gray-500 text-xs">Inactive</span>
                        @endif
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ $bank->bank_name }} &bull; {{ $bank->account_title }}
                        @if($bank->account_number) &bull; <span class="font-mono">{{ $bank->account_number }}</span>@endif
                    </div>
                    @if($bank->iban)
                    <div class="text-xs text-gray-400 font-mono mt-0.5">IBAN: {{ $bank->iban }}</div>
                    @endif
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button onclick="openEdit({{ $bank->id }}, {{ json_encode($bank) }})"
                            class="btn-outline btn-sm"><i class="fas fa-pencil-alt"></i></button>
                    <form method="POST" action="{{ route('admin.bank-accounts.destroy', $bank) }}"
                          onsubmit="return confirm('Delete this bank account?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
            @empty
            <div class="px-5 py-10 text-center text-sm text-gray-500">
                <i class="fas fa-university text-3xl text-gray-300 mb-3 block"></i>
                No bank accounts yet. Add one using the form.
            </div>
            @endforelse
        </div>
    </div>

    {{-- Add / Edit form --}}
    <div class="lg:col-span-2">
        <div class="card p-5" id="form-card">
            <h2 class="font-semibold text-gray-800 mb-4" id="form-title">Add Bank Account</h2>

            <form method="POST" id="bank-form" action="{{ route('admin.bank-accounts.store') }}" class="space-y-3">
                @csrf
                <div id="method-field"></div>

                <div>
                    <label class="form-label">Label / Display Name <span class="text-red-500">*</span></label>
                    <input type="text" name="label" id="f-label" placeholder="e.g. HBL — Main Counter"
                           class="form-input" required>
                    <p class="form-hint">Shown to cashier in the POS dropdown</p>
                </div>
                <div>
                    <label class="form-label">Bank Name <span class="text-red-500">*</span></label>
                    <input type="text" name="bank_name" id="f-bank-name" placeholder="e.g. HBL, Meezan, UBL"
                           class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Account Title <span class="text-red-500">*</span></label>
                    <input type="text" name="account_title" id="f-account-title" placeholder="e.g. Muhammad Ali"
                           class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Account Number</label>
                    <input type="text" name="account_number" id="f-account-number" placeholder="0001-1234567890"
                           class="form-input font-mono">
                </div>
                <div>
                    <label class="form-label">IBAN <span class="text-gray-400 font-normal text-xs">(optional)</span></label>
                    <input type="text" name="iban" id="f-iban" placeholder="PK36HABB0000000123456702"
                           class="form-input font-mono">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" id="f-sort-order" value="0" min="0" class="form-input w-full">
                    </div>
                    <div class="flex items-end pb-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" id="f-is-active" value="1" checked
                                   class="w-4 h-4 text-primary-600 rounded">
                            <span class="text-sm font-medium text-gray-700">Active</span>
                        </label>
                    </div>
                </div>
                <div class="flex gap-2 pt-1">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save mr-1.5"></i> <span id="btn-label">Save Account</span>
                    </button>
                    <button type="button" onclick="resetForm()" class="btn-outline">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openEdit(id, bank) {
    document.getElementById('form-title').textContent = 'Edit Bank Account';
    document.getElementById('btn-label').textContent  = 'Update Account';
    document.getElementById('bank-form').action       = '/admin/bank-accounts/' + id;
    document.getElementById('method-field').innerHTML = '<input type="hidden" name="_method" value="PUT">';

    document.getElementById('f-label').value          = bank.label;
    document.getElementById('f-bank-name').value      = bank.bank_name;
    document.getElementById('f-account-title').value  = bank.account_title;
    document.getElementById('f-account-number').value = bank.account_number ?? '';
    document.getElementById('f-iban').value           = bank.iban ?? '';
    document.getElementById('f-sort-order').value     = bank.sort_order;
    document.getElementById('f-is-active').checked    = bank.is_active == 1;

    document.getElementById('form-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function resetForm() {
    document.getElementById('form-title').textContent = 'Add Bank Account';
    document.getElementById('btn-label').textContent  = 'Save Account';
    document.getElementById('bank-form').action       = '{{ route('admin.bank-accounts.store') }}';
    document.getElementById('method-field').innerHTML = '';
    document.getElementById('bank-form').reset();
    document.getElementById('f-is-active').checked    = true;
}
</script>
@endpush
@endsection
