@extends('layouts.admin')
@section('title', 'Add Expense')

@section('content')
@php $rPrefix = auth()->user()->panelPrefix(); @endphp
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route("{$rPrefix}.expenses.index") }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Add Expense</h1>
</div>

<div class="max-w-xl">
    <form method="POST" action="{{ route("{$rPrefix}.expenses.store") }}" enctype="multipart/form-data"
          x-data="{ paymentMethod: '{{ old('payment_method', 'cash') }}' }">
        @csrf
        <div class="card p-6 space-y-4">
            <div>
                <label class="form-label">Description *</label>
                <input type="text" name="description" value="{{ old('description') }}"
                       class="form-input @error('description') border-red-500 @enderror"
                       placeholder="e.g. Office supplies, Electricity bill..." required>
                @error('description') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Amount (Rs.) *</label>
                    <input type="number" name="amount" value="{{ old('amount') }}" step="0.01" min="0"
                           class="form-input @error('amount') border-red-500 @enderror" required>
                    @error('amount') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Category</label>
                    <select name="expense_category_id" class="form-select">
                        <option value="">— Uncategorized —</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('expense_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Date *</label>
                    <input type="date" name="expense_date" value="{{ old('expense_date', today()->format('Y-m-d')) }}"
                           class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Payment Method *</label>
                    <select name="payment_method" x-model="paymentMethod" class="form-select">
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                </div>
            </div>

            {{-- Bank account selector — only shown when Bank Transfer is selected --}}
            <div x-show="paymentMethod === 'bank_transfer'" x-transition>
                <label class="form-label">Bank Account *</label>
                <select name="bank_account_id" class="form-select @error('bank_account_id') border-red-500 @enderror"
                        :required="paymentMethod === 'bank_transfer'">
                    <option value="">— Select bank account —</option>
                    @foreach($banks as $bank)
                    <option value="{{ $bank->id }}" {{ old('bank_account_id') == $bank->id ? 'selected' : '' }}>
                        {{ $bank->label }} — {{ $bank->account_title }}
                    </option>
                    @endforeach
                </select>
                @error('bank_account_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="2" class="form-textarea" placeholder="Additional details...">{{ old('notes') }}</textarea>
            </div>
            <div>
                <label class="form-label">Receipt / Attachment</label>
                <input type="file" name="receipt_image" accept="image/*" class="form-input">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">Save Expense</button>
                <a href="{{ route("{$rPrefix}.expenses.index") }}" class="btn-outline">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
