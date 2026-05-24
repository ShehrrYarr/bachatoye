@extends('layouts.admin')
@section('title', 'POS Sessions')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">POS Sessions</h1>
    <a href="{{ route('pos.index') }}" class="btn-primary btn-sm">
        <i class="fas fa-cash-register mr-1"></i> Open POS
    </a>
</div>

<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Opened By</th>
                <th>Opened At</th>
                <th>Closed At</th>
                <th>Duration</th>
                <th class="text-right">Opening Cash</th>
                <th class="text-right">Sales</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sessions as $session)
            <tr>
                <td class="font-medium text-gray-800">{{ $session->user?->name ?? '—' }}</td>
                <td class="text-sm text-gray-600">{{ $session->opened_at->format('d M Y H:i') }}</td>
                <td class="text-sm text-gray-500">
                    {{ $session->closed_at ? $session->closed_at->format('d M Y H:i') : '—' }}
                </td>
                <td class="text-sm text-gray-500">
                    @if($session->closed_at)
                        {{ $session->opened_at->diff($session->closed_at)->format('%hh %im') }}
                    @else
                        <span class="text-green-600">Ongoing</span>
                    @endif
                </td>
                <td class="text-right font-medium">
                    Rs. {{ number_format($session->opening_cash ?? 0) }}
                </td>
                <td class="text-right font-semibold text-primary-700">
                    Rs. {{ number_format($session->total_sales ?? 0) }}
                </td>
                <td>
                    @if($session->closed_at)
                        <span class="badge bg-gray-100 text-gray-600">Closed</span>
                    @else
                        <span class="badge bg-green-100 text-green-700">Open</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center py-12 text-gray-400">No POS sessions recorded yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4 border-t border-gray-200">{{ $sessions->links() }}</div>
</div>
@endsection
