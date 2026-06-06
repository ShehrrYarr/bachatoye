@extends('layouts.admin')
@section('title', 'Customer Online Accounts')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-900">Customer Online Accounts</h1>
        <p class="text-sm text-gray-500 mt-0.5">Customers who have registered an account on the online store.</p>
    </div>
</div>

{{-- Filter --}}
<div class="card p-4 mb-6">
    <form method="GET" action="{{ route('admin.customer-accounts.index') }}" class="flex flex-wrap gap-3 items-end">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Email, name, phone..."
               class="form-input text-sm flex-1 min-w-48">
        <select name="status" class="form-select text-sm">
            <option value="">All Statuses</option>
            <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
            <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
        </select>
        <button type="submit" class="btn-primary btn-sm">Filter</button>
        @if(request()->hasAny(['q', 'status']))
            <a href="{{ route('admin.customer-accounts.index') }}" class="btn-outline btn-sm">Clear</a>
        @endif
    </form>
</div>

<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Email</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Registered</th>
                <th>Last Login</th>
                <th class="text-center">Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($accounts as $account)
            <tr>
                <td>
                    <div class="font-medium text-sm text-gray-800">{{ $account->email }}</div>
                </td>
                <td>
                    @if($account->customer)
                        <a href="{{ route('admin.customers.show', $account->customer) }}"
                           class="text-sm text-primary-600 hover:underline font-medium">
                            {{ $account->customer->name }}
                        </a>
                    @else
                        <span class="text-gray-400 text-sm">—</span>
                    @endif
                </td>
                <td class="text-sm font-mono text-gray-600">{{ $account->customer?->phone ?? '—' }}</td>
                <td class="text-sm text-gray-600">{{ $account->created_at->format('d M Y') }}</td>
                <td class="text-sm text-gray-600">
                    {{ $account->last_login_at ? $account->last_login_at->diffForHumans() : '—' }}
                </td>
                <td class="text-center">
                    @if($account->is_active)
                        <span class="badge bg-green-100 text-green-700">Active</span>
                    @else
                        <span class="badge bg-red-100 text-red-700">Disabled</span>
                    @endif
                </td>
                <td class="text-right">
                    <form method="POST"
                          action="{{ route('admin.customer-accounts.toggle', $account) }}"
                          onsubmit="return confirm('{{ $account->is_active ? 'Disable this account?' : 'Enable this account?' }}')">
                        @csrf @method('PATCH')
                        <button type="submit"
                                class="btn-sm {{ $account->is_active ? 'btn-outline text-red-600 border-red-200 hover:bg-red-50' : 'btn-outline text-green-600 border-green-200 hover:bg-green-50' }}">
                            {{ $account->is_active ? 'Disable' : 'Enable' }}
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-gray-400 py-10">No customer accounts found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($accounts->hasPages())
    <div class="p-4 border-t border-gray-100">
        {{ $accounts->links() }}
    </div>
    @endif
</div>
@endsection
