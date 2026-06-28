<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // المديونية بقت محسوبة (مطالبات − محصل) فمفيش داعي للعمود اليدوي
        Schema::table('governorates', function (Blueprint $table) {
            $table->dropColumn('debt_amount');
        });
    }

    public function down(): void
    {
        Schema::table('governorates', function (Blueprint $table) {
            $table->decimal('debt_amount', 14, 2)->nullable()->after('supervising_counselor');
        });
    }
};
