<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1 — أضف العمود الجديد nullable مؤقتاً
        Schema::table('office_statistics', function (Blueprint $table) {
            $table->unsignedBigInteger('stat_type_id')->nullable()->after('office_id');
        });

        // 2 — حوّل البيانات الموجودة (IDs مضمونة من migration السابق)
        DB::table('office_statistics')->where('stat_type', 'transactions')->update(['stat_type_id' => 1]);
        DB::table('office_statistics')->where('stat_type', 'form_sales')->update(['stat_type_id' => 2]);
        DB::table('office_statistics')->where('stat_type', 'folder_sales')->update(['stat_type_id' => 3]);

        // 3 — اجعله NOT NULL + أضف FK
        Schema::table('office_statistics', function (Blueprint $table) {
            $table->unsignedBigInteger('stat_type_id')->nullable(false)->change();
            $table->foreign('stat_type_id')->references('id')->on('stat_types')->cascadeOnDelete();
        });

        // 4 — احذف عمود ENUM القديم
        /*Schema::table('office_statistics', function (Blueprint $table) {
            $table->dropColumn('stat_type');
        });*/
    }

    public function down(): void
    {
        // عمود stat_type القديم لم يُحذف في up()، فنكتفي بإزالة العمود الجديد + الـ FK
        Schema::table('office_statistics', function (Blueprint $table) {
            $table->dropForeign(['stat_type_id']);
            $table->dropColumn('stat_type_id');
        });
    }
};
