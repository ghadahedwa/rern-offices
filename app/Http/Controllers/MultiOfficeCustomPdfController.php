<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Reports\OfficeColumns;
use Illuminate\Http\Request;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class MultiOfficeCustomPdfController extends Controller
{
    /** حد الأعمدة لاختيار اتجاه A4: ≤8 عمودي · 9-12 عرضي (مطابق لـ MultiOffice::MAX_CUSTOM_PDF_COLS) */
    private const PORTRAIT_MAX = 8;

    public function __invoke(Request $request)
    {
        $user = $request->user();
        abort_unless(
            $user?->hasRole('super-admin') || $user?->can('offices.export'),
            403
        );

        $ids  = session('report_office_ids', []);
        $keys = session('report_custom_columns', []);
        abort_if(empty($ids) || empty($keys), 404, 'لا توجد نتائج للعرض');

        // تقارير كبيرة: رفع حدود الـ PCRE/الذاكرة/الوقت
        ini_set('pcre.backtrack_limit', '100000000');
        ini_set('pcre.recursion_limit', '100000000');
        ini_set('memory_limit', '1024M');
        @set_time_limit(300);

        $isSuperAdmin  = $user->hasRole('super-admin');
        $allowedGovIds = $isSuperAdmin ? null : $user->governorates()->pluck('governorates.id')->all();

        $offices = Office::whereIn('id', $ids)
            ->when($allowedGovIds, fn ($q) => $q->whereIn('governorate_id', $allowedGovIds))
            ->with([
                'governorate', 'officeType', 'locationDescription', 'workSystem', 'workingHour',
                'connectionType', 'contractualStatus', 'structuralCondition',
                'MicrofilmOption', 'DisabilitieAccess', 'FireSafety',
                'DocumentPhotocopyingService', 'BuffetService', 'CleanlinessContract',
                'brokenDevices.deviceType',
            ])
            ->orderBy('name')
            ->get();

        // الأعمدة المطلوبة (مرتّبة حسب الكتالوج، بلا نصوص طويلة)
        $columns = array_filter(
            OfficeColumns::select($keys),
            fn ($c) => ! $c['excelOnly']
        );

        // اتجاه A4 حسب عدد الأعمدة (خط 10pt ثابت)
        $landscape   = count($columns) > self::PORTRAIT_MAX;
        $format      = $landscape ? 'A4-L' : 'A4';
        $orientation = $landscape ? 'L' : 'P';

        $logoPath   = public_path('images/logo3.png');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $html = view('print.multi-office-custom-pdf', compact('offices', 'columns', 'logoBase64'))->render();

        $fontDirs = (new ConfigVariables())->getDefaults()['fontDir'];
        $fontData = (new FontVariables())->getDefaults()['fontdata'];

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => $format,
            'orientation'   => $orientation,
            'default_font'  => 'dejavusans',
            'margin_top'    => 8,
            'margin_bottom' => 8,
            'margin_left'   => 4,
            'margin_right'  => 4,
            'fontDir'       => array_merge($fontDirs, [storage_path('fonts')]),
            'fontdata'      => $fontData,
            'tempDir'       => storage_path('mpdf'),
            // الخط ثابت 10pt — المحتوى يلتف ولا يُصغَّر الخط
            'shrink_tables_to_fit' => 0,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);

        $filename = 'offices-custom-' . now()->format('Ymd-His') . '.pdf';

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'              => 'no-cache',
        ]);
    }
}
