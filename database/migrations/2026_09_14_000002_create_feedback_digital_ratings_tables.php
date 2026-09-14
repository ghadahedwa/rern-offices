<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بوابة «تقييم المنصات الرقمية» — الفورم الثالث في بوابة رأي المواطن.
 *
 * الأسئلة منقولة من القسم الثاني في استمارة الوزارة الورقية (٢٠١–٢١٣)، والأعمدة
 * **بأرقام الأسئلة نفسها** (q201…): المطابقة مع الورقة ودليل الترميز فورية، وأسئلة
 * القسم الثالث (٣٠١…) تمشي على النظام نفسه حين تصل.
 *
 * ⚠️ **كل عمود بعد q201 nullable عمداً — والـNULL معناه «لم يُسأل» لا «لا».**
 * الأسئلة تظهر حسب الإجابات (عمود «انتقل إلى» في الورقة)، فمَن جاء بلا حجز لم يُسأل
 * عن مشاكل الحجز. ونسبة «قابلتهم مشاكل» تُحسب على مَن سُئل وحده.
 *
 * أسئلة الاختيار المتعدد (q209 · q212 — الحروف A/B في الورقة) في جدول
 * feedback_digital_choices: صف لكل (رأي × سؤال × اختيار). العدّ بـGROUP BY واحد
 * على المحرّكين، وسؤال متعدد جديد في القسم الثالث = صفوف لا هجرة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_digital_ratings', function (Blueprint $table) {
            $table->id();
            // nullOnDelete: الرأي يبقى حتى لو حُذف المقر (نفس جدولي التقييم والمقترحات)
            $table->foreignId('governorate_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();

            // الهوية اختيارية + بصمة الجهاز (انظر FeedbackDevice)
            $table->string('name', 100)->nullable();
            $table->string('national_id', 14)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('device_token', 64)->nullable();

            $table->string('q201', 20);                            // حجز / بدون حجز — الوحيد الإجباري دائماً
            $table->string('q202', 20)->nullable();                // منصة الحجز
            $table->string('q203', 10)->nullable();                // مشاكل في الحجز؟
            $table->text('q204')->nullable();                      // المشاكل — نص حر
            $table->unsignedTinyInteger('q205')->nullable();       // تقييم المنصة ٠–١٠ (الصفر إجابة صحيحة)
            $table->string('q206', 20)->nullable();                // دخل في ميعاده؟
            $table->string('q207', 20)->nullable();                // مدة الانتظار بعد الميعاد
            $table->string('q208', 10)->nullable();                // يعرف منصة الحجز؟
            $table->text('q210')->nullable();                      // لماذا لم يستخدمها — نص حر
            $table->string('q211', 10)->nullable();                // سمع/شاهد الإعلان؟
            $table->string('q213', 20)->nullable();                // تقييم الحملة الإعلامية

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            // SoftDeletes كجدولي التقييم والمقترحات: الصف المحذوف إدارياً يظل حارساً لنافذة التكرار
            $table->softDeletes();

            $table->index(['national_id', 'office_id']);
            $table->index(['phone', 'office_id']);
            $table->index(['device_token', 'office_id']);
            $table->index('office_id');
            $table->index('created_at');
        });

        Schema::create('feedback_digital_choices', function (Blueprint $table) {
            $table->foreignId('feedback_digital_rating_id')->constrained()->cascadeOnDelete();
            $table->string('question', 8);    // q209 · q212
            $table->string('option', 30);     // مفتاح الاختيار من FeedbackDigitalRating::QUESTIONS

            $table->primary(['feedback_digital_rating_id', 'question', 'option'], 'feedback_digital_choices_primary');
            $table->index(['question', 'option']);   // عدّ كل اختيار في النتائج
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_digital_choices');
        Schema::dropIfExists('feedback_digital_ratings');
    }
};
