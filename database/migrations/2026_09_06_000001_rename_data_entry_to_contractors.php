<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * «مدخلو البيانات» ← «العاملون بالتعاقد» — الموديول يضمّ المترجم والعامل
 * والسائق والمساحي، لا مدخل البيانات وحده (طلب العميلة 2026-09-06).
 *
 * ⚠️ **إعادة تسمية لا نسخ**: `RENAME TABLE` عمليةٌ على البنية لا على الصفوف —
 *    ذرّية ولحظية، والمفاتيح الأجنبية في الجداول المشيرة تتبع الاسم الجديد
 *    تلقائياً. والنسخ إلى جداول جديدة كان يضيف مواضع خطأ (المعرّفات · الترقيم
 *    التلقائي · إعادة بناء المفاتيح) بلا مكسب، ويترك في الداتابيز **توأماً
 *    بائتاً** يبدو حيّاً وقد بطل تحديثه. والنسخ الاحتياطي مكانه خارج الداتابيز.
 *
 * ⚠️ **أسماء الصلاحيات تتغيّر ومعرّفاتها لا** — و`role_has_permissions` يربط
 *    بالمعرّف، فكل دور يحتفظ بصلاحياته. ولذلك يُمسح كاش Spatie في آخر الهجرة:
 *    بدونه تبقى الأسماء القديمة في الكاش فيصير أصحابها بلا صلاحية (٤٠٣).
 *
 * ⚠️ **`attendable_type` يخزّن اسم الكلاس** — وهو اليوم بلا صفوف (شاشة التسجيل
 *    لم تُبنَ بعد)، والسطر موجود ليصحّ على أي نسخةٍ فيها صفوف.
 *
 * ⚠️ **الهجرة تعدّ الصفوف قبل وبعد وتتوقف إن اختلف الرقم** — لا تُسلَّم النتيجة
 *    للثقة: خطأٌ صامت هنا يظهر بعد أسبوع بلا وسيلة لمعرفة متى وقع.
 *
 * ⚠️ **الهجرات الأقدم تبقى بأسمائها القديمة** — هي سجلّ تاريخي: داتابيز جديدة
 *    (الاختبارات) تُنشئ `data_entry_*` ثم تمرّ من هنا فتصير `contractors`.
 *    وتعديلها لتُنشئ الاسم الجديد مباشرةً يجعل هذه الهجرة تبحث عن جدولٍ غير موجود.
 */
return new class extends Migration
{
    /** الاسم القديم ← الجديد. */
    private const TABLES = [
        'data_entry_operators'   => 'contractors',
        'data_entry_assignments' => 'contractor_assignments',
    ];

    private const OLD_MORPH = 'App\\Models\\DataEntryOperator';
    private const NEW_MORPH = 'App\\Models\\Contractor';

    public function up(): void
    {
        $before = $this->countRows(array_keys(self::TABLES));

        foreach (self::TABLES as $old => $new) {
            Schema::rename($old, $new);
        }

        Schema::table('contractor_assignments', function (Blueprint $table) {
            $table->renameColumn('operator_id', 'contractor_id');
        });

        $this->moveMorph(self::OLD_MORPH, self::NEW_MORPH);
        $this->renamePermissions('data-entry.', 'contractors.');

        $this->assertCounts(array_combine(array_values(self::TABLES), $before));
        $this->forgetPermissionCache();
    }

    public function down(): void
    {
        $before = $this->countRows(array_values(self::TABLES));

        Schema::table('contractor_assignments', function (Blueprint $table) {
            $table->renameColumn('contractor_id', 'operator_id');
        });

        foreach (self::TABLES as $old => $new) {
            Schema::rename($new, $old);
        }

        $this->moveMorph(self::NEW_MORPH, self::OLD_MORPH);
        $this->renamePermissions('contractors.', 'data-entry.');

        $this->assertCounts(array_combine(array_keys(self::TABLES), $before));
        $this->forgetPermissionCache();
    }

    /**
     * @param  array<int, string>  $tables
     * @return array<int, int>
     */
    private function countRows(array $tables): array
    {
        return array_map(fn (string $table) => DB::table($table)->count(), $tables);
    }

    private function moveMorph(string $from, string $to): void
    {
        DB::table('attendance_days')->where('attendable_type', $from)->update(['attendable_type' => $to]);
    }

    private function renamePermissions(string $from, string $to): void
    {
        foreach (DB::table('permissions')->where('name', 'like', $from.'%')->get() as $permission) {
            DB::table('permissions')
                ->where('id', $permission->id)
                ->update(['name' => str_replace($from, $to, $permission->name)]);
        }
    }

    /** @param  array<string, int>  $expected */
    private function assertCounts(array $expected): void
    {
        foreach ($expected as $table => $count) {
            $actual = DB::table($table)->count();

            if ($actual !== $count) {
                throw new RuntimeException(
                    "إعادة التسمية غيّرت عدد الصفوف في {$table}: كان {$count} وصار {$actual}."
                );
            }
        }
    }

    private function forgetPermissionCache(): void
    {
        if (app()->bound(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
};
