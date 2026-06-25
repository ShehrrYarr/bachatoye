<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    {{-- Avatar + name --}}
    <div class="p-6 border-b border-gray-100"
         style="background: var(--app-gradient, linear-gradient(135deg, #be123c 0%, #881337 100%))">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                <i class="fas fa-user text-white text-lg"></i>
            </div>
            <div class="min-w-0">
                <div class="font-bold text-white truncate">{{ $customer->name }}</div>
                <div class="text-xs text-red-100 truncate">{{ $account->email ?: $account->phone }}</div>
            </div>
        </div>
    </div>
    {{-- Nav links --}}
    <nav class="p-3 space-y-0.5">
        <a href="{{ route('account.dashboard') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                  {{ request()->routeIs('account.dashboard') ? 'bg-primary-50 text-primary-700' : 'text-gray-700 hover:bg-gray-50' }}">
            <i class="fas fa-home w-4 text-center {{ request()->routeIs('account.dashboard') ? 'text-primary-600' : 'text-gray-400' }}"></i>
            Dashboard
        </a>
        <a href="{{ route('account.orders') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                  {{ request()->routeIs('account.orders*') ? 'bg-primary-50 text-primary-700' : 'text-gray-700 hover:bg-gray-50' }}">
            <i class="fas fa-box w-4 text-center {{ request()->routeIs('account.orders*') ? 'text-primary-600' : 'text-gray-400' }}"></i>
            My Orders
        </a>
        @if($customer->source === 'pos')
        <a href="{{ route('account.returns') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                  {{ request()->routeIs('account.returns') ? 'bg-primary-50 text-primary-700' : 'text-gray-700 hover:bg-gray-50' }}">
            <i class="fas fa-undo w-4 text-center {{ request()->routeIs('account.returns') ? 'text-primary-600' : 'text-gray-400' }}"></i>
            Returns
        </a>
        <a href="{{ route('account.ledger') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                  {{ request()->routeIs('account.ledger') ? 'bg-amber-50 text-amber-700' : 'text-gray-700 hover:bg-gray-50' }}">
            <i class="fas fa-book w-4 text-center {{ request()->routeIs('account.ledger') ? 'text-amber-600' : 'text-gray-400' }}"></i>
            Khata / Ledger
        </a>
        @endif
        <a href="{{ route('account.profile') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                  {{ request()->routeIs('account.profile') ? 'bg-primary-50 text-primary-700' : 'text-gray-700 hover:bg-gray-50' }}">
            <i class="fas fa-user-edit w-4 text-center {{ request()->routeIs('account.profile') ? 'text-primary-600' : 'text-gray-400' }}"></i>
            Edit Profile
        </a>
        <div class="pt-2 mt-2 border-t border-gray-100">
            <form method="POST" action="{{ route('account.logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                    <i class="fas fa-sign-out-alt w-4 text-center"></i>
                    Logout
                </button>
            </form>
        </div>
    </nav>
</div>
