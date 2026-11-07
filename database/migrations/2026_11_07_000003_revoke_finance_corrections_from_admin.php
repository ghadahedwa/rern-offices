<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * تصحيح الشهر المالي وحذفه للسوبر أدمن وحده (قرار العميل).
 *
 * هجرة المرحلة (أ) منحت الأربع لـ`admin` كباقي الموديولات — والشهر يُقفل لحظة
 * حفظه لأنه صدر به أمر دفع، فالتصحيح استثناءٌ لا عملٌ إداري عادي. يبقى لـ`admin`
 * العرض والإدخال، ومَن احتاج التصحيح يُمنحه من شاشة الأدوار صراحةً.
 */
return new class extends Migration
{
    private const REVOKED = ['finance.edit', 'finance.delete'];

    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $admin = Role::where('name', 'admin')->first();

        foreach (self::REVOKED as $name) {
            if ($admin?->hasPermissionTo($name)) {
                $admin->revokePermissionTo($name);
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Role::where('name', 'admin')->first()?->givePermissionTo(self::REVOKED);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
