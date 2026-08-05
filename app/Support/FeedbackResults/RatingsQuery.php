<?php

namespace App\Support\FeedbackResults;

use App\Models\FeedbackRating;
use App\Models\User;
use App\Support\ArabicText;
use Illuminate\Database\Eloquent\Builder;

/**
 * استعلام التقييمات المفلتر — مصدر واحد لما يُعرض على الشاشة،
 * ولما يُحذف جماعياً، ولما يُصدَّر إلى Excel/PDF.
 */
final class RatingsQuery
{
    /** القائمة البيضاء للترتيب — تقرأها الشاشة وكنترولر الـ PDF معاً. */
    public const SORTABLE = ['created_at', 'overall_rating'];

    public static function build(
        FeedbackFilterSet $filters,
        ?User $user = null,
        string $search = '',
        bool $trashed = false,
    ): Builder {
        $query = FeedbackRating::query();

        if ($trashed) {
            $query->onlyTrashed();
        }

        return $filters->apply(FeedbackScope::apply($query, $user))
            ->when(trim($search) !== '', function ($q) use ($search) {
                $term = trim($search);
                $norm = ArabicText::normalize($term);
                $q->where(function ($sub) use ($term, $norm) {
                    $sub->whereRaw(ArabicText::sqlNormalize('name').' LIKE ?', ["%{$norm}%"])
                        // نص الملاحظة الحر: أغنى ما في البيانات، ومطبَّع عربياً مثل الاسم
                        ->orWhereRaw(ArabicText::sqlNormalize('notes').' LIKE ?', ["%{$norm}%"])
                        ->orWhere('national_id', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
            });
    }
}
