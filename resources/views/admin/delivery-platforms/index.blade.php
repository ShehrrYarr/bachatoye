@extends('layouts.admin')
@section('title', 'Delivery Platforms')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Delivery Platforms</h1>
    <a href="{{ route('admin.platform-payouts.index') }}" class="btn-outline btn-sm">
        <i class="fas fa-money-check-alt mr-1"></i> Platform Payouts
    </a>
</div>

@if(session('success'))
    <div class="alert-success mb-4">{{ session('success') }}</div>
@endif
@if($errors->has('error'))
    <div class="alert-error mb-4">{{ $errors->first('error') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Platform list --}}
    <div class="card">
        <div class="card-header">
            <h2 class="font-semibold text-gray-800">All Platforms</h2>
        </div>
        @forelse($platforms as $platform)
        <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100 last:border-0">
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-gray-800">{{ $platform->name }}</span>
                    @if(!$platform->active)
                        <span class="badge bg-gray-100 text-gray-500">Inactive</span>
                    @endif
                </div>
                @if($platform->contact)
                    <div class="text-xs text-gray-400 mt-0.5"><i class="fas fa-phone mr-1"></i>{{ $platform->contact }}</div>
                @endif
                @if($platform->notes)
                    <div class="text-xs text-gray-400 mt-0.5">{{ $platform->notes }}</div>
                @endif
            </div>
            <div class="flex gap-2 shrink-0">
                {{-- Toggle active --}}
                <form method="POST" action="{{ route('admin.delivery-platforms.update', $platform) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="name" value="{{ $platform->name }}">
                    <input type="hidden" name="active" value="{{ $platform->active ? 0 : 1 }}">
                    <button type="submit" class="btn-outline btn-sm text-xs
                        {{ $platform->active ? 'text-gray-500' : 'text-green-600 border-green-300' }}"
                        title="{{ $platform->active ? 'Deactivate' : 'Activate' }}">
                        <i class="fas {{ $platform->active ? 'fa-toggle-on text-green-500' : 'fa-toggle-off' }}"></i>
                    </button>
                </form>
                {{-- Edit trigger --}}
                <button onclick="openEdit({{ $platform->id }}, '{{ addslashes($platform->name) }}', '{{ addslashes($platform->contact ?? '') }}', '{{ addslashes($platform->notes ?? '') }}')"
                        class="btn-outline btn-sm">
                    <i class="fas fa-edit"></i>
                </button>
                {{-- Delete --}}
                <form method="POST" action="{{ route('admin.delivery-platforms.destroy', $platform) }}"
                      onsubmit="return confirm('Delete {{ $platform->name }}?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-outline btn-sm text-red-500 border-red-200 hover:bg-red-50">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>
        @empty
            <div class="text-center py-10 text-gray-400">
                <i class="fas fa-truck text-3xl mb-2"></i>
                <p class="text-sm">No delivery platforms yet. Add one →</p>
            </div>
        @endforelse
    </div>

    {{-- Add / Edit form --}}
    <div>
        {{-- Add form --}}
        <div class="card p-5 mb-4" id="addForm">
            <h2 class="font-semibold text-gray-800 mb-4">Add Platform</h2>
            <form method="POST" action="{{ route('admin.delivery-platforms.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="form-label">Platform Name *</label>
                    <input type="text" name="name" class="form-input @error('name') border-red-400 @enderror"
                           placeholder="e.g. Leopards, TCS, BlueEx" required value="{{ old('name') }}">
                    @error('name')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Contact / Account Manager</label>
                    <input type="text" name="contact" class="form-input" placeholder="Phone or name" value="{{ old('contact') }}">
                </div>
                <div>
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input" rows="2" placeholder="Payment terms, remittance cycle...">{{ old('notes') }}</textarea>
                </div>
                <button type="submit" class="btn-primary w-full justify-center">
                    <i class="fas fa-plus mr-2"></i> Add Platform
                </button>
            </form>
        </div>

        {{-- Edit form (hidden by default) --}}
        <div class="card p-5 hidden" id="editForm">
            <h2 class="font-semibold text-gray-800 mb-4">Edit Platform</h2>
            <form method="POST" id="editFormEl" class="space-y-3">
                @csrf @method('PATCH')
                <div>
                    <label class="form-label">Platform Name *</label>
                    <input type="text" name="name" id="editName" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Contact</label>
                    <input type="text" name="contact" id="editContact" class="form-input">
                </div>
                <div>
                    <label class="form-label">Notes</label>
                    <textarea name="notes" id="editNotes" class="form-input" rows="2"></textarea>
                </div>
                <input type="hidden" name="active" value="1">
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary flex-1 justify-center">Save Changes</button>
                    <button type="button" onclick="cancelEdit()" class="btn-outline btn-sm">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEdit(id, name, contact, notes) {
    document.getElementById('addForm').classList.add('hidden');
    document.getElementById('editForm').classList.remove('hidden');
    document.getElementById('editFormEl').action = `/admin/delivery-platforms/${id}`;
    document.getElementById('editName').value    = name;
    document.getElementById('editContact').value = contact;
    document.getElementById('editNotes').value   = notes;
}
function cancelEdit() {
    document.getElementById('editForm').classList.add('hidden');
    document.getElementById('addForm').classList.remove('hidden');
}
</script>
@endsection
