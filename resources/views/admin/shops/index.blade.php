@extends('layouts.admin')
@section('title', 'Sub Shops')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Sub Shops</h1>
    <a href="{{ route('admin.shops.create') }}" class="btn-primary"><i class="fas fa-plus mr-2"></i> Add Sub Shop</a>
</div>

<div class="card">
    <div class="overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>Shop</th>
                <th>Code</th>
                <th>Login</th>
                <th>Password</th>
                <th class="text-right">Today's Sales</th>
                <th class="text-center">Customers</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($shops as $shop)
            <tr>
                <td>
                    <div class="font-semibold text-gray-800">{{ $shop->name }}</div>
                    @if($shop->phone)<div class="text-xs text-gray-400">{{ $shop->phone }}</div>@endif
                </td>
                <td><span class="badge bg-indigo-50 text-indigo-700 font-mono">{{ $shop->code }}</span></td>
                <td class="text-sm text-gray-600">{{ $shop->loginUser?->email ?? '—' }}</td>
                <td x-data="{ show: false, pwd: {{ json_encode($shop->loginUser?->password_plain ?? '') }} }">
                    @if($shop->loginUser?->password_plain)
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs text-gray-700" x-text="show ? pwd : '••••••••'"></span>
                            <button @click="show = !show" class="text-gray-400 hover:text-gray-700 text-xs">
                                <i :class="show ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                            </button>
                        </div>
                    @else
                        <span class="text-gray-300 text-xs italic">Not stored</span>
                    @endif
                </td>
                <td class="text-right font-semibold text-gray-800">Rs. {{ number_format($todaySales[$shop->id] ?? 0) }}</td>
                <td class="text-center text-sm">{{ $shop->customers_count }}</td>
                <td>
                    @if($shop->is_active)
                        <span class="badge bg-green-100 text-green-700">Active</span>
                    @else
                        <span class="badge bg-red-100 text-red-700">Inactive</span>
                    @endif
                </td>
                <td class="text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.shops.show', $shop) }}" class="btn-outline btn-sm" title="View"><i class="fas fa-eye"></i></a>
                        <a href="{{ route('admin.shops.edit', $shop) }}" class="btn-outline btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                        <form method="POST" action="{{ route('admin.shops.toggle', $shop) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn-sm {{ $shop->is_active ? 'btn-danger' : 'btn-success' }}"
                                    title="{{ $shop->is_active ? 'Deactivate (blocks its login)' : 'Activate' }}"
                                    onclick="return confirm('{{ $shop->is_active ? 'Deactivate this shop? Its login will be blocked.' : 'Activate this shop?' }}')">
                                <i class="fas {{ $shop->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center py-12 text-gray-400">
                No sub shops yet. Create one, then send it stock via a transfer.
            </td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    @if($shops->hasPages())
    <div class="p-4 border-t border-gray-200">{{ $shops->links() }}</div>
    @endif
</div>
@endsection
