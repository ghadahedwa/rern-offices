<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * `data-entry.settings` — إدارة حالات الحضور (قائمة مرجعية).
 *
 * ⚠️ تسكن «إدارة النظام» لا فرع مدخلي البيانات، كـ`offices.settings`
 *    و`warehouses.settings` — ولذلك **لا تُطالِب صاحبها باختيار محافظات**:
 *    مدير القوائم المرجعية لا نطاق له. والاستثناء مكتوب في `PermissionGroups`
 *    (`except`) وإلا لالتقطتها بادئة `data-entry.` فطالبته بمحافظات بلا معنى.
 */
return new class extends Migration
{
    private const PERMISSION = 'data-entry.settings';

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
