@extends('layouts.admin')
@section('title', 'Ledger: ' . $customer->name)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.customers.show', $customer) }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-xl font-bold text-gray-900">Khata Ledger: {{ $customer->name }}</h1>
    </div>
    <div class="flex items-center gap-3">
        <span class="text-sm {{ $customer->credit_balance < 0 ? 'text-red-600 font-bold' : 'text-green-600 font-bold' }}">
            Balance: Rs. {{ number_format($customer->credit_balance) }}
        </span>
        <a href="{{ route('admin.customers.ledger.print', $customer) }}" target="_blank"
           class="btn-outline btn-sm">
            <i class="fas fa-file-pdf mr-1.5 text-red-500"></i> Print as PDF
        </a>
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
                        <th class="text-right">Debit (Owed)</th>
                        <th class="text-right">Credit (Paid)</th>
                        <th class="text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                    <tr>
                        <td class="text-xs text-gray-500 whitespace-nowrap">{{ $entry->created_at->format('d M Y H:i') }}</td>
                        <td class="text-sm">{{ $entry->description }}</td>
                        <td class="text-xs text-gray-400 font-mono">{{ $entry->reference ?? '—' }}</td>
                        <td class="text-right text-sm {{ $entry->type === 'debit' ? 'text-red-600 font-semibold' : 'text-gray-300' }}">
                            {{ $entry->type === 'debit' ? 'Rs. '.number_format($entry->amount) : '—' }}
                        </td>
                        <td class="text-right text-sm {{ $entry->type === 'credit' ? 'text-green-600 font-semibold' : 'text-gray-300' }}">
                            {{ $entry->type === 'credit' ? 'Rs. '.number_format($entry->amount) : '—' }}
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
            <form method="POST" action="{{ route('admin.customers.ledger.add', $customer) }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="form-label">Entry Type *</label>
                        <select name="type" class="form-select" required>
                            <option value="debit">Debit (Customer owes more)</option>
                            <option value="credit">Credit (Customer paid / refund)</option>
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
                    <button type="submit" class="btn-primary w-full justify-center">
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
