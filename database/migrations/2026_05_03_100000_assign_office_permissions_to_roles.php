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

        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($officePermissions);
        }

        $admin = Role::where('name', 'admin')->first();
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

        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->revokePermissionTo($officePermissions);
        }
    }
};
