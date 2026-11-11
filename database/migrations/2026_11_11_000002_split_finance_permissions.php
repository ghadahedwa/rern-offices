<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * تقسيم صلاحيات الشئون المالية (ملف العميل 2026-11-11): العرض والإدخال صارا لكلٍّ من
 * الحسابات العامة · إجمالي أبنية المحاكم · الوحدات، وأُضيفت «التقارير».
 *
 * ⚠️ **لا يفقد دورٌ شيئاً**: مَن له `finance.view` يأخذ صلاحيات العرض الثلاث، ومَن له
 *    `finance.create` يأخذ الإدخالين، ومَن له الأربع القديمة كلها يأخذ «التقارير» معها.
 *    ثم تُحذف القديمتان. `finance.edit`/`finance.delete` باقيتان كما هما.
 * ⚠️ الكاش يُمسح أولاً وآخراً — بدونه تبقى الأسماء القديمة في كاش Spatie فيُرفض أصحابها (٤٠٣).
 */
return new class extends Migration
{
    private const VIEWS   = ['finance.accounts.view', 'finance.court-total.view', 'finance.units.view'];
    private const CREATES = ['finance.accounts.create', 'finance.units.create'];
    private const REPORTS = 'finance.reports';
    private const OLD_ALL = ['finance.view', 'finance.create', 'finance.edit', 'finance.delete'];

    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ([...self::VIEWS, ...self::CREATES, self::REPORTS] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (Role::with('permissions')->get() as $role) {
            $has = $role->permissions->pluck('name');

            if ($has->contains('finance.view')) {
                $role->givePermissionTo(self::VIEWS);
            }
            if ($has->contains('finance.create')) {
                $role->givePermissionTo(self::CREATES);
            }
            if ($has->intersect(self::OLD_ALL)->count() === count(self::OLD_ALL)) {
                $role->givePermissionTo(self::REPORTS);
            }
        }

        Role::where('name', 'super-admin')->first()
            ?->givePermissionTo([...self::VIEWS, ...self::CREATES, self::REPORTS]);

        Permission::whereIn('name', ['finance.view', 'finance.create'])->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'finance.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'finance.create', 'guard_name' => 'web']);

        foreach (Role::with('permissions')->get() as $role) {
            $has = $role->permissions->pluck('name');

            if ($has->intersect(self::VIEWS)->isNotEmpty()) {
                $role->givePermissionTo('finance.view');
            }
            if ($has->intersect(self::CREATES)->isNotEmpty()) {
                $role->givePermissionTo('finance.create');
            }
        }

        Permission::whereIn('name', [...self::VIEWS, ...self::CREATES, self::REPORTS])->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
