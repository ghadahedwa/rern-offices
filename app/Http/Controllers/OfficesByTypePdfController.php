<?php

namespace App\Http\Controllers;

use App\Models\Governorate;
use App\Models\Office;
use App\Models\OfficeType;
use Illuminate\Http\Request;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class OfficesByTypePdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        abort_unless(
            $user?->hasRole('super-admin') || $user?->can('offices.export'),
            403
        );

        ini_set('memory_limit', '512M');
        @set_time_limit(120);

        $f = session('offices_by_type_filters', []);

        $isSuperAdmin  = $user->hasRole('super-admin');
        $allowedGovIds = $isSuperAdmin ? null : $user->governorates()->pluck('governorates.id')->all();

        $governorates = Governorate::query()
            ->when($allowedGovIds, fn ($q) => $q->whereIn('id', $allowedGovIds))
            ->when($f['governorateIds'] ?? [], fn ($q) => $q->whereIn('id', $f['governorateIds']))
            ->orderBy('order')->orderBy('id')
            ->get(['id', 'name']);

        $types = OfficeType::query()
            ->when($f['typeIds'] ?? [], fn ($q) => $q->whereIn('id', $f['typeIds']))
            ->orderBy('name')
            ->get(['id', 'name']);

        $counts = Office::query()
            ->when($allowedGovIds, fn ($q) => $q->whereIn('governorate_id', $allowedGovIds))
            ->when($f['governorateIds'] ?? [], fn ($q) => $q->whereIn('governorate_id', $f['governorateIds']))
            ->when($f['typeIds'] ?? [], fn ($q) => $q->whereIn('type_id', $f['typeIds']))
            ->selectRaw('governorate_id, type_id, COUNT(*) as cnt')
            ->groupBy('governorate_id', 'type_id')
            ->get();

        $map = [];
        foreach ($counts as $c) {
            $map[$c->governorate_id][$c->type_id] = (int) $c->cnt;
        }

        $logoPath   = public_path('images/logo3.png');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        // الاتجاه حسب عدد الأنواع (الأعمدة): أكثر من 6 نوع → عرضي
        $landscape   = $types->count() > 6;
        $format      = $landscape ? 'A4-L' : 'A4';
        $orientation = $landscape ? 'L' : 'P';

        $html = view('print.offices-by-type-pdf', compact('governorates', 'types', 'map', 'logoBase64'))->render();

        $fontDirs = (new ConfigVariables())->getDefaults()['fontDir'];
        $fontData = (new FontVariables())->getDefaults()['fontdata'];

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => $format,
            'orientation'   => $orientation,
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

        $filename = 'offices-by-type-' . now()->format('Ymd-His') . '.pdf';

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'              => 'no-cache',
        ]);
    }
}
