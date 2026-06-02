<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\OfficeStat;
use Illuminate\Http\Request;

class OfficePdfController extends Controller
{
    public function __invoke(Request $request, Office $office)
    {
        $user = $request->user();
        abort_unless(
            $user?->hasRole('super-admin') || $user?->can('offices.export'),
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

        // آخر 5 سنوات من الإحصائيات مجمّعة باسم النوع
        $stats = OfficeStat::where('office_id', $office->id)
            ->with('statType')
            ->where('year', '>=', now()->year - 4)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        $statGroups = $stats->groupBy(fn($s) => $s->statType->name ?? '—');

        return view('print.office', compact('office', 'statGroups'));
    }
}
