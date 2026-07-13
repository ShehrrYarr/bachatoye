<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['expenses.edit', 'expenses.delete'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $subshop = Role::firstOrCreate(['name' => 'subshop', 'guard_name' => 'web']);
        $subshop->givePermissionTo(['expenses.edit', 'expenses.delete']);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $subshop = Role::where('name', 'subshop')->where('guard_name', 'web')->first();
        $subshop?->revokePermissionTo(['expenses.edit', 'expenses.delete']);
    }
};
