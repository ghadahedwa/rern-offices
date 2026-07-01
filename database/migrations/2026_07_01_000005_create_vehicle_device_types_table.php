<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_device_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        DB::table('vehicle_device_types')->insert([
            ['name' => 'لاب توب',           'created_at' => now(), 'updated_at' => now()],
            ['name' => 'جهاز بصمة',         'created_at' => now(), 'updated_at' => now()],
            ['name' => 'طابعة',             'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ماكينة تحصيل',      'created_at' => now(), 'updated_at' => now()],
            ['name' => 'MiFi',              'created_at' => now(), 'updated_at' => now()],
            ['name' => 'مولد كهربائي',      'created_at' => now(), 'updated_at' => now()],
            ['name' => 'كاميرا مراقبة',     'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_device_types');
    }
};
