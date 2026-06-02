<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('stat_types')->insert([
            [
                'name'       => 'مشهرات قانون ٩',
                'period'     => 'yearly',
                'value_type' => 'count',
                'group_key'  => 'law9_registrations',
                'order'      => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('stat_types')->where('group_key', 'law9_registrations')->delete();
    }
};
