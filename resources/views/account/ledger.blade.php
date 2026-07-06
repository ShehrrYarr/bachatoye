@extends('layouts.ecom')
@section('title', 'Khata / Ledger')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col lg:flex-row gap-6">

        <aside class="lg:w-64 shrink-0">
            @include('account._sidebar')
        </aside>

        <main class="flex-1 min-w-0 space-y-5">

            {{-- Balance header --}}
            <div class="rounded-2xl p-5 sm:p-6 text-white shadow-sm"
                 style="background: var(--app-gradient, linear-gradient(135deg, #be123c 0%, #881337 100%))">
                <p class="text-sm text-red-100 mb-1">Current Balance</p>
                <div class="text-3xl sm:text-4xl font-extrabold">
                    Rs. {{ number_format(abs($balance)) }}
                </div>
                <p class="text-sm text-red-200 mt-1">
                    @if($balance < 0) You owe this amount
                    @elseif($balance > 0) Credit in your favour
                    @else Your account is settled
                    @endif
                </p>
            </div>

            {{-- Ledger table --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
                <div class="px-5 sm:px-6 py-4 border-b border-gray-100">
                    <h2 class="font-bold text-gray-900 text-sm sm:text-base">Transaction History</h2>
                </div>

                @if($entries->isEmpty())
                <div class="py-14 text-center text-gray-400">
                    <i class="fas fa-book-open text-4xl mb-3"></i>
                    <p class="text-sm">No transactions yet.</p>
                </div>
                @else

                {{-- Mobile: cards --}}
                <div class="sm:hidden divide-y divide-gray-50">
                    @foreach($entries as $entry)
                    @php $isDebit = $entry->type === 'debit'; @endphp
                    <div class="px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-xs text-gray-400">{{ $entry->created_at->format('d M Y, h:i A') }}</div>
                                @if($entry->description)
                                <div class="text-sm text-gray-700 mt-0.5 truncate">{{ $entry->description }}</div>
                                @endif
                                @if($entry->return_id)
                                <span class="text-xs bg-orange-100 text-orange-600 px-1.5 py-0.5 rounded-full font-medium mt-1 inline-block">Return</span>
                                @endif
                            </div>
                            <div class="text-right shrink-0">
                                <div class="font-bold text-sm {{ $isDebit ? 'text-red-600' : 'text-green-600' }}">
                                    {{ $isDebit ? '+' : '-' }} Rs. {{ number_format($entry->amount) }}
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ $isDebit ? 'You owe' : 'Payment' }}</div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Desktop: table --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr class="text-xs text-gray-500 uppercase tracking-wide">
                                <th class="text-left px-6 py-3 font-semibold">Date</th>
                                <th class="text-left px-6 py-3 font-semibold">Description</th>
                                <th class="text-right px-6 py-3 font-semibold text-red-500">You Owe (Dr)</th>
                                <th class="text-right px-6 py-3 font-semibold text-green-600">Payment (Cr)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($entries as $entry)
                            @php $isDebit = $entry->type === 'debit'; @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-3.5 text-gray-500 text-xs whitespace-nowrap">
                                    {{ $entry->created_at->format('d M Y') }}<br>
                                    <span class="text-gray-400">{{ $entry->created_at->format('h:i A') }}</span>
                                </td>
                                <td class="px-6 py-3.5 text-gray-700">
                                    {{ $entry->description ?: ($isDebit ? 'Purchase on credit' : 'Payment received') }}
                                    @if($entry->return_id)
                                    <span class="ml-1 text-xs bg-orange-100 text-orange-600 px-1.5 py-0.5 rounded-full font-medium">Return</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 text-right font-semibold text-red-600">
                                    {{ $isDebit ? 'Rs. ' . number_format($entry->amount) : '—' }}
                                </td>
                                <td class="px-6 py-3.5 text-right font-semibold text-green-600">
                                    {{ !$isDebit ? 'Rs. ' . number_format($entry->amount) : '—' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($entries->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $entries->links() }}
                </div>
                @endif
                @endif
            </div>

        </main>
    </div>
</div>
@endsection
