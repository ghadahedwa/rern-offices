<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        // صلاحيات فرع المخازن
        foreach ([
            'warehouses.index',
            'warehouses.view',
            'warehouses.create',
            'warehouses.edit',
            'warehouses.delete',
            'warehouses.export',
            'warehouses.attachments',
            'warehouses.settings',
        ] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', [
            'warehouses.index', 'warehouses.view', 'warehouses.create', 'warehouses.edit',
            'warehouses.delete', 'warehouses.export', 'warehouses.attachments', 'warehouses.settings',
        ])->delete();
    }
};
