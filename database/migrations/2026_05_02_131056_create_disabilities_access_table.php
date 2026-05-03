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
        Schema::create('disabilities_access', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        DB::table('disabilities_access')->insert([
            ['name' => 'يوجد', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'لا يوجد ويصلح لعمل التجهيزات',     'created_at' => now(), 'updated_at' => now()],
            ['name' => 'لا يوجد ولا يصلح', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disabilities_access');
    }
};
