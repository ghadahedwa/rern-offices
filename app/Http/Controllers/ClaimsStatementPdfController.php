<?php

namespace App\Http\Controllers;

use App\Models\Governorate;
use App\Reports\ClaimsStatement;
use Illuminate\Http\Request;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class ClaimsStatementPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        abort_unless(
            $user?->hasRole('super-admin') || $user?->can('claims.export'),
            403
        );

        $f = session('claims_statement_filters', []);
        abort_unless(! empty($f['governorateId']), 404);

        $isSuperAdmin  = $user->hasRole('super-admin');
        $allowedGovIds = $isSuperAdmin ? null : $user->governorates()->pluck('governorates.id')->all();

        $gov = Governorate::findOrFail($f['governorateId']);
        abort_unless($isSuperAdmin || in_array($gov->id, $allowedGovIds ?? [], true), 403);

        $statement = ClaimsStatement::build($gov, (int) $f['fromKey'], (int) $f['toKey']);
        $months    = ClaimsStatement::months();

        $fromLabel = ($months[$f['fromKey'] % 100] ?? '') . ' ' . intdiv($f['fromKey'], 100);
        $toLabel   = ($months[$f['toKey'] % 100] ?? '') . ' ' . intdiv($f['toKey'], 100);

        $logoPath   = public_path('images/logo3.png');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $html = view('print.claims-statement-pdf', compact(
            'statement', 'months', 'gov', 'fromLabel', 'toLabel', 'logoBase64'
        ))->render();

        $fontDirs = (new ConfigVariables())->getDefaults()['fontDir'];
        $fontData = (new FontVariables())->getDefaults()['fontdata'];

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'default_font'  => 'dejavusans',
            'margin_top'    => 10,
            'margin_bottom' => 10,
            'margin_left'   => 10,
            'margin_right'  => 10,
            'fontDir'       => array_merge($fontDirs, [storage_path('fonts')]),
            'fontdata'      => $fontData,
            'tempDir'       => storage_path('mpdf'),
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);

        $filename = 'claims-statement-' . $gov->id . '-' . now()->format('Ymd-His') . '.pdf';

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'              => 'no-cache',
        ]);
    }
}
