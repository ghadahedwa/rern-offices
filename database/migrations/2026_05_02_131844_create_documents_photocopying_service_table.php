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
        Schema::create('documents_photocopying_service', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        DB::table('documents_photocopying_service')->insert([
            ['name' =>'يوجد وتتبع ابنيه المحاكم', 'created_at' => now(), 'updated_at' => now()],
            ['name' =>'يوجد وتتبع المقر ', 'created_at' => now(), 'updated_at' => now()],
            ['name' =>'لا يوجد', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents_photocopying_service');
    }
};
