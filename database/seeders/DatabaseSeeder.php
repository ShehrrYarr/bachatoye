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

        // Permissions
        $permissions = [
            'pos.access',
            'pos.process_returns',
            'orders.view',
            'orders.manage',
            'products.view',
            'products.manage',
            'inventory.view',
            'inventory.adjust',
            'customers.view',
            'customers.manage',
            'accounts.view',
            'accounts.manage',
            'expenses.view',
            'expenses.manage',
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
