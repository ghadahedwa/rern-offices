<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->string('electricity_meter_type')->nullable()->after('air_conditioners_count');
            $table->string('electricity_meter_debt')->nullable()->after('electricity_meter_type');
            $table->string('water_meter_type')->nullable()->after('electricity_meter_debt');
            $table->string('water_meter_debt')->nullable()->after('water_meter_type');
        });
    }

    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn([
                'electricity_meter_type',
                'electricity_meter_debt',
                'water_meter_type',
                'water_meter_debt',
            ]);
        });
    }
};
