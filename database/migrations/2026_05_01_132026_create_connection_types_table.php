<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connection_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        DB::table('connection_types')->insert([
            ['name' => 'فايبر',       'created_at' => now(), 'updated_at' => now()],
            ['name' => 'نحاسي',       'created_at' => now(), 'updated_at' => now()],
            ['name' => 'واي فاي',     'created_at' => now(), 'updated_at' => now()],
            ['name' => 'شبكة سلامة', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('connection_types');
    }
};
