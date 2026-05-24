@extends('layouts.admin')
@section('title', 'Expense Categories')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-900">Expense Categories</h1>
    <a href="{{ route('admin.expenses.index') }}" class="btn-outline btn-sm">
        <i class="fas fa-arrow-left mr-1"></i> Back to Expenses
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Add / Edit Form --}}
    <div class="card p-5" x-data="{ editing: null, name: '', color: '#6366f1' }">
        <h2 class="font-semibold text-gray-800 mb-4" x-text="editing ? 'Edit Category' : 'Add Category'"></h2>

        {{-- Add form --}}
        <form x-show="!editing" method="POST" action="{{ route('admin.expense-categories.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="form-label">Category Name *</label>
                <input type="text" name="name" class="form-input @error('name') border-red-500 @enderror"
                       placeholder="e.g. Utilities, Rent, Staff" required>
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label">Color</label>
                <input type="color" name="color" value="#6366f1" class="h-10 w-20 rounded-lg border border-gray-300 cursor-pointer p-1">
            </div>
            <button type="submit" class="btn-primary">
                <i class="fas fa-plus mr-2"></i> Add Category
            </button>
        </form>

        {{-- Edit forms (one per category, shown via Alpine) --}}
        @foreach($categories as $cat)
        <form x-show="editing === {{ $cat->id }}" method="POST"
              action="{{ route('admin.expense-categories.update', $cat) }}" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="form-label">Category Name *</label>
                <input type="text" name="name" value="{{ $cat->name }}" class="form-input" required>
            </div>
            <div>
                <label class="form-label">Color</label>
                <input type="color" name="color" value="{{ $cat->color ?? '#6366f1' }}" class="h-10 w-20 rounded-lg border border-gray-300 cursor-pointer p-1">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary">Save Changes</button>
                <button type="button" @click="editing = null" class="btn-outline">Cancel</button>
            </div>
        </form>
        @endforeach
    </div>

    {{-- Category list --}}
    <div class="card overflow-hidden">
        <div class="card-header"><h2 class="font-semibold text-gray-800">All Categories</h2></div>
        <table class="data-table">
            <thead>
                <tr><th>Name</th><th class="text-center">Expenses</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                <tr>
                    <td>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full shrink-0" style="background: {{ $cat->color ?? '#6366f1' }}"></span>
                            <span class="font-medium text-gray-800">{{ $cat->name }}</span>
                        </div>
                    </td>
                    <td class="text-center text-sm text-gray-500">{{ $cat->expenses_count }}</td>
                    <td class="text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button @click="editing = {{ $cat->id }}" class="btn-outline btn-sm">
                                <i class="fas fa-edit"></i>
                            </button>
                            @if($cat->expenses_count == 0)
                            <form method="POST" action="{{ route('admin.expense-categories.destroy', $cat) }}"
                                  onsubmit="return confirm('Delete this category?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center py-8 text-gray-400">No categories yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
