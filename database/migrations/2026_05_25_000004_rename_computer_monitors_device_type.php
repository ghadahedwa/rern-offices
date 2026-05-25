<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('device_types')
            ->where('name', 'شاشات الكمبيوتر')
            ->update(['name' => 'شاشات العرض']);
    }

    public function down(): void
    {
        DB::table('device_types')
            ->where('name', 'شاشات العرض')
            ->update(['name' => 'شاشات الكمبيوتر']);
    }
};
