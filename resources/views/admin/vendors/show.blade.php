@extends('layouts.admin')
@section('title', $vendor->name)

@section('content')
@php $rPrefix = auth()->user()->panelPrefix(); @endphp
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route("{$rPrefix}.vendors.index") }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-xl font-bold text-gray-900">{{ $vendor->name }}</h1>
        @if($vendor->company)
            <span class="text-gray-400 text-sm">{{ $vendor->company }}</span>
        @endif
    </div>
    <div class="flex gap-2">
        @can('purchases.manage')
        <a href="{{ route("{$rPrefix}.purchases.create") }}?vendor_id={{ $vendor->id }}" class="btn-primary btn-sm">
            <i class="fas fa-plus mr-1"></i> New Purchase
        </a>
        @endcan
        <a href="{{ route("{$rPrefix}.vendors.khata", $vendor) }}" class="btn-outline btn-sm text-purple-700 border-purple-300 hover:bg-purple-50">
            <i class="fas fa-book mr-1"></i> Khata
        </a>
        @can('vendors.manage')
        <a href="{{ route("{$rPrefix}.vendors.edit", $vendor) }}" class="btn-outline btn-sm">
            <i class="fas fa-edit mr-1"></i> Edit
        </a>
        @endcan
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Left: purchases + sales --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Purchases list --}}
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-gray-800">Purchase History</h2>
                <a href="{{ route('admin.purchases.index') }}?vendor={{ $vendor->id }}" class="text-sm text-primary-600 hover:underline">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table text-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Paid</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                        <tr>
                            <td class="text-xs">{{ $purchase->purchase_date->format('d M Y') }}</td>
                            <td class="font-mono text-xs">{{ $purchase->reference ?? '—' }}</td>
                            <td class="text-right font-semibold">Rs. {{ number_format($purchase->total) }}</td>
                            <td class="text-right">Rs. {{ number_format($purchase->amount_paid) }}</td>
                            <td>
                                <span class="badge
                                    @if($purchase->payment_status === 'paid') bg-green-100 text-green-700
                                    @elseif($purchase->payment_status === 'partial') bg-orange-100 text-orange-700
                                    @else bg-red-100 text-red-700 @endif">
                                    {{ ucfirst($purchase->payment_status) }}
                                </span>
                            </td>
                            <td>
                                @can('purchases.view')
                                <a href="{{ route("{$rPrefix}.purchases.show", $purchase) }}" class="text-primary-600 hover:underline text-xs">View</a>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-gray-400 py-6">No purchases yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($purchases->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">{{ $purchases->links() }}</div>
            @endif
        </div>

        {{-- Sales to this vendor (POS sales with vendor_id) --}}
        @php $canViewOrders = auth()->user()->isAdmin() || auth()->user()->can('orders.view'); @endphp
        <div class="card" id="sales">
            <div class="card-header">
                <h2 class="font-semibold text-gray-800">Sale History</h2>
                <span class="text-sm text-gray-500">{{ number_format($summary['sales_count']) }} {{ Str::plural('sale', $summary['sales_count']) }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table text-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Order</th>
                            <th>Items</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Paid</th>
                            <th class="text-right">On Khata</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                        @php
                            // Khata part = what the sale added to the vendor's ledger
                            // (full total for khata, the unpaid rest for partial)
                            $onKhata  = in_array($sale->payment_method, ['khata', 'partial'])
                                ? max(0, (float) $sale->total - (float) $sale->amount_paid) : 0;
                            $paid     = (float) $sale->total - $onKhata;
                            $returned = (float) $sale->returns->sum('refund_amount');
                            $items    = $sale->items;
                        @endphp
                        <tr>
                            <td class="text-xs whitespace-nowrap">{{ $sale->created_at->format('d M Y') }}</td>
                            <td class="font-mono text-xs whitespace-nowrap">
                                @if($canViewOrders)
                                    <a href="{{ route("{$rPrefix}.orders.show", $sale) }}" class="text-primary-600 hover:underline">{{ $sale->order_number }}</a>
                                @else
                                    {{ $sale->order_number }}
                                @endif
                                @if($returned > 0)
                                    <div class="mt-1">
                                        <span class="badge bg-orange-100 text-orange-700">Returned Rs. {{ number_format($returned) }}</span>
                                    </div>
                                @endif
                            </td>
                            <td class="text-xs text-gray-600">
                                {{ $items->take(2)->map(fn($i) => $i->product_name . ' x' . $i->quantity)->join(', ') }}
                                @if($items->count() > 2)
                                    <span class="text-gray-400">+{{ $items->count() - 2 }} more</span>
                                @endif
                            </td>
                            <td class="text-right font-semibold whitespace-nowrap">Rs. {{ number_format($sale->total) }}</td>
                            <td class="text-right whitespace-nowrap">Rs. {{ number_format($paid) }}</td>
                            <td class="text-right whitespace-nowrap {{ $onKhata > 0 ? 'text-green-600 font-semibold' : 'text-gray-400' }}">
                                {{ $onKhata > 0 ? 'Rs. ' . number_format($onKhata) : '—' }}
                            </td>
                            <td>
                                @php
                                    [$payLabel, $payClass] = match ($sale->payment_method) {
                                        'khata'         => ['Khata', 'bg-purple-100 text-purple-700'],
                                        'partial'       => ['Partial', 'bg-orange-100 text-orange-700'],
                                        'bank_transfer' => ['Bank', 'bg-blue-100 text-blue-700'],
                                        'split'         => ['Cash + Bank', 'bg-indigo-100 text-indigo-700'],
                                        default         => [ucfirst($sale->payment_method), 'bg-green-100 text-green-700'],
                                    };
                                @endphp
                                <span class="badge {{ $payClass }}">{{ $payLabel }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-gray-400 py-6">No sales to this vendor yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($sales->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">{{ $sales->fragment('sales')->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Right: info + quick payment --}}
    <div class="space-y-5">

        {{-- Balance card --}}
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Account Summary</h2>
            <div class="text-center py-4">
                @if($vendor->balance > 0)
                    <div class="text-3xl font-bold text-red-600">Rs. {{ number_format($vendor->balance) }}</div>
                    <div class="text-sm text-gray-500 mt-1">You owe this vendor</div>
                @elseif($vendor->balance < 0)
                    <div class="text-3xl font-bold text-green-600">Rs. {{ number_format(abs($vendor->balance)) }}</div>
                    <div class="text-sm text-gray-500 mt-1">Vendor owes you</div>
                @else
                    <div class="text-3xl font-bold text-gray-400">Settled</div>
                    <div class="text-sm text-gray-500 mt-1">No outstanding balance</div>
                @endif
            </div>
            <dl class="border-t border-gray-100 pt-4 space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Total Purchases</dt>
                    <dd class="font-semibold">{{ number_format($summary['purchases_count']) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Total Spent</dt>
                    <dd class="font-semibold">Rs. {{ number_format($summary['purchases_total']) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Total Sales</dt>
                    <dd class="font-semibold">{{ number_format($summary['sales_count']) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Total Sold</dt>
                    <dd class="font-semibold">Rs. {{ number_format($summary['sales_total']) }}</dd>
                </div>
            </dl>
            <a href="{{ route("{$rPrefix}.vendors.khata", $vendor) }}"
               class="mt-4 flex items-center justify-center gap-2 w-full py-2 rounded-lg border border-purple-300 text-purple-700 text-sm font-medium hover:bg-purple-50 transition-colors">
                <i class="fas fa-book"></i> View Full Khata
            </a>
        </div>

        {{-- Contact info --}}
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Contact</h2>
            <dl class="space-y-2 text-sm">
                @if($vendor->phone)
                <div><dt class="text-xs text-gray-400">Phone</dt><dd class="font-medium">{{ $vendor->phone }}</dd></div>
                @endif
                @if($vendor->email)
                <div><dt class="text-xs text-gray-400">Email</dt><dd>{{ $vendor->email }}</dd></div>
                @endif
                @if($vendor->address)
                <div><dt class="text-xs text-gray-400">Address</dt><dd>{{ $vendor->address }}</dd></div>
                @endif
                @if($vendor->notes)
                <div class="pt-2 border-t border-gray-100">
                    <dt class="text-xs text-gray-400 mb-1">Notes</dt>
                    <dd class="text-gray-600 text-xs">{{ $vendor->notes }}</dd>
                </div>
                @endif
            </dl>
        </div>

        {{-- Quick payment (admin only — ledger.add route not available to salesmen) --}}
        @hasrole('admin')
        @if($vendor->balance != 0)
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Record Payment</h2>
            <form method="POST" action="{{ route('admin.vendors.ledger.add', $vendor) }}" class="space-y-3"
                  x-data="{ payMethod: 'cash' }">
                @csrf
                <input type="hidden" name="type" value="debit">

                <div>
                    <label class="form-label text-sm">Amount Paid (Rs.)</label>
                    <input type="number" name="amount" min="0.01" step="0.01"
                           max="{{ $vendor->balance }}"
                           class="form-input" placeholder="0.00" required>
                </div>

                <div>
                    <label class="form-label text-sm">Payment Method</label>
                    <div class="flex gap-3 mt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="payment_method" value="cash"
                                   x-model="payMethod" class="text-primary-600">
                            <span class="text-sm font-medium"><i class="fas fa-money-bill-wave text-green-500 mr-1"></i>Cash</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="payment_method" value="bank_transfer"
                                   x-model="payMethod" class="text-primary-600">
                            <span class="text-sm font-medium"><i class="fas fa-university text-blue-500 mr-1"></i>Bank Transfer</span>
                        </label>
                    </div>
                </div>

                <div x-show="payMethod === 'bank_transfer'" x-transition style="display:none;">
                    <label class="form-label text-sm">Bank Account</label>
                    <select name="bank_account_id" class="form-input"
                            :required="payMethod === 'bank_transfer'">
                        <option value="">— Select Bank —</option>
                        @foreach($bankAccounts as $bank)
                            <option value="{{ $bank->id }}">{{ $bank->label }} — {{ $bank->account_title }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label text-sm">Note (optional)</label>
                    <input type="text" name="description" class="form-input" placeholder="Add a note...">
                </div>

                <button type="submit" class="btn-primary w-full justify-center">
                    <i class="fas fa-check mr-2"></i> Record Payment
                </button>
            </form>
        </div>
        @endif
        @endrole
    </div>
</div>
@endsection
