<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('governorates', function (Blueprint $table) {
            $table->unsignedSmallInteger('order')->default(0)->after('name');
        });

        // Set existing records' order = their id to preserve current display order
        DB::statement('UPDATE governorates SET `order` = id');
    }

    public function down(): void
    {
        Schema::table('governorates', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
