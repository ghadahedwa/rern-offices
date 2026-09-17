<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * «وصل الكشف» — كشفُ هذا العامل في هذا المقر عن هذا الشهر وصل وروجِع.
 *
 * ⚠️ **لماذا يلزم أصلاً**: كل خلية تبدأ «حاضر»، فعاملٌ لم يصل كشفه يخرج «حاضراً الشهر
 *    كله» بلا فرقٍ ظاهر عمّن حضر فعلاً. غياب الصفّ هنا يُخرجه في التقرير «غير مراجَع».
 * ⚠️ **المقر جزءٌ من المفتاح** (قرار العميلة ٢٠٢٦-٠٩-١٥: التعليم على المقر): المنقول
 *    في منتصف الشهر يُرسل رئيسا فرعين كشفَي نصفين، ووصول أحدهما لا يعني وصول الآخر.
 *    ولذلك يُقرأ اليوم «مراجَعاً» حين يكون لمقرّه **في ذلك اليوم** صفٌّ هنا.
 * ⚠️ **الشهر أول يومه** (`date`) لا نصٌّ `Y-m`: يُقارن ويُفهرس كتاريخ في المحرّكين.
 * ⚠️ polymorphic كـ`attendance_days` — نفس السبب (موظفو «تشكيل المكتب» لاحقاً).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_reviews', function (Blueprint $table) {
            $table->id();
            $table->morphs('attendable');
            // حذف المقر يحذف مراجعاته: مفتاحٌ بلا مقر لا يُطابِق يوماً في أي تقرير.
            $table->foreignId('office_id')->constrained('offices')->cascadeOnDelete();
            $table->date('month');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['attendable_type', 'attendable_id', 'office_id', 'month'], 'attendance_reviews_unique');
            $table->index(['office_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_reviews');
    }
};
