<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * إحصائيات السيارات (طلب العميل 2026-10-14):
 * - متوسطا الدعم والانتقال اليوميان: رقم واحد للسيارة كمتوسط المعاملات (`avg_daily_transactions`).
 * - «عدد الانتقالات» شهرياً: نوع إحصائية جديد بمجموعة خاصة بالسيارات (`vehicle_trips`)
 *   — لا تقرؤه أي شاشة مقرات (كلها تختار أنواعها بـ`group_key`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->unsignedInteger('avg_daily_support')->nullable()->after('avg_daily_transactions');
            $table->unsignedInteger('avg_daily_trips')->nullable()->after('avg_daily_support');
        });

        if (! DB::table('stat_types')->where('group_key', 'vehicle_trips')->exists()) {
            DB::table('stat_types')->insert([
                'name'       => 'عدد الانتقالات',
                'period'     => 'monthly',
                'value_type' => 'count',
                'group_key'  => 'vehicle_trips',
                'order'      => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // حذف النوع يحذف أرقامه (cascade على vehicle_statistics)
        DB::table('stat_types')->where('group_key', 'vehicle_trips')->delete();

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['avg_daily_support', 'avg_daily_trips']);
        });
    }
};
