<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\OfficeStat;
use Illuminate\Http\Request;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class OfficePdfController extends Controller
{
    public function __invoke(Request $request, Office $office)
    {
        $user = $request->user();
        abort_unless(
            $user?->hasRole('super-admin') || $user?->can('offices.view') || $user?->can('offices.export'),
            403
        );

        $office->load([
            'governorate',
            'officeType',
            'locationDescription',
            'workSystem',
            'workingHour',
            'connectionType',
            'contractualStatus',
            'structuralCondition',
            'MicrofilmOption',
            'DisabilitieAccess',
            'FireSafety',
            'DocumentPhotocopyingService',
            'BuffetService',
            'CleanlinessContract',
            'brokenDevices.deviceType',
        ]);

        $stats = OfficeStat::where('office_id', $office->id)
            ->with('statType')
            ->where('year', '>=', now()->year - 4)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        $statGroups = $stats->groupBy(fn($s) => $s->statType->name ?? '—');

        $logoPath = public_path('images/logo3.png');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $html = view('print.office-pdf', compact('office', 'statGroups', 'logoBase64'))->render();

        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $mpdf = new Mpdf([
            'mode'              => 'utf-8',
            'format'            => 'A4',
            'default_font_size' => 11,
            'default_font'      => 'dejavusans',
            'margin_top'        => 15,
            'margin_bottom'     => 15,
            'margin_left'       => 15,
            'margin_right'      => 15,
            'fontDir'           => array_merge($fontDirs, [storage_path('fonts')]),
            'fontdata'          => $fontData,
            'tempDir'           => storage_path('mpdf'),
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);

        $filename = 'office-' . $office->getKey() . '-' . now()->format('Ymd') . '.pdf';

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
