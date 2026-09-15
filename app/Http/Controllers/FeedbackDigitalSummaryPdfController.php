<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersFeedbackPdf;
use App\Support\FeedbackResults\DigitalReport;
use App\Support\FeedbackResults\FeedbackFilterSet;
use Illuminate\Http\Request;

/**
 * تقرير «ملخص المنصات الرقمية» — تجميعي بالكامل (بلا بيانات شخصية).
 * الفلاتر تصل في الـ query string نفسه الذي على الشاشة، والأرقام من DigitalReport نفسه.
 * (قائمة الآراء بلا PDF: ثلاثة عشر عمود إجابة لا تدخل صفحة مطبوعة — Excel لها.)
 */
class FeedbackDigitalSummaryPdfController extends Controller
{
    use RendersFeedbackPdf;

    public function __invoke(Request $request)
    {
        $this->guardPdf($request);

        $filters = FeedbackFilterSet::fromRequest($request);
        $order   = $this->param($request, 'order') === 'asc' ? 'asc' : 'desc';
        $report  = new DigitalReport($filters, $request->user());

        $data = $report->all($order) + [
            'filters'     => $filters,
            'generatedAt' => now(),
            'maxRows'     => self::MAX_ROWS,
        ];

        return $this->renderPdf('print.feedback-digital-summary-pdf', $data, 'feedback-digital-summary');
    }
}
