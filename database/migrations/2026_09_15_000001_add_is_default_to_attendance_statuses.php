<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * الحالة الافتراضية — «حاضر»: ما تعنيه الخلية التي لا صفّ لها في `attendance_days`.
 *
 * ⚠️ **لا تُخزَّن أبداً** (المبدأ الحاكم: الحضور مشتقّ). وشبكة التسجيل تحتاج أن تعرفها
 *    لتستبعدها من أدوات التعليم: صفٌّ مخزَّن بها يُحسب **استثناءً** في `summaryFor`
 *    فيُنقص الحضور يوماً بتعليمه «حاضراً» — عكس معناه تماماً.
 * ⚠️ **عمودٌ لا اسم**: الاسم يعدّله المدير من شاشة الحالات، ومطابقته بـ«حاضر» في الكود
 *    تنكسر صامتةً بأول تعديل. وهذا العمود **ليس «طبيعة الحالة»** المؤجَّلة (أي الحالات
 *    تدخل مقام النسبة) — يحدّد حالةً واحدة هي معنى الفراغ، لا تصنيفاً للحالات.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_statuses', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('is_system');
        });

        // «حاضر» الأساسية بالاسم، وإن كان المدير قد أعاد تسميتها فأول الأساسية ترتيباً
        // (زُرعت بترتيب ١).
        $id = DB::table('attendance_statuses')->where('is_system', true)->where('name', 'حاضر')->value('id')
            ?? DB::table('attendance_statuses')->where('is_system', true)->orderBy('order')->orderBy('id')->value('id');

        if ($id) {
            DB::table('attendance_statuses')->where('id', $id)->update(['is_default' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('attendance_statuses', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
};
