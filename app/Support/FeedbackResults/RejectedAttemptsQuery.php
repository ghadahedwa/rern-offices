<?php

namespace App\Support\FeedbackResults;

use App\Models\FeedbackRejectedAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * استعلام المحاولات المرفوضة المفلتر — مصدر واحد للشاشة والحذف والتصدير.
 * الجدول بلا سلة محذوفات (يُنظَّف تلقائياً ولا علاقة له بمنع التكرار).
 */
final class RejectedAttemptsQuery
{
    public static function build(
        FeedbackFilterSet $filters,
        ?User $user = null,
        string $search = '',
        string $reason = '',
        string $type = '',
    ): Builder {
        return $filters->apply(
            FeedbackScope::apply(FeedbackRejectedAttempt::query(), $user),
            // الجدول ليس فيه governorate_id — نمرّ عبر علاقة المقر
            fn ($q) => $q->whereHas('office', fn ($o) => $o->where('governorate_id', $filters->governorateId)),
        )
            ->when($reason !== '', fn ($q) => $q->where('reason', $reason))
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->when(trim($search) !== '', function ($q) use ($search) {
                $term = trim($search);
                $q->where(fn ($sub) => $sub->where('national_id', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('ip_address', 'like', "%{$term}%"));
            });
    }
}
