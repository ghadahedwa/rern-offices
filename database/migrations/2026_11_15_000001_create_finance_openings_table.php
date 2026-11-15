<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * اتصالات على الوحدات الحسابية (المرحلة ٢ · طلب العميل 2026-11-15): رصيدٌ افتتاحي **لكل وحدة**
 * بتاريخ آخر سبتمبر ٢٠٢٦ — كانت ثلاثة أعمدة على `finance_accounts` لحسابٍ واحد للجمهورية.
 *
 *   - جدولٌ لا أعمدة على الوحدة: الرصيد للحساب ذي الرصيد × الوحدة، فحسابٌ آخر برصيدٍ لا يحتاج هجرة.
 *   - ⚠️ `restrictOnDelete` على الوحدة: وحدةٌ لها رصيدٌ افتتاحي لا تُحذف (الحارس `FinanceUnit::isInUse`).
 *   - ⚠️ أعمدة الحساب القديمة تُحذف بلا نقل — لا بيانات اتصالات على السيرفر (مؤكَّد قبل التنفيذ).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_openings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_account_id')->constrained('finance_accounts')->cascadeOnDelete();
            $table->foreignId('finance_unit_id')->constrained('finance_units')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->foreignId('set_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('set_at')->nullable();
            $table->timestamps();

            $table->unique(['finance_account_id', 'finance_unit_id'], 'fin_openings_account_unit_unique');
        });

        Schema::table('finance_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('opening_set_by');
        });

        Schema::table('finance_accounts', function (Blueprint $table) {
            $table->dropColumn(['opening_balance', 'opening_set_at']);
        });
    }

    public function down(): void
    {
        Schema::table('finance_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('opening_balance')->nullable()->after('order');
            $table->foreignId('opening_set_by')->nullable()->after('opening_balance')->constrained('users')->nullOnDelete();
            $table->timestamp('opening_set_at')->nullable()->after('opening_set_by');
        });

        Schema::dropIfExists('finance_openings');
    }
};
