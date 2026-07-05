<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->enum('mobility_bag', ['available', 'not_available'])->nullable();
            $table->unsignedSmallInteger('laptops_count')->nullable();
            $table->unsignedSmallInteger('fingerprints_count')->nullable();
            $table->unsignedSmallInteger('printers_count')->nullable();
            $table->unsignedSmallInteger('collection_machines_count')->nullable();
            $table->unsignedSmallInteger('mifi_count')->nullable();
            $table->enum('generator_status', ['available', 'not_available', 'broken'])->nullable();
            $table->enum('surveillance_cameras', ['available', 'not_available', 'broken'])->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'mobility_bag', 'laptops_count', 'fingerprints_count', 'printers_count',
                'collection_machines_count', 'mifi_count', 'generator_status', 'surveillance_cameras',
            ]);
        });
    }
};
