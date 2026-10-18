<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * الانتقالات الفورية (طلب العميل 2026-10-18): «عدد الانتقالات» صار «عدد الانتقالات العادية» بأرقامه كما هي،
 * ونوعٌ شهري جديد بجواره بمجموعة خاصة (`vehicle_instant_trips`) — لا تقرؤه أي شاشة مقرات.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('stat_types')->where('group_key', 'vehicle_trips')
            ->update(['name' => 'عدد الانتقالات العادية', 'updated_at' => now()]);

        if (! DB::table('stat_types')->where('group_key', 'vehicle_instant_trips')->exists()) {
            DB::table('stat_types')->insert([
                'name'       => 'عدد الانتقالات الفورية',
                'period'     => 'monthly',
                'value_type' => 'count',
                'group_key'  => 'vehicle_instant_trips',
                'order'      => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // حذف النوع يحذف أرقامه (cascade على vehicle_statistics)
        DB::table('stat_types')->where('group_key', 'vehicle_instant_trips')->delete();

        DB::table('stat_types')->where('group_key', 'vehicle_trips')
            ->update(['name' => 'عدد الانتقالات', 'updated_at' => now()]);
    }
};
