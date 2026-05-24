@extends('layouts.admin')
@section('title', 'Salesmen')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Salesmen</h1>
    <a href="{{ route('admin.salesmen.create') }}" class="btn-primary"><i class="fas fa-plus mr-2"></i> Add Salesman</a>
</div>

<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Password</th>
                <th>Permissions</th>
                <th>Last Login</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($salesmen as $s)
            <tr>
                <td>
                    <div class="font-semibold text-gray-800">{{ $s->name }}</div>
                </td>
                <td class="text-sm text-gray-600">{{ $s->email }}</td>
                <td class="text-sm text-gray-600 font-mono">{{ $s->phone ?? '—' }}</td>
                <td x-data="{ show: false, pwd: {{ json_encode($s->password_plain ?? '') }} }">
                    @if($s->password_plain)
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs text-gray-700" x-text="show ? pwd : '••••••••'"></span>
                            <button @click="show = !show" class="text-gray-400 hover:text-gray-700 text-xs" :title="show ? 'Hide' : 'Show'">
                                <i :class="show ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                            </button>
                        </div>
                    @else
                        <span class="text-gray-300 text-xs italic">Not stored</span>
                    @endif
                </td>
                <td>
                    <div class="flex flex-wrap gap-1">
                        @foreach($s->permissions->take(3) as $perm)
                        <span class="badge bg-blue-50 text-blue-600 text-xs">{{ $perm->name }}</span>
                        @endforeach
                        @if($s->permissions->count() > 3)
                        <span class="badge bg-gray-100 text-gray-500 text-xs">+{{ $s->permissions->count() - 3 }}</span>
                        @endif
                    </div>
                </td>
                <td class="text-xs text-gray-500">
                    @if($s->loginLogs->first())
                        {{ $s->loginLogs->first()->logged_in_at->diffForHumans() }}
                    @else <span class="text-gray-300">Never</span> @endif
                </td>
                <td>
                    @if($s->is_active)
                        <span class="badge bg-green-100 text-green-700">Active</span>
                    @else
                        <span class="badge bg-red-100 text-red-700">Inactive</span>
                    @endif
                </td>
                <td class="text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.salesmen.show', $s) }}" class="btn-outline btn-sm"><i class="fas fa-eye"></i></a>
                        <a href="{{ route('admin.salesmen.edit', $s) }}" class="btn-outline btn-sm"><i class="fas fa-edit"></i></a>
                        <form method="POST" action="{{ route('admin.salesmen.toggle', $s) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn-sm {{ $s->is_active ? 'btn-danger' : 'btn-success' }}"
                                    title="{{ $s->is_active ? 'Deactivate' : 'Activate' }}">
                                <i class="fas {{ $s->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center py-12 text-gray-400">No salesmen added yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
