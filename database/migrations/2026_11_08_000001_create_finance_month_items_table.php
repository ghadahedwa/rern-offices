<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * قيم عناصر أبنية المحاكم لكل شهر — المرحلة (د).
 *
 * ⚠️ الاسمان (`finance_month_items` · `court_building_item_id`) يقرؤهما حارس حذف العنصر
 *    (`CourtBuildingItem::isInUse`) — فيصير حقيقياً لحظة إنشاء الجدول.
 * ⚠️ العنصر `restrictOnDelete`: حذفه يُنقص مجموع شهرٍ مقفول صدر به أمر دفع.
 * `finance_months.amount` يبقى **الإجمالي** محسوباً عند الحفظ — فصفحة الحساب وإجمالي
 * السنة والتنبيهات تقرأ عموداً واحداً للحسابات كلها.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_month_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_month_id')->constrained('finance_months')->cascadeOnDelete();
            $table->foreignId('court_building_item_id')->constrained('court_building_items')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->timestamps();

            // ⚠️ اسمٌ قصير: المولَّد تلقائياً يتجاوز حدّ MySQL (٦٤ حرفاً) — sqlite لا يكشفه
            $table->unique(['finance_month_id', 'court_building_item_id'], 'fin_month_items_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_month_items');
    }
};
