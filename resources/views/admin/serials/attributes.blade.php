@extends('layouts.admin')
@section('title', 'Serial Attribute Fields')

@section('content')
@php $rPrefix = auth()->user()->panelPrefix(); @endphp

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-900">
            <i class="fas fa-tags text-indigo-500 mr-2"></i>Serial Attribute Fields
        </h1>
        <p class="text-sm text-gray-500 mt-0.5">
            Define custom fields shown when entering serial / IMEI numbers (e.g. PTA Status, Memory, Storage).
            To show one as a price-selector on the store, go to the <strong>Product page</strong> and choose it under
            <em>Store Attribute</em>.
        </p>
    </div>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm flex items-start gap-2">
    <i class="fas fa-check-circle mt-0.5 shrink-0"></i><span>{{ session('success') }}</span>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Existing definitions --}}
    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-gray-800">Defined Fields</h2>
            </div>
            @if($definitions->isEmpty())
            <div class="card-body text-center py-10 text-gray-400">
                <i class="fas fa-tags text-3xl mb-2 block"></i>
                No attribute fields defined yet. Create your first one →
            </div>
            @else
            <div class="divide-y divide-gray-100">
                @foreach($definitions as $def)
                <div class="p-5" x-data="{ editing: false, name: '{{ addslashes($def->name) }}', options: '{{ addslashes(implode(', ', $def->options)) }}', active: {{ $def->is_active ? 'true' : 'false' }} }">

                    {{-- View mode --}}
                    <div x-show="!editing" class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-semibold text-gray-800">{{ $def->name }}</span>
                                @if(!$def->is_active)
                                <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">Inactive</span>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                @foreach($def->options as $opt)
                                <span class="text-xs bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-full px-2.5 py-0.5 font-medium">
                                    {{ $opt }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button @click="editing = true" class="btn-outline btn-sm">
                                <i class="fas fa-edit mr-1"></i> Edit
                            </button>
                            <form method="POST" action="{{ route("{$rPrefix}.serials.attributes.destroy", $def) }}"
                                  onsubmit="return confirm('Delete attribute field \'{{ $def->name }}\'? This won\'t remove data already saved on existing serials.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger btn-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Edit mode --}}
                    <div x-show="editing" x-cloak>
                        <form method="POST" action="{{ route("{$rPrefix}.serials.attributes.update", $def) }}" class="space-y-3">
                            @csrf @method('PUT')
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="form-label text-sm">Field Name</label>
                                    <input type="text" name="name" x-model="name" class="form-input" required>
                                </div>
                                <div class="flex items-center gap-2 pt-5">
                                    <input type="checkbox" name="is_active" id="active_{{ $def->id }}" value="1"
                                           x-model="active" class="w-4 h-4 text-indigo-600 rounded">
                                    <label for="active_{{ $def->id }}" class="text-sm text-gray-700">Active</label>
                                </div>
                            </div>
                            <div>
                                <label class="form-label text-sm">Options <span class="text-gray-400 font-normal">(comma-separated)</span></label>
                                <input type="text" name="options" x-model="options" class="form-input font-mono text-sm"
                                       placeholder="New, Used, Refurbished" required>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="btn-primary btn-sm">
                                    <i class="fas fa-save mr-1"></i> Save
                                </button>
                                <button type="button" @click="editing = false" class="btn-outline btn-sm">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Add new field + hint --}}
    <div>
        <div class="card p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Add New Field</h2>

            @if($errors->any())
            <div class="mb-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-3 py-2 text-sm">
                @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route("{$rPrefix}.serials.attributes.store") }}" class="space-y-4">
                @csrf
                <div>
                    <label class="form-label">Field Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           placeholder="e.g. Memory, PTA Status, Condition"
                           class="form-input">
                    <p class="text-xs text-gray-400 mt-1">This label appears on every serial entry form</p>
                </div>
                <div>
                    <label class="form-label">Options <span class="text-red-500">*</span></label>
                    <input type="text" name="options" value="{{ old('options') }}" required
                           placeholder="6 GB, 8 GB, 12 GB"
                           class="form-input font-mono text-sm">
                    <p class="text-xs text-gray-400 mt-1">Comma-separated list of values</p>
                </div>
                <button type="submit" class="btn-primary w-full justify-center">
                    <i class="fas fa-plus mr-2"></i> Create Field
                </button>
            </form>
        </div>

        <div class="card p-5 mt-4 bg-indigo-50 border border-indigo-100">
            <h3 class="font-semibold text-indigo-800 text-sm mb-2">
                <i class="fas fa-lightbulb mr-1"></i> How per-product attributes work
            </h3>
            <div class="text-xs text-indigo-700 space-y-2 leading-relaxed">
                <p>Each product can show a <strong>different</strong> attribute on the store page:</p>
                <ul class="list-disc list-inside space-y-1 pl-1">
                    <li>iPhone → <em>Memory</em> (6 GB, 8 GB…)</li>
                    <li>Laptop → <em>Storage</em> (256 GB, 512 GB…)</li>
                    <li>Tablet → <em>RAM</em> (4 GB, 8 GB…)</li>
                </ul>
                <p class="pt-1">
                    Open a <strong>Product page</strong>, scroll to
                    <em>Store Attribute</em>, pick from the dropdown and save.
                </p>
            </div>
        </div>

        <div class="card p-5 mt-4 bg-gray-50 border border-gray-100">
            <h3 class="font-semibold text-gray-700 text-sm mb-2">
                <i class="fas fa-list mr-1"></i> Examples
            </h3>
            <div class="space-y-3 text-xs text-gray-600">
                <div>
                    <div class="font-semibold">PTA Status</div>
                    <div class="text-gray-400">PTA Approved, Non-PTA, Factory Unlocked, JV</div>
                </div>
                <div>
                    <div class="font-semibold">Condition</div>
                    <div class="text-gray-400">New, Open Box, Used, Refurbished</div>
                </div>
                <div>
                    <div class="font-semibold">Warranty</div>
                    <div class="text-gray-400">1 Year, 6 Months, No Warranty</div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
