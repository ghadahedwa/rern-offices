<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contractual_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        DB::table('contractual_statuses')->insert([
            ['name' => 'إيجار جديد', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'إيجار قديم', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ملك',        'created_at' => now(), 'updated_at' => now()],
            ['name' => 'تخصيص',      'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('contractual_statuses');
    }
};
