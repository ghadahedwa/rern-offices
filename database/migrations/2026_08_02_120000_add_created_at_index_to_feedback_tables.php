<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فهرس على created_at لجداول البوابة.
 *
 * شاشات النتائج تفلتر بالفترة (from/to) على كل استعلام تجميعي في الداشبورد،
 * وأمر feedback:prune-rejected يحذف بـ created_at < X يومياً — الاثنان كانا
 * بلا فهرس. (فلترة الفترة كانت أيضاً تستخدم whereDate فتعطّل أي فهرس؛ صُحّحت
 * في WithFeedbackFilters::applyDateRange مع هذه الهجرة.)
 */
return new class extends Migration
{
    private const TABLES = ['feedback_ratings', 'feedback_suggestions', 'feedback_rejected_attempts'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->index('created_at');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex(['created_at']);
            });
        }
    }
};
