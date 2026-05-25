@extends('layouts.admin')
@section('title', 'Run Migrations')
@section('page-title', 'Run Migrations')

@section('content')
<div class="max-w-2xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.dashboard') }}" class="btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-xl font-bold text-gray-900">Database Migrations</h1>
    </div>

    @if(count($ran) > 0)
    <div class="card p-6 mb-4 border-l-4 border-green-500">
        <div class="flex items-center gap-2 mb-3">
            <i class="fas fa-check-circle text-green-500 text-xl"></i>
            <h2 class="font-semibold text-green-800">{{ count($ran) }} migration(s) ran successfully</h2>
        </div>
        <ul class="space-y-1">
            @foreach($ran as $migration)
            <li class="font-mono text-sm text-gray-700 bg-green-50 rounded px-3 py-1">{{ $migration }}</li>
            @endforeach
        </ul>
    </div>
    @else
    <div class="card p-6 mb-4 border-l-4 border-blue-400">
        <div class="flex items-center gap-2">
            <i class="fas fa-info-circle text-blue-400 text-xl"></i>
            <h2 class="font-semibold text-blue-800">No pending migrations — database is already up to date.</h2>
        </div>
    </div>
    @endif

    <div class="card p-5">
        <h3 class="font-semibold text-gray-700 mb-2 text-sm uppercase tracking-wide">Artisan Output</h3>
        <pre class="text-xs text-gray-600 bg-gray-50 rounded p-3 overflow-auto whitespace-pre-wrap">{{ strip_tags($output) ?: 'Nothing to migrate.' }}</pre>
    </div>

    <div class="mt-4 flex gap-3">
        <a href="{{ route('admin.run_migrations') }}" class="btn-primary">
            <i class="fas fa-sync mr-2"></i> Run Again
        </a>
        <a href="{{ route('admin.dashboard') }}" class="btn-outline">Back to Dashboard</a>
    </div>
</div>
@endsection
