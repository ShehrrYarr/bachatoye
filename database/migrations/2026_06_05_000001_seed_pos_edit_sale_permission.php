<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::firstOrCreate(['name' => 'pos.edit_sale', 'guard_name' => 'web']);
    }

    public function down(): void
    {
        Permission::where('name', 'pos.edit_sale')->delete();
    }
};
