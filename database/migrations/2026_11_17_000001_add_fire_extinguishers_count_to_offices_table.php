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
            $table->unsignedSmallInteger('fire_extinguishers_count')->nullable()->after('ups_count');
        });

        if (! DB::table('device_types')->where('name', 'طفاية حريق')->exists()) {
            DB::table('device_types')->insert(['name' => 'طفاية حريق', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn('fire_extinguishers_count');
        });

        DB::table('device_types')->where('name', 'طفاية حريق')->delete();
    }
};
