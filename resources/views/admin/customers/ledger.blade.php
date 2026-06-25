@extends('layouts.admin')
@section('title', 'Ledger: ' . $customer->name)

@section('content')
@php
    $isAdmin    = auth()->user()->hasRole('admin');
    $backRoute  = $isAdmin ? route('admin.customers.show', $customer) : route('salesman.customers.index');
    $printRoute = $isAdmin ? route('admin.customers.ledger.print', $customer) : route('salesman.customers.ledger.print', $customer);
    $addRoute   = $isAdmin ? route('admin.customers.ledger.add', $customer) : route('salesman.customers.ledger.add', $customer);
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Ledger table --}}
    <div class="lg:col-span-2">
        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Reference</th>
                        <th>Method / Bank</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                    <tr>
                        <td class="text-xs text-gray-500 whitespace-nowrap">{{ $entry->created_at->format('d M Y H:i') }}</td>
                        <td class="text-sm">{{ $entry->description }}</td>
                        <td class="text-xs text-gray-400 font-mono">{{ $entry->reference ?? '—' }}</td>
                        <td class="text-xs text-gray-500">
                            @if($entry->type === 'credit' && $entry->payment_method)
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
                            <i class="fas {{ $entry->type === 'debit' ? 'fa-arrow-down' : 'fa-arrow-up' }} mr-1"></i>
                            Rs. {{ number_format($entry->amount) }}
                        </td>
                        <td class="text-right text-sm font-bold {{ $entry->balance_after < 0 ? 'text-red-600' : 'text-gray-800' }}">
                            Rs. {{ number_format($entry->balance_after) }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-12 text-gray-400">No ledger entries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
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
                            <option value="credit">Credit (Customer paid / refund)</option>
                            <option value="debit">Debit (Customer owes more)</option>
                        </select>
                    </div>

                    {{-- Payment method — only shown for credit entries --}}
                    <div x-show="entryType === 'credit'" x-transition>
                        <label class="form-label">Paid Via</label>
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
                        <p x-show="entryType === 'credit' && payMethod === ''"
                           class="text-xs text-red-500 mt-1 font-medium">
                            <i class="fas fa-exclamation-circle mr-1"></i>Please select Cash or Bank
                        </p>
                    </div>

                    {{-- Bank account selector — shown when Bank is selected --}}
                    <div x-show="entryType === 'credit' && payMethod === 'bank_transfer'" x-transition>
                        <label class="form-label">Select Bank Account *</label>
                        <select name="bank_account_id" class="form-select">
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
                            :disabled="entryType === 'credit' && payMethod === ''"
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
</div>
@endsection
