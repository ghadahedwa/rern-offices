<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * نطاق المخازن للمستخدم — نظير `governorate_user` في موديول المقرات.
 *
 * ⚠️ الاسم `user_warehouse` لا `warehouse_user`: Laravel يشتقّ اسم جدول
 *    الوسيط **أبجدياً** من اسمَي الموديلين، وهو نفسه سببُ شكل
 *    `governorate_user` (g قبل u). ومخالفتُه تُخرج «no such table» عند أول
 *    قراءةٍ للعلاقة لا عند الهجرة، فيبدو العطل بعيداً عن موضعه.
 *
 * ⚠️ **لماذا ربطٌ صريح لا اشتقاق من محافظات المستخدم؟** ثلاثة أعذار مقيسة:
 *   - «المخزن الرئيسي بالمصلحة» **بلا محافظة**، فلا اشتقاق يبلغه، وأمينُه
 *     ليس مفتش محافظة أصلاً.
 *   - أسيوط فيها **مخزنان** (الإقليمي والفرعي)، فالاشتقاق يمنح مفتشها
 *     الإقليميَّ أيضاً بلا قرار.
 *   - والأخطر: ربط المحافظات يخدم **المقرات**. فإضافةُ محافظةٍ لمفتشٍ لأجل
 *     تفتيش مقارها كانت ستمنحه — في الخفاء — حقَّ إعادة كتابة الرصيد
 *     الافتتاحي لمخزنها. فعلان بخطرين متباعدين لا يُعلَّقان على رابطٍ واحد.
 *
 * والفورم يقترح مخازن محافظاته اقتراحاً **ظاهراً قابلاً للتعديل قبل الحفظ** —
 * وهو غير الاشتقاق وقت الاستعلام الذي لا يُرى.
 *
 * ⚠️ و`users.all_warehouses` تعني **«بلا حدّ»**، والقائمة الفارغة تعني
 *    **«لا يرى شيئاً»**. الخلط بين المعنيين هو ما يفتح المنظومة كلها لمن
 *    لا نطاق له — درسٌ مدفوع ثمنه في موديول رأي المواطن.
 *    ولها سببٌ عملي أيضاً: أمين المخزن الرئيسي ينقل إلى كل المحافظات،
 *    ولو عُلِّمت له الثلاثون فرداً لَعجز عن النقل إلى **مخزنٍ يُنشأ بعد ذلك**.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_warehouse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'warehouse_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('all_warehouses')->default(false)->after('job_title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_warehouse');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('all_warehouses');
        });
    }
};
