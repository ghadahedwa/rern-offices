<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * صفة العامل المتعاقد — قائمة مرجعية تُدار من «إدارة النظام» كحالات الحضور.
 *
 * الخمس المزروعة (مدخل بيانات · مترجم · عامل · سائق · مساحي) هي ما تتعاقد عليه
 * الشركات اليوم، وما زاد يضيفه المدير من الشاشة بلا تعديل كود.
 *
 * ⚠️ **الصفة على الشخص لا على التسكين** (قرار العميلة): تقريرٌ عن شهرٍ مضى يعرض
 *    صفته الحالية. ولو لزم تأريخها لاحقاً فمكانها عمودٌ على `contractor_assignments`
 *    لا تعديلٌ هنا — وحينها يصير لكل مدةِ تسكينٍ صفتُها.
 * ⚠️ **`is_system` على «مدخل بيانات»**: هي صفة كل الصفوف القائمة (٢٧٦ عاملاً)،
 *    وحذفها يترك جمهور الموديول بلا صفة.
 * ⚠️ **العمود nullable و`nullOnDelete`** لا `restrictOnDelete`: صفٌّ بلا صفة
 *    يظهر ناقصاً ويُصحَّح، وصفٌّ يمنع حذف صفةٍ من الشاشة يُربك المدير. والحارس
 *    الفعلي على الحذف في الشاشة (الصفة المستعمَلة لا تُحذف) كحالات الحضور.
 */
return new class extends Migration
{
    /** الصفة الافتراضية: كل ما دخل قبل هذه الهجرة كان مدخل بيانات. */
    private const DEFAULT = 'مدخل بيانات';

    public function up(): void
    {
        Schema::create('professions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        $now = now();

        DB::table('professions')->insert([
            ['name' => self::DEFAULT, 'order' => 1, 'is_active' => true, 'is_system' => true,  'created_at' => $now, 'updated_at' => $now],
            ['name' => 'مترجم',       'order' => 2, 'is_active' => true, 'is_system' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'عامل',        'order' => 3, 'is_active' => true, 'is_system' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'سائق',        'order' => 4, 'is_active' => true, 'is_system' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'مساحي',       'order' => 5, 'is_active' => true, 'is_system' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::table('contractors', function (Blueprint $table) {
            $table->foreignId('profession_id')->nullable()->after('name')
                ->constrained('professions')->nullOnDelete();
        });

        // كل الصفوف القائمة دخلت من موديول «مدخلي البيانات» — فصفتها معروفة يقيناً
        $default = DB::table('professions')->where('name', self::DEFAULT)->value('id');

        DB::table('contractors')->whereNull('profession_id')->update(['profession_id' => $default]);
    }

    public function down(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->dropForeign(['profession_id']);
            $table->dropColumn('profession_id');
        });

        Schema::dropIfExists('professions');
    }
};
