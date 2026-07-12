<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        // صلاحيات فرع الاجتماعات
        foreach (['meetings.index', 'meetings.view', 'meetings.create', 'meetings.edit', 'meetings.delete'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', [
            'meetings.index', 'meetings.view', 'meetings.create', 'meetings.edit', 'meetings.delete',
        ])->delete();
    }
};
