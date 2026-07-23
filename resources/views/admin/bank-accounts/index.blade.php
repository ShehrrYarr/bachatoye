@extends('layouts.admin')
@section('title', 'Cash & Bank Balances')

@section('content')
@php $rPrefix = auth()->user()->panelPrefix(); @endphp
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-900">{{ ($currentShop ?? null) ? $currentShop->name . ' — ' : '' }}Cash & Bank Balances</h1>
        <p class="text-sm text-gray-500 mt-0.5">Live balances computed from all recorded transactions.</p>
    </div>
</div>

@if(auth()->user()->isAdmin() && ($shops ?? collect())->count())
<div class="card p-3 mb-5 flex items-center gap-3">
    <span class="text-sm font-semibold text-gray-600"><i class="fas fa-store mr-1.5 text-gray-400"></i>Location:</span>
    <form method="GET" action="{{ route('admin.bank-accounts.index') }}">
        <select name="shop" onchange="this.form.submit()" class="form-select text-sm">
            <option value="main">Main Shop</option>
            @foreach($shops as $shopOption)
            <option value="{{ $shopOption->id }}" {{ request('shop') == $shopOption->id ? 'selected' : '' }}>{{ $shopOption->name }}</option>
            @endforeach
        </select>
    </form>
</div>
@endif

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">
    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
</div>
@endif

