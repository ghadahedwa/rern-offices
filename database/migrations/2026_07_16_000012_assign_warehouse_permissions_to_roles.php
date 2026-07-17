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

        $warehousePermissions = Permission::whereIn('name', [
            'warehouses.index', 'warehouses.view', 'warehouses.create', 'warehouses.edit',
            'warehouses.delete', 'warehouses.export', 'warehouses.attachments', 'warehouses.settings',
        ])->get();

        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($warehousePermissions);
        }

        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo($warehousePermissions);
        }
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $warehousePermissions = Permission::whereIn('name', [
            'warehouses.index', 'warehouses.view', 'warehouses.create', 'warehouses.edit',
            'warehouses.delete', 'warehouses.export', 'warehouses.attachments', 'warehouses.settings',
        ])->get();

        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->revokePermissionTo($warehousePermissions);
        }
    }
};
