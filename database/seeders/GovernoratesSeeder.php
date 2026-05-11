<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GovernoratesSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('governorates')->exists()) {
            return;
        }

        DB::table('governorates')->insert([
            ['name' => 'شمال القاهرة',       'created_at' => now(), 'updated_at' => now()],
            ['name' => 'جنوب القاهرة',       'created_at' => now(), 'updated_at' => now()],
            ['name' => 'مكتب رئيس القطاع',        'created_at' => now(), 'updated_at' => now()],
            ['name' => 'الجيزة',        'created_at' => now(), 'updated_at' => now()],
            ['name' => 'الإسكندرية',    'created_at' => now(), 'updated_at' => now()],
            ['name' => 'الدقهلية',      'created_at' => now(), 'updated_at' => now()],
            ['name' => 'البحر الأحمر',  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'البحيرة',       'created_at' => now(), 'updated_at' => now()],
            ['name' => 'الفيوم',        'created_at' => now(), 'updated_at' => now()],
            ['name' => 'الغربية',       'created_at' => now(), 'updated_at' => now()],
            ['name' => 'الإسماعيلية',   'created_at' => now(), 'updated_at' => now()],
            ['name' => 'المنوفية',      'created_at' => now(), 'updated_at' => now()],
            ['name' => 'المنيا',        'created_at' => now(), 'updated_at' => now()],
            ['name' => 'القليوبية',     'created_at' => now(), 'updated_at' => now()],
            ['name' => 'الوادي الجديد', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'السويس',        'created_at' => now(), 'updated_at' => now()],
            ['name' => 'اسوان',         'created_at' => now(), 'updated_at' => now()],
            ['name' => 'اسيوط',         'created_at' => now(), 'updated_at' => now()],
            ['name' => 'بني سويف',      'created_at' => now(), 'updated_at' => now()],
            ['name' => 'بورسعيد',       'created_at' => now(), 'updated_at' => now()],
            ['name' => 'دمياط',         'created_at' => now(), 'updated_at' => now()],
            ['name' => 'جنوب سيناء',    'created_at' => now(), 'updated_at' => now()],
            ['name' => 'شمال سيناء',    'created_at' => now(), 'updated_at' => now()],
            ['name' => 'سوهاج',         'created_at' => now(), 'updated_at' => now()],
            ['name' => 'قنا',           'created_at' => now(), 'updated_at' => now()],
            ['name' => 'كفر الشيخ',     'created_at' => now(), 'updated_at' => now()],
            ['name' => 'مطروح',         'created_at' => now(), 'updated_at' => now()],
            ['name' => 'الأقصر',        'created_at' => now(), 'updated_at' => now()],
            ['name' => 'عين شمس',       'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
