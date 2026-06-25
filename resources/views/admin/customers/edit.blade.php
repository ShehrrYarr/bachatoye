@extends('layouts.admin')
@section('title', 'Edit Customer')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.customers.show', $customer) }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
    <h1 class="text-xl font-bold text-gray-900">Edit: {{ $customer->name }}</h1>
</div>

<div class="max-w-xl">
    <form method="POST" action="{{ route('admin.customers.update', $customer) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="card p-6 space-y-4">

            {{-- Photo upload --}}
            <div x-data="{
                    preview: '{{ $customer->photo ? Storage::url($customer->photo) : '' }}',
                    removed: false,
                    hasExisting: {{ $customer->photo ? 'true' : 'false' }},
                    handleFile(e) {
                        const file = e.target.files[0];
                        if (file) { this.preview = URL.createObjectURL(file); this.removed = false; }
                    },
                    removePhoto() { this.preview = ''; this.removed = true; }
                }" class="flex items-center gap-5">
                <div class="relative shrink-0">
                    <div class="w-20 h-20 rounded-full overflow-hidden bg-gray-100 border-2 border-dashed border-gray-300 flex items-center justify-center">
                        <template x-if="!preview">
                            <i class="fas fa-user text-3xl text-gray-300"></i>
                        </template>
                        <template x-if="preview">
                            <img :src="preview" class="w-full h-full object-cover">
                        </template>
                    </div>
                    {{-- Remove button (only shown when there is a photo) --}}
                    <template x-if="preview">
                        <button type="button" @click="removePhoto()"
                                class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors">
                            <i class="fas fa-times text-[9px]"></i>
                        </button>
                    </template>
                </div>
                <div class="flex-1">
                    <label class="form-label">Customer Photo <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input type="file" name="photo" accept="image/*"
                           class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 cursor-pointer"
                           @change="handleFile($event)">
                    <p class="form-hint">JPG, PNG or WebP · Max 2 MB · Leave blank to keep current</p>
                    @error('photo') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                {{-- Hidden flag sent when the user explicitly removes the photo --}}
                <input type="hidden" name="remove_photo" :value="removed ? '1' : '0'">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name', $customer->name) }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Phone *</label>
                    <input type="tel" name="phone" value="{{ old('phone', $customer->phone) }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="form-input">
                </div>
                <div class="col-span-2">
                    <label class="form-label">Address</label>
                    <textarea name="address" rows="2" class="form-textarea">{{ old('address', $customer->address) }}</textarea>
                </div>
                <div>
                    <label class="form-label">City</label>
                    <input type="text" name="city" value="{{ old('city', $customer->city) }}" class="form-input">
                </div>
            </div>
            <div class="flex items-start gap-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                <input type="hidden" name="khata_enabled" value="0">
                <input type="checkbox" name="khata_enabled" id="khata_enabled" value="1"
                       {{ old('khata_enabled', $customer->khata_enabled) ? 'checked' : '' }}
                       class="mt-0.5 w-4 h-4 text-amber-600 rounded border-amber-300 focus:ring-amber-500">
                <div>
                    <label for="khata_enabled" class="text-sm font-semibold text-gray-800 cursor-pointer">Enable Khata / Ledger</label>
                    <p class="text-xs text-gray-500 mt-0.5">Allow khata and partial payment and ledger entries for this customer.</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $customer->is_active) ? 'checked' : '' }} class="w-4 h-4 text-primary-600 rounded">
                <label for="is_active" class="text-sm font-medium text-gray-700 cursor-pointer">Active</label>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">Update Customer</button>
                <a href="{{ route('admin.customers.show', $customer) }}" class="btn-outline">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
