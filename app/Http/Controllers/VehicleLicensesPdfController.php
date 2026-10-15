<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class VehicleLicensesPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        abort_unless(
            $user?->hasRole('super-admin') || $user?->can('vehicles.export'),
            403
        );

        ini_set('memory_limit', '512M');
        @set_time_limit(120);

        $f        = session('vehicle_licenses_filters', []);
        $soonDays = is_numeric($f['withinDays'] ?? null) ? (int) $f['withinDays'] : 30;

        $isSuperAdmin  = $user->hasRole('super-admin');
        $allowedGovIds = $isSuperAdmin ? null : $user->governorates()->pluck('governorates.id')->all();

        // استعلام الشاشة نفسه (الفلاتر والترتيب) — لا نسخة ثانية تختلف عنه
        $rows = \App\Livewire\Reports\VehicleLicenses::rowsFor($allowedGovIds, $f);

        $logoPath   = public_path('images/logo3.png');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $html = view('print.vehicle-licenses-pdf', compact('rows', 'soonDays', 'logoBase64'))->render();

        $fontDirs = (new ConfigVariables())->getDefaults()['fontDir'];
        $fontData = (new FontVariables())->getDefaults()['fontdata'];

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'orientation'   => 'P',
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

        $filename = 'vehicle-licenses-' . now()->format('Ymd-His') . '.pdf';

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'              => 'no-cache',
        ]);
    }
}
