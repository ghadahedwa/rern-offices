<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * سلة محذوفات لآراء المواطنين.
 *
 * الحذف من شاشات النتائج حذف منطقي لا نهائي، لسببين:
 * 1. الاسترجاع — «حدّد كل المطابق للفلتر» يمسح مئات الصفوف بضغطة واحدة.
 * 2. منع التكرار — FeedbackGate::duplicateRetryDate يقرأ الصفوف المحذوفة أيضاً
 *    (withTrashed)، فحذف رأي عبثي إدارياً لا يفتح لصاحبه باباً لإعادة إرساله فوراً.
 *
 * جدول feedback_rejected_attempts مستثنى عمداً: يُنظَّف تلقائياً كل ٣٠ يوماً
 * ولا علاقة له بمنع التكرار، فحذفه نهائي.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback_ratings', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('feedback_suggestions', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('feedback_ratings', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('feedback_suggestions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
