<?php

namespace App\Http\Controllers;

use App\Models\Governorate;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class VehicleStatusPdfController extends Controller
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

        $f = session('vehicle_status_filters', []);
        $govIds = $f['governorateIds'] ?? [];

        $isSuperAdmin  = $user->hasRole('super-admin');
        $allowedGovIds = $isSuperAdmin ? null : $user->governorates()->pluck('governorates.id')->all();

        $governorates = Governorate::query()
            ->when($allowedGovIds, fn ($q) => $q->whereIn('id', $allowedGovIds))
            ->when($govIds, fn ($q) => $q->whereIn('id', $govIds))
            ->orderBy('order')->orderBy('id')
            ->get(['id', 'name']);

        $rows = Vehicle::query()
            ->when($allowedGovIds, fn ($q) => $q->whereIn('governorate_id', $allowedGovIds))
            ->when($govIds, fn ($q) => $q->whereIn('governorate_id', $govIds))
            ->selectRaw('governorate_id, status, COUNT(*) as cnt')
            ->groupBy('governorate_id', 'status')
            ->get();

        $counts = [];
        foreach ($rows as $r) {
            $counts[$r->governorate_id][$r->status] = (int) $r->cnt;
        }

        $statuses = Vehicle::STATUSES;

        $logoPath   = public_path('images/logo3.png');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $html = view('print.vehicle-status-pdf', compact('governorates', 'counts', 'statuses', 'logoBase64'))->render();

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

        $filename = 'vehicle-status-' . now()->format('Ymd-His') . '.pdf';

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'              => 'no-cache',
        ]);
    }
}
