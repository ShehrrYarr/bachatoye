<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $new = ['sections.manage', 'categories.manage', 'brands.manage'];

        foreach ($new as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Admin already gets all via seeder, but ensure it here too
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo($new);

        // Ensure these exist (they're normally seeded, but may not yet during migrate:fresh)
        $extra = ['accounts.view', 'accounts.manage', 'vendors.view', 'vendors.manage'];
        foreach ($extra as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Grant all 5 catalogue+khata permissions to salesman
        $salesman = Role::firstOrCreate(['name' => 'salesman', 'guard_name' => 'web']);
        $salesman->givePermissionTo(array_merge($new, $extra));
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $salesman = Role::where('name', 'salesman')->where('guard_name', 'web')->first();
        if ($salesman) $salesman->revokePermissionTo([
            'sections.manage', 'categories.manage', 'brands.manage',
            'accounts.view', 'accounts.manage',
            'vendors.view', 'vendors.manage',
        ]);

        foreach (['sections.manage', 'categories.manage', 'brands.manage'] as $name) {
            Permission::where('name', $name)->delete();
        }
    }
};
