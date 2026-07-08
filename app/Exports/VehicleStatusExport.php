<?php

namespace App\Exports;

use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * الحالة التشغيلية للسيارات: محافظة × (تعمل / صيانة / متوقفة + الإجمالي + نسبة الجاهزية).
 */
class VehicleStatusExport implements FromArray, WithHeadings, WithEvents, WithTitle, ShouldAutoSize
{
    /**
     * @param  Collection  $governorates  صفوف (id, name)
     * @param  array        $counts        counts[gov_id][status] = عدد
     */
    public function __construct(
        protected Collection $governorates,
        protected array $counts,
    ) {}

    public function title(): string
    {
        return 'الحالة التشغيلية';
    }

    public function headings(): array
    {
        return array_merge(
            ['المحافظة'],
            array_values(Vehicle::STATUSES),
            ['الإجمالي', 'نسبة الجاهزية'],
        );
    }

    public function array(): array
    {
        $statusKeys = array_keys(Vehicle::STATUSES);
        $rows       = [];
        $colTotals  = array_fill_keys($statusKeys, 0);

        foreach ($this->governorates as $gov) {
            $row      = [$gov->name];
            $rowTotal = 0;

            foreach ($statusKeys as $st) {
                $v = $this->counts[$gov->id][$st] ?? 0;
                $row[] = $v;
                $colTotals[$st] += $v;
                $rowTotal += $v;
            }
            $row[] = $rowTotal;
            $row[] = $this->readiness($this->counts[$gov->id]['working'] ?? 0, $rowTotal);

            $rows[] = $row;
        }

        // صف الإجمالي
        $grandTotal = array_sum($colTotals);
        $totalRow   = ['الإجمالي'];
        foreach ($statusKeys as $st) {
            $totalRow[] = $colTotals[$st];
        }
        $totalRow[] = $grandTotal;
        $totalRow[] = $this->readiness($colTotals['working'] ?? 0, $grandTotal);
        $rows[]     = $totalRow;

        return $rows;
    }

    private function readiness(int $working, int $total): string
    {
        return $total > 0 ? round($working / $total * 100) . '%' : '—';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet    = $event->sheet->getDelegate();
                // المحافظة + 3 حالات + إجمالي + نسبة
                $colCount = 1 + count(Vehicle::STATUSES) + 2;
                $lastCol  = Coordinate::stringFromColumnIndex($colCount);
                $lastRow  = $sheet->getHighestRow();
                $sheet->setRightToLeft(true);

                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C9A847']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);

                $sheet->getStyle("A2:A{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '555555']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F0E0']],
                ]);

                // عمود الإجمالي (قبل الأخير)
                $totalCol = Coordinate::stringFromColumnIndex($colCount - 1);
                $sheet->getStyle("{$totalCol}2:{$totalCol}{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F0E0']],
                ]);

                $sheet->getStyle("A{$lastRow}:{$lastCol}{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EDE3C2']],
                ]);

                $sheet->getStyle("B2:{$lastCol}{$lastRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getStyle("A1:{$lastCol}{$lastRow}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E0E0E0');

                $sheet->freezePane('B2');
                $sheet->getRowDimension(1)->setRowHeight(26);
            },
        ];
    }
}
