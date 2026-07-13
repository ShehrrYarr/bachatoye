@extends('layouts.admin')
@section('title', 'Khata: ' . $vendor->name)

@section('content')
@php $rPrefix = auth()->user()->panelPrefix(); @endphp
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route("{$rPrefix}.vendors.show", $vendor) }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-xl font-bold text-gray-900">Vendor Khata: {{ $vendor->name }}</h1>
    </div>
    <div class="flex items-center gap-3 flex-wrap">
        <span class="text-sm {{ $vendor->balance > 0 ? 'text-red-600 font-bold' : ($vendor->balance < 0 ? 'text-green-600 font-bold' : 'text-gray-500') }}">
            Balance: Rs. {{ number_format($vendor->balance) }}
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
                let url = '{{ route("{$rPrefix}.vendors.khata.print", $vendor) }}';
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
        <div class="card">
            <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Reference</th>
                        <th>Method / Bank</th>
                        <th class="text-right">Credit (Owed)</th>
                        <th class="text-right">Debit (Paid)</th>
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
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        {{-- Credit = we owe MORE (red) --}}
                        <td class="text-right text-sm {{ $entry->type === 'credit' ? 'text-red-600 font-semibold' : 'text-gray-300' }}">
                            {{ $entry->type === 'credit' ? 'Rs. '.number_format($entry->amount) : '—' }}
                        </td>
                        {{-- Debit = we PAID (green) --}}
                        <td class="text-right text-sm {{ $entry->type === 'debit' ? 'text-green-600 font-semibold' : 'text-gray-300' }}">
                            {{ $entry->type === 'debit' ? 'Rs. '.number_format($entry->amount) : '—' }}
                        </td>
                        <td class="text-right text-sm font-bold {{ $entry->balance_after > 0 ? 'text-red-600' : ($entry->balance_after < 0 ? 'text-green-600' : 'text-gray-400') }}">
                            Rs. {{ number_format($entry->balance_after) }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-12 text-gray-400">No ledger entries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            <div class="p-4 border-t border-gray-200">{{ $entries->links() }}</div>
        </div>
    </div>

    {{-- Add entry form --}}
    @can('vendors.manage')
    <div>
        <div class="card p-5 sticky top-6">
            <h2 class="font-semibold text-gray-800 mb-4">Add Manual Entry</h2>
            <form method="POST" action="{{ route("{$rPrefix}.vendors.ledger.add", $vendor) }}"
                  x-data="{ entryType: 'debit', payMethod: '' }">
                @csrf
                <div class="space-y-4">

                    <div>
                        <label class="form-label">Entry Type *</label>
                        <select name="type" x-model="entryType" class="form-select" required>
                            <option value="debit">Payment Made (We paid vendor)</option>
                            <option value="credit">Debt Added (Vendor supplied on credit)</option>
                        </select>
                    </div>

                    {{-- Payment method — only for debit (we paid) --}}
                    <div x-show="entryType === 'debit'" x-transition>
                        <label class="form-label">Paid Via *</label>
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
                        <p x-show="entryType === 'debit' && payMethod === ''"
                           class="text-xs text-red-500 mt-1 font-medium">
                            <i class="fas fa-exclamation-circle mr-1"></i>Select Cash or Bank
                        </p>
                    </div>

                    {{-- Bank account selector --}}
                    <div x-show="entryType === 'debit' && payMethod === 'bank_transfer'" x-transition style="display:none;">
                        <label class="form-label">Bank Account *</label>
                        <select name="bank_account_id" class="form-select"
                                :required="entryType === 'debit' && payMethod === 'bank_transfer'">
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
                        <label class="form-label">Description</label>
                        <input type="text" name="description" class="form-input"
                               placeholder="e.g. Cash payment to vendor, Credit purchase...">
                    </div>

                    <button type="submit"
                            :disabled="entryType === 'debit' && payMethod === ''"
                            class="btn-primary w-full justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-plus mr-2"></i> Add Entry
                    </button>
                </div>
            </form>

            {{-- Current balance summary --}}
            <div class="mt-5 pt-5 border-t border-gray-200">
                <div class="text-xs text-gray-500 mb-2">Current Balance</div>
                <div class="text-xl font-extrabold {{ $vendor->balance > 0 ? 'text-red-600' : ($vendor->balance < 0 ? 'text-green-600' : 'text-gray-400') }}">
                    Rs. {{ number_format($vendor->balance) }}
                </div>
                <div class="text-xs text-gray-400 mt-1">
                    @if($vendor->balance > 0)
                        We owe this vendor
                    @elseif($vendor->balance < 0)
                        Vendor owes us (overpaid)
                    @else
                        Settled — no balance
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endcan
</div>
@endsection
