<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_broken_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_type_id')->constrained('device_types')->cascadeOnDelete();
            $table->unsignedSmallInteger('count')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_broken_devices');
    }
};
