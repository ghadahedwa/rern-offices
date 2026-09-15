<?php

namespace App\Support\FeedbackResults;

use App\Models\FeedbackDigitalRating;
use App\Models\User;
use App\Support\ArabicText;
use Illuminate\Database\Eloquent\Builder;

/**
 * استعلام آراء المنصات الرقمية المفلتر — مصدر واحد لما يُعرض على الشاشة،
 * ولما يُحذف جماعياً، ولما يُصدَّر، ولما يحسبه ملخص المنصات.
 */
final class DigitalRatingsQuery
{
    /** القائمة البيضاء للترتيب. */
    public const SORTABLE = ['created_at', 'q205'];

    /** فلتر المسار — قيم q201. القيمة المجهولة تُهمَل ولا تُفرغ الشاشة. */
    public const PATHS = ['booked', 'walk_in'];

    public static function build(
        FeedbackFilterSet $filters,
        ?User $user = null,
        string $search = '',
        bool $trashed = false,
        string $path = '',
    ): Builder {
        $query = FeedbackDigitalRating::query();

        if ($trashed) {
            $query->onlyTrashed();
        }

        return $filters->apply(FeedbackScope::apply($query, $user))
            ->when(in_array($path, self::PATHS, true), fn ($q) => $q->where('q201', $path))
            ->when(trim($search) !== '', function ($q) use ($search) {
                $term = trim($search);
                $norm = ArabicText::normalize($term);
                $q->where(function ($sub) use ($term, $norm) {
                    $sub->whereRaw(ArabicText::sqlNormalize('name').' LIKE ?', ["%{$norm}%"])
                        // النصان الحرّان: المشاكل (٢٠٤) وأسباب عدم الاستخدام (٢١٠)
                        ->orWhereRaw(ArabicText::sqlNormalize('q204').' LIKE ?', ["%{$norm}%"])
                        ->orWhereRaw(ArabicText::sqlNormalize('q210').' LIKE ?', ["%{$norm}%"])
                        ->orWhere('national_id', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
            });
    }
}
