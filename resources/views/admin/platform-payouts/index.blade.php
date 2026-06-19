@extends('layouts.admin')
@section('title', 'Platform Payouts')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Platform Payouts</h1>
    <div class="flex gap-2">
        <a href="{{ route('admin.delivery-platforms.index') }}" class="btn-outline btn-sm">
            <i class="fas fa-cog mr-1"></i> Manage Platforms
        </a>
        <a href="{{ route('admin.platform-payouts.create') }}" class="btn-primary btn-sm">
            <i class="fas fa-plus mr-1"></i> Record Payout
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert-success mb-4">{{ session('success') }}</div>
@endif

{{-- Receivables summary --}}
@php
    $hasReceivables = $platforms->some(fn($p) => $p->orders->count() > 0);
@endphp

<div class="card mb-6">
    <div class="card-header">
        <h2 class="font-semibold text-gray-800">Outstanding COD Receivables</h2>
        <span class="text-xs text-gray-400">Delivered orders awaiting platform payment</span>
    </div>
    @if(!$hasReceivables)
        <div class="text-center py-8 text-gray-400 text-sm">
            <i class="fas fa-check-circle text-green-400 text-2xl mb-2"></i>
            <p>All COD orders are settled — no outstanding receivables.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 p-5">
            @foreach($platforms as $platform)
                @php $pending = $platform->orders; @endphp
                @if($pending->count() > 0)
                <div class="border border-orange-200 bg-orange-50 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-semibold text-gray-800">{{ $platform->name }}</span>
                        <span class="badge bg-orange-100 text-orange-700">{{ $pending->count() }} orders</span>
                    </div>
                    <div class="text-2xl font-bold text-orange-700">
                        Rs. {{ number_format($pending->sum('total')) }}
                    </div>
                    <div class="text-xs text-gray-500 mt-1">Oldest: {{ $pending->min('created_at')?->diffForHumans() }}</div>
                    <a href="{{ route('admin.platform-payouts.create', ['platform_id' => $platform->id]) }}"
                       class="mt-3 flex items-center justify-center gap-1.5 w-full py-1.5 rounded-lg bg-orange-600 hover:bg-orange-700 text-white text-xs font-semibold transition-colors">
                        <i class="fas fa-money-check-alt"></i> Record Payout
                    </a>
                </div>
                @endif
            @endforeach
        </div>
    @endif
</div>

{{-- Payout history --}}
<div class="card">
    <div class="card-header">
        <h2 class="font-semibold text-gray-800">Payout History</h2>
    </div>
    @if($payouts->isEmpty())
        <div class="text-center py-10 text-gray-400 text-sm">No payouts recorded yet.</div>
    @else
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Platform</th>
                        <th>Reference</th>
                        <th>Bank</th>
                        <th class="text-center">Orders</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">Recorded By</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payouts as $payout)
                    <tr>
                        <td class="text-xs text-gray-600 whitespace-nowrap">
                            {{ $payout->payout_date->format('d M Y') }}
                        </td>
                        <td class="font-medium text-gray-800">{{ $payout->platform?->name ?? '—' }}</td>
                        <td class="text-xs font-mono text-gray-500">{{ $payout->reference ?? '—' }}</td>
                        <td class="text-xs text-gray-500">{{ $payout->bankAccount?->label ?? '—' }}</td>
                        <td class="text-center">
                            <span class="badge bg-blue-100 text-blue-700">{{ $payout->orders->count() }}</span>
                        </td>
                        <td class="text-right font-bold text-green-700">Rs. {{ number_format($payout->amount) }}</td>
                        <td class="text-right text-xs text-gray-400">{{ $payout->createdBy?->name ?? '—' }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.platform-payouts.destroy', $payout) }}"
                                  onsubmit="return confirm('Delete this payout? The {{ $payout->orders->count() }} orders will revert to pending COD.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-600 text-xs" title="Delete payout">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($payouts->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">{{ $payouts->links() }}</div>
        @endif
    @endif
</div>
@endsection
