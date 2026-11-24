<?php

namespace App\Support\FeedbackResults;

use App\Models\FeedbackMessage;
use App\Models\User;
use App\Support\ArabicText;
use Illuminate\Database\Eloquent\Builder;

/**
 * استعلام رسائل نوعٍ بعينه — مصدر واحد للشاشة والحذف الجماعي (والتصدير لاحقاً).
 */
final class MessagesQuery
{
    public const SORTABLE = ['created_at'];

    public static function build(
        string $type,
        FeedbackFilterSet $filters,
        ?User $user = null,
        string $search = '',
        bool $trashed = false,
    ): Builder {
        $query = FeedbackMessage::query()->ofType($type);

        if ($trashed) {
            $query->onlyTrashed();
        }

        return $filters->apply(FeedbackScope::apply($query, $user))
            ->when(trim($search) !== '', function ($q) use ($search) {
                $term = trim($search);
                $norm = ArabicText::normalize($term);
                $q->where(function ($sub) use ($term, $norm) {
                    $sub->whereRaw(ArabicText::sqlNormalize('name').' LIKE ?', ["%{$norm}%"])
                        ->orWhereRaw(ArabicText::sqlNormalize('employee_name').' LIKE ?', ["%{$norm}%"])
                        ->orWhereRaw(ArabicText::sqlNormalize('body').' LIKE ?', ["%{$norm}%"])
                        ->orWhere('national_id', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
            });
    }
}
