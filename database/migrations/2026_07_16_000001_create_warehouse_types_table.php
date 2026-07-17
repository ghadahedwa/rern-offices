<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // اسم النوع
            $table->unsignedTinyInteger('level');         // 1=رئيسي، 2=إقليمي، 3=فرعي (قاعدة النقل)
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        DB::table('warehouse_types')->insert([
            ['name' => 'رئيسي', 'level' => 1, 'order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'إقليمي', 'level' => 2, 'order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'فرعي',   'level' => 3, 'order' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_types');
    }
};
