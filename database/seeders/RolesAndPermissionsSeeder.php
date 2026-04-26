<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'index',
            'view',
            'create',
            'edit',
            'delete',
            'export-report',
            'manage-users',
            'manage-roles',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $admin      = Role::firstOrCreate(['name' => 'admin']);
        $user       = Role::firstOrCreate(['name' => 'user']);

        // super-admin gets all permissions
        $superAdmin->syncPermissions(Permission::all());

        // admin gets everything except manage-roles
        $admin->syncPermissions(
            Permission::whereNotIn('name', ['manage-roles'])->get()
        );

        // user gets read-only
        $user->syncPermissions(['index', 'view']);

        // Create a default super-admin account
        $superAdminUser = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name'     => 'Super Admin',
                'username' => 'superadmin',
                'password' => bcrypt('password'),
            ]
        );
        $superAdminUser->assignRole('super-admin');
    }
}
