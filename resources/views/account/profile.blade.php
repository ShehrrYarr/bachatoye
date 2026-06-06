@extends('layouts.ecom')
@section('title', 'Edit Profile')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-10">
    <div class="flex flex-col lg:flex-row gap-6">

        <aside class="lg:w-64 shrink-0">
            @include('account._sidebar')
        </aside>

        <main class="flex-1 min-w-0 space-y-5">

            @if(session('success'))
            <div class="flex items-center gap-2 bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-xl">
                <i class="fas fa-check-circle shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
            @endif

            <form method="POST" action="{{ route('account.profile.update') }}" class="space-y-5">
                @csrf

                {{-- Personal Info --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="font-semibold text-gray-900">Personal Information</h2>
                    </div>
                    <div class="p-6 space-y-5">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
                                <input type="text" name="name" value="{{ old('name', $customer->name) }}" required
                                       class="w-full px-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500
                                              {{ $errors->has('name') ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-gray-50' }}">
                                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone Number</label>
                                <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" required
                                       class="w-full px-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500
                                              {{ $errors->has('phone') ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-gray-50' }}">
                                @error('phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
                            <input type="email" value="{{ $account->email }}" disabled
                                   class="w-full px-4 py-2.5 border border-gray-200 bg-gray-100 rounded-xl text-sm text-gray-500 cursor-not-allowed">
                            <p class="text-xs text-gray-400 mt-1">Email cannot be changed.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Address</label>
                                <input type="text" name="address" value="{{ old('address', $customer->address) }}"
                                       class="w-full px-4 py-2.5 border border-gray-300 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">City</label>
                                <input type="text" name="city" value="{{ old('city', $customer->city) }}"
                                       class="w-full px-4 py-2.5 border border-gray-300 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Change Password --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="font-semibold text-gray-900">Change Password</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Leave blank to keep your current password.</p>
                    </div>
                    <div class="p-6 space-y-5">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Current Password</label>
                            <input type="password" name="current_password"
                                   class="w-full px-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500
                                          {{ $errors->has('current_password') ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-gray-50' }}">
                            @error('current_password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
                                <input type="password" name="new_password"
                                       class="w-full px-4 py-2.5 border border-gray-300 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm New Password</label>
                                <input type="password" name="new_password_confirmation"
                                       class="w-full px-4 py-2.5 border border-gray-300 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                            </div>
                        </div>

                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="px-8 py-2.5 rounded-xl text-white font-semibold text-sm transition-opacity hover:opacity-90"
                            style="background: var(--app-gradient, linear-gradient(135deg, #be123c 0%, #881337 100%))">
                        Save Changes
                    </button>
                </div>

            </form>
        </main>
    </div>
</div>
@endsection
