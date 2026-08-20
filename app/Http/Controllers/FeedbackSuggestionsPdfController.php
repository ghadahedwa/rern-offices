<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersFeedbackPdf;
use App\Support\FeedbackResults\FeedbackFilterSet;
use App\Support\FeedbackResults\FeedbackSort;
use App\Support\FeedbackResults\SuggestionsQuery;
use Illuminate\Http\Request;

/**
 * قائمة مقترحات المواطنين كتقرير مطبوع.
 * بيانات المواطن لا تظهر إلا بـ ?personal=1 (خانة اختيار صريحة على الشاشة).
 */
class FeedbackSuggestionsPdfController extends Controller
{
    use RendersFeedbackPdf;

    public function __invoke(Request $request)
    {
        $this->guardPdf($request);

        $filters  = FeedbackFilterSet::fromRequest($request);
        $personal = $request->boolean('personal');
        $trashed  = $this->viewingTrash($request);

        $query = SuggestionsQuery::build(
            $filters,
            $request->user(),
            $this->param($request, 'q'),
            $trashed,
        );

        $total = (clone $query)->count();

        $suggestions = FeedbackSort::apply(
            $query->with(['office:id,name', 'governorate:id,name', 'topics.domain'])->withCount('topics'),
            $this->param($request, 'sort'),
            $this->param($request, 'dir'),
            SuggestionsQuery::SORTABLE,
        )->limit(self::MAX_ROWS)->get();

        return $this->renderPdf('print.feedback-suggestions-pdf', [
            'suggestions' => $suggestions,
            'total'       => $total,
            'maxRows'     => self::MAX_ROWS,
            'personal'    => $personal,
            'trashed'     => $trashed,
            'filters'     => $filters,
            'generatedAt' => now(),
        ], 'feedback-suggestions', landscape: true);
    }
}
