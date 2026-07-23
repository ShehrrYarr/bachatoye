@extends('layouts.admin')
@section('title', 'Ledger: ' . $customer->name)

@section('content')
@php
    $isAdmin    = auth()->user()->hasRole('admin');
    $prefix     = auth()->user()->panelPrefix();
    $canManage  = auth()->user()->can('accounts.manage');
    $backRoute  = $prefix === 'salesman' ? route('salesman.customers.index') : route("{$prefix}.customers.show", $customer);
    $printRoute = route("{$prefix}.customers.ledger.print", $customer);
    $addRoute   = route("{$prefix}.customers.ledger.add", $customer);
    // '__ID__' placeholder is swapped for the real entry id in JS / per-row forms
    $updateBase = route("{$prefix}.customers.ledger.update", [$customer, '__ID__']);
@endphp
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ $backRoute }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-xl font-bold text-gray-900">Khata Ledger: {{ $customer->name }}</h1>
    </div>
    <div class="flex items-center gap-3 flex-wrap">
        <span class="text-sm {{ $customer->credit_balance < 0 ? 'text-red-600 font-bold' : 'text-green-600 font-bold' }}">
            Balance: Rs. {{ number_format($customer->credit_balance) }}
        </span>
        {{-- Date filter + print --}}
        <div x-data="{ from: '', to: '' }" class="flex items-center gap-2 flex-wrap">
            <input type="date" x-model="from"
                   class="form-input text-sm py-1 h-8 w-36"
                   title="From date">
            <span class="text-gray-400 text-xs font-medium">to</span>
            <input type="date" x-model="to"
                   class="form-input text-sm py-1 h-8 w-36"
                   title="To date">
            <button @click="
                let url = '{{ $printRoute }}';
                let p = new URLSearchParams();
                if (from) p.append('date_from', from);
                if (to) p.append('date_to', to);
                if (p.toString()) url += '?' + p.toString();
                window.open(url, '_blank');
            " class="btn-outline btn-sm whitespace-nowrap">
                <i class="fas fa-file-pdf mr-1.5 text-red-500"></i> Print as PDF
            </button>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="customerLedger()">

    {{-- Ledger table --}}
    <div class="lg:col-span-2">
        <div class="card">
            <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Reference</th>
                        <th>Method / Bank</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">Balance</th>
                        @if($canManage)<th class="text-right">Actions</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                    @php
                        $isManual = is_null($entry->order_id) && is_null($entry->return_id);
                        $editData = [
                            'id'              => $entry->id,
                            'type'            => $entry->type,
                            'payment_method'  => $entry->payment_method,
                            'bank_account_id' => $entry->bank_account_id,
                            'amount'          => (float) $entry->amount,
                            'description'     => $entry->description,
                            'reference'       => $entry->reference,
                        ];
                    @endphp
                    <tr>
                        <td class="text-xs text-gray-500 whitespace-nowrap">{{ $entry->created_at->format('d M Y H:i') }}</td>
                        <td class="text-sm">
                            {{ $entry->description }}
                            @if($entry->edited_at)
                                <span class="ml-1 text-[10px] text-amber-600 font-medium cursor-help"
                                      title="Edited{{ $entry->editedBy ? ' by '.$entry->editedBy->name : '' }} on {{ $entry->edited_at->format('d M Y H:i') }}">
                                    (edited)
                                </span>
                            @endif
                        </td>
                        <td class="text-xs text-gray-400 font-mono">{{ $entry->reference ?? '—' }}</td>
                        <td class="text-xs text-gray-500">
                            @if($entry->payment_method)
                                @if($entry->payment_method === 'cash')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                        <i class="fas fa-money-bill-wave"></i> Cash
                                    </span>
                                @elseif($entry->payment_method === 'bank_transfer')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                        <i class="fas fa-university"></i>
                                        {{ $entry->bankAccount?->label ?? 'Bank' }}
                                    </span>
                                    @if($entry->bankAccount?->account_number)
                                        <div class="text-[10px] text-gray-400 mt-0.5">{{ $entry->bankAccount->account_number }}</div>
                                    @endif
                                @endif
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="text-right text-sm font-semibold {{ $entry->type === 'debit' ? 'text-red-600' : 'text-green-600' }}">
                            Rs. {{ number_format($entry->amount) }}
                        </td>
                        <td class="text-right text-sm font-bold {{ $entry->balance_after < 0 ? 'text-red-600' : 'text-gray-800' }}">
                            Rs. {{ number_format($entry->balance_after) }}
                        </td>
                        @if($canManage)
                        <td class="text-right whitespace-nowrap">
                            @if($isManual)
                                <button type="button" title="Edit entry"
                                        class="text-gray-400 hover:text-primary-600 px-1.5 py-1 transition-colors"
                                        @click='openEdit(@json($editData))'>
                                    <i class="fas fa-pen text-xs"></i>
                                </button>
                                <form method="POST" action="{{ str_replace('__ID__', $entry->id, route("{$prefix}.customers.ledger.delete", [$customer, '__ID__'])) }}"
                                      class="inline" onsubmit="return confirm('Delete this manual entry? The running balance will be recalculated.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Delete entry"
                                            class="text-gray-400 hover:text-red-600 px-1.5 py-1 transition-colors">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                </form>
                            @else
                                <span class="text-gray-300 text-xs" title="Auto entry from a sale or return — not editable">
                                    <i class="fas fa-lock"></i>
                                </span>
                            @endif
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr><td colspan="{{ $canManage ? 7 : 6 }}" class="text-center py-12 text-gray-400">No ledger entries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            <div class="p-4 border-t border-gray-200">{{ $entries->links() }}</div>
        </div>
    </div>

    {{-- Add entry form --}}
    <div>
        <div class="card p-5 sticky top-6">
            <h2 class="font-semibold text-gray-800 mb-4">Add Manual Entry</h2>
            <form method="POST" action="{{ $addRoute }}"
                  x-data="{ entryType: 'credit', payMethod: '' }">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="form-label">Entry Type *</label>
                        <select name="type" x-model="entryType" class="form-select" required>
                            <option value="credit">Payment In (Customer paid / refund)</option>
                            <option value="debit">Payment Out (Customer owes more)</option>
                        </select>
                    </div>

                    {{-- Payment method — required for both directions: money moves either way --}}
                    <div>
                        <label class="form-label" x-text="entryType === 'credit' ? 'Paid Via' : 'Paid Out Via'"></label>
                        <div class="flex gap-2">
                            <label class="flex-1 flex items-center justify-center gap-2 p-2 border-2 rounded-xl cursor-pointer transition-all has-[:checked]:border-green-500 has-[:checked]:bg-green-50 border-gray-200">
                                <input type="radio" name="payment_method" value="cash" x-model="payMethod" class="sr-only">
                                <i class="fas fa-money-bill-wave text-green-600"></i>
                                <span class="text-sm font-medium">Cash</span>
                            </label>
                            <label class="flex-1 flex items-center justify-center gap-2 p-2 border-2 rounded-xl cursor-pointer transition-all has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 border-gray-200">
                                <input type="radio" name="payment_method" value="bank_transfer" x-model="payMethod" class="sr-only">
                                <i class="fas fa-university text-blue-600"></i>
                                <span class="text-sm font-medium">Bank</span>
                            </label>
                        </div>
                        <p x-show="payMethod === ''"
                           class="text-xs text-red-500 mt-1 font-medium">
                            <i class="fas fa-exclamation-circle mr-1"></i>Please select Cash or Bank
                        </p>
                    </div>

                    {{-- Bank account selector — shown when Bank is selected --}}
                    <div x-show="payMethod === 'bank_transfer'" x-transition>
                        <label class="form-label">Select Bank Account *</label>
                        <select name="bank_account_id" class="form-select"
                                :required="payMethod === 'bank_transfer'">
                            <option value="">— Select account —</option>
                            @foreach($bankAccounts as $bank)
                                <option value="{{ $bank->id }}">
                                    {{ $bank->label }} — {{ $bank->bank_name }}
                                    @if($bank->account_number) · {{ $bank->account_number }} @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Amount (Rs.) *</label>
                        <input type="number" name="amount" class="form-input @error('amount') border-red-500 @enderror"
                               min="0.01" step="0.01" placeholder="0.00" required>
                        @error('amount') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Description *</label>
                        <input type="text" name="description" class="form-input @error('description') border-red-500 @enderror"
                               placeholder="e.g. Payment received, Credit for return..." required>
                        @error('description') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit"
                            :disabled="payMethod === ''"
                            class="btn-primary w-full justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-plus mr-2"></i> Add Entry
                    </button>
                </div>
            </form>

            {{-- Current balance summary --}}
            <div class="mt-5 pt-5 border-t border-gray-200">
                <div class="text-xs text-gray-500 mb-2">Current Balance</div>
                <div class="text-xl font-extrabold {{ $customer->credit_balance < 0 ? 'text-red-600' : 'text-green-600' }}">
                    Rs. {{ number_format($customer->credit_balance) }}
                </div>
                <div class="text-xs text-gray-400 mt-1">
                    {{ $customer->credit_balance < 0 ? 'Customer owes this amount' : ($customer->credit_balance > 0 ? 'Credit balance' : 'Settled') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Edit manual entry modal --}}
    @if($canManage)
    <div x-show="showEdit" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         @keydown.escape.window="showEdit = false"
         @click.self="showEdit = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-900">Edit Ledger Entry</h3>
                <button type="button" @click="showEdit = false"
                        class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>
            <form method="POST" :action="updateBase.replace('__ID__', form.id)" class="p-6 space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <label class="form-label">Entry Type *</label>
                    <select name="type" x-model="form.type" class="form-select" required>
                        <option value="credit">Payment In (Customer paid / refund)</option>
                        <option value="debit">Payment Out (Customer owes more)</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Paid Via *</label>
                    <div class="flex gap-2">
                        <label class="flex-1 flex items-center justify-center gap-2 p-2 border-2 rounded-xl cursor-pointer transition-all"
                               :class="form.payment_method === 'cash' ? 'border-green-500 bg-green-50' : 'border-gray-200'">
                            <input type="radio" name="payment_method" value="cash" x-model="form.payment_method" class="sr-only">
                            <i class="fas fa-money-bill-wave text-green-600"></i>
                            <span class="text-sm font-medium">Cash</span>
                        </label>
                        <label class="flex-1 flex items-center justify-center gap-2 p-2 border-2 rounded-xl cursor-pointer transition-all"
                               :class="form.payment_method === 'bank_transfer' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                            <input type="radio" name="payment_method" value="bank_transfer" x-model="form.payment_method" class="sr-only">
                            <i class="fas fa-university text-blue-600"></i>
                            <span class="text-sm font-medium">Bank</span>
                        </label>
                    </div>
                </div>
                <div x-show="form.payment_method === 'bank_transfer'" x-transition>
                    <label class="form-label">Select Bank Account *</label>
                    <select name="bank_account_id" x-model="form.bank_account_id" class="form-select"
                            :required="form.payment_method === 'bank_transfer'">
                        <option value="">— Select account —</option>
                        @foreach($bankAccounts as $bank)
                            <option value="{{ $bank->id }}">
                                {{ $bank->label }} — {{ $bank->bank_name }}
                                @if($bank->account_number) · {{ $bank->account_number }} @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Amount (Rs.) *</label>
                    <input type="number" name="amount" x-model="form.amount" class="form-input"
                           min="0.01" step="0.01" required>
                </div>
                <div>
                    <label class="form-label">Description *</label>
                    <input type="text" name="description" x-model="form.description" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Reference</label>
                    <input type="text" name="reference" x-model="form.reference" class="form-input"
                           placeholder="Optional reference / note">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showEdit = false" class="btn-outline flex-1 justify-center">Cancel</button>
                    <button type="submit" :disabled="form.payment_method === ''"
                            class="btn-primary flex-1 justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
function customerLedger() {
    return {
        showEdit:   false,
        updateBase: @json($updateBase),
        form: { id: null, type: 'credit', payment_method: '', bank_account_id: '', amount: '', description: '', reference: '' },
        openEdit(entry) {
            this.form = {
                id:              entry.id,
                type:            entry.type,
                payment_method:  entry.payment_method || '',
                bank_account_id: entry.bank_account_id ? String(entry.bank_account_id) : '',
                amount:          entry.amount,
                description:     entry.description || '',
                reference:       entry.reference || '',
            };
            this.showEdit = true;
        },
    };
}
</script>
@endpush
@endsection
