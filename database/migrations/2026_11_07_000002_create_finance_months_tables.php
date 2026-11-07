<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الشئون المالية — المرحلة (ب): الشهور وأوامر الدفع ومرفقاتها.
 *
 *   finance_months          شهرٌ واحد لكل حساب (`UNIQUE`) — الشهر أول يومه
 *   finance_payment_orders  أمر دفع أو أكثر للشهر (قرار العميل: «غالباً» واحد)
 *   finance_attachments     صورة أمر الدفع — واحدة على الأقل لكل أمر، حتى خمس
 *
 * ⚠️ `amount` **nullable**: للحساب ذي المبلغ الواحد. أبنية المحاكم مجموع عناصرها
 *    (المرحلة د) واتصالات إيرادٌ ومنصرف (المرحلة هـ) — أعمدتهما تُضاف معهما.
 * ⚠️ **جنيه صحيح** (تصحيح المستخدمة): `unsignedBigInteger` لا `decimal`.
 * ⚠️ الحساب `restrictOnDelete`: الحسابات أربعة ثابتة، وحذف أحدها يمحو سجلّاً مالياً.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_months', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_account_id')->constrained('finance_accounts')->restrictOnDelete();
            $table->date('month');
            $table->unsignedBigInteger('amount')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // ⚠️ الحارس الأخير لإدخال الشهر نفسه مرتين في وقتٍ واحد
            $table->unique(['finance_account_id', 'month']);
        });

        Schema::create('finance_payment_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_month_id')->constrained('finance_months')->cascadeOnDelete();
            $table->string('number', 100);
            $table->date('issued_on');
            $table->unsignedTinyInteger('position')->default(1);
            $table->timestamps();
        });

        Schema::create('finance_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_payment_order_id')->constrained('finance_payment_orders')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_attachments');
        Schema::dropIfExists('finance_payment_orders');
        Schema::dropIfExists('finance_months');
    }
};
