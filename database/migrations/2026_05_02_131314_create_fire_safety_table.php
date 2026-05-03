<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fire_safety', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        DB::table('fire_safety')->insert([
            ['name' => 'اطفاء ذاتي', 'created_at' => now(), 'updated_at' => now()],
            ['name' =>'شبكة اطفاء',     'created_at' => now(), 'updated_at' => now()],
            ['name' =>'يوجد طفايات حريق ولا تحتاج صيانة',     'created_at' => now(), 'updated_at' => now()],
            ['name' =>'يوجد طفايات حريق وتحتاج صيانة',     'created_at' => now(), 'updated_at' => now()],
             ['name' =>'لا يوجد طفايات',     'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fire_safety');
    }
};