{{-- ── Balance Cards ──────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-8" x-data="{ cashOpen: false }">

    {{-- Cash Card --}}
    <div class="card overflow-hidden">
        <div class="px-5 pt-5 pb-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Cash on Hand</p>
                    <p class="text-3xl font-bold mt-1 {{ $cashSummary['balance'] >= 0 ? 'text-gray-900' : 'text-red-600' }}">
                        Rs. {{ number_format($cashSummary['balance']) }}
                    </p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-green-100 flex items-center justify-center shrink-0">
                    <i class="fas fa-money-bill-wave text-green-600 text-lg"></i>
                </div>
            </div>

            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                <div class="bg-green-50 rounded-lg px-3 py-2">
                    <p class="text-green-700 font-medium">Total In</p>
                    <p class="text-green-900 font-bold text-sm">Rs. {{ number_format($cashSummary['in_pos'] + $cashSummary['in_ecom'] + $cashSummary['in_khata'] + ($cashSummary['in_vendor'] ?? 0)) }}</p>
                </div>
                <div class="bg-red-50 rounded-lg px-3 py-2">
                    <p class="text-red-700 font-medium">Total Out</p>
                    <p class="text-red-900 font-bold text-sm">Rs. {{ number_format($cashSummary['out_exp'] + $cashSummary['out_pur'] + $cashSummary['out_ret'] + ($cashSummary['out_khata'] ?? 0) + ($cashSummary['out_vendor'] ?? 0)) }}</p>
                </div>
            </div>
        </div>

        {{-- Breakdown toggle --}}
        <button @click="cashOpen = !cashOpen"
                class="w-full flex items-center justify-between px-5 py-2.5 bg-gray-50 border-t border-gray-100 text-xs text-gray-500 hover:bg-gray-100 transition">
            <span>Breakdown</span>
            <i class="fas fa-chevron-down transition-transform" :class="cashOpen && 'rotate-180'"></i>
        </button>
        <div x-show="cashOpen" x-collapse class="border-t border-gray-100">
            <div class="px-5 py-3 space-y-1.5 text-xs text-gray-600">
                @if($cashSummary['opening'])
                <div class="flex justify-between">
                    <span class="text-gray-500">Opening balance</span>
                    <span class="font-medium">Rs. {{ number_format($cashSummary['opening']) }}</span>
                </div>
                @endif
                <div class="flex justify-between text-green-700">
                    <span>+ POS cash sales</span>
                    <span>Rs. {{ number_format($cashSummary['in_pos']) }}</span>
                </div>
                <div class="flex justify-between text-green-700">
                    <span>+ Ecom COD collected</span>
                    <span>Rs. {{ number_format($cashSummary['in_ecom']) }}</span>
                </div>
                @if($cashSummary['in_khata'])
                <div class="flex justify-between text-green-700">
                    <span>+ Khata repayments (cash)</span>
                    <span>Rs. {{ number_format($cashSummary['in_khata']) }}</span>
                </div>
                @endif
                @if($cashSummary['in_vendor'] ?? 0)
                <div class="flex justify-between text-green-700">
                    <span>+ Vendor received (cash)</span>
                    <span>Rs. {{ number_format($cashSummary['in_vendor']) }}</span>
                </div>
                @endif
                <div class="border-t border-gray-100 my-1"></div>
                <div class="flex justify-between text-red-600">
                    <span>− Expenses (cash)</span>
                    <span>Rs. {{ number_format($cashSummary['out_exp']) }}</span>
                </div>
                <div class="flex justify-between text-red-600">
                    <span>− Purchases (cash)</span>
                    <span>Rs. {{ number_format($cashSummary['out_pur']) }}</span>
                </div>
                @if($cashSummary['out_ret'])
                <div class="flex justify-between text-red-600">
                    <span>− Refunds (cash)</span>
                    <span>Rs. {{ number_format($cashSummary['out_ret']) }}</span>
                </div>
                @endif
                @if($cashSummary['out_khata'] ?? 0)
                <div class="flex justify-between text-red-600">
                    <span>− Khata payouts to customers (cash)</span>
                    <span>Rs. {{ number_format($cashSummary['out_khata']) }}</span>
                </div>
                @endif
                @if($cashSummary['out_vendor'] ?? 0)
                <div class="flex justify-between text-red-600">
                    <span>− Vendor payments (cash)</span>
                    <span>Rs. {{ number_format($cashSummary['out_vendor']) }}</span>
                </div>
                @endif
            </div>
            {{-- Edit opening balance --}}
            <form method="POST" action="{{ route("{$rPrefix}.bank-accounts.cash-opening") }}"
                  class="px-5 pb-4 pt-1 border-t border-gray-100 flex items-end gap-2">
                @csrf
                <div class="flex-1">
                    <label class="form-label text-xs">Opening Balance</label>
                    <input type="number" name="cash_opening_balance" step="0.01" min="0"
                           value="{{ $cashSummary['opening'] }}"
                           class="form-input text-sm py-1.5">
                </div>
                <button type="submit" class="btn-outline btn-sm mb-0.5">Set</button>
            </form>
        </div>
    </div>

    {{-- Per-Bank Cards --}}
    @foreach($banks as $bank)
    <div class="card overflow-hidden" x-data="{ open: false }">
        <div class="px-5 pt-5 pb-4">
            <div class="flex items-start justify-between">
                <div class="min-w-0 pr-2">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide truncate">{{ $bank->label }}</p>
                    <p class="text-3xl font-bold mt-1 {{ $bank->computed_balance >= 0 ? 'text-gray-900' : 'text-red-600' }}">
                        Rs. {{ number_format($bank->computed_balance) }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $bank->bank_name }} &bull; {{ $bank->account_title }}</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
                    <i class="fas fa-university text-blue-600 text-lg"></i>
                </div>
            </div>

            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                <div class="bg-green-50 rounded-lg px-3 py-2">
                    <p class="text-green-700 font-medium">Total In</p>
                    <p class="text-green-900 font-bold text-sm">Rs. {{ number_format($bank->computed_in) }}</p>
                </div>
                <div class="bg-red-50 rounded-lg px-3 py-2">
                    <p class="text-red-700 font-medium">Total Out</p>
                    <p class="text-red-900 font-bold text-sm">Rs. {{ number_format($bank->computed_out) }}</p>
                </div>
            </div>
        </div>

        <button @click="open = !open"
                class="w-full flex items-center justify-between px-5 py-2.5 bg-gray-50 border-t border-gray-100 text-xs text-gray-500 hover:bg-gray-100 transition">
            <span>Breakdown &amp; Edit</span>
            <i class="fas fa-chevron-down transition-transform" :class="open && 'rotate-180'"></i>
        </button>
        <div x-show="open" x-collapse class="border-t border-gray-100">
            <div class="px-5 py-3 space-y-1.5 text-xs text-gray-600">
                @if($bank->opening_balance)
                <div class="flex justify-between">
                    <span class="text-gray-500">Opening balance</span>
                    <span class="font-medium">Rs. {{ number_format($bank->opening_balance) }}</span>
                </div>
                @endif
                <div class="flex justify-between text-green-700">
                    <span>+ Sales received</span>
                    <span>Rs. {{ number_format($bank->computed_in) }}</span>
                </div>
                <div class="border-t border-gray-100 my-1"></div>
                <div class="flex justify-between text-red-600">
                    <span>− Purchases paid</span>
                    <span>Rs. {{ number_format($bank->computed_out - $bank->computed_out_expenses) }}</span>
                </div>
                @if($bank->computed_out_expenses > 0)
                <div class="flex justify-between text-red-600">
                    <span>− Expenses paid</span>
                    <span>Rs. {{ number_format($bank->computed_out_expenses) }}</span>
                </div>
                @endif
            </div>
            {{-- Edit bank account --}}
            <div class="px-5 pb-4 pt-1 border-t border-gray-100">
                <button onclick="openEdit({{ $bank->id }}, {{ json_encode($bank) }})"
                        class="btn-outline btn-sm w-full"><i class="fas fa-pencil-alt mr-1.5"></i>Edit Account</button>
                <form method="POST" action="{{ route("{$rPrefix}.bank-accounts.destroy", $bank) }}"
                      onsubmit="return confirm('Delete this bank account?')" class="mt-2">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-danger btn-sm w-full"><i class="fas fa-trash mr-1.5"></i>Delete</button>
                </form>
            </div>
        </div>
    </div>
    @endforeach

</div>

{{-- Ecom bank note (no specific account linked to ecom orders) --}}
@if($unattributedEcomBank > 0)
<div class="mb-6 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-3 text-sm">
    <p class="font-semibold mb-1"><i class="fas fa-info-circle mr-1.5"></i>Unattributed Bank Transactions</p>
    <p class="text-xs text-amber-700">
        Ecom bank transfers are not linked to a specific account — include them in your opening balance when reconciling:
    </p>
    <ul class="mt-2 text-xs">
        <li><span class="font-medium">Ecom bank transfers received:</span> Rs. {{ number_format($unattributedEcomBank) }}</li>
    </ul>
</div>
@endif

{{-- ── Bank Account Management ────────────────────────────────────────────── --}}
<div class="mb-4">
    <h2 class="text-base font-semibold text-gray-800">Manage Bank Accounts</h2>
    <p class="text-xs text-gray-500 mt-0.5">Add or edit accounts that appear in POS and purchase payment dropdowns.</p>
</div>

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
                    <div class="text-xs text-gray-400 mt-0.5">
                        Opening: Rs. {{ number_format($bank->opening_balance) }}
                        &bull; Balance: <span class="{{ $bank->computed_balance >= 0 ? 'text-green-700' : 'text-red-600' }} font-semibold">Rs. {{ number_format($bank->computed_balance) }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button onclick="openEdit({{ $bank->id }}, {{ json_encode($bank) }})"
                            class="btn-outline btn-sm"><i class="fas fa-pencil-alt"></i></button>
                    <form method="POST" action="{{ route("{$rPrefix}.bank-accounts.destroy", $bank) }}"
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

            <form method="POST" id="bank-form" action="{{ route("{$rPrefix}.bank-accounts.store") }}" class="space-y-3">
                @csrf
                <div id="method-field"></div>
                {{-- Admin: new banks belong to the location currently being viewed --}}
                <input type="hidden" name="shop_id" value="{{ ($currentShop ?? null)?->id }}">

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
                <div>
                    <label class="form-label">Opening Balance <span class="text-gray-400 font-normal text-xs">(Rs.)</span></label>
                    <input type="number" name="opening_balance" id="f-opening-balance" value="0" min="0" step="0.01"
                           class="form-input">
                    <p class="form-hint">Set this to the bank balance at the time you started tracking — it will be added to computed inflows/outflows.</p>
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
    document.getElementById('bank-form').action       = '/{{ $rPrefix === 'shop' ? 'shop' : 'admin' }}/bank-accounts/' + id;
    document.getElementById('method-field').innerHTML = '<input type="hidden" name="_method" value="PUT">';

    document.getElementById('f-label').value            = bank.label;
    document.getElementById('f-bank-name').value        = bank.bank_name;
    document.getElementById('f-account-title').value    = bank.account_title;
    document.getElementById('f-account-number').value   = bank.account_number ?? '';
    document.getElementById('f-iban').value             = bank.iban ?? '';
    document.getElementById('f-opening-balance').value  = bank.opening_balance ?? 0;
    document.getElementById('f-sort-order').value       = bank.sort_order;
    document.getElementById('f-is-active').checked      = bank.is_active == 1;

    document.getElementById('form-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function resetForm() {
    document.getElementById('form-title').textContent = 'Add Bank Account';
    document.getElementById('btn-label').textContent  = 'Save Account';
    document.getElementById('bank-form').action       = '{{ route("{$rPrefix}.bank-accounts.store") }}';
    document.getElementById('method-field').innerHTML = '';
    document.getElementById('bank-form').reset();
    document.getElementById('f-is-active').checked    = true;
    document.getElementById('f-opening-balance').value = 0;
}
</script>
@endpush
@endsection
