<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $officePermissions = Permission::whereIn('name', [
            'offices.index',
            'offices.view',
            'offices.create',
            'offices.edit',
            'offices.delete',
            'offices.export',
        ])->get();

        $superAdmin = Role::findByName('super-admin');
        if ($superAdmin) {
            $superAdmin->givePermissionTo($officePermissions);
        }

        $admin = Role::findByName('admin');
        if ($admin) {
            $admin->givePermissionTo($officePermissions);
        }
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $officePermissions = Permission::whereIn('name', [
            'offices.index',
            'offices.view',
            'offices.create',
            'offices.edit',
            'offices.delete',
            'offices.export',
        ])->get();

        $admin = Role::findByName('admin');
        if ($admin) {
            $admin->revokePermissionTo($officePermissions);
        }
    }
};
