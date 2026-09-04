<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * حالات الحضور — قائمة مرجعية تُدار من «إدارة النظام».
 *
 * الثلاث المزروعة (حاضر · غائب · إجازة) هي حالات العمل اليوم، وما زاد عليها
 * (حضر متأخر · مأمورية …) يضيفه المدير من الشاشة بلا تعديل كود.
 *
 * ⚠️ `is_system` تمنع حذف الثلاث الأساسية: حذف إحداها يترك المفتش بلا حالةٍ
 *    يسجّل بها، وسجلّاً قديماً يشير إلى حالةٍ غير موجودة.
 * ⚠️ التقرير **عدٌّ لكل حالة على حدة** (قرار المستخدمة) لا نسبة مئوية — فلا
 *    عمود «طبيعة الحالة» هنا. ولو طُلبت نسبةٌ لاحقاً فستلزم معرفة أي الحالات
 *    تدخل المقام، وهو عمودٌ يُضاف على هذا الجدول وحقلٌ في هذه الشاشة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            // لون سداسي يُطبَّق inline في التقويم والتقارير.
            // ⚠️ inline لا فئة Tailwind: الفئات المركَّبة نصّاً لا يراها البناء.
            $table->string('color', 7)->default('#71717a');
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        $now = now();

        DB::table('attendance_statuses')->insert([
            ['name' => 'حاضر',  'color' => '#16a34a', 'order' => 1, 'is_active' => true, 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'غائب',  'color' => '#dc2626', 'order' => 2, 'is_active' => true, 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'إجازة', 'color' => '#2563eb', 'order' => 3, 'is_active' => true, 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_statuses');
    }
};
