@extends('layouts.superadmin')
@section('title', 'Staff & Logins')

@section('content')
<div x-data="{ resetUser: null }">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold text-gray-900">Staff Accounts & Login History</h1>
        <span class="text-sm text-gray-500">{{ $staff->count() }} account(s)</span>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Password</th>
                    <th>Last Login</th>
                    <th>Last Logout</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staff as $s)
                <tr>
                    <td class="font-semibold text-gray-800">{{ $s->name }}</td>
                    <td class="text-sm text-gray-600">{{ $s->email }}</td>
                    <td>
                        @php
                            $roleName = $s->hasRole('admin') ? 'Admin' : ($s->hasRole('subshop') ? 'Shop: ' . ($s->shop?->name ?? '—') : 'Salesman');
                            $roleColor = $s->hasRole('admin') ? 'bg-purple-100 text-purple-700' : ($s->hasRole('subshop') ? 'bg-blue-100 text-blue-700' : 'bg-teal-100 text-teal-700');
                        @endphp
                        <span class="badge {{ $roleColor }}">{{ $roleName }}</span>
                    </td>
                    <td x-data="{ show: false, pwd: {{ json_encode($s->password_plain ?? '') }} }">
                        @if($s->password_plain)
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs text-gray-700" x-text="show ? pwd : '••••••••'"></span>
                                <button @click="show = !show" class="text-gray-400 hover:text-gray-700 text-xs" :title="show ? 'Hide' : 'Show'">
                                    <i :class="show ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                </button>
                            </div>
                        @else
                            <span class="text-gray-300 text-xs italic">Not available — reset to set one</span>
                        @endif
                    </td>
                    <td class="text-xs text-gray-500">
                        @if($s->latestLogin)
                            {{ $s->latestLogin->logged_in_at->format('d M Y, H:i') }}
                        @else
                            <span class="text-gray-300">Never</span>
                        @endif
                    </td>
                    <td class="text-xs text-gray-500">
                        @if($s->latestLogin?->logged_out_at)
                            {{ $s->latestLogin->logged_out_at->format('d M Y, H:i') }}
                        @elseif($s->latestLogin)
                            <span class="badge bg-green-100 text-green-700">Still Active</span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td>
                        @if($s->is_active)
                            <span class="badge bg-green-100 text-green-700">Active</span>
                        @else
                            <span class="badge bg-red-100 text-red-700">Inactive</span>
                        @endif
                    </td>
                    <td class="text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('superadmin.staff.history', $s) }}" class="btn-outline btn-sm" title="Full login history">
                                <i class="fas fa-history"></i>
                            </a>
                            <button type="button" class="btn-outline btn-sm" title="Reset password"
                                    @click="resetUser = { id: {{ $s->id }}, name: {{ json_encode($s->name) }} }">
                                <i class="fas fa-key"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-12 text-gray-400">No staff accounts found.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    {{-- Reset password modal --}}
    <div x-show="resetUser" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         @keydown.escape.window="resetUser = null"
         @click.self="resetUser = null">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-900">Reset Password</h3>
                <button type="button" @click="resetUser = null" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>
            <form method="POST" :action="resetUser ? '{{ url('superadmin/staff') }}/' + resetUser.id + '/reset-password' : '#'" class="p-6 space-y-4">
                @csrf
                <p class="text-sm text-gray-600">New password for <span class="font-semibold" x-text="resetUser?.name"></span>.</p>
                <div>
                    <label class="form-label">New Password *</label>
                    <input type="text" name="password" class="form-input" minlength="8" required autocomplete="off">
                </div>
                <div>
                    <label class="form-label">Confirm Password *</label>
                    <input type="text" name="password_confirmation" class="form-input" minlength="8" required autocomplete="off">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="resetUser = null" class="btn-outline flex-1 justify-center">Cancel</button>
                    <button type="submit" class="btn-primary flex-1 justify-center">Reset</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
