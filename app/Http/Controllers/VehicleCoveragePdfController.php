<?php

namespace App\Http\Controllers;

use App\Models\Governorate;
use App\Models\Vehicle;
use App\Models\VehicleLocation;
use Illuminate\Http\Request;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class VehicleCoveragePdfController extends Controller
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

        $f = session('vehicle_coverage_filters', []);
        $govIds = $f['governorateIds'] ?? [];

        $isSuperAdmin  = $user->hasRole('super-admin');
        $allowedGovIds = $isSuperAdmin ? null : $user->governorates()->pluck('governorates.id')->all();

        $vehicles = Vehicle::query()
            ->with(['governorate:id,name', 'locations:id,vehicle_id,day,address'])
            ->when($allowedGovIds, fn ($q) => $q->whereIn('governorate_id', $allowedGovIds))
            ->when($govIds, fn ($q) => $q->whereIn('governorate_id', $govIds))
            ->orderBy(Governorate::select('order')->whereColumn('governorates.id', 'vehicles.governorate_id'))
            ->orderBy('governorate_id')
            ->orderBy('name')
            ->get();

        $days = VehicleLocation::DAYS;

        $rows = $vehicles->map(function (Vehicle $v) use ($days) {
            $vDays = [];
            foreach (array_keys($days) as $day) {
                $addresses = $v->locations->where('day', $day)->pluck('address')->filter()->implode(' | ');
                $vDays[$day] = $addresses !== '' ? $addresses : null;
            }

            return [
                'name'             => $v->name,
                'governorate_name' => $v->governorate->name ?? '—',
                'days'             => $vDays,
            ];
        });

        $logoPath   = public_path('images/logo3.png');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $html = view('print.vehicle-coverage-pdf', compact('rows', 'days', 'logoBase64'))->render();

        $fontDirs = (new ConfigVariables())->getDefaults()['fontDir'];
        $fontData = (new FontVariables())->getDefaults()['fontdata'];

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4-L',
            'orientation'   => 'L',
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

        $filename = 'vehicle-coverage-' . now()->format('Ymd-His') . '.pdf';

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'              => 'no-cache',
        ]);
    }
}
