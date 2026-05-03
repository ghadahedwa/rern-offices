<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_descriptions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        DB::table('location_descriptions')->insert([
            ['name' => 'مقر عادي',       'created_at' => now(), 'updated_at' => now()],
            ['name' => 'محكمة',          'created_at' => now(), 'updated_at' => now()],
            ['name' => 'بريد',           'created_at' => now(), 'updated_at' => now()],
            ['name' => 'مجتمع عمراني',   'created_at' => now(), 'updated_at' => now()],
            ['name' => 'أندية',          'created_at' => now(), 'updated_at' => now()],
            ['name' => 'استثمار',        'created_at' => now(), 'updated_at' => now()],
            ['name' => 'مول',            'created_at' => now(), 'updated_at' => now()],
            ['name' => 'أورنج',          'created_at' => now(), 'updated_at' => now()],
            ['name' => 'اتصالات',        'created_at' => now(), 'updated_at' => now()],
            ['name' => 'WE',        'created_at' => now(), 'updated_at' => now()],
             ['name' => 'حياة كريمة',        'created_at' => now(), 'updated_at' => now()],
              ['name' => 'خدمات مصر',        'created_at' => now(), 'updated_at' => now()],
               ['name' => 'VIP',        'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('location_descriptions');
    }
};
