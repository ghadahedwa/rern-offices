<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_work_systems', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        DB::table('vehicle_work_systems')->insert([
            ['name' => 'تنقلات فقط',       'created_at' => now(), 'updated_at' => now()],
            ['name' => 'دعم فقط',           'created_at' => now(), 'updated_at' => now()],
            ['name' => 'تنقلات ودعم',       'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_work_systems');
    }
};
