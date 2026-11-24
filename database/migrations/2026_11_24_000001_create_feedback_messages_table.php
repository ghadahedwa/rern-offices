<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * رسائل المواطنين النصية — جدول واحد لأنواع البوابة الجديدة (شكر موظف أولاً، ثم أصحاب الهمم
 * وفكرة التطوير والإبلاغ عن مشكلة أو سلوك) بعمود `type`. تعريف كل نوع (إلزام الهوية والمقر
 * واسم الموظف) في FeedbackMessage::TYPES، فنوعٌ جديد إعدادٌ لا جدول.
 *
 * ⚠️ المقر والمحافظة nullable: «فكرة لتطوير الخدمات» بلا مقر، والحذف nullOnDelete
 *    كباقي جداول البوابة (الرسالة تبقى لو حُذف المقر).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_messages', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);

            $table->foreignId('governorate_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name', 100)->nullable();
            $table->string('national_id', 14)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('device_token', 64)->nullable();

            $table->string('employee_name', 150)->nullable();
            $table->text('body');

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            // الصف المحذوف إدارياً يظل حارساً لنافذة التكرار (نمط باقي جداول البوابة)
            $table->softDeletes();

            $table->index(['type', 'created_at']);
            $table->index(['type', 'office_id']);
            $table->index(['national_id', 'office_id']);
            $table->index(['phone', 'office_id']);
            $table->index(['device_token', 'office_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_messages');
    }
};
