<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فحص التكرار في FeedbackGate::duplicateRetryDate يبحث بـ
 * (national_id OR phone) + office_id، وفرع الهاتف كان بلا فهرس فيتحوّل
 * إلى مسح كامل للجدول — والاستعلام يتنفّذ أثناء الكتابة في الفورم العام.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback_ratings', function (Blueprint $table) {
            $table->index(['phone', 'office_id']);
        });

        Schema::table('feedback_suggestions', function (Blueprint $table) {
            $table->index(['phone', 'office_id']);
        });
    }

    public function down(): void
    {
        Schema::table('feedback_ratings', function (Blueprint $table) {
            $table->dropIndex(['phone', 'office_id']);
        });

        Schema::table('feedback_suggestions', function (Blueprint $table) {
            $table->dropIndex(['phone', 'office_id']);
        });
    }
};
