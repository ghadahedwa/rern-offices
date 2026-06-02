<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->unsignedSmallInteger('ups_count')->nullable()->after('air_conditioners_count');
        });

        DB::table('device_types')->insert(['name' => 'UPS']);
    }

    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn('ups_count');
        });

        DB::table('device_types')->where('name', 'UPS')->delete();
    }
};
