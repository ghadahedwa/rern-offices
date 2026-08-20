<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * `feedback.rejected` — شاشة المحاولات المرفوضة بصلاحية مستقلة.
 *
 * انفصلت عن `feedback.view` بقرار المستخدمة: الشاشة **أمنية لا تقريرية**
 * (سبب الرفض · الـIP · بصمة المتصفح)، فمَن يتابع رضا المواطنين لا يلزمه سجل
 * محاولات البوابة. وتبقى مفلترة بالمحافظات لغير السوبر أدمن كباقي الشاشات.
 *
 * ⚠️ هجرة مستقلة عن `2026_08_20_100000` عن قصد — تلك كانت قد نُفِّذت محلياً،
 *    وتعديل هجرة منفَّذة يعني حذف الصلاحيات وإعادة زرعها (وضياع ما أُسنِد منها لدور).
 */
return new class extends Migration
{
    private const PERMISSION = 'feedback.rejected';

    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::firstOrCreate(['name' => self::PERMISSION, 'guard_name' => 'web']);

        foreach (['super-admin', 'admin'] as $roleName) {
            Role::where('name', $roleName)->first()?->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::where('name', self::PERMISSION)->delete();
    }
};
