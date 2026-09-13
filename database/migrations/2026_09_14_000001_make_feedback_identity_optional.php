<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * هوية مقدّم الرأي تصير اختيارية (الاسم + الرقم القومي + الهاتف)، ويحلّ محلّها
 * في منع التكرار عمودُ device_token: رمز عشوائي يُحفظ في كوكي مشفَّر على متصفح
 * المواطن (سنة) ويُكتب على كل صف — حتى المُرسَل بهوية كاملة، وإلا صار مَن أرسل
 * باسمه اليوم يعيد الإرسال مجهولاً غداً من الهاتف نفسه.
 *
 * الفهرس ['device_token','office_id'] يخدم فحص النافذة كما يخدمه فهرسا
 * الرقم القومي والهاتف — والفحص يتنفّذ مع الكتابة (wire:model.live).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['feedback_ratings', 'feedback_suggestions'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('name', 100)->nullable()->change();
                $t->string('national_id', 14)->nullable()->change();
                $t->string('phone', 20)->nullable()->change();

                $t->string('device_token', 64)->nullable()->after('phone');
                $t->index(['device_token', 'office_id']);
            });
        }

        Schema::table('feedback_rejected_attempts', function (Blueprint $t) {
            $t->string('device_token', 64)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        foreach (['feedback_ratings', 'feedback_suggestions'] as $table) {
            // الصفوف المجهولة لا تُقبل في أعمدة NOT NULL — تُملأ بفراغ قبل الإرجاع
            DB::table($table)->whereNull('name')->update(['name' => '']);
            DB::table($table)->whereNull('national_id')->update(['national_id' => '']);
            DB::table($table)->whereNull('phone')->update(['phone' => '']);

            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex(['device_token', 'office_id']);
                $t->dropColumn('device_token');

                $t->string('name', 100)->nullable(false)->change();
                $t->string('national_id', 14)->nullable(false)->change();
                $t->string('phone', 20)->nullable(false)->change();
            });
        }

        Schema::table('feedback_rejected_attempts', function (Blueprint $t) {
            $t->dropColumn('device_token');
        });
    }
};
