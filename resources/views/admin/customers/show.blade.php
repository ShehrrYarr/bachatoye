@extends('layouts.admin')
@section('title', $customer->name)

@section('content')
@php $isAdmin = auth()->user()->hasRole('admin'); @endphp
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route(auth()->user()->panelPrefix() . '.customers.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-xl font-bold text-gray-900">{{ $customer->name }}</h1>
        @if(!$customer->is_active)
        <span class="badge bg-gray-100 text-gray-500">Inactive</span>
        @endif
    </div>
    <div class="flex gap-2">
        @if($hasPosActivity)
        <a href="{{ route(auth()->user()->panelPrefix() . '.customers.ledger', $customer) }}" class="btn-outline btn-sm">
            <i class="fas fa-book mr-1"></i> Khata Ledger
        </a>
        @endif
        @if($isAdmin)
        <a href="{{ route(auth()->user()->panelPrefix() . '.customers.edit', $customer) }}" class="btn-primary btn-sm">
            <i class="fas fa-edit mr-1"></i> Edit
        </a>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-5">

        {{-- Purchase History --}}
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-gray-800"><i class="fas fa-shopping-bag text-primary-500 mr-1.5"></i>Purchase History</h2>
                <span class="text-sm text-gray-500">{{ $customer->orders->count() }} order(s)</span>
            </div>

            @forelse($customer->orders->sortByDesc('created_at') as $order)
            <div x-data="{ open: false }" class="border-b border-gray-100 last:border-0">
                {{-- Order header row --}}
                <button type="button" @click="open = !open"
                        class="w-full flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50 transition-colors text-left">
                    <i class="fas fa-chevron-right text-gray-400 text-xs transition-transform duration-200"
                       :class="open ? 'rotate-90' : ''"></i>
                    <span class="font-mono text-sm font-semibold text-primary-600 w-28 shrink-0">{{ $order->order_number }}</span>
                    <span class="text-xs text-gray-400 shrink-0">{{ $order->created_at->format('d M Y') }}</span>
                    <span class="text-xs px-1.5 py-0.5 rounded-full font-medium shrink-0
                        {{ $order->status === 'delivered' ? 'bg-green-100 text-green-700' :
                           ($order->status === 'cancelled' ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-700') }}">
                        {{ ucfirst($order->status) }}
                    </span>
                    @if($order->source === 'pos')
                    <span class="text-xs bg-indigo-50 text-indigo-600 px-1.5 py-0.5 rounded-full shrink-0">POS</span>
                    @endif
                    <span class="ml-auto font-bold text-sm text-gray-800 shrink-0">Rs. {{ number_format($order->total) }}</span>
                </button>

                {{-- Items breakdown --}}
                <div x-show="open" x-collapse class="bg-gray-50 border-t border-gray-100 px-5 pb-3 pt-2">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-400 uppercase border-b border-gray-200">
                                <th class="text-left py-1.5 font-semibold">Product</th>
                                <th class="text-center py-1.5 font-semibold w-16">Qty</th>
                                <th class="text-right py-1.5 font-semibold w-24">Unit Price</th>
                                <th class="text-right py-1.5 font-semibold w-24">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                            <tr class="border-b border-gray-100 last:border-0">
                                <td class="py-1.5 text-gray-700">
                                    {{ $item->product_name }}
                                    @if($item->variant_label)
                                    <span class="text-xs text-gray-400 ml-1">({{ $item->variant_label }})</span>
                                    @endif
                                </td>
                                <td class="py-1.5 text-center text-gray-600">{{ $item->quantity }}</td>
                                <td class="py-1.5 text-right text-gray-600">Rs. {{ number_format($item->unit_price) }}</td>
                                <td class="py-1.5 text-right font-semibold text-gray-800">Rs. {{ number_format($item->line_total) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="text-sm">
                                <td colspan="3" class="pt-2 text-right text-gray-500">
                                    Payment: <span class="font-medium text-gray-700">{{ ucfirst(str_replace('_', ' ', $order->payment_method ?? '—')) }}</span>
                                </td>
                                <td class="pt-2 text-right font-bold text-gray-900">Rs. {{ number_format($order->total) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                    @if($isAdmin)
                    <div class="mt-2 flex justify-end">
                        <a href="{{ route('admin.orders.show', $order) }}" class="text-xs text-primary-600 hover:underline">
                            View full order <i class="fas fa-arrow-right ml-0.5 text-[10px]"></i>
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            @empty
            <div class="px-5 py-10 text-center text-gray-400">
                <i class="fas fa-shopping-bag text-3xl mb-2 block"></i>
                No purchases yet.
            </div>
            @endforelse
        </div>

        {{-- Returns History --}}
        @if($customer->returns->count())
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-gray-800"><i class="fas fa-undo text-orange-500 mr-1.5"></i>Returns History</h2>
                <span class="text-sm text-gray-500">{{ $customer->returns->count() }} return(s)</span>
            </div>

            @foreach($customer->returns->sortByDesc('created_at') as $return)
            <div x-data="{ open: false }" class="border-b border-gray-100 last:border-0">
                <button type="button" @click="open = !open"
                        class="w-full flex items-center gap-3 px-5 py-3.5 hover:bg-orange-50/50 transition-colors text-left">
                    <i class="fas fa-chevron-right text-gray-400 text-xs transition-transform duration-200"
                       :class="open ? 'rotate-90' : ''"></i>
                    <span class="font-mono text-sm font-semibold text-orange-600 w-28 shrink-0">{{ $return->return_number }}</span>
                    <span class="text-xs text-gray-400 shrink-0">{{ $return->created_at->format('d M Y') }}</span>
                    @if($return->order)
                    <span class="text-xs text-gray-500 shrink-0">← {{ $return->order->order_number }}</span>
                    @endif
                    <span class="ml-auto font-bold text-sm text-orange-700 shrink-0">Rs. {{ number_format($return->items->sum('line_total')) }}</span>
                </button>

                <div x-show="open" x-collapse class="bg-orange-50/40 border-t border-orange-100 px-5 pb-3 pt-2">
                    @if($return->reason)
                    <p class="text-xs text-gray-500 mb-2"><span class="font-semibold">Reason:</span> {{ $return->reason }}</p>
                    @endif
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-400 uppercase border-b border-orange-100">
                                <th class="text-left py-1.5 font-semibold">Product</th>
                                <th class="text-center py-1.5 font-semibold w-16">Qty</th>
                                <th class="text-right py-1.5 font-semibold w-24">Refund</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($return->items as $item)
                            <tr class="border-b border-orange-100 last:border-0">
                                <td class="py-1.5 text-gray-700">{{ $item->product_name }}</td>
                                <td class="py-1.5 text-center text-gray-600">{{ $item->quantity }}</td>
                                <td class="py-1.5 text-right font-semibold text-orange-700">Rs. {{ number_format($item->line_total) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="pt-2 text-right text-xs text-gray-500">Total Refunded</td>
                                <td class="pt-2 text-right font-bold text-orange-800">Rs. {{ number_format($return->items->sum('line_total')) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Recent ledger entries — POS customers only --}}
        @if($hasPosActivity)
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-gray-800">Khata Ledger</h2>
                <a href="{{ route(auth()->user()->panelPrefix() . '.customers.ledger', $customer) }}" class="text-sm text-primary-600 hover:underline">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr><th>Date</th><th>Description</th><th>Debit</th><th>Credit</th><th>Balance</th></tr>
                    </thead>
                    <tbody>
                        @forelse($customer->ledgerEntries->take(8) as $entry)
                        <tr>
                            <td class="text-xs">{{ $entry->created_at->format('d M Y') }}</td>
                            <td class="text-sm">{{ $entry->description }}</td>
                            <td class="text-sm text-red-600">{{ $entry->type === 'debit' ? 'Rs. '.number_format($entry->amount) : '—' }}</td>
                            <td class="text-sm text-green-600">{{ $entry->type === 'credit' ? 'Rs. '.number_format($entry->amount) : '—' }}</td>
                            <td class="text-sm font-semibold {{ $entry->balance_after < 0 ? 'text-red-600' : 'text-gray-800' }}">
                                Rs. {{ number_format($entry->balance_after) }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-8 text-gray-400">No khata entries yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    {{-- Sidebar --}}
    <div class="space-y-5">
        <div class="card p-5">
            @if($customer->photo)
            <div class="flex justify-center mb-4">
                <img src="{{ Storage::url($customer->photo) }}" alt="{{ $customer->name }}"
                     class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-md">
            </div>
            @endif
            <h2 class="font-semibold text-gray-800 mb-4">Customer Info</h2>
            <dl class="space-y-3 text-sm">
                <div><dt class="text-gray-500 text-xs">Phone</dt><dd class="font-medium font-mono">{{ $customer->phone }}</dd></div>
                @if($customer->email)<div><dt class="text-gray-500 text-xs">Email</dt><dd>{{ $customer->email }}</dd></div>@endif
                @if($customer->address)<div><dt class="text-gray-500 text-xs">Address</dt><dd>{{ $customer->address }}</dd></div>@endif
                @if($customer->city)<div><dt class="text-gray-500 text-xs">City</dt><dd>{{ $customer->city }}</dd></div>@endif
                <div><dt class="text-gray-500 text-xs">Customer Since</dt><dd class="text-xs">{{ $customer->created_at->format('d M Y') }}</dd></div>
            </dl>
        </div>

        {{-- Balance card — POS customers only --}}
        @if($hasPosActivity)
        <div class="card p-5 {{ $customer->credit_balance < 0 ? 'border-red-200 bg-red-50' : ($customer->credit_balance > 0 ? 'border-green-200 bg-green-50' : '') }} border-2">
            <h2 class="font-semibold text-gray-800 mb-2">Khata Balance</h2>
            <div class="text-2xl font-extrabold {{ $customer->credit_balance < 0 ? 'text-red-600' : ($customer->credit_balance > 0 ? 'text-green-600' : 'text-gray-500') }}">
                Rs. {{ number_format(abs($customer->credit_balance)) }}
            </div>
            <div class="text-sm text-gray-500 mt-1">
                {{ $customer->credit_balance < 0 ? 'Customer owes this amount' : ($customer->credit_balance > 0 ? 'Credit available' : 'Settled') }}
            </div>
            <a href="{{ route(auth()->user()->panelPrefix() . '.customers.ledger', $customer) }}" class="btn-outline btn-sm mt-3 w-full justify-center">
                <i class="fas fa-plus mr-1"></i> Add Ledger Entry
            </a>
        </div>
        @endif

        {{-- Portal Login Credentials (POS customers only, admin only) --}}
        @if($isAdmin && $customer->source === 'pos' && $customer->account)
        <div x-data="{ show: false }" class="card p-5 border-amber-200 bg-amber-50">
            <h2 class="font-semibold text-gray-800 mb-3"><i class="fas fa-key text-amber-500 mr-1.5"></i>Portal Access</h2>
            <dl class="space-y-2 text-sm">
                <div>
                    <dt class="text-gray-500 text-xs">Login with</dt>
                    <dd class="font-mono font-medium text-gray-800">{{ $customer->account->email ?: $customer->account->phone }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 text-xs">Password</dt>
                    <dd class="flex items-center gap-2">
                        <span x-show="!show" class="font-mono text-gray-400 tracking-widest">••••••••••</span>
                        <span x-show="show" class="font-mono font-semibold text-gray-800">{{ $customer->account->plain_password }}</span>
                        <button type="button" @click="show = !show"
                                class="text-xs text-amber-600 hover:text-amber-800 font-medium">
                            <span x-text="show ? 'Hide' : 'Show'"></span>
                        </button>
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 text-xs">Status</dt>
                    <dd>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $customer->account->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                            {{ $customer->account->is_active ? 'Active' : 'Disabled' }}
                        </span>
                    </dd>
                </div>
            </dl>
            <a href="{{ route('account.login') }}" target="_blank"
               class="mt-3 w-full flex items-center justify-center gap-1.5 text-xs font-medium text-amber-700 bg-amber-100 hover:bg-amber-200 border border-amber-300 rounded-lg py-2 transition-colors">
                <i class="fas fa-external-link-alt"></i> Open Customer Portal
            </a>
        </div>
        @endif

        {{-- Stats --}}
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Stats</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Total Orders</dt>
                    <dd class="font-semibold">{{ $customer->orders->count() }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Total Spent</dt>
                    <dd class="font-semibold text-primary-700">Rs. {{ number_format($customer->orders->sum('total')) }}</dd>
                </div>
                @if($customer->returns->count())
                <div class="flex justify-between border-t border-gray-100 pt-2">
                    <dt class="text-gray-500">Returns</dt>
                    <dd class="font-semibold text-orange-600">{{ $customer->returns->count() }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Total Refunded</dt>
                    <dd class="font-semibold text-orange-600">Rs. {{ number_format($customer->returns->flatMap->items->sum('line_total')) }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>
</div>
@endsection
