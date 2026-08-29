<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * تفتيت `warehouses.create` الخشنة، وحذف صلاحيتين ميتتين.
 *
 * ⚠️ **العلّة**: الصلاحية الواحدة كانت تفتح ثلاثة أفعال متباعدة الخطر —
 *    تسجيل نقلٍ يومي، وتسجيل وارد، و**ضبط الرصيد الافتتاحي**. والأخير
 *    يكتب رصيد أي مخزن كتابةً (`$after = $quantity` في `WarehouseLedger`،
 *    لا جمع)، فمنحُ موظفٍ حقَّ النقل كان يمنحه حقَّ إعادة كتابة أرصدة
 *    «المخزن الرئيسي بالمصلحة» — مليون وستمائة ألف صنف.
 *
 * ⚠️ و`warehouses.view` و`warehouses.edit` **ميتتان**: لا يفحصهما سطرٌ
 *    واحد في المشروع، ولا تظهران إلا في شبكة الأدوار — فمَن يمنحهما يظن
 *    أنه منح شيئاً. تُحذفان مع التفتيت لا بعده، فالدين لا يُرحَّل.
 *
 * والنقل هنا **لا يوسّع لأحد ولا يضيّق**: كل مَن يملك الخشنة اليوم يخرج
 * من هذه الهجرة بالثلاث مجتمعةً، دوراً كان أو مستخدماً.
 */
return new class extends Migration
{
    /** الأفعال الثلاثة التي كانت تسكن `warehouses.create`. */
    private const SPLIT = [
        'warehouses.opening',   // الرصيد الافتتاحي — يضبط الرصيد ضبطاً
        'warehouses.incoming',  // تسجيل الوارد على المخزن الرئيسي
        'warehouses.transfer',  // النقل بين المخازن
    ];

    private const DEAD = [
        'warehouses.view',
        'warehouses.edit',
    ];

    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::SPLIT as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $old = Permission::where('name', 'warehouses.create')->first();

        if ($old) {
            $new = Permission::whereIn('name', self::SPLIT)->get();

            // الأدوار والمستخدمون على السواء: الإسناد المباشر للمستخدم وارد
            // في هذا المشروع، وتركُه يُخرج مستخدماً كان يعمل أمس عاجزاً اليوم
            foreach ($old->roles as $role) {
                $role->givePermissionTo($new);
            }

            foreach ($old->users as $user) {
                $user->givePermissionTo($new);
            }

            $old->delete();
        }

        Permission::whereIn('name', self::DEAD)->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $old = Permission::firstOrCreate(['name' => 'warehouses.create', 'guard_name' => 'web']);

        foreach (self::DEAD as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // مَن ملك **أياً** من الثلاث يستعيد الخشنة — الرجوع لا يُسقط أحداً
        foreach (Permission::whereIn('name', self::SPLIT)->get() as $permission) {
            foreach ($permission->roles as $role) {
                $role->givePermissionTo($old);
            }

            foreach ($permission->users as $user) {
                $user->givePermissionTo($old);
            }
        }

        Permission::whereIn('name', self::SPLIT)->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
