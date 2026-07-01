<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('governorate_id')->constrained('governorates');
            $table->foreignId('type_id')->nullable()->constrained('vehicle_types')->nullOnDelete();
            $table->foreignId('work_system_id')->nullable()->constrained('vehicle_work_systems')->nullOnDelete();
            $table->foreignId('working_hours_id')->nullable()->constrained('vehicle_working_hours')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('vehicle_brands')->nullOnDelete();
            $table->string('name');
            $table->string('license_plate')->nullable();
            $table->unsignedSmallInteger('manufacture_year')->nullable();
            $table->string('chassis_number')->nullable();
            $table->date('license_expiry_date')->nullable();
            $table->enum('status', ['working', 'maintenance', 'stopped'])->nullable();
            $table->string('overnight_address')->nullable();
            $table->string('storage_room_location')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
