<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تسكين مدخل البيانات في مقر بمدىً زمني — يستوعب الالتحاق والنقل وإنهاء الخدمة معاً:
 *   الإضافة     → تسكين مفتوح (ended_on = NULL)
 *   النقل       → يُغلق السابق ويُفتح جديد في اليوم التالي
 *   إنهاء الخدمة → يُغلق الأخير بلا جديد
 *
 * ⚠️ **مدة الخدمة تُقرأ من هنا وحدها** — وعليها يقوم حساب الحضور: مَن التحق يوم ١٥
 *    لا يُحسب حاضراً من يوم ١، ومَن انتهت خدمته لا يُحسب بعدها. (الحضور مشتقّ لا مخزَّن،
 *    فبلا هذا القيد يظهر الغائب أصلاً عن العمل حاضراً كل يوم.)
 * ⚠️ لا حذف لمدخل بل أرشفة — إغلاق التسكين هو الأرشفة: يخرج من الأعداد الحالية
 *    ويبقى في تقارير الفترات التي خدم فيها.
 * ⚠️ FK المقر `nullOnDelete` (نمط بوابة رأي المواطن): حذف مقر لا يمحو تاريخ من عمل فيه.
 * ⚠️ منع تداخل مدد التسكين لمدخلٍ واحد **لا يُعبَّر عنه في المحرّك** (قيد مدى لا صف)،
 *    فحارسه في `DataEntryAssignment::overlapsExisting()` ويُستدعى قبل كل حفظ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_entry_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained('data_entry_operators')->cascadeOnDelete();
            $table->foreignId('office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->date('started_on');
            $table->date('ended_on')->nullable();
            // سبب الإغلاق: 'transfer' نقل · 'left' إنهاء خدمة — للعرض لا للحساب.
            $table->string('end_reason', 20)->nullable();
            $table->timestamps();

            $table->index(['operator_id', 'started_on']);
            $table->index(['office_id', 'started_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_entry_assignments');
    }
};
