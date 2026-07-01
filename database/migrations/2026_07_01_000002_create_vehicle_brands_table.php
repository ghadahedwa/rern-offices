<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        DB::table('vehicle_brands')->insert([
            ['name' => 'تويوتا',        'created_at' => now(), 'updated_at' => now()],
            ['name' => 'هيونداي',       'created_at' => now(), 'updated_at' => now()],
            ['name' => 'كيا',           'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ميتسوبيشي',    'created_at' => now(), 'updated_at' => now()],
            ['name' => 'نيسان',         'created_at' => now(), 'updated_at' => now()],
            ['name' => 'فورد',          'created_at' => now(), 'updated_at' => now()],
            ['name' => 'شيفروليه',      'created_at' => now(), 'updated_at' => now()],
            ['name' => 'فولكس فاجن',   'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_brands');
    }
};
