<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * الشئون المالية — المرحلة (أ): الهيكل. المرجع `documentation/finance-system.md`.
 *
 * ثلاثة أشياء:
 *   1. **الحسابات الأربعة** صفوفاً ثابتة بمفتاح وشكل — جداول الشهور (المرحلة ب)
 *      تربط بها بمفتاح أجنبي. الاسم المعروض في ملف اللغة لا هنا (`fin_account_{key}`).
 *   2. **عناصر أبنية المحاكم** الثمانية — قائمة تُدار من «إدارة النظام»
 *      فعنصرٌ تاسع لا يحتاج كوداً.
 *   3. **أربع صلاحيات** `finance.view/create/edit/delete` تسري على الصفحات الأربع معاً.
 *
 * ⚠️ لا محافظة ولا مقر: الأرقام للجمهورية كلها — فلا يدخل الفرع
 *    `PermissionGroups::GOVERNORATE_BRANCHES`.
 * ⚠️ الأدوار تُنشأ من شاشة الأدوار لا من هجرة: الصلاحيات تُمنح هنا لـ`super-admin`/`admin` وحدهما.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'finance.view',
        'finance.create',
        'finance.edit',
        'finance.delete',
    ];

    /** المفتاح => الشكل، بترتيب ورقة العميل. */
    private const ACCOUNTS = [
        'court_buildings'     => 'itemized', // مجموع عناصر
        'mechanization'       => 'single',   // مبلغ واحد
        'telecom'             => 'ledger',   // إيراد ومنصرف برصيد مرحَّل
        'ministry_of_finance' => 'single',
    ];

    private const COURT_BUILDING_ITEMS = [
        'قيمة نموذج طلب الشهر ق٩',
        'مصاريف إدارية',
        'قيمة نماذج التوكيلات المؤمَّنة',
        'سيارات صباحي',
        'سيارات مسائي',
        'سيارات جمعة',
        'مكاتب مميزة',
        'فترة مسائية ثانية',
    ];

    public function up(): void
    {
        Schema::create('finance_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('kind');
            $table->unsignedTinyInteger('order');
            $table->timestamps();
        });

        Schema::create('court_building_items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();

        $order = 0;
        foreach (self::ACCOUNTS as $key => $kind) {
            DB::table('finance_accounts')->insert([
                'key' => $key, 'kind' => $kind, 'order' => ++$order,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        foreach (self::COURT_BUILDING_ITEMS as $i => $name) {
            DB::table('court_building_items')->insert([
                'name' => $name, 'order' => $i + 1, 'is_active' => true,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $permissions = Permission::whereIn('name', self::PERMISSIONS)->get();

        foreach (['super-admin', 'admin'] as $roleName) {
            Role::where('name', $roleName)->first()?->givePermissionTo($permissions);
        }
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::whereIn('name', self::PERMISSIONS)->delete();

        Schema::dropIfExists('court_building_items');
        Schema::dropIfExists('finance_accounts');
    }
};
