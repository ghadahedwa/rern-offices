<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cleanliness_contracts', function (Blueprint $table) {
            $table->string('name');
        });

        DB::table('cleanliness_contracts')->insert([
            ['name' => 'مشترك مع المحكمة', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'نظافة من قبل الجهة التابع لها المقر', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'تعاقد مستقل', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'لا يوجد', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        //
    }
};
