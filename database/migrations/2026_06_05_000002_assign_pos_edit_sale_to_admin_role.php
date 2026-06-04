<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::firstOrCreate(['name' => 'pos.edit_sale', 'guard_name' => 'web']);

        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($adminRole && ! $adminRole->hasPermissionTo('pos.edit_sale')) {
            $adminRole->givePermissionTo($permission);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($adminRole) {
            $adminRole->revokePermissionTo('pos.edit_sale');
        }
    }
};
