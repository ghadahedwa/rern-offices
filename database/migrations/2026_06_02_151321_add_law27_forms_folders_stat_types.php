<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('stat_types')->insert([
            [
                'name'       => 'بيع النماذج',
                'period'     => 'monthly',
                'value_type' => 'count',
                'group_key'  => 'law27_forms_folders',
                'order'      => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'بيع الحوافظ',
                'period'     => 'monthly',
                'value_type' => 'count',
                'group_key'  => 'law27_forms_folders',
                'order'      => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('stat_types')->where('group_key', 'law27_forms_folders')->delete();
    }
};
