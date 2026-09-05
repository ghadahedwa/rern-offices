<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * سجل الحضور — **جدول استثناءات فقط** (غياب · إجازة).
 *
 * ⚠️ القاعدة الحاكمة للموديول كله (قرار العميلة): المفتش يُدخل أقل داتا ممكنة —
 *    يسجّل أيام الغياب والإجازة، و**كل يوم عملٍ بلا صفٍّ هنا حضورٌ بالاشتقاق**.
 *    فلا صفّ لكل يوم لكل مدخل، ولا معنى لتخزين «حاضر» (يبقى في جدول الحالات
 *    لمن أراد تعليماً صريحاً، ولا يُخزَّن افتراضياً).
 * ⚠️ ولذلك **لا يُسجَّل استثناء في جمعة ولا في عطلة رسمية ولا خارج مدة الخدمة**:
 *    اليوم مخصوم أصلاً من أيام العمل، فتسجيله غياباً يخصمه مرة ثانية.
 * ⚠️ polymorphic بقصد: `attendable` اليوم مدخل بيانات، وغداً موظف «تشكيل المكتب»
 *    بلا هجرة مؤلمة ولا جدول ثانٍ بنفس المنطق.
 * ⚠️ `UNIQUE(attendable, date)` — يومٌ واحد لا يحمل حالتين، والقيد في المحرّك
 *    لا في الشاشة: تسجيلان متزامنان يمرّان من فحصٍ في PHP ولا يمرّان من هنا.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_days', function (Blueprint $table) {
            $table->id();
            $table->morphs('attendable');
            $table->date('date');
            $table->foreignId('status_id')->constrained('attendance_statuses')->restrictOnDelete();
            // سجل مساءلة: مَن سجّل هذا اليوم — يبقى الصفّ لو حُذف المستخدم.
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['attendable_type', 'attendable_id', 'date'], 'attendance_days_unique_day');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_days');
    }
};
