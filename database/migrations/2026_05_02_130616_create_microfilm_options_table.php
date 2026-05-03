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
        Schema::create('microfilm_options', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        DB::table('microfilm_options')->insert([
            ['name' =>  'يوجد ويتبع الأهرام',       'created_at' => now(), 'updated_at' => now()],
            ['name' => 'لا يوجد ويتبع الاخبار',       'created_at' => now(), 'updated_at' => now()],
            ['name' => 'لا يوجد',     'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('microfilm_options');
    }
};
