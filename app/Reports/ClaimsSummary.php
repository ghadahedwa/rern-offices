<?php

namespace App\Reports;

use App\Models\GovernorateCancelledDemand;
use App\Models\GovernorateClaim;
use App\Models\GovernorateDemand;
use Illuminate\Support\Collection;

/**
 * ملخص المديونية لكل المحافظات (إجمالي تراكمي): مطالبات − ملغاة − محصل = مديونية، + نسبة التحصيل.
 */
class ClaimsSummary
{
    /**
     * @param  Collection  $governorates  المحافظات المسموح بها (لها id, name)
     * @return array{rows:array, totalDemands:float, totalCancelled:float, totalCollected:float, totalDebt:float, rate:float|null}
     */
    public static function build(Collection $governorates): array
    {
        $ids = $governorates->pluck('id');

        $demByGov = GovernorateDemand::whereIn('governorate_id', $ids)
            ->selectRaw('governorate_id, SUM(amount) as t')->groupBy('governorate_id')->pluck('t', 'governorate_id');
        $canByGov = GovernorateCancelledDemand::whereIn('governorate_id', $ids)
            ->selectRaw('governorate_id, SUM(amount) as t')->groupBy('governorate_id')->pluck('t', 'governorate_id');
        $colByGov = GovernorateClaim::whereIn('governorate_id', $ids)
            ->selectRaw('governorate_id, SUM(value) as t')->groupBy('governorate_id')->pluck('t', 'governorate_id');

        $rows = [];
        $totalDemands = $totalCancelled = $totalCollected = 0.0;

        foreach ($governorates as $gov) {
            $d = (float) ($demByGov[$gov->id] ?? 0);
            $x = (float) ($canByGov[$gov->id] ?? 0);
            $c = (float) ($colByGov[$gov->id] ?? 0);
            $net  = $d - $x;                       // صافي المطالبات (بعد الملغاة)
            $debt = $net - $c;                     // المديونية
            $rate = $net > 0 ? round($c / $net * 100, 1) : null;

            $rows[] = [
                'name'      => $gov->name,
                'demands'   => $d,
                'cancelled' => $x,
                'collected' => $c,
                'debt'      => $debt,
                'rate'      => $rate,
            ];

            $totalDemands   += $d;
            $totalCancelled += $x;
            $totalCollected += $c;
        }

        $totalNet  = $totalDemands - $totalCancelled;
        $totalDebt = $totalNet - $totalCollected;
        $rate      = $totalNet > 0 ? round($totalCollected / $totalNet * 100, 1) : null;

        return [
            'rows'           => $rows,
            'totalDemands'   => $totalDemands,
            'totalCancelled' => $totalCancelled,
            'totalCollected' => $totalCollected,
            'totalDebt'      => $totalDebt,
            'rate'           => $rate,
        ];
    }
}
