<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * مدخلو البيانات — العاملون لدى الشركة المتعاقدة، موزَّعون على المقرات.
 *
 * ⚠️ لا عمود مقر هنا ولا عمود محافظة: التسكين في `data_entry_assignments`
 *    بمدىً زمني، لأن المدخل ينتقل بين المقرات وتقريرُ فترةٍ ماضية يجب أن ينسب
 *    أيامها إلى مقره **وقتها** لا إلى مقره اليوم.
 * ⚠️ ولا عمود شركة (قرار العميلة): الأدمن ينشئ حساب الشركة ويحدّد نطاقه
 *    بالمحافظات كأي مستخدم، فلا كيان «شركات» في النظام.
 * ⚠️ ولا تاريخ التحاق/انتهاء هنا: مدة الخدمة هي مجموع مدد التسكين — مصدر واحد
 *    لا اثنان، وإلا اختلف رقمان عن الحقيقة نفسها.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_entry_operators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_entry_operators');
    }
};
