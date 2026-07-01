<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'vehicles.index',
            'vehicles.view',
            'vehicles.create',
            'vehicles.edit',
            'vehicles.delete',
            'vehicles.export',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', [
            'vehicles.index',
            'vehicles.view',
            'vehicles.create',
            'vehicles.edit',
            'vehicles.delete',
            'vehicles.export',
        ])->delete();
    }
};
