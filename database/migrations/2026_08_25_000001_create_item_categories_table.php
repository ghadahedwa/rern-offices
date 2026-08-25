<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');                        // اسم القسم كما يُكتب في عنوان البيان
            $table->integer('order')->default(0);          // ترتيب القسم في القوائم والتقارير
            $table->boolean('is_active')->default(true);   // قسم متوقّف يبقى على أصنافه ولا يُعرض لصنف جديد
            $table->timestamps();
        });

        // الأقسام الستة عشر المحصورة من بيانات الأرصدة الورقية الموقَّعة
        // (documentation/Warehouse sections/Scan document20260825_122519.pdf).
        // ⚠️ الترتيب هو ترتيب صفحات الملف نفسه — أي ترتيب الدستة الورقية كما
        //    يتسلّمها أمين المخزن. فهو ترتيب العرض في القوائم والتقارير، ولا
        //    يُعاد ترتيبه أبجدياً ولا حسب حجم القسم.
        // الاسم يحمل كلمة «مخزن» حيث تحملها الورقة، لأن عنوان البيان يتركّب منه.
        $now = now();
        $names = [
            'مخزن التصوير',                                        // ص ١
            'مخزن المستديم',                                       // ص ٢
            'مخزن السيارات',                                       // ص ٣
            'مخزن المستهلك',                                       // ص ٤
            'مخزن ذات القيمة',                                     // ص ٥
            'نماذج قانون ٩ و٢٧',                                   // ص ٦
            'أظرف قانون ٩ لسنة ٢٠٢٢',                              // ص ٧ (أعلى)
            'أظرف قانون ٢٧ لسنة ٢٠١٨',                             // ص ٧ (أسفل)
            'فهرس التوثيق',                                        // ص ٨
            'الدفتر العقاري (١)',                                  // ص ٩–١٠
            'مخزن السجل العيني',                                   // ص ١١
            'مخزن الكمبيوتر',                                      // ص ١٢
            'الورق والحافظات والنماذج المؤمنة والعقود المتموغة',    // ص ١٣
            'الأرصدة الكتابية',                                    // ص ١٤
            'الأختام',                                             // ص ١٥
            'الأرصدة الحسابية',                                    // ص ١٦
        ];

        DB::table('item_categories')->insert(
            collect($names)->map(fn ($name, $i) => [
                'name'       => $name,
                'order'      => $i + 1,
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('item_categories');
    }
};
