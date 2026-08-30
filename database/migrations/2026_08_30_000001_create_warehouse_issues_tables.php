<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * الصرف إلى مقر — **النوع الخامس من الحركة**، وبه ينقص مخزن المحافظة.
 *
 * ⚠️ العلّة التي يسدّها: أنواع الحركة الأربعة (`opening` · `incoming` ·
 *    `transfer_out` · `transfer_in`) **ليس فيها ما يُنقص مخزناً مستقبِلاً**.
 *    فالمخزن الفرعي لا ينقص إلا أن ينقل هو، وهو لا ينقل — فرصيدُه المعروض
 *    كان **مجموع ما وصله منذ نشأته لا ما فيه**.
 *
 * ووصف العميل الحركة بلفظه: «يودّي جهاز الكمبيوتر لفرع شبين القناطر… كده
 * هيقدر يخصم من الرصيد اللي عنده». و«فرع شبين القناطر» **مقرٌّ** في موديول
 * المقرات لا مخزن — فالمستلِم هنا `offices` لا `warehouses`، وهو ما يفرّق
 * هذه الحركة عن النقل.
 *
 * ⚠️ والمقر **ليس مكان رصيد**: الصرف يُنقص المخزن ويقيّد المستلِم بالاسم،
 *    فتُجيب المنظومة «أين ذهب هذا الجهاز ومَن استلمه» من سجل الحركات. ولو
 *    أُريد لاحقاً «ما عند المقر الآن» أُضيف فوق هذا بلا هدم.
 */
return new class extends Migration
{
    public function up(): void
    {
        // رأس مستند الصرف — من مخزن إلى مقر، بصنفٍ أو أكثر
        Schema::create('warehouse_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            // ⚠️ `restrictOnDelete` كالنقل: المقر المستلِم جزء من سجلّ محاسبي
            //    موقَّع، فحذفُه لا يجوز أن يمحو مَن استلم
            $table->foreignId('office_id')->constrained('offices')->restrictOnDelete();
            $table->date('issued_at');                        // تاريخ الصرف
            $table->string('document_type')->nullable();      // إذن صرف / استمارة نقل عهدة
            $table->string('attachment_path');                // مرفق إجباري كالوارد والنقل
            $table->string('attachment_original_name');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('issued_at');
        });

        Schema::create('warehouse_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_issue_id')->constrained('warehouse_issues')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ⚠️ صلاحية مستقلة: الصرف فعل **المفتش** لا أمين المخزن الرئيسي،
        //    فلا تُجمع مع `warehouses.transfer` وإلا عاد الخلط الذي فُتِّتت
        //    `warehouses.create` من أجله.
        Permission::firstOrCreate(['name' => 'warehouses.issue', 'guard_name' => 'web']);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_issue_items');
        Schema::dropIfExists('warehouse_issues');

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::where('name', 'warehouses.issue')->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
