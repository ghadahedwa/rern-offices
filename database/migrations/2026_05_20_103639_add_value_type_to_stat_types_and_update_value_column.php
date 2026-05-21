<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1 — أضف value_type لجدول stat_types
        Schema::table('stat_types', function (Blueprint $table) {
            $table->enum('value_type', ['count', 'amount'])->default('count')->after('period');
        });

        // 2 — المحصل من الطلبات (id=4) هو الوحيد بمبلغ
        DB::table('stat_types')->where('id', 4)->update(['value_type' => 'amount']);

        // 3 — غيّر عمود value في office_statistics من integer إلى decimal
        Schema::table('office_statistics', function (Blueprint $table) {
            $table->decimal('value', 12, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('office_statistics', function (Blueprint $table) {
            $table->unsignedInteger('value')->default(0)->change();
        });

        Schema::table('stat_types', function (Blueprint $table) {
            $table->dropColumn('value_type');
        });
    }
};
