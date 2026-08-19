<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * أدوار المراسلات الثلاثة — **بلا مستخدمين**.
 *
 * الحسابات في CorrespondenceDemoUsersSeeder المنفصل، وهو يرفض production.
 * والفصل مقصود: الأدوار إعداد رسمي يُستحسن وجوده على أي بيئة،
 * والحسابات الخمسة تجريبية محلية لا يجوز أن تصل الإنتاج بحال.
 *
 * ⚠️ لا يُستدعى من DatabaseSeeder ولا من أي هجرة — يُشغَّل يدوياً:
 *     php artisan db:seed --class=CorrespondenceRolesSeeder
 *
 * الأدوار بيانات لا كود، والـgit لا ينقلها؛ فوجود التعريف هنا يجعله مراجَعاً
 * وقابلاً للتكرار، ولا يخالف قاعدة «الهجرات تزرع صلاحيات لا أدواراً».
 *
 * idempotent: syncPermissions يصحّح دوراً قائماً عُدِّل يدوياً.
 */
class CorrespondenceRolesSeeder extends Seeder
{
    /**
     * الدور => صلاحياته (بلا البادئة correspondence.)
     *
     * ⚠️ `delete` غير ممنوحة لأحد: الحذف من سجل رسمي مرقَّم فعل ثقيل،
     *    ومنح الصلاحية لاحقاً أهون من استرجاع رقم حُذف.
     * ⚠️ `share` غير ممنوحة: مؤجَّلة بقرار العميل (س٨) فلا كود ينفّذها،
     *    ومنحها يوهم بقدرة غير موجودة.
     * ⚠️ `settings` تبقى لمدير النظام وحده.
     */
    public const ROLES = [
        'سكرتارية' => ['index', 'view', 'create', 'attachments', 'export'],
        'رئيس جهة' => ['index', 'view', 'create', 'attachments', 'export', 'assign', 'approve', 'stamp', 'delegate'],
        'مفتش'     => ['index', 'view', 'create', 'attachments'],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::ROLES as $roleName => $abilities) {
            $permissions = array_map(fn ($a) => 'correspondence.'.$a, $abilities);

            $missing = array_diff($permissions, Permission::whereIn('name', $permissions)->pluck('name')->all());
            if ($missing) {
                throw new \RuntimeException('صلاحيات غير مزروعة: '.implode(', ', $missing).' — شغّل php artisan migrate أولاً.');
            }

            $role = Role::findOrCreate($roleName, 'web');

            // المستوى ١ دائماً: المستوى معناه «نشاط مَن أرى» ويُضبط من السلّم الإداري
            // (مفتش/مستشار/رئيس) لا من أدوار الفروع.
            $role->forceFill(['level' => 1])->save();

            $role->syncPermissions($permissions);

            $this->command?->info("الدور «{$roleName}»: ".count($permissions).' صلاحية');
        }
    }
}
