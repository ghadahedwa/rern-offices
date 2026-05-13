<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeviceTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'أجهزة الكمبيوتر',
            'الطابعات',
            'الأسكانر',
            'البصمة',
            'ماكينات التحصيل',
        ];

        foreach ($types as $name) {
            DB::table('device_types')->insertOrIgnore(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
