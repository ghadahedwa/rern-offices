<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        DB::table('office_types')->insert([
            ['name' => 'مكتب رئيسي',                   'created_at' => now(), 'updated_at' => now()],
            ['name' => 'فرع توثيق',                     'created_at' => now(), 'updated_at' => now()],
            ['name' => 'مأمورية شهر',                   'created_at' => now(), 'updated_at' => now()],
            ['name' => 'فرع توثيق ومأمورية شهر (مدمج)', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'سجل عيني',                      'created_at' => now(), 'updated_at' => now()],
            ['name' => 'إدارة',                         'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('office_types');
    }
};
