<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * سبب مطابقة رفض «التكرار»: بأي مفتاح طابق (رقم قومي · هاتف · بصمة جهاز) ومع أي رأي.
 *
 * بدونهما تعرض الشاشة الهوية المكتوبة في المحاولة وحدها، فحين تكون المطابقة بالبصمة
 * يبحث المدير عن الرقم في النتائج فلا يجده ويظنّ الحجب خطأً.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback_rejected_attempts', function (Blueprint $table) {
            $table->string('matched_by', 40)->nullable()->after('reason');
            $table->unsignedBigInteger('matched_id')->nullable()->after('matched_by');
            $table->timestamp('matched_at')->nullable()->after('matched_id');
        });

        $this->backfill();
    }

    /**
     * يملأ سبب المطابقة لرفوض التكرار القائمة — بالمنطق نفسه: آخر رأي من النوع نفسه للمقر
     * نفسه بأحد المفاتيح خلال المدة قبل لحظة الرفض. الصفّ الذي لا يُعثر له على رأي يبقى فارغاً.
     */
    private function backfill(): void
    {
        $tables = ['rating' => 'feedback_ratings', 'suggestion' => 'feedback_suggestions', 'digital' => 'feedback_digital_ratings'];
        $windowDays = (int) config('feedback.window_days', 7);

        DB::table('feedback_rejected_attempts')
            ->where('reason', 'duplicate_window')->whereNotNull('office_id')
            ->orderBy('id')
            ->each(function ($attempt) use ($tables, $windowDays) {
                $table = $tables[$attempt->type] ?? null;
                $keys = array_filter([
                    'national_id'  => (string) $attempt->national_id,
                    'phone'        => (string) $attempt->phone,
                    'device_token' => (string) $attempt->device_token,
                ], fn ($value) => $value !== '');

                if (! $table || ! Schema::hasTable($table) || $keys === []) {
                    return;
                }

                $at = Carbon::parse($attempt->created_at);
                $last = DB::table($table)
                    ->where('office_id', $attempt->office_id)
                    ->where(function ($q) use ($keys) {
                        foreach ($keys as $column => $value) {
                            $q->orWhere($column, $value);
                        }
                    })
                    ->whereBetween('created_at', [$at->copy()->subDays($windowDays), $at])
                    ->orderByDesc('created_at')
                    ->first();

                if (! $last) {
                    return;
                }

                DB::table('feedback_rejected_attempts')->where('id', $attempt->id)->update([
                    'matched_by' => implode(',', array_keys(array_filter($keys, fn ($value, $column) => (string) $last->{$column} === $value, ARRAY_FILTER_USE_BOTH))),
                    'matched_id' => $last->id,
                    'matched_at' => $last->created_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('feedback_rejected_attempts', function (Blueprint $table) {
            $table->dropColumn(['matched_by', 'matched_id', 'matched_at']);
        });
    }
};
