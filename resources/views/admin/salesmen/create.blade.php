@extends('layouts.admin')
@section('title', 'Add Salesman')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.salesmen.index') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Add Salesman</h1>
</div>

<div class="max-w-2xl">
    <form method="POST" action="{{ route('admin.salesmen.store') }}">
        @csrf
        <div class="space-y-5">
            <div class="card p-6 space-y-4">
                <h2 class="font-semibold text-gray-800">Account Details</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-input @error('name') border-red-500 @enderror" required>
                        @error('name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-input @error('email') border-red-500 @enderror" required>
                        @error('email') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-input @error('password') border-red-500 @enderror" required>
                        @error('password') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Confirm Password *</label>
                        <input type="password" name="password_confirmation" class="form-input" required>
                    </div>
                </div>
            </div>

            {{-- Section Access --}}
            @if($sections->count())
            <div class="card p-6">
                <h2 class="font-semibold text-gray-800 mb-1">Section Access</h2>
                <p class="text-sm text-gray-500 mb-4">Which shop sections can this salesman sell on POS? Leave all unchecked to allow access to everything.</p>
                <div class="grid grid-cols-2 gap-3">
                    @foreach($sections as $section)
                    <label class="flex items-center gap-3 p-4 border border-gray-200 rounded-xl cursor-pointer hover:border-primary-300 hover:bg-primary-50 transition-all">
                        <input type="checkbox" name="section_ids[]" value="{{ $section->id }}"
                               {{ in_array($section->id, old('section_ids', [])) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary-600 rounded">
                        <div>
                            <div class="text-sm font-semibold text-gray-800">{{ $section->name }}</div>
                            <div class="text-xs text-gray-400">{{ $section->categories_count ?? 0 }} categories</div>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="card p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Permissions</h2>
                <p class="text-sm text-gray-500 mb-4">Select what this salesman can access:</p>
                <div class="grid grid-cols-2 gap-3">
                    @foreach($permissions as $perm)
                    <label class="flex items-start gap-2 p-3 border border-gray-200 rounded-xl cursor-pointer hover:border-primary-300 hover:bg-primary-50 transition-all">
                        <input type="checkbox" name="permissions[]" value="{{ $perm->name }}"
                               {{ in_array($perm->name, old('permissions', [])) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary-600 rounded mt-0.5">
                        <div>
                            <div class="text-sm font-medium text-gray-800">{{ ucwords(str_replace(['.', '_'], ' ', $perm->name)) }}</div>
                            <div class="text-xs text-gray-400">{{ $perm->name }}</div>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary">Create Salesman</button>
                <a href="{{ route('admin.salesmen.index') }}" class="btn-outline">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
