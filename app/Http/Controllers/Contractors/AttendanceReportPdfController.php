<?php

namespace App\Http\Controllers\Contractors;

use App\Http\Controllers\Controller;
use App\Support\Contractors\AttendanceReport;
use App\Support\Contractors\AttendanceReportQuery;
use Illuminate\Http\Request;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * تقرير حضور مطبوع — كنترولر واحد للمستويات الثلاثة (المستوى في الرابط).
 *
 * ⚠️ **الأرقام من `AttendanceReportQuery` نفسه الذي تقرأ منه الشاشة** — الكنترولر
 *    لا يبني استعلاماً ولا يحسب رقماً، فلا يخرج ملفٌّ يخالف ما رآه المستخدم.
 * ⚠️ **النطاق يسري تلقائياً** لأن الاستعلام يأخذ `$request->user()`؛ ومحافظةٌ أو
 *    مقرٌّ من خارج نطاقه في الرابط يُهمَل ولا يُمرَّر.
 */
class AttendanceReportPdfController extends Controller
{
    /**
     * سقف صفوف التقرير المطبوع — mpdf يبني شجرة العناصر كلها في الذاكرة، فجدولٌ
     * بعشرات الآلاف من الصفوف يقتل الطلب. مَن يريد الكل يستخدم Excel.
     */
    private const MAX_ROWS = 2000;

    public function __invoke(Request $request)
    {
        // ⚠️ **حارسٌ داخل الكنترولر لا في الـmiddleware وحده**: الرابط يُفتح منسوخاً
        //    في تبويبٍ آخر. والتقرير المطبوع **تصدير** لا عرض — ملفٌّ يخرج من النظام
        //    ويُتداول — فيُفحص بـ`export` لا بـ`index`.
        abort_unless($request->user()?->can('contractors.export'), 403);

        $query = AttendanceReportQuery::fromRequest($request, $request->user());

        abort_unless($query !== null, 404);

        $rows     = $query->rows();
        $capped   = count($rows) > self::MAX_ROWS;
        $shown    = $capped ? array_slice($rows, 0, self::MAX_ROWS) : $rows;
        $statuses = AttendanceReport::statusColumns($rows);

        $data = [
            'query'     => $query,
            'level'     => $query->level,
            'statuses'  => $statuses,
            'breakdown' => $query->report()->breakdown(),
            'holidays'  => $query->report()->holidays(),
            'totals'    => AttendanceReport::sum($rows),
            'capped'    => $capped,
            'maxRows'   => self::MAX_ROWS,
            'title'     => $this->titleFor($query->level),
            'generatedAt' => now(),
        ];

        $data += $query->level === 'governorates'
            ? [
                'groups'      => $this->orderedGroups($shown),
                'contractors' => count(array_unique(array_column($rows, 'contractor_id'))),
            ]
            : ['rows' => $shown, 'contractors' => count(array_unique(array_column($rows, 'contractor_id')))];

        if ($query->level === 'contractor') {
            $data['subject']    = $query->subject();
            $data['exceptions'] = $query->exceptionDates($shown);
        }

        // ⚠️ **الوضع مقيسٌ لا مقدَّر**: بحالتي الحضور المزروعتين (٧ أعمدة) يخرج رأس
        //    الجدول في **سطرٍ واحد رأسياً** (١٨.٤٥pt مقيسة في `AttendancePdfTest`)،
        //    والرأسيُّ أقرب لورقةٍ تُقرأ وتُرسَل. وحالةٌ إضافية من شاشة الحالات تزيد
        //    عموداً، فيتحوّل التقرير أفقياً قبل أن تضيق أعمدة الأرقام وينكسر رأساها.
        return $this->renderPdf('print.contractors-attendance-pdf', $data, 'contractors-attendance', landscape: $statuses->count() > 2);
    }

    /** المحافظات بترتيبها التنظيمي لا بترتيب ظهورها في الصفوف. */
    private function orderedGroups(array $rows): array
    {
        $groups = AttendanceReport::groupBy($rows, 'governorate_id');

        uasort($groups, fn ($a, $b) => ($a['governorate_name'] ?? '') <=> ($b['governorate_name'] ?? ''));

        return $groups;
    }

    private function titleFor(string $level): string
    {
        return match ($level) {
            'office'     => __('home.ct_rep_offices_title'),
            'contractor' => __('home.ct_rep_contractor_title'),
            default      => __('home.ct_rep_governorates_title'),
        };
    }

    private function logoBase64(): ?string
    {
        $path = public_path('images/logo3.png');

        return file_exists($path)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($path))
            : null;
    }

    /** نفس إعدادات باقي تقارير المشروع حتى تخرج بهوية واحدة. */
    private function renderPdf(string $view, array $data, string $filename, bool $landscape = false)
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
