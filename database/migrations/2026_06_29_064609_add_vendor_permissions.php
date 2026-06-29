<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()['cache']->forget('spatie.permission.cache');

        $perms = ['vendors.view', 'vendors.manage'];

        foreach ($perms as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        $admin = Role::findByName('admin');
        $admin->givePermissionTo($perms);
    }

    public function down(): void
    {
        app()['cache']->forget('spatie.permission.cache');
        Permission::whereIn('name', ['vendors.view', 'vendors.manage'])->delete();
    }
};
