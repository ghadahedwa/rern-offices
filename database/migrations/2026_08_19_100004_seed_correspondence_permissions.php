<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * صلاحيات المراسلات التشغيلية — عنوان «المراسلات» في شبكة الأدوار.
 *
 * تُزرع الآن لأن ثمّة كوداً يقرأها بالفعل: الأقسام المشروطة في فورم المستخدم
 * (App\Support\PermissionGroups::needsEntity) — فبدونها لا يظهر حقل الطرف لأحد.
 * ⚠️ أمّا الإنفاذ في الشاشات (approve/stamp/...) فيصل مع كل شاشة في موضعها.
 *
 * correspondence.settings مزروعة سابقاً وتبقى تحت «إدارة النظام» — ولا تُظهر حقل الطرف.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'correspondence.index',
        'correspondence.view',
        'correspondence.create',
        'correspondence.approve',
        'correspondence.delegate',
        'correspondence.assign',
        'correspondence.share',
        'correspondence.delete',
        'correspondence.export',
        'correspondence.attachments',
        'correspondence.stamp',
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
