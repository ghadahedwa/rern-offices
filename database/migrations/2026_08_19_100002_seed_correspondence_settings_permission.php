<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * صلاحية إعدادات المراسلات وحدها — تحت عنوان «إدارة النظام» مع إعدادات المقرات والمخازن.
 *
 * ⚠️ صلاحيات المراسلات الباقية (index/view/create/approve/stamp…) تُزرع مع شاشاتها،
 * لا الآن — صلاحية بلا كود يفحصها تظهر في شبكة الأدوار فيمنحها المدير ولا تفعل شيئاً.
 * وهو نمط المشروع القائم (seed_claims_export_permission زرع صلاحية واحدة لاحقاً).
 */
return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name'       => 'correspondence.settings',
            'guard_name' => 'web',
        ]);

        foreach (['super-admin', 'admin'] as $roleName) {
            Role::where('name', $roleName)->first()?->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::where('name', 'correspondence.settings')->delete();
    }
};
