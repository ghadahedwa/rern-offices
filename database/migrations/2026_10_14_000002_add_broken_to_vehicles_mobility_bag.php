<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * شنطة التنقلات: «معطلة» خيار ثالث (طلب العميل 2026-10-14).
 * ⚠️ العمود enum — بدون هذه الهجرة يرفض MySQL القيمة الجديدة فيقع الحفظ بـ٥٠٠.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->enum('mobility_bag', ['available', 'not_available', 'broken'])->nullable()->change();
        });
    }

    public function down(): void
    {
        // القيمة الثالثة لا مكان لها في التعريف القديم — تُفرَّغ قبل تضييقه وإلا فشل الرجوع
        DB::table('vehicles')->where('mobility_bag', 'broken')->update(['mobility_bag' => null]);

        Schema::table('vehicles', function (Blueprint $table) {
            $table->enum('mobility_bag', ['available', 'not_available'])->nullable()->change();
        });
    }
};
