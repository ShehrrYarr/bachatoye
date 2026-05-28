@php
    $current = request()->route()->getName();
    $is = fn(string $prefix) => str_starts_with($current ?? '', $prefix);
@endphp

<a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ $is('admin.dashboard') ? 'active' : '' }}">
    <i class="fas fa-chart-pie"></i> Dashboard
</a>

<div class="pt-3 pb-1 px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Catalogue</div>

<a href="{{ route('admin.products.index') }}" class="sidebar-link {{ $is('admin.products') ? 'active' : '' }}">
    <i class="fas fa-box-open"></i> Products
</a>
<a href="{{ route('admin.sections.index') }}" class="sidebar-link {{ $is('admin.sections') ? 'active' : '' }}">
    <i class="fas fa-layer-group"></i> Sections
</a>
<a href="{{ route('admin.categories.index') }}" class="sidebar-link {{ $is('admin.categories') ? 'active' : '' }}">
    <i class="fas fa-tags"></i> Categories
</a>
<a href="{{ route('admin.brands.index') }}" class="sidebar-link {{ $is('admin.brands') ? 'active' : '' }}">
    <i class="fas fa-trademark"></i> Brands
</a>
<a href="{{ route('admin.inventory.index') }}" class="sidebar-link {{ $is('admin.inventory') ? 'active' : '' }}">
    <i class="fas fa-warehouse"></i> Inventory
</a>
<a href="{{ route('admin.deals.index') }}" class="sidebar-link {{ $is('admin.deals') ? 'active' : '' }}">
    <i class="fas fa-percent"></i> Deals
</a>
<a href="{{ route('admin.coupons.index') }}" class="sidebar-link {{ $is('admin.coupons') ? 'active' : '' }}">
    <i class="fas fa-ticket-alt"></i> Coupons
</a>
<a href="{{ route('admin.banners.index') }}" class="sidebar-link {{ $is('admin.banners') ? 'active' : '' }}">
    <i class="fas fa-image"></i> Banners
</a>

<div class="pt-3 pb-1 px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Sales</div>

<a href="{{ route('admin.orders.index') }}" class="sidebar-link {{ $is('admin.orders') ? 'active' : '' }}">
    <i class="fas fa-shopping-bag"></i> Orders
</a>
<a href="{{ route('admin.returns.index') }}" class="sidebar-link {{ $is('admin.returns') ? 'active' : '' }}">
    <i class="fas fa-undo-alt"></i> Returns
</a>
<a href="{{ route('admin.customers.index') }}" class="sidebar-link {{ $is('admin.customers') ? 'active' : '' }}">
    <i class="fas fa-users"></i> Customers
</a>
<a href="{{ route('pos.index') }}" class="sidebar-link">
    <i class="fas fa-cash-register"></i> Point of Sale
</a>

<div class="pt-3 pb-1 px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Purchasing</div>

<a href="{{ route('admin.vendors.index') }}" class="sidebar-link {{ $is('admin.vendors') ? 'active' : '' }}">
    <i class="fas fa-truck-loading"></i> Vendors
</a>
<a href="{{ route('admin.purchases.index') }}" class="sidebar-link {{ $is('admin.purchases') ? 'active' : '' }}">
    <i class="fas fa-file-invoice-dollar"></i> Purchases
</a>

<div class="pt-3 pb-1 px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Finance</div>

<a href="{{ route('admin.expenses.index') }}" class="sidebar-link {{ $is('admin.expenses') ? 'active' : '' }}">
    <i class="fas fa-receipt"></i> Expenses
</a>

<div class="pt-3 pb-1 px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Reports</div>

<a href="{{ route('admin.reports.sales') }}" class="sidebar-link {{ $is('admin.reports.sales') ? 'active' : '' }}">
    <i class="fas fa-chart-bar"></i> Sales Report
</a>
<a href="{{ route('admin.reports.profit_loss') }}" class="sidebar-link {{ $is('admin.reports.profit_loss') ? 'active' : '' }}">
    <i class="fas fa-balance-scale"></i> Profit & Loss
</a>
<a href="{{ route('admin.reports.inventory') }}" class="sidebar-link {{ $is('admin.reports.inventory') ? 'active' : '' }}">
    <i class="fas fa-boxes"></i> Inventory Report
</a>
<a href="{{ route('admin.reports.salesman_performance') }}" class="sidebar-link {{ $is('admin.reports.salesman_performance') ? 'active' : '' }}">
    <i class="fas fa-user-chart"></i> Salesman Stats
</a>
<a href="{{ route('admin.reports.purchases') }}" class="sidebar-link {{ $is('admin.reports.purchases') ? 'active' : '' }}">
    <i class="fas fa-file-invoice-dollar"></i> Purchase Report
</a>

<div class="pt-3 pb-1 px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Admin</div>

<a href="{{ route('admin.salesmen.index') }}" class="sidebar-link {{ $is('admin.salesmen') ? 'active' : '' }}">
    <i class="fas fa-user-tie"></i> Salesmen
</a>
<a href="{{ route('admin.bank-accounts.index') }}" class="sidebar-link {{ $is('admin.bank-accounts') ? 'active' : '' }}">
    <i class="fas fa-university"></i> Bank Accounts
</a>
<a href="{{ route('admin.settings.index') }}" class="sidebar-link {{ $is('admin.settings') ? 'active' : '' }}">
    <i class="fas fa-cog"></i> Settings
</a>
