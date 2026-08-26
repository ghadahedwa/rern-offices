<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * سطر الجهة في ترويسة «بيان بأرصدة {القسم}».
 *
 * ⚠️ السطران الأولان (وزارة العدل · مصلحة الشهر العقاري والتوثيق) فوق الجميع
 *    فيبقيان ثابتين في القالب. أما الثالث فجهةُ المخزن نفسه، وطبعُه ثابتاً كان
 *    يُخرج بيان مخزن المحافظة منسوباً إلى «الادارة العامة للتعاقدات والمخازن»
 *    — نسبةٌ خاطئة على ورقةٍ تُوقَّع وتُختم.
 *
 * ⚠️ ولا قيمة افتراضية عند الفراغ: السطر **يُحذف** من الورقة. غيابٌ ظاهر خيرٌ
 *    من نسبةٍ خاطئة صامتة، والمخازن القائمة تُملأ عند أول طباعة.
 */
return new class extends Migration
{
    private const MAIN_LETTERHEAD = 'الادارة العامة للتعاقدات والمخازن';

    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->string('letterhead')->nullable()->after('governorate_id');
        });

        // المخازن الرئيسية وحدها تُزرع بنصّ الورقة المعروف — وغيرها يُكتب يدوياً
        DB::table('warehouses')
            ->whereIn('warehouse_type_id', DB::table('warehouse_types')->where('level', 1)->pluck('id'))
            ->update(['letterhead' => self::MAIN_LETTERHEAD]);
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn('letterhead');
        });
    }
};
