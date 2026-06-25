<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $edit   = Permission::firstOrCreate(['name' => 'expenses.edit',   'guard_name' => 'web']);
        $delete = Permission::firstOrCreate(['name' => 'expenses.delete', 'guard_name' => 'web']);

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo([$edit, $delete]);
    }

    public function down(): void
    {
        Permission::where('name', 'expenses.edit')->delete();
        Permission::where('name', 'expenses.delete')->delete();
    }
};
