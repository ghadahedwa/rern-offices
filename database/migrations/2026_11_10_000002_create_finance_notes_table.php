<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ملاحظات الشهور — المرحلة (و).
 *
 * ⚠️ **على الحساب والشهر لا على صفّ `finance_months`** (قرار العميل): الملاحظة مسموحة على
 *    شهرٍ لم تُدخَل أرقامه بعد (سبب التأخير)، وتبقى إن حُذف الشهر وأُعيد إدخاله.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_account_id')->constrained('finance_accounts')->restrictOnDelete();
            $table->date('month');
            $table->text('body');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['finance_account_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_notes');
    }
};
