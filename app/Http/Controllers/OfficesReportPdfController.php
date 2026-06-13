<?php

namespace App\Http\Controllers;

use App\Models\Office;
use Illuminate\Http\Request;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class OfficesReportPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        abort_unless(
            $user?->hasRole('super-admin') || $user?->can('offices.export'),
            403
        );

        $ids = session('report_office_ids', []);
        abort_if(empty($ids), 404, 'لا توجد نتائج للعرض');

        // تقارير كبيرة: رفع حدود الـ PCRE/الذاكرة/الوقت (mPDF يفشل لو الـ HTML تجاوز pcre.backtrack_limit)
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

        $logoPath   = public_path('images/logo3.png');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $html = view('print.multi-office-pdf', compact('offices', 'logoBase64'))->render();

        $fontDirs = (new ConfigVariables())->getDefaults()['fontDir'];
        $fontData = (new FontVariables())->getDefaults()['fontdata'];

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4-L',
            'orientation'   => 'L',
            'default_font'  => 'dejavusans',
            'margin_top'    => 8,
            'margin_bottom' => 8,
            'margin_left'   => 4,
            'margin_right'  => 4,
            'fontDir'       => array_merge($fontDirs, [storage_path('fonts')]),
            'fontdata'      => $fontData,
            'tempDir'       => storage_path('mpdf'),
            // امنع mPDF من تصغير خط الجدول لو اتسع — يفضل الخط ثابت والمحتوى يلتف
            'shrink_tables_to_fit' => 0,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);

        $filename = 'offices-report-' . now()->format('Ymd-His') . '.pdf';

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'              => 'no-cache',
        ]);
    }
}
