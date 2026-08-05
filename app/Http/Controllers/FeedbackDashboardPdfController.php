<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersFeedbackPdf;
use App\Support\FeedbackResults\DashboardReport;
use App\Support\FeedbackResults\FeedbackFilterSet;
use Illuminate\Http\Request;

/**
 * تقرير لوحة نتائج رأي المواطن — تجميعي بالكامل (بلا بيانات شخصية).
 *
 * الفلاتر تصل في الـ query string نفسه الذي على شاشة اللوحة، والأرقام تُحسب
 * من DashboardReport نفسه — فالمطبوع مطابق للمعروض بالضرورة لا بالمصادفة.
 */
class FeedbackDashboardPdfController extends Controller
{
    use RendersFeedbackPdf;

    public function __invoke(Request $request)
    {
        $this->guardPdf($request);

        $filters = FeedbackFilterSet::fromRequest($request);
        $report  = new DashboardReport($filters, $request->user());

        $data = $report->all() + [
            'filters'     => $filters,
            'offices'     => $report->officesTable(),
            'generatedAt' => now(),
        ];

        return $this->renderPdf('print.feedback-dashboard-pdf', $data, 'feedback-dashboard');
    }
}
