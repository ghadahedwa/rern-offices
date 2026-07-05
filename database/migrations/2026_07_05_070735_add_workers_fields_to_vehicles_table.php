<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->string('notary_name')->nullable();
            $table->string('notary_phone')->nullable();
            $table->string('reviewer_name')->nullable();
            $table->string('reviewer_phone')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'driver_name', 'driver_phone',
                'notary_name', 'notary_phone',
                'reviewer_name', 'reviewer_phone',
            ]);
        });
    }
};
