<?php

namespace App\Http\Controllers;

use App\Livewire\Reports\VehicleFridayWork as Screen;
use App\Reports\VehicleStatisticsBreakdown;
use Illuminate\Http\Request;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class VehicleStatisticsBreakdownPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->hasRole('super-admin') || $user?->can('vehicles.export'), 403);

        ini_set('memory_limit', '512M');
        @set_time_limit(120);

        // الفلاتر من الـsession تُفحص في `make()` كما لو وصلت من العميل
        $report = VehicleStatisticsBreakdown::make(Screen::allowedGovIdsFor($user), session('vehicle_statistics_breakdown_filters', []));

        $logoPath   = public_path('images/logo3.png');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $html = view('print.vehicle-statistics-breakdown-pdf', compact('report', 'logoBase64'))->render();

        $fontDirs = (new ConfigVariables())->getDefaults()['fontDir'];
        $fontData = (new FontVariables())->getDefaults()['fontdata'];

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            // المتقاطع أفقي (حتى ١٢ شهراً + الإجمالي + عدّا السيارات)، والرأسي (سيارة/محافظة واحدة) طولي
            'orientation'   => $report->isVertical() ? 'P' : 'L',
            'default_font'  => 'dejavusans',
            'margin_top'    => 10,
            'margin_bottom' => 10,
            'margin_left'   => 8,
            'margin_right'  => 8,
            'fontDir'       => array_merge($fontDirs, [storage_path('fonts')]),
            'fontdata'      => $fontData,
            'tempDir'       => storage_path('mpdf'),
            'shrink_tables_to_fit' => 1,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);

        $filename = 'vehicle-statistics-breakdown-' . now()->format('Ymd-His') . '.pdf';

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'              => 'no-cache',
        ]);
    }
}
