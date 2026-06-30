<?php

namespace App\Reports;

use App\Models\Governorate;
use App\Models\GovernorateCancelledDemand;
use App\Models\GovernorateClaim;
use App\Models\GovernorateDemand;

/**
 * كشف حساب محافظة (تراكمي): مطالبات + محصل + رصيد تراكمي خلال فترة.
 * مفتاح الفترة = year*100 + month (تصاعدي قابل للمقارنة).
 */
class ClaimsStatement
{
    public static function months(): array
    {
        return [
            1 => 'يناير',  2 => 'فبراير', 3 => 'مارس',    4 => 'أبريل',
            5 => 'مايو',   6 => 'يونيو',  7 => 'يوليو',   8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];
    }

    /**
     * @return array{governorate:Governorate, opening:float, rows:array<int,array{year:int,month:int,demand:float,collected:float,balance:float}>, totalDemand:float, totalCollected:float, closing:float}
     */
    public static function build(Governorate $governorate, int $fromKey, int $toKey): array
    {
        $govId = $governorate->id;

        // الرصيد الافتتاحي = (مطالبات − ملغاة − محصل) لكل ما قبل بداية الفترة
        $openDemand = (float) GovernorateDemand::where('governorate_id', $govId)
            ->whereRaw('(year * 100 + month) < ?', [$fromKey])->sum('amount');
        $openCancelled = (float) GovernorateCancelledDemand::where('governorate_id', $govId)
            ->whereRaw('(year * 100 + month) < ?', [$fromKey])->sum('amount');
        $openCollected = (float) GovernorateClaim::where('governorate_id', $govId)
            ->whereRaw('(year * 100 + month) < ?', [$fromKey])->sum('value');
        $opening = $openDemand - $openCancelled - $openCollected;

        // حركات الفترة
        $demandRows = GovernorateDemand::where('governorate_id', $govId)
            ->whereRaw('(year * 100 + month) between ? and ?', [$fromKey, $toKey])
            ->get(['year', 'month', 'amount']);
        $cancelRows = GovernorateCancelledDemand::where('governorate_id', $govId)
            ->whereRaw('(year * 100 + month) between ? and ?', [$fromKey, $toKey])
            ->selectRaw('year, month, SUM(amount) as amount')
            ->groupBy('year', 'month')->get();
        $collectRows = GovernorateClaim::where('governorate_id', $govId)
            ->whereRaw('(year * 100 + month) between ? and ?', [$fromKey, $toKey])
            ->get(['year', 'month', 'value']);

        // دمج حسب (سنة/شهر)
        $map = [];
        $blank = fn ($r) => ['year' => $r->year, 'month' => $r->month, 'demand' => 0.0, 'cancelled' => 0.0, 'collected' => 0.0];
        foreach ($demandRows as $r) {
            $k = $r->year * 100 + $r->month;
            $map[$k] = ($map[$k] ?? $blank($r));
            $map[$k]['demand'] = (float) $r->amount;
        }
        foreach ($cancelRows as $r) {
            $k = $r->year * 100 + $r->month;
            $map[$k] = ($map[$k] ?? $blank($r));
            $map[$k]['cancelled'] = (float) $r->amount;
        }
        foreach ($collectRows as $r) {
            $k = $r->year * 100 + $r->month;
            $map[$k] = ($map[$k] ?? $blank($r));
            $map[$k]['collected'] = (float) $r->value;
        }
        ksort($map);

        $running        = $opening;
        $rows           = [];
        $totalDemand    = 0.0;
        $totalCancelled = 0.0;
        $totalCollected = 0.0;
        foreach ($map as $m) {
            $running        += $m['demand'] - $m['cancelled'] - $m['collected'];
            $totalDemand    += $m['demand'];
            $totalCancelled += $m['cancelled'];
            $totalCollected += $m['collected'];
            $rows[] = [
                'year'      => $m['year'],
                'month'     => $m['month'],
                'demand'    => $m['demand'],
                'cancelled' => $m['cancelled'],
                'net'       => $m['demand'] - $m['cancelled'],
                'collected' => $m['collected'],
                'balance'   => $running,
            ];
        }

        return [
            'governorate'    => $governorate,
            'opening'        => $opening,
            'rows'           => $rows,
            'totalDemand'    => $totalDemand,
            'totalCancelled' => $totalCancelled,
            'totalNet'       => $totalDemand - $totalCancelled, // صافي المطالبات (مطالبات − ملغاة)
            'totalCollected' => $totalCollected,
            'periodNet'      => $totalDemand - $totalCancelled - $totalCollected, // صافي مديونية الفترة (بدون ما قبلها)
            'closing'        => $running,
        ];
    }
}
