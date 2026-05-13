<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('location_descriptions', function (Blueprint $table) {
            $table->boolean('shows_windows_count')->default(false)->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('location_descriptions', function (Blueprint $table) {
            $table->dropColumn('shows_windows_count');
        });
    }
};
