<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn(['type', 'location_description', 'work_system', 'connection_type', 'working_hours', 'contractual_status']);
        });

        Schema::table('offices', function (Blueprint $table) {
            $table->foreignId('type_id')->nullable()->constrained('office_types')->nullOnDelete();
            $table->foreignId('location_description_id')->nullable()->constrained('location_descriptions')->nullOnDelete();
            $table->foreignId('work_system_id')->nullable()->constrained('work_systems')->nullOnDelete();
            $table->foreignId('connection_type_id')->nullable()->constrained('connection_types')->nullOnDelete();
            $table->foreignId('working_hours_id')->nullable()->constrained('working_hours')->nullOnDelete();
            $table->foreignId('contractual_status_id')->nullable()->constrained('contractual_statuses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('type_id');
            $table->dropConstrainedForeignId('location_description_id');
            $table->dropConstrainedForeignId('work_system_id');
            $table->dropConstrainedForeignId('connection_type_id');
            $table->dropConstrainedForeignId('working_hours_id');
            $table->dropConstrainedForeignId('contractual_status_id');
        });

        Schema::table('offices', function (Blueprint $table) {
            $table->string('type')->nullable();
            $table->string('location_description')->nullable();
            $table->string('work_system')->nullable();
            $table->string('connection_type')->nullable();
            $table->string('working_hours')->nullable();
            $table->string('contractual_status')->nullable();
        });
    }
};
