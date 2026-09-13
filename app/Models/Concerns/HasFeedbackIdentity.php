<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * هوية مقدّم الرأي — مشتركة بين FeedbackRating وFeedbackSuggestion.
 *
 * الحقول الثلاثة (الاسم · الرقم القومي · الهاتف) اختيارية منذ 2026-09-14.
 * **مجهول = بلا رقم قومي وبلا هاتف** — لا مفتاح هوية ولا وسيلة تواصل. الاسم
 * وحده لا يُعرّف صاحبه، فمَن كتب اسمه فقط يُحسب مجهولاً ويُعرض اسمه كما كتبه.
 *
 * ⚠️ التعريف هنا وحده (الفلتر · مؤشر اللوحة · شارة الصف تقرؤه كلها) — تعريفان
 * مختلفان يُخرجان «نسبة المجهول» في اللوحة غير عدد صفوف فلتر «مجهول».
 */
trait HasFeedbackIdentity
{
    /** بصمة الجهاز مفتاح تقني لمنع التكرار — لا تُعرض ولا تُسلسَل. */
    public function initializeHasFeedbackIdentity(): void
    {
        $this->mergeHidden(['device_token']);
    }

    public function scopeAnonymous(Builder $query): Builder
    {
        return $query->whereNull($query->qualifyColumn('national_id'))
            ->whereNull($query->qualifyColumn('phone'));
    }

    public function scopeIdentified(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereNotNull($q->qualifyColumn('national_id'))
            ->orWhereNotNull($q->qualifyColumn('phone')));
    }

    public function isAnonymous(): bool
    {
        return $this->national_id === null && $this->phone === null;
    }
}
