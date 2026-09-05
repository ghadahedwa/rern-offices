<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * العطلات الرسمية — قائمة مرجعية واحدة للجمهورية كلها، يديرها السوبر أدمن.
 *
 * ⚠️ لماذا جدول لا استنتاج؟ لأن قرار **ترحيل** العطلة (المولد النبوي من الثلاثاء
 *    إلى الخميس) قرارٌ يصدر قبل موعده بأيام ولا قاعدة له — فلا هو محسوب بالتقويم
 *    الميلادي ولا بالهجري. الثابتة وحدها تُزرع بضغطة، والباقي يُدخل عند إعلانه.
 * ⚠️ الشاشة **مفتوحة**: تُضاف العطلة في أي وقت وتُعدَّل (الترحيل = تعديل تاريخ).
 *    والحساب لحظي لا مخزَّن، فتصحيح عطلةٍ يصحّح تقارير الشهر الماضي معها.
 *
 * العطلة قد تكون يوماً واحداً (starts_on = ends_on) أو مدى (وقفة + عيد).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->timestamps();

            // الاستعلام الوحيد على هذا الجدول تقاطعُ مدى بمدى، وطرفه الأيسر starts_on.
            $table->index(['starts_on', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_holidays');
    }
};
