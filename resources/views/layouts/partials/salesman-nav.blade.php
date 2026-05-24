@php $is = fn(string $p) => str_starts_with(request()->route()->getName() ?? '', $p); @endphp

<a href="{{ route('salesman.dashboard') }}" class="sidebar-link {{ $is('salesman.dashboard') ? 'active' : '' }}">
    <i class="fas fa-chart-pie"></i> Dashboard
</a>

@can('pos.access')
<a href="{{ route('pos.index') }}" class="sidebar-link">
    <i class="fas fa-cash-register"></i> Point of Sale
</a>
@endcan

@can('orders.view')
<a href="{{ route('salesman.orders.index') }}" class="sidebar-link {{ $is('salesman.orders') ? 'active' : '' }}">
    <i class="fas fa-shopping-bag"></i> Orders
</a>
@endcan

@can('customers.view')
<a href="{{ route('salesman.customers.index') }}" class="sidebar-link {{ $is('salesman.customers') ? 'active' : '' }}">
    <i class="fas fa-users"></i> Customers
</a>
@endcan

@can('inventory.view')
<a href="{{ route('salesman.inventory.index') }}" class="sidebar-link {{ $is('salesman.inventory') ? 'active' : '' }}">
    <i class="fas fa-warehouse"></i> Inventory
</a>
@endcan

@can('expenses.view')
<a href="{{ route('salesman.expenses.index') }}" class="sidebar-link {{ $is('salesman.expenses') ? 'active' : '' }}">
    <i class="fas fa-receipt"></i> Expenses
</a>
@endcan

@can('reports.view')
<a href="{{ route('salesman.reports.sales') }}" class="sidebar-link {{ $is('salesman.reports') ? 'active' : '' }}">
    <i class="fas fa-chart-bar"></i> Sales Report
</a>
@endcan
