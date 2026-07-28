<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'serials.lookup', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'serials.manage_attributes', 'guard_name' => 'web']);

        // Admin already has every permission; salesman gets them opt-in per
        // user via the existing checkbox UI (off by default).
        $admin = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        $admin?->givePermissionTo(['serials.lookup', 'serials.manage_attributes']);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'serials.lookup')->delete();
        Permission::where('name', 'serials.manage_attributes')->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
