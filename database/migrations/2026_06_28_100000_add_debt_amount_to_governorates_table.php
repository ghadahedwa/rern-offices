<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('governorates', function (Blueprint $table) {
            // مديونية الطلبات — قيمة واحدة لكل محافظة
            $table->decimal('debt_amount', 14, 2)->nullable()->after('supervising_counselor');
        });
    }

    public function down(): void
    {
        Schema::table('governorates', function (Blueprint $table) {
            $table->dropColumn('debt_amount');
        });
    }
};
