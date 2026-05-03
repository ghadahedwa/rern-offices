<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        DB::table('working_hours')->insert([
            ['name' => 'صباحي ومسائي أول',          'created_at' => now(), 'updated_at' => now()],
            ['name' => 'صباحي ومسائي أول وثاني',    'created_at' => now(), 'updated_at' => now()],
            ['name' => 'مسائي فقط',                 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('working_hours');
    }
};
