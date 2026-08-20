<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersFeedbackPdf;
use App\Models\FeedbackRating;
use App\Support\FeedbackResults\FeedbackFilterSet;
use App\Support\FeedbackResults\FeedbackSort;
use App\Support\FeedbackResults\RatingsQuery;
use Illuminate\Http\Request;

/**
 * قائمة تقييمات المواطنين كتقرير مطبوع.
 * بيانات المواطن لا تظهر إلا بـ ?personal=1 (خانة اختيار صريحة على الشاشة).
 */
class FeedbackRatingsPdfController extends Controller
{
    use RendersFeedbackPdf;

    public function __invoke(Request $request)
    {
        $this->guardPdf($request);

        $filters  = FeedbackFilterSet::fromRequest($request);
        $personal = $request->boolean('personal');
        $trashed  = $this->viewingTrash($request);

        $query = RatingsQuery::build(
            $filters,
            $request->user(),
            $this->param($request, 'q'),
            $trashed,
        );

        $total = (clone $query)->count();

        $ratings = FeedbackSort::apply(
            $query->with(['office:id,name', 'governorate:id,name']),
            $this->param($request, 'sort'),
            $this->param($request, 'dir'),
            RatingsQuery::SORTABLE,
        )->limit(self::MAX_ROWS)->get();

        return $this->renderPdf('print.feedback-ratings-pdf', [
            'ratings'       => $ratings,
            'total'         => $total,
            'maxRows'       => self::MAX_ROWS,
            'personal'      => $personal,
            'trashed'       => $trashed,
            'filters'       => $filters,
            'waitTimes'     => FeedbackRating::WAIT_TIMES_SHORT,
            'criteria'      => FeedbackRating::CRITERIA,
            'criteriaShort' => FeedbackRating::CRITERIA_SHORT,
            'generatedAt'   => now(),
        ], 'feedback-ratings', landscape: true);
    }
}
