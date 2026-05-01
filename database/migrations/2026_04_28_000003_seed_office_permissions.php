<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'offices.index',
            'offices.view',
            'offices.create',
            'offices.edit',
            'offices.delete',
            'offices.export',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', [
            'offices.index', 'offices.view', 'offices.create',
            'offices.edit', 'offices.delete', 'offices.export',
        ])->delete();
    }
};
