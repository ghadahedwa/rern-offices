<?php

use App\Support\ArabicText;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * «هل هذا النوع مقرٌّ به عمالة متعاقدة؟» — على **النوع** لا على المقر، بنمط `is_public`.
 *
 * بعض الأنواع ليست مقارّ عمل أصلاً: استراحة · تحت الإنشاء · معلَّق بقرار ·
 * منتهي العمل به · مقر غير مستغل · أراضٍ. وهي تبقى في النظام ببياناتها
 * وإحصائياتها، لكنها لا تُعرض حين يُسأل المستخدم «أين يعمل هذا الشخص؟».
 *
 * ⚠️ **خانة على النوع لا قائمة أسماء في الكود**: الأسماء تُكتب بإملاءات مختلفة
 *    («وزارى» بالمقصورة · «لمالكة» بالتاء المربوطة)، ونوعٌ يُضاف غداً يحتاج
 *    تعديل كود. والخانة تُدار من شاشة أنواع المقرات كما تُدار `is_public`.
 * ⚠️ **والمطابقة هنا بالتطبيع** (`ArabicText`) لا حرفياً — لنفس سبب الإملاء.
 *    وعلى داتابيز جديدة (الاختبارات) لا يطابق شيءٌ فتبقى الأنواع كلها به عمالة متعاقدةة،
 *    وهو الصحيح: البذرة الأولى لا تحوي هذه الأنواع أصلاً.
 */
return new class extends Migration
{
    /** الأنواع التي ليست مقارَّ عمل — قرار العميلة 2026-09-07. */
    private const NOT_OPERATIONAL = [
        'استراحة',
        'مقر غير مستغل',
        'تحت الانشاء',
        'معلق بقرار وزاري',
        'معلق بقرار الجهة التابع لها',
        'انهاء العمل به بقرار وزاري',
        'انهاء العمل به ومسلم لمالكه',
        'أراضي',
    ];

    public function up(): void
    {
        Schema::table('office_types', function (Blueprint $table) {
            $table->boolean('has_contract_workers')->default(true)->after('name');
        });

        $excluded = array_map([ArabicText::class, 'normalize'], self::NOT_OPERATIONAL);

        foreach (DB::table('office_types')->get(['id', 'name']) as $type) {
            if (in_array(ArabicText::normalize($type->name), $excluded, true)) {
                DB::table('office_types')->where('id', $type->id)->update(['has_contract_workers' => false]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('office_types', function (Blueprint $table) {
            $table->dropColumn('has_contract_workers');
        });
    }
};
