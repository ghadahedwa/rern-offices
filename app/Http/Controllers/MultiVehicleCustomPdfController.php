<?php

namespace App\Http\Controllers;

use App\Models\Governorate;
use App\Models\Vehicle;
use App\Reports\VehicleColumns;
use Illuminate\Http\Request;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class MultiVehicleCustomPdfController extends Controller
{
    /** حد الأعمدة لاختيار اتجاه A4: ≤8 عمودي · 9-12 عرضي (مطابق لـ MultiVehicle::MAX_CUSTOM_PDF_COLS) */
    private const PORTRAIT_MAX = 8;

    public function __invoke(Request $request)
    {
        $user = $request->user();
        abort_unless(
            $user?->hasRole('super-admin') || $user?->can('vehicles.export'),
            403
        );

        $ids  = session('report_vehicle_ids', []);
        $keys = session('report_custom_columns', []);
        abort_if(empty($ids) || empty($keys), 404, 'لا توجد نتائج للعرض');

        ini_set('pcre.backtrack_limit', '100000000');
        ini_set('pcre.recursion_limit', '100000000');
        ini_set('memory_limit', '1024M');
        @set_time_limit(300);

        $isSuperAdmin  = $user->hasRole('super-admin');
        $allowedGovIds = $isSuperAdmin ? null : $user->governorates()->pluck('governorates.id')->all();

        $vehicles = Vehicle::whereIn('id', $ids)
            ->when($allowedGovIds, fn ($q) => $q->whereIn('governorate_id', $allowedGovIds))
            ->with([
                'governorate', 'type', 'workSystem', 'workingHour', 'brand',
                'locations', 'brokenDevices.deviceType', 'statistics',
            ])
            ->orderBy(Governorate::select('order')->whereColumn('governorates.id', 'vehicles.governorate_id'))
            ->orderBy('governorate_id')
            ->orderBy('name')
            ->get();

        $columns = array_filter(
            VehicleColumns::select($keys),
            fn ($c) => ! $c['excelOnly']
        );

        $landscape   = count($columns) > self::PORTRAIT_MAX;
        $format      = $landscape ? 'A4-L' : 'A4';
        $orientation = $landscape ? 'L' : 'P';

        $logoPath   = public_path('images/logo3.png');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $html = view('print.multi-vehicle-custom-pdf', compact('vehicles', 'columns', 'logoBase64'))->render();

        $fontDirs = (new ConfigVariables())->getDefaults()['fontDir'];
        $fontData = (new FontVariables())->getDefaults()['fontdata'];

        $mpdf = new Mpdf([
            'mode'               => 'utf-8',
            'format'             => $format,
            'orientation'        => $orientation,
            'default_font'       => 'dejavusans',
            'default_font_size'  => 10,
            'margin_top'         => 8,
            'margin_bottom'      => 8,
            'margin_left'        => 4,
            'margin_right'       => 4,
            'fontDir'            => array_merge($fontDirs, [storage_path('fonts')]),
            'fontdata'           => $fontData,
            'tempDir'            => storage_path('mpdf'),
            'shrink_tables_to_fit' => 0,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);

        $filename = 'vehicles-custom-' . now()->format('Ymd-His') . '.pdf';

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'              => 'no-cache',
        ]);
    }
}
