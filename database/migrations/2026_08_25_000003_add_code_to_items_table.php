<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // رقم الصنف كما في الدفتر: «٤٠ ق» · «٥٤ ق م» — نصّ لا عدد صحيح.
            // اختياري: عمود الرقم لا يوجد إلا في بيان الدفتر العقاري، وبقية
            // الأقسام تعرّف الصنف باسمه وحده.
            $table->string('code', 50)->nullable()->after('item_category_id');

            // ⚠️ فهرس عادي لا UNIQUE: الرقم يتكرّر فعلاً في الورق الرسمي —
            //    «٤ توثيق» و«٥ توثيق» يحملهما صنفان في بيان فهرس التوثيق نفسه.
            //    فالقيد كان سيرفض بيانات صحيحة، والفحص تحذيرٌ في شاشة الصنف.
            //
            // ⚠️ على `code` وحده لا على (item_category_id, code): الفهرس المركّب
            //    صدره item_category_id فيبتلعه MySQL بدل فهرس المفتاح الأجنبي،
            //    ثم يرفض إسقاطه لأن الـFK صار معتمداً عليه — فتتعطّل `down()`.
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['code']);
            $table->dropColumn('code');
        });
    }
};
