<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * الصلاحيات الست للشئون المالية (المرحلة ٤ · 2026-11-15): الحسابات الأربعة صارت تُدخلها الوحدات،
 * فلا «حسابات عامة» بعد اليوم — عرض الإجمالي واحد لكل الحسابات.
 *
 *   `finance.court-total.view` ← **يُعاد تسميته** `finance.total.view` (المعرّف نفسه فالأدوار تحتفظ به)
 *   `finance.accounts.view`    ← مَن له يأخذ `finance.total.view` ثم يُحذف
 *   `finance.accounts.create`  ← مَن له يأخذ `finance.units.create` ثم يُحذف
 *
 * ⚠️ **لا يفقد دورٌ شيئاً**: من كان يرى حساباً يرى إجماليه، ومن كان يُدخل يُدخل الوحدات (المربوطة به).
 * ⚠️ الكاش يُمسح أولاً وآخراً — بدونه تبقى الأسماء القديمة في كاش Spatie فيُرفض أصحابها (٤٠٣).
 */
return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $total = Permission::where('name', 'finance.court-total.view')->where('guard_name', 'web')->first();
        if ($total) {
            $total->update(['name' => 'finance.total.view']);
        } else {
            $total = Permission::firstOrCreate(['name' => 'finance.total.view', 'guard_name' => 'web']);
        }
        $create = Permission::firstOrCreate(['name' => 'finance.units.create', 'guard_name' => 'web']);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (Role::with('permissions')->get() as $role) {
            $has = $role->permissions->pluck('name');

            if ($has->contains('finance.accounts.view')) {
                $role->givePermissionTo($total);
            }
            if ($has->contains('finance.accounts.create')) {
                $role->givePermissionTo($create);
            }
        }

        Role::where('name', 'super-admin')->first()?->givePermissionTo([$total, $create]);

        Permission::whereIn('name', ['finance.accounts.view', 'finance.accounts.create'])->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $view   = Permission::firstOrCreate(['name' => 'finance.accounts.view', 'guard_name' => 'web']);
        $create = Permission::firstOrCreate(['name' => 'finance.accounts.create', 'guard_name' => 'web']);

        foreach (Role::with('permissions')->get() as $role) {
            $has = $role->permissions->pluck('name');

            if ($has->contains('finance.total.view')) {
                $role->givePermissionTo($view);
            }
            if ($has->contains('finance.units.create')) {
                $role->givePermissionTo($create);
            }
        }

        DB::table('permissions')->where('name', 'finance.total.view')->update(['name' => 'finance.court-total.view']);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
