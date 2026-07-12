<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        // offices.settings → إدارة القوائم المرجعية للمقرات والسيارات (تهيئة فرع إدارة النظام)
        Permission::firstOrCreate(['name' => 'offices.settings', 'guard_name' => 'web']);
    }

    public function down(): void
    {
        Permission::where('name', 'offices.settings')->delete();
    }
};
