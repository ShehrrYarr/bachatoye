{{-- Themed account sidebar — same routes and conditions as the default one. --}}
<div class="t-card overflow-hidden">
    <div class="p-6" style="background: var(--app-gradient);">
        <div class="flex items-center gap-3">
            <span class="w-11 h-11 rounded-full flex items-center justify-center shrink-0" style="background:rgba(255,255,255,.2);">
                <i class="fas fa-user text-white text-lg"></i>
            </span>
            <div class="min-w-0">
                <div class="font-bold text-white truncate">{{ $customer->name }}</div>
                <div class="text-xs truncate" style="color:rgba(255,255,255,.8);">{{ $account->email ?: $account->phone }}</div>
            </div>
        </div>
    </div>

    <nav class="p-3 space-y-0.5">
        <a href="{{ route('account.dashboard') }}" class="ta-link {{ request()->routeIs('account.dashboard') ? 'ta-link-on' : '' }}">
            <i class="fas fa-house w-4 text-center"></i> Dashboard
        </a>
        <a href="{{ route('account.orders') }}" class="ta-link {{ request()->routeIs('account.orders*') ? 'ta-link-on' : '' }}">
            <i class="fas fa-box w-4 text-center"></i> My Orders
        </a>

        @if($customer->source === 'pos')
        <a href="{{ route('account.returns') }}" class="ta-link {{ request()->routeIs('account.returns') ? 'ta-link-on' : '' }}">
            <i class="fas fa-rotate-left w-4 text-center"></i> Returns
        </a>
        <a href="{{ route('account.ledger') }}" class="ta-link {{ request()->routeIs('account.ledger') ? 'ta-link-on' : '' }}">
            <i class="fas fa-book w-4 text-center"></i> Khata / Ledger
        </a>
        @endif

        <a href="{{ route('account.profile') }}" class="ta-link {{ request()->routeIs('account.profile') ? 'ta-link-on' : '' }}">
            <i class="fas fa-{{ $customer->source === 'pos' ? 'user' : 'user-pen' }} w-4 text-center"></i>
            {{ $customer->source === 'pos' ? 'View Profile' : 'Edit Profile' }}
        </a>

        <div class="pt-2 mt-2" style="border-top:1px solid var(--t-border);">
            <form method="POST" action="{{ route('account.logout') }}">
                @csrf
                <button type="submit" class="ta-link w-full" style="color:#ef4444;">
                    <i class="fas fa-arrow-right-from-bracket w-4 text-center"></i> Logout
                </button>
            </form>
        </div>
    </nav>
</div>

@once
@push('styles')
<style>
    .ta-link {
        display: flex; align-items: center; gap: .75rem;
        padding: .625rem .75rem;
        border-radius: var(--t-radius-sm);
        font-size: .875rem; font-weight: 600;
        color: var(--t-muted);
        transition: background .16s ease, color .16s ease;
    }
    .ta-link:hover { background: var(--t-surface-2); color: var(--t-text); }
    .ta-link-on {
        background: rgb(var(--t-accent-rgb) / .12);
        color: var(--t-accent);
    }
</style>
@endpush
@endonce
