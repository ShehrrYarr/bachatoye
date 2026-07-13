@php $is = fn(string $p) => str_starts_with(request()->route()->getName() ?? '', $p); @endphp

<a href="{{ route('shop.dashboard') }}" class="sidebar-link {{ $is('shop.dashboard') ? 'active' : '' }}">
    <i class="fas fa-chart-pie"></i> Dashboard
</a>

@can('pos.access')
<a href="{{ route('pos.index') }}" class="sidebar-link">
    <i class="fas fa-cash-register"></i> Point of Sale
</a>
@endcan

@can('customers.view')
<a href="{{ route('shop.customers.index') }}" class="sidebar-link {{ $is('shop.customers') ? 'active' : '' }}">
    <i class="fas fa-users"></i> Customers & Khata
</a>
@endcan

@can('accounts.view')
<a href="{{ route('shop.bank-accounts.index') }}" class="sidebar-link {{ $is('shop.bank-accounts') ? 'active' : '' }}">
    <i class="fas fa-university"></i> Banks & Cash
</a>
@endcan

@can('expenses.view')
<a href="{{ route('shop.expenses.index') }}" class="sidebar-link {{ $is('shop.expenses') ? 'active' : '' }}">
    <i class="fas fa-receipt"></i> Expenses
</a>
@endcan

@can('inventory.view')
<a href="{{ route('shop.inventory.index') }}" class="sidebar-link {{ $is('shop.inventory') ? 'active' : '' }}">
    <i class="fas fa-warehouse"></i> Stock
</a>
@endcan
