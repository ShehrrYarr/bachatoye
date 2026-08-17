<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $user = User::firstOrCreate(
            ['email' => 'sherry@gmail.com'],
            [
                'name'           => 'Sherry',
                'password'       => Hash::make('@Admin123'),
                'password_plain' => '@Admin123',
                'is_active'      => true,
            ]
        );

        if (! $user->hasRole('super-admin')) {
            $user->assignRole('super-admin');
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $user = User::where('email', 'sherry@gmail.com')->first();
        $user?->removeRole('super-admin');

        Role::where('name', 'super-admin')->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
