<?php

namespace App\Exports;

use App\Reports\VehicleFridayWork;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * العمل أيام الجمع: ورقة الملخص (محافظة × الفئات) + ورقة السيارات بأسمائها.
 * الأرقام من `VehicleFridayWork` نفسه الذي تقرأ منه الشاشة.
 */
class VehicleFridayWorkExport implements WithMultipleSheets
{
    public function __construct(protected VehicleFridayWork $report) {}

    public function sheets(): array
    {
        $labels = VehicleFridayWork::labels();
        $keys   = array_keys($labels);

        // ── الملخص ──
        $counts    = $this->report->counts();
        $summary   = [];
        $colTotals = array_fill_keys($keys, 0);
        foreach ($this->report->governorates() as $gov) {
            $row = [$gov->name];
            foreach ($keys as $k) {
                $v = $counts[$gov->id][$k] ?? 0;
                $row[] = $v;
                $colTotals[$k] += $v;
            }
            $row[]     = array_sum(array_slice($row, 1));
            $summary[] = $row;
        }
        $summary[] = array_merge(['الإجمالي'], array_values($colTotals), [array_sum($colTotals)]);

        // ── السيارات ──
        $list = $this->report->vehicles()->values()->map(fn ($v, $i) => [
            $i + 1,
            $v->governorate->name ?? '—',
            $v->name,
            $v->type->name ?? '—',
            $labels[VehicleFridayWork::keyOf($v->works_on_friday)],
        ])->all();

        return [
            new VehicleFridayWorkSheet('الملخص', array_merge(['المحافظة'], array_values($labels), ['الإجمالي']), $summary, totalRow: true),
            new VehicleFridayWorkSheet('السيارات', ['م', 'المحافظة', 'اسم السيارة', 'نوع السيارة', 'العمل أيام الجمع'], $list, totalRow: false),
        ];
    }
}
