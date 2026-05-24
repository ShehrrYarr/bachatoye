@extends('layouts.admin')
@section('title', 'Login Log: ' . $salesman->name)

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.salesmen.show', $salesman) }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Login Log: {{ $salesman->name }}</h1>
</div>

<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Logged In</th>
                <th>Logged Out</th>
                <th>Duration</th>
                <th>IP Address</th>
                <th>Browser / Device</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td class="text-sm">{{ $log->logged_in_at->format('d M Y') }}</td>
                <td class="text-sm font-medium">{{ $log->logged_in_at->format('H:i:s') }}</td>
                <td class="text-sm">
                    @if($log->logged_out_at)
                        {{ $log->logged_out_at->format('H:i:s') }}
                    @else
                        <span class="badge bg-green-100 text-green-700">Still Active</span>
                    @endif
                </td>
                <td class="text-sm text-gray-500">{{ $log->duration ?? '—' }}</td>
                <td class="text-xs font-mono text-gray-400">{{ $log->ip_address ?? '—' }}</td>
                <td class="text-xs text-gray-400 max-w-xs truncate">{{ $log->user_agent ? Str::limit($log->user_agent, 50) : '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center py-12 text-gray-400">No login history recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4 border-t border-gray-200">{{ $logs->links() }}</div>
</div>
@endsection
