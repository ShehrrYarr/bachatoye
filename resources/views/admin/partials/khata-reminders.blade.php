@if($khataReminders->isNotEmpty())
@php
    $overdue  = $khataReminders->filter(fn($r) => $r->promise_date->lt(today()));
    $dueToday = $khataReminders->filter(fn($r) => $r->promise_date->isToday());
    $upcoming = $khataReminders->filter(fn($r) => $r->promise_date->gt(today()));
    $rPrefix  = auth()->user()->hasRole('admin') ? 'admin' : 'salesman';
@endphp
<div class="card overflow-hidden mb-4">
    <div class="card-header">
        <div class="flex items-center gap-2">
            <i class="fas fa-bell text-amber-500"></i>
            <h2 class="font-semibold text-gray-800">Khata Payment Reminders</h2>
            <span class="bg-amber-100 text-amber-800 text-xs font-bold px-2 py-0.5 rounded-full">
                {{ $khataReminders->count() }}
            </span>
        </div>
        @if(auth()->user()->hasRole('admin'))
        <a href="{{ route('admin.customers.index') }}" class="text-xs text-primary-600 hover:underline">View Customers</a>
        @endif
    </div>

    <div class="divide-y divide-gray-100">

        {{-- Overdue --}}
        @foreach($overdue as $reminder)
        <div x-data="{ gone: false }" x-show="!gone" x-transition
             class="flex items-center gap-3 px-5 py-3 bg-red-50">
            <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                <i class="fas fa-exclamation-circle text-red-500 text-sm"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-semibold text-gray-800 text-sm">{{ $reminder->customer->name }}</span>
                    <span class="text-xs bg-red-100 text-red-700 font-semibold px-2 py-0.5 rounded-full">Overdue</span>
                </div>
                <div class="text-xs text-gray-500 mt-0.5">
                    Promised: <span class="font-medium text-red-600">{{ $reminder->promise_date->format('d M Y') }}</span>
                    &bull; {{ $reminder->promise_date->diffForHumans() }}
                    &bull; Order: {{ $reminder->reference }}
                </div>
            </div>
            <div class="text-right shrink-0">
                <div class="text-sm font-bold text-red-600">Rs. {{ number_format($reminder->amount) }}</div>
                <div class="text-xs text-gray-400">Khata owed: Rs. {{ number_format(abs($reminder->customer->credit_balance)) }}</div>
            </div>
            <div class="flex flex-col gap-1 shrink-0">
                <a href="{{ route($rPrefix . '.customers.ledger', $reminder->customer) }}"
                   class="text-xs px-2 py-1 bg-indigo-100 text-indigo-700 hover:bg-indigo-200 border border-indigo-300 rounded font-semibold text-center">
                    <i class="fas fa-wallet mr-1"></i> Pay
                </a>
                <button
                    @click="fetch('{{ route($rPrefix . '.ledger.dismiss_promise', $reminder) }}', {method:'PATCH',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}}).then(r=>r.json()).then(d=>{ if(d.success) gone=true })"
                    class="text-xs px-2 py-1 bg-green-100 text-green-700 hover:bg-green-200 border border-green-300 rounded font-semibold">
                    <i class="fas fa-check mr-1"></i> Done
                </button>
            </div>
        </div>
        @endforeach

        {{-- Due Today --}}
        @foreach($dueToday as $reminder)
        <div x-data="{ gone: false }" x-show="!gone" x-transition
             class="flex items-center gap-3 px-5 py-3 bg-orange-50">
            <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center shrink-0">
                <i class="fas fa-clock text-orange-500 text-sm"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-semibold text-gray-800 text-sm">{{ $reminder->customer->name }}</span>
                    <span class="text-xs bg-orange-100 text-orange-700 font-semibold px-2 py-0.5 rounded-full">Due Today</span>
                </div>
                <div class="text-xs text-gray-500 mt-0.5">
                    Phone: {{ $reminder->customer->phone }}
                    &bull; Order: {{ $reminder->reference }}
                </div>
            </div>
            <div class="text-right shrink-0">
                <div class="text-sm font-bold text-orange-600">Rs. {{ number_format($reminder->amount) }}</div>
                <div class="text-xs text-gray-400">Khata owed: Rs. {{ number_format(abs($reminder->customer->credit_balance)) }}</div>
            </div>
            <div class="flex flex-col gap-1 shrink-0">
                <a href="{{ route($rPrefix . '.customers.ledger', $reminder->customer) }}"
                   class="text-xs px-2 py-1 bg-indigo-100 text-indigo-700 hover:bg-indigo-200 border border-indigo-300 rounded font-semibold text-center">
                    <i class="fas fa-wallet mr-1"></i> Pay
                </a>
                <button
                    @click="fetch('{{ route($rPrefix . '.ledger.dismiss_promise', $reminder) }}', {method:'PATCH',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}}).then(r=>r.json()).then(d=>{ if(d.success) gone=true })"
                    class="text-xs px-2 py-1 bg-green-100 text-green-700 hover:bg-green-200 border border-green-300 rounded font-semibold">
                    <i class="fas fa-check mr-1"></i> Done
                </button>
            </div>
        </div>
        @endforeach

        {{-- Upcoming (within 5 days) --}}
        @foreach($upcoming as $reminder)
        <div x-data="{ gone: false }" x-show="!gone" x-transition
             class="flex items-center gap-3 px-5 py-3">
            <div class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center shrink-0">
                <i class="fas fa-calendar-alt text-yellow-500 text-sm"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-semibold text-gray-800 text-sm">{{ $reminder->customer->name }}</span>
                    <span class="text-xs bg-yellow-100 text-yellow-700 font-semibold px-2 py-0.5 rounded-full">
                        {{ $reminder->promise_date->diffForHumans() }}
                    </span>
                </div>
                <div class="text-xs text-gray-500 mt-0.5">
                    Promised: <span class="font-medium text-gray-700">{{ $reminder->promise_date->format('d M Y') }}</span>
                    &bull; Order: {{ $reminder->reference }}
                </div>
            </div>
            <div class="text-right shrink-0">
                <div class="text-sm font-bold text-gray-700">Rs. {{ number_format($reminder->amount) }}</div>
                <div class="text-xs text-gray-400">Khata owed: Rs. {{ number_format(abs($reminder->customer->credit_balance)) }}</div>
            </div>
            <div class="flex flex-col gap-1 shrink-0">
                <a href="{{ route($rPrefix . '.customers.ledger', $reminder->customer) }}"
                   class="text-xs px-2 py-1 bg-indigo-100 text-indigo-700 hover:bg-indigo-200 border border-indigo-300 rounded font-semibold text-center">
                    <i class="fas fa-wallet mr-1"></i> Pay
                </a>
                <button
                    @click="fetch('{{ route($rPrefix . '.ledger.dismiss_promise', $reminder) }}', {method:'PATCH',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}}).then(r=>r.json()).then(d=>{ if(d.success) gone=true })"
                    class="text-xs px-2 py-1 bg-green-100 text-green-700 hover:bg-green-200 border border-green-300 rounded font-semibold">
                    <i class="fas fa-check mr-1"></i> Done
                </button>
            </div>
        </div>
        @endforeach

    </div>
</div>
@endif
