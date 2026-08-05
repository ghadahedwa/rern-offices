<?php

namespace App\Support\FeedbackResults;

use App\Models\FeedbackSuggestion;
use App\Models\User;
use App\Support\ArabicText;
use Illuminate\Database\Eloquent\Builder;

/**
 * استعلام المقترحات المفلتر — مصدر واحد للشاشة والحذف الجماعي والتصدير.
 */
final class SuggestionsQuery
{
    /**
     * القائمة البيضاء للترتيب — تقرأها الشاشة وكنترولر الـ PDF معاً.
     * topics_count عمود محسوب، فيلزم withCount('topics') قبل الترتيب به.
     */
    public const SORTABLE = ['created_at', 'topics_count'];

    public static function build(
        FeedbackFilterSet $filters,
        ?User $user = null,
        string $search = '',
        bool $trashed = false,
    ): Builder {
        $query = FeedbackSuggestion::query();

        if ($trashed) {
            $query->onlyTrashed();
        }

        return $filters->apply(FeedbackScope::apply($query, $user))
            ->when(trim($search) !== '', function ($q) use ($search) {
                $term = trim($search);
                $norm = ArabicText::normalize($term);
                $q->where(function ($sub) use ($term, $norm) {
                    $sub->whereRaw(ArabicText::sqlNormalize('name').' LIKE ?', ["%{$norm}%"])
                        // الاقتراح الحر + عناوين الكتالوج المختارة
                        ->orWhereRaw(ArabicText::sqlNormalize('other_suggestion').' LIKE ?', ["%{$norm}%"])
                        ->orWhereHas('topics', fn ($t) => $t->whereRaw(
                            ArabicText::sqlNormalize('suggestion_topics.name').' LIKE ?', ["%{$norm}%"]
                        ))
                        ->orWhere('national_id', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
            });
    }
}
