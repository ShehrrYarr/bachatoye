<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Roles
        $admin    = Role::firstOrCreate(['name' => 'admin']);
        $salesman = Role::firstOrCreate(['name' => 'salesman']);
        $subshop  = Role::firstOrCreate(['name' => 'subshop']);

        // Permissions
        $permissions = [
            'pos.access',
            'pos.edit_sale',
            'pos.delete_sale',
            'pos.process_returns',
            'orders.view',
            'orders.manage',
            'products.view',
            'products.manage',
            'products.view_cost',
            'inventory.view',
            'inventory.adjust',
            'customers.view',
            'customers.manage',
            'accounts.view',
            'accounts.manage',
            'expenses.view',
            'expenses.manage',
            'expenses.edit',
            'expenses.delete',
            'purchases.view',
            'purchases.manage',
            'vendors.view',
            'vendors.manage',
            'sections.manage',
            'categories.manage',
            'brands.manage',
            'reports.view',
            'deals.manage',
            'banners.manage',
            'users.manage',
            'settings.manage',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Admin gets all permissions
        $admin->syncPermissions($permissions);

        // Salesman gets basic permissions by default
        $salesman->syncPermissions([
            'pos.access',
            'orders.view',
            'products.view',
            'inventory.view',
            'customers.view',
            'accounts.view',
            'accounts.manage',
            'vendors.view',
            'vendors.manage',
            'sections.manage',
            'categories.manage',
            'brands.manage',
        ]);

        // Sub shop login: POS (incl. own-shop edit/delete) + mini back-office
        $subshop->syncPermissions([
            'pos.access',
            'pos.edit_sale',
            'pos.delete_sale',
            'pos.process_returns',
            'customers.view',
            'customers.manage',
            'accounts.view',
            'accounts.manage',
            'expenses.view',
            'expenses.manage',
            'expenses.edit',
            'expenses.delete',
            'inventory.view',
        ]);

        // Create admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@mobilehub.com'],
            [
                'name'      => 'Super Admin',
                'password'  => bcrypt('admin123'),
                'phone'     => '03001234567',
                'is_active' => true,
            ]
        );
        $adminUser->assignRole('admin');

        // Default settings
        Setting::setMany([
            'shop_name'           => 'MobileHub',
            'shop_tagline'        => 'Your One-Stop Mobile Store',
            'shop_phone'          => '03001234567',
            'shop_email'          => 'info@mobilehub.com',
            'shop_address'        => 'Lahore, Pakistan',
            'currency_symbol'     => 'Rs.',
            'receipt_header'      => 'MobileHub - Thank you for shopping!',
            'receipt_footer'      => 'Exchange within 7 days with receipt.',
            'delivery_charge'     => '150',
            'free_delivery_above' => '5000',
            'low_stock_threshold' => '5',
        ]);

        // Default expense categories
        foreach (['Rent', 'Utilities', 'Salaries', 'Transport', 'Marketing', 'Miscellaneous'] as $cat) {
            ExpenseCategory::firstOrCreate(['name' => $cat]);
        }
    }
}
