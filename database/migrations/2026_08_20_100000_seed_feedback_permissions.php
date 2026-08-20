<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * صلاحيات موديول نتائج رأي المواطن — عنوان «رأي المواطن» في شبكة الأدوار.
 *
 * قبلها كان الموديول كله `super_admin_only`. الثلاث مفصولة عن قصد:
 * العرض حاجة يومية للمشرف، والتصدير يُخرج البيانات من النظام،
 * والحذف يمسّ رأي مواطن — فلا يصحّ أن يفتح أحدها الثلاثة.
 *
 * ⚠️ النطاق ليس هنا: مَن له `feedback.view` يرى **محافظاته وحدها**
 *    (App\Support\FeedbackResults\FeedbackScope) — الصلاحية تقول «ماذا أفعل»
 *    والمحافظة تقول «على أي بيانات».
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'feedback.view',
        'feedback.export',
        'feedback.delete',
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
