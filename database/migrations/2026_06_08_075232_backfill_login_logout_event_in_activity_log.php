<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * تحديث سجلات الدخول/الخروج القديمة (event = null) لتعمل مع فلتر لوحة التحكم.
     * تُطابق حسب وصف السجل.
     */
    public function up(): void
    {
        DB::table('activity_log')
            ->whereNull('event')
            ->where('description', 'تسجيل دخول')
            ->update(['event' => 'login']);

        DB::table('activity_log')
            ->whereNull('event')
            ->where('description', 'تسجيل خروج')
            ->update(['event' => 'logout']);
    }

    /**
     * إرجاع التحديث.
     */
    public function down(): void
    {
        DB::table('activity_log')
            ->where('event', 'login')
            ->where('description', 'تسجيل دخول')
            ->update(['event' => null]);

        DB::table('activity_log')
            ->where('event', 'logout')
            ->where('description', 'تسجيل خروج')
            ->update(['event' => null]);
    }
};
