<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structural_conditions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        DB::table('structural_conditions')->insert([
            ['name' => 'ممتازة',                              'created_at' => now(), 'updated_at' => now()],
            ['name' => 'جيدة',                              'created_at' => now(), 'updated_at' => now()],
            ['name' => 'متوسطة',                            'created_at' => now(), 'updated_at' => now()],
            ['name' => 'متهالك ويحتاج رفع كفاءة',           'created_at' => now(), 'updated_at' => now()],
            ['name' => 'متهالك ويحتاج لمقر بديل',           'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::table('offices', function (Blueprint $table) {
            $table->date('visited_at')->nullable()->after('established_at');
            $table->foreignId('structural_condition_id')->nullable()->after('structural_condition')
                  ->constrained('structural_conditions')->nullOnDelete();
            $table->string('work_schedule_commitment')->nullable()->after('archive_rating');
            $table->string('citizen_treatment_commitment')->nullable()->after('work_schedule_commitment');
        });
    }

    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('structural_condition_id');
            $table->dropColumn(['visited_at', 'work_schedule_commitment', 'citizen_treatment_commitment']);
        });

        Schema::dropIfExists('structural_conditions');
    }
};
