<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Permissions granted to the subshop role. All are seeded elsewhere too,
     * but firstOrCreate keeps this migration safe under migrate:fresh
     * (before DatabaseSeeder runs).
     */
    private array $permissions = [
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
        'inventory.view',
    ];

    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $subshop = Role::firstOrCreate(['name' => 'subshop', 'guard_name' => 'web']);
        $subshop->givePermissionTo($this->permissions);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::where('name', 'subshop')->where('guard_name', 'web')->delete();
    }
};
