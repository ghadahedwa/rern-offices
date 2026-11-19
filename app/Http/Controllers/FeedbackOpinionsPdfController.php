<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersFeedbackPdf;
use App\Support\FeedbackResults\FeedbackFilterSet;
use App\Support\FeedbackResults\OpinionsQuery;
use Illuminate\Http\Request;

/**
 * تقرير «آراء المواطنين» المطبوع — من OpinionsQuery نفسه الذي تقرأ منه الشاشة.
 * بيانات المواطن لا تظهر إلا بـ ?personal=1 (خانة اختيار صريحة على الشاشة).
 */
class FeedbackOpinionsPdfController extends Controller
{
    use RendersFeedbackPdf;

    public function __invoke(Request $request)
    {
        $this->guardPdf($request);

        $filters = FeedbackFilterSet::fromRequest($request);
        $type    = OpinionsQuery::type($this->param($request, 'type'));

        $query = OpinionsQuery::build(
            $filters,
            $request->user(),
            $this->param($request, 'q'),
            $type,
            $this->param($request, 'dir'),
        );

        $total = (clone $query)->count();
        $rows  = $query->limit(self::MAX_ROWS)->get()->map(fn ($row) => OpinionsQuery::hydrate($row));

        return $this->renderPdf('print.feedback-opinions-pdf', [
            'rows'        => $rows,
            'topics'      => OpinionsQuery::topicsFor($rows),
            'type'        => $type,
            'total'       => $total,
            'maxRows'     => self::MAX_ROWS,
            'personal'    => $request->boolean('personal'),
            'filters'     => $filters,
            'generatedAt' => now(),
        ], 'feedback-opinions', landscape: true);
    }
}
