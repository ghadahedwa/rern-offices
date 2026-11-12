<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * شهور أبنية المحاكم على الوحدات الحسابية (الدفعة الثانية · ملف العميل 2026-11-11).
 *
 *   - `finance_months.finance_unit_id`: وحدة الشهر — لأبنية المحاكم وحدها، والحسابات الثلاثة NULL.
 *     ⚠️ `restrictOnDelete`: وحدةٌ لها شهر لا تُحذف (يُنقص إجمالي شهرٍ مقفول) — وحارس الشاشة
 *     `FinanceUnit::isInUse` يصير حقيقياً بهذا العمود.
 *   - **التفرّد (الحساب · الوحدة · الشهر)** بدل (الحساب · الشهر). ⚠️ MySQL يعدّ NULL متمايزة في
 *     القيد الفريد، فقيدٌ على العمود مباشرةً كان يقبل شهرين للحساب ذي المبلغ الواحد — فالقيد على
 *     عمودٍ مولَّد `unit_key` = الوحدة أو صفر. والقيد الجديد يُنشأ قبل حذف القديم: يبدأ بعمود
 *     الحساب فيبقى فهرساً لمفتاحه الأجنبي.
 *   - `finance_notes.finance_unit_id`: ملاحظة شهر الوحدة، وNULL لملاحظة الإجمالي والحسابات الثلاثة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_months', function (Blueprint $table) {
            $table->foreignId('finance_unit_id')->nullable()->after('finance_account_id')
                ->constrained('finance_units')->restrictOnDelete();
            $table->unsignedBigInteger('unit_key')->virtualAs('COALESCE(finance_unit_id, 0)');
        });

        Schema::table('finance_months', function (Blueprint $table) {
            $table->unique(['finance_account_id', 'unit_key', 'month'], 'fin_months_account_unit_month_unique');
        });

        Schema::table('finance_months', function (Blueprint $table) {
            $table->dropUnique(['finance_account_id', 'month']);
        });

        Schema::table('finance_notes', function (Blueprint $table) {
            $table->foreignId('finance_unit_id')->nullable()->after('finance_account_id')
                ->constrained('finance_units')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('finance_notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('finance_unit_id');
        });

        Schema::table('finance_months', function (Blueprint $table) {
            $table->unique(['finance_account_id', 'month']);
        });

        Schema::table('finance_months', function (Blueprint $table) {
            $table->dropUnique('fin_months_account_unit_month_unique');
            $table->dropColumn('unit_key');
            $table->dropConstrainedForeignId('finance_unit_id');
        });
    }
};
