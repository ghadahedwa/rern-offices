<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * حساب اتصالات — المرحلة (هـ).
 *
 *   - `finance_months.income`: إيراد الشهر للحساب ذي الرصيد وحده، و`amount` فيه **المنصرف**
 *     (ما يغطيه أمر الدفع) — فتنبيه «المبلغ بعيد عن الشهر السابق» يقارن منصرفاً بمنصرف.
 *   - `finance_accounts.opening_balance`: الرصيد قبل أول شهر (آخر سبتمبر ٢٠٢٦)، يُكتب مرة
 *     ويُقفل كالشهر. ⚠️ **المرحَّل لا يُخزَّن** — يُحسب من الافتتاحي والشهور في كل عرض
 *     (`TelecomLedger`)، فتصحيح شهرٍ قديم يصل لما بعده بلا تحديث صفوف.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_months', function (Blueprint $table) {
            $table->unsignedBigInteger('income')->nullable()->after('amount');
        });

        Schema::table('finance_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('opening_balance')->nullable()->after('order');
            $table->foreignId('opening_set_by')->nullable()->after('opening_balance')->constrained('users')->nullOnDelete();
            $table->timestamp('opening_set_at')->nullable()->after('opening_set_by');
        });
    }

    public function down(): void
    {
        Schema::table('finance_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('opening_set_by');
            $table->dropColumn(['opening_balance', 'opening_set_at']);
        });

        Schema::table('finance_months', function (Blueprint $table) {
            $table->dropColumn('income');
        });
    }
};
