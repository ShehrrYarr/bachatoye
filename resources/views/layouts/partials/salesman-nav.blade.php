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

@can('products.view')
<a href="{{ route('salesman.products.index') }}" class="sidebar-link {{ $is('salesman.products') ? 'active' : '' }}">
    <i class="fas fa-box"></i> Products
</a>
@endcan

@can('vendors.view')
<a href="{{ route('salesman.vendors.index') }}" class="sidebar-link {{ $is('salesman.vendors') ? 'active' : '' }}">
    <i class="fas fa-truck"></i> Vendors
</a>
@endcan

@can('sections.manage')
<a href="{{ route('salesman.sections.index') }}" class="sidebar-link {{ $is('salesman.sections') ? 'active' : '' }}">
    <i class="fas fa-layer-group"></i> Sections
</a>
@endcan

@can('categories.manage')
<a href="{{ route('salesman.categories.index') }}" class="sidebar-link {{ $is('salesman.categories') ? 'active' : '' }}">
    <i class="fas fa-sitemap"></i> Categories
</a>
@endcan

@can('brands.manage')
<a href="{{ route('salesman.brands.index') }}" class="sidebar-link {{ $is('salesman.brands') ? 'active' : '' }}">
    <i class="fas fa-tag"></i> Brands
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

@can('serials.lookup')
<a href="{{ route('salesman.serials.lookup') }}" class="sidebar-link {{ $is('salesman.serials.lookup') ? 'active' : '' }}">
    <i class="fas fa-search"></i> Serial Lookup
</a>
@endcan

@can('serials.manage_attributes')
<a href="{{ route('salesman.serials.attributes.index') }}" class="sidebar-link {{ $is('salesman.serials.attributes') ? 'active' : '' }}">
    <i class="fas fa-tags"></i> Serial Attributes
</a>
@endcan

@can('purchases.view')
<a href="{{ route('salesman.purchases.index') }}" class="sidebar-link {{ $is('salesman.purchases') ? 'active' : '' }}">
    <i class="fas fa-file-invoice-dollar"></i> Purchases
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
