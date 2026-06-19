@extends('layouts.admin')
@section('title', 'Record Platform Payout')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.platform-payouts.index') }}" class="btn-outline btn-sm">
        <i class="fas fa-arrow-left"></i>
    </a>
    <h1 class="text-xl font-bold text-gray-900">Record Platform Payout</h1>
</div>

@if($errors->any())
    <div class="alert-error mb-4">{{ $errors->first() }}</div>
@endif

{{-- Step 1: Platform selector (shown only if no platform selected yet) --}}
@if(!$platform)
<div class="card p-6 max-w-md mx-auto mt-12">
    <div class="text-center mb-5">
        <div class="w-14 h-14 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-3">
            <i class="fas fa-truck text-orange-600 text-xl"></i>
        </div>
        <h2 class="font-semibold text-gray-800">Select Delivery Platform</h2>
        <p class="text-sm text-gray-400 mt-1">Which platform sent you this payment?</p>
    </div>
    <form method="GET" action="{{ route('admin.platform-payouts.create') }}" class="space-y-4">
        <select name="platform_id" class="form-select" required>
            <option value="">— Choose Platform —</option>
            @foreach($platforms as $p)
                <option value="{{ $p->id }}">{{ $p->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-primary w-full justify-center">
            Continue <i class="fas fa-arrow-right ml-2"></i>
        </button>
    </form>
</div>

@else

{{-- Step 2: Full payout form --}}
<form method="POST" action="{{ route('admin.platform-payouts.store') }}" x-data="payoutForm()">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: order selection --}}
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="font-semibold text-gray-800">
                            Pending COD Orders — {{ $platform->name }}
                        </h2>
                        <p class="text-xs text-gray-400 mt-0.5">Select the orders included in this payout</p>
                    </div>
                    <button type="button" @click="toggleAll()"
                            class="btn-outline btn-sm text-xs"
                            x-text="allSelected ? 'Deselect All' : 'Select All'">
                    </button>
                </div>

                @if($pendingOrders->isEmpty())
                    <div class="text-center py-16 text-gray-400">
                        <i class="fas fa-check-circle text-green-400 text-4xl mb-3"></i>
                        <p class="font-medium">No pending COD orders for {{ $platform->name }}</p>
                        <p class="text-xs mt-1">All orders from this platform are already settled.</p>
                        <a href="{{ route('admin.platform-payouts.index') }}" class="btn-outline btn-sm mt-4 inline-flex">
                            <i class="fas fa-arrow-left mr-1"></i> Back
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="w-8">
                                        <input type="checkbox" x-model="allSelected" @change="toggleAll()"
                                               class="rounded text-primary-600">
                                    </th>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Delivered</th>
                                    <th>Tracking</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingOrders as $order)
                                <tr class="cursor-pointer" @click="toggleOrder({{ $order->id }})">
                                    <td @click.stop>
                                        <input type="checkbox"
                                               name="order_ids[]"
                                               value="{{ $order->id }}"
                                               x-model="selectedIds"
                                               :value="{{ $order->id }}"
                                               class="rounded text-primary-600">
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.orders.show', $order) }}" target="_blank"
                                           class="font-mono text-xs text-primary-600 hover:underline" @click.stop>
                                            {{ $order->order_number }}
                                        </a>
                                    </td>
                                    <td>
                                        <div class="font-medium text-sm">{{ $order->customer_name }}</div>
                                        @if($order->customer_phone)
                                            <div class="text-xs text-gray-400">{{ $order->customer_phone }}</div>
                                        @endif
                                    </td>
                                    <td class="text-xs text-gray-500 whitespace-nowrap">
                                        {{ $order->updated_at->format('d M Y') }}
                                    </td>
                                    <td class="text-xs text-gray-400 font-mono">
                                        {{ $order->tracking_number ?? '—' }}
                                    </td>
                                    <td class="text-right font-semibold text-gray-800">
                                        Rs. {{ number_format($order->total) }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                <tr>
                                    <td colspan="4" class="px-4 py-3 text-sm font-bold text-gray-700">
                                        <span x-text="selectedIds.length + ' of {{ $pendingOrders->count() }} orders selected'"></span>
                                    </td>
                                    <td></td>
                                    <td class="px-4 py-3 text-right font-bold text-lg text-green-700">
                                        Rs. <span x-text="fmt(selectedTotal)"></span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Right: payout details --}}
        @if($pendingOrders->isNotEmpty())
        <div>
            <div class="card p-5 sticky top-6 space-y-4">
                <h3 class="font-semibold text-gray-800">Payout Details</h3>

                <input type="hidden" name="delivery_platform_id" value="{{ $platform->id }}">

                <div>
                    <label class="form-label">Platform</label>
                    <div class="flex items-center gap-2 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm">
                        <i class="fas fa-truck text-gray-400"></i>
                        <span class="font-medium">{{ $platform->name }}</span>
                        <a href="{{ route('admin.platform-payouts.create') }}" class="ml-auto text-xs text-primary-600 hover:underline">
                            Change
                        </a>
                    </div>
                </div>

                <div>
                    <label class="form-label">Payout Date *</label>
                    <input type="date" name="payout_date" class="form-input" required
                           value="{{ old('payout_date', today()->toDateString()) }}">
                </div>

                <div>
                    <label class="form-label">Bank Account *</label>
                    <select name="bank_account_id" class="form-select" required>
                        <option value="">— Select Bank —</option>
                        @foreach($bankAccounts as $bank)
                            <option value="{{ $bank->id }}" {{ old('bank_account_id') == $bank->id ? 'selected' : '' }}>
                                {{ $bank->label }} — {{ $bank->account_title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">Amount Received (Rs.) *</label>
                    <input type="number" name="amount" class="form-input" step="0.01" min="0.01" required
                           :value="selectedTotal.toFixed(2)"
                           placeholder="0.00">
                    <p class="text-xs text-gray-400 mt-1">Pre-filled from selected orders. Adjust if platform deducted charges.</p>
                </div>

                <div>
                    <label class="form-label">Remittance Reference</label>
                    <input type="text" name="reference" class="form-input" placeholder="e.g. TCS-REM-2026-001"
                           value="{{ old('reference') }}">
                </div>

                <div>
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input" rows="2" placeholder="Optional notes...">{{ old('notes') }}</textarea>
                </div>

                <button type="submit"
                        :disabled="selectedIds.length === 0"
                        class="btn-primary w-full justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-check mr-2"></i>
                    Record Payout
                    <span x-show="selectedIds.length > 0" x-text="'(' + selectedIds.length + ' orders)'"></span>
                </button>

                <div x-show="selectedIds.length === 0" class="text-xs text-center text-red-500">
                    Select at least one order
                </div>
            </div>
        </div>
        @endif

    </div>
</form>
@endif

<script>
function payoutForm() {
    const orders = @json($pendingOrders->map(fn($o) => ['id' => $o->id, 'total' => (float)$o->total]));

    return {
        selectedIds: orders.map(o => o.id),
        allSelected: true,

        get selectedTotal() {
            return orders
                .filter(o => this.selectedIds.includes(o.id))
                .reduce((s, o) => s + o.total, 0);
        },

        toggleOrder(id) {
            const idx = this.selectedIds.indexOf(id);
            if (idx >= 0) {
                this.selectedIds.splice(idx, 1);
            } else {
                this.selectedIds.push(id);
            }
            this.allSelected = this.selectedIds.length === orders.length;
        },

        toggleAll() {
            if (this.allSelected) {
                this.selectedIds = orders.map(o => o.id);
            } else {
                this.selectedIds = [];
            }
        },

        fmt(n) {
            return Math.round(n).toLocaleString();
        },
    };
}
</script>
@endsection
