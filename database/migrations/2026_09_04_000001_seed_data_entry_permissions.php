<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * صلاحيات مدخلي البيانات — عنوان «مدخلو البيانات» في شبكة الأدوار.
 *
 * ستٌّ لا أكثر. وثلاث ملاحظات على ما ليس فيها:
 *   - لا `data-entry.view` منفصلة: سجل شخصٍ واحد ليس صفحةً كصفحة المقر،
 *     وإضافتها لاحقاً تعني إعادة تعديل كل دورٍ يدوياً — فالقرار يُتّخذ الآن.
 *   - لا صلاحية لمستوى التقرير (فرع/محافظة/جمهورية): النطاق يتكفّل بها —
 *     المفتش المربوط بمحافظتين يرى محافظتيه، والسوبر أدمن يرى الكل، بنفس الشاشة.
 *   - `attendance` مفصولة عن `edit` عمداً: مَن يسجّل الحضور اليومي ليس بالضرورة
 *     مَن يعدّل أسماء المدخلين وأرقام هواتفهم.
 *
 * ⚠️ النطاق **محافظة** كالمقرات (نفس pivot `governorate_user`) — ولذلك أُضيف
 *    العنوان إلى `PermissionGroups::GOVERNORATE_BRANCHES`؛ بدونه يُحفظ المستخدم
 *    بلا محافظة فيجد الشاشة فارغة أبداً.
 *
 * ⚠️ الأدوار تُنشأ من شاشة الأدوار لا من هجرة (قاعدة المشروع) — هذه تزرع
 *    الصلاحيات وتمنحها لـ super-admin/admin فقط، ودور المفتش يُعلَّم من الشاشة.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'data-entry.index',
        'data-entry.attendance',
        'data-entry.create',
        'data-entry.edit',
        'data-entry.export',
        'data-entry.delete',
    ];

    public function up(): void
    {
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
    }
};
