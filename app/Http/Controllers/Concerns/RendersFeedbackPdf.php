<?php

namespace App\Http\Controllers\Concerns;

use App\Support\FeedbackResults\FeedbackAccess;
use Illuminate\Http\Request;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * توليد تقارير PDF لموديول نتائج رأي المواطن (mpdf، عربي RTL).
 * نفس إعدادات باقي تقارير المشروع حتى تخرج بهوية واحدة.
 */
trait RendersFeedbackPdf
{
    /**
     * سقف صفوف التقرير المطبوع. mpdf يبني شجرة العناصر كلها في الذاكرة،
     * فجدول بعشرات الآلاف من الصفوف يقتل الطلب. مَن يريد الكل يستخدم Excel.
     */
    protected const MAX_ROWS = 2000;

    /**
     * حارس ثانٍ فوق middleware الراوت — الرابط قد يُفتح منسوخاً في تبويب آخر،
     * فلا يكفي أن الشاشة نفسها محمية.
     *
     * ⚠️ التقرير المطبوع **تصدير** لا عرض: ملف يخرج من النظام ويُتداول،
     *    فيُفحص بـfeedback.export لا بـfeedback.view. والنطاق يسري تلقائياً
     *    لأن الكنترولر يمرّر `$request->user()` لكلاسات الاستعلام.
     */
    protected function guardPdf(Request $request): void
    {
        abort_unless(FeedbackAccess::canExport($request->user()), 403);
    }

    /**
     * سلة المحذوفات في التقرير — لمن يملك الحذف وحده.
     * `?trashed=1` يصل من الرابط، فمَن له التصدير بلا حذف لا يطبع سلة.
     */
    protected function viewingTrash(Request $request): bool
    {
        return $request->boolean('trashed') && FeedbackAccess::canDelete($request->user());
    }

    /** قيمة نصية من الـ URL قد تصل مصفوفة — نتجاهلها بدل الانهيار. */
    protected function param(Request $request, string $key): string
    {
        $value = $request->query($key, '');

        return is_scalar($value) ? trim((string) $value) : '';
    }

    protected function logoBase64(): ?string
    {
        $path = public_path('images/logo3.png');

        return file_exists($path)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($path))
            : null;
    }

    protected function renderPdf(string $view, array $data, string $filename, bool $landscape = false)
    {
        ini_set('memory_limit', '512M');
        @set_time_limit(120);

        $html = view($view, $data + ['logoBase64' => $this->logoBase64()])->render();

        $fontDirs = (new ConfigVariables())->getDefaults()['fontDir'];
        $fontData = (new FontVariables())->getDefaults()['fontdata'];

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => $landscape ? 'A4-L' : 'A4',
            'orientation'   => $landscape ? 'L' : 'P',
            'default_font'  => 'dejavusans',
            'margin_top'    => 10,
            'margin_bottom' => 10,
            'margin_left'   => 8,
            'margin_right'  => 8,
            'fontDir'       => array_merge($fontDirs, [storage_path('fonts')]),
            'fontdata'      => $fontData,
            'tempDir'       => storage_path('mpdf'),
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'-'.now()->format('Ymd-His').'.pdf"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'              => 'no-cache',
        ]);
    }
}
