<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Support\Finance\FinanceAccess;
use App\Support\Finance\FinanceReport;
use App\Support\PdfFonts;
use Illuminate\Http\Request;
use Mpdf\Mpdf;

/**
 * تقرير الشئون المالية المطبوع — الفلاتر في الـquery string كالشاشة، والأرقام من `FinanceReport` نفسه.
 *
 * ⚠️ الحارس داخل الكنترولر: الرابط يُفتح منسوخاً في تبويبٍ آخر.
 * ⚠️ أفقيٌّ دائماً: سبعة أعمدة أرقام (اتصالات بثلاثة) مع عمود الشهر/الوحدة.
 */
class FinanceReportPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless($request->user()?->can(FinanceAccess::REPORTS), 403);

        $report = FinanceReport::fromInput($request->query());
        abort_if($report === null, 404);

        $logo = public_path('images/logo3.png');

        $html = view('print.finance-report-pdf', [
            'report'      => $report,
            'data'        => $report->kind === 'unit' ? $report->unitReport() : $report->totalReport(),
            'items'       => $report->items(),
            'generatedAt' => now(),
            'logoBase64'  => file_exists($logo) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logo)) : null,
        ])->render();

        $mpdf = new Mpdf(PdfFonts::config() + [
            'mode'          => 'utf-8',
            'format'        => 'A4-L',
            'orientation'   => 'L',
            'margin_top'    => 10,
            'margin_bottom' => 10,
            'margin_left'   => 8,
            'margin_right'  => 8,
            'tempDir'       => storage_path('mpdf'),
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="finance-report-'.now()->format('Ymd-His').'.pdf"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'              => 'no-cache',
        ]);
    }
}
