<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // نصّ لا رقم: الأصفار في أوله جزء منه، وطوله يتجاوز ما يسعه عدد صحيح
            $table->string('engine_number', 50)->nullable()->after('chassis_number');
            // NULL = لم يُحدَّد بعد — لا يُقرأ «لا تعمل»
            $table->boolean('works_on_friday')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['engine_number', 'works_on_friday']);
        });
    }
};
