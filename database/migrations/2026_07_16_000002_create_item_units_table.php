<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_units', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // الوحدة (قطعة/جهاز/عبوة/...)
            $table->timestamps();
        });

        DB::table('item_units')->insert([
            ['name' => 'قطعة', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'جهاز', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'عبوة', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('item_units');
    }
};
