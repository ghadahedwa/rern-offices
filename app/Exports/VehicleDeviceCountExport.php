<?php

namespace App\Exports;

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
 * بيان تجهيزات السيارات العددي: محافظة × (أعمدة التجهيزات العاملة + إجمالي) × (أعمدة المعطلة بالنوع + إجمالي).
 * نفس نمط DeviceCountExport بالحرف.
 */
class VehicleDeviceCountExport implements FromArray, WithHeadings, WithEvents, WithTitle, ShouldAutoSize
{
    /**
     * @param  Collection  $governorates  صفوف (id, name)
     * @param  array        $workingCols   [col => label] للتجهيزات العاملة المعروضة
     * @param  Collection  $brokenTypes   أنواع المعطلة المعروضة (id, name)
     * @param  array        $sums          sums[gov_id][col] = مجموع
     * @param  array        $brokenSums    brokenSums[gov_id][type_id] = مجموع
     */
    public function __construct(
        protected Collection $governorates,
        protected array $workingCols,
        protected Collection $brokenTypes,
        protected array $sums,
        protected array $brokenSums,
    ) {}

    public function title(): string
    {
        return 'بيان تجهيزات السيارات';
    }

    public function headings(): array
    {
        return array_merge(
            ['المحافظة'],
            array_values($this->workingCols),
            $this->brokenTypes->pluck('name')->map(fn ($n) => $n . ' (معطل)')->all(),
        );
    }

    public function array(): array
    {
        $workingKeys = array_keys($this->workingCols);
        $rows        = [];

        $wColTotals  = array_fill_keys($workingKeys, 0);
        $bColTotals  = array_fill_keys($this->brokenTypes->pluck('id')->all(), 0);

        foreach ($this->governorates as $gov) {
            $row = [$gov->name];

            foreach ($workingKeys as $col) {
                $v = $this->sums[$gov->id][$col] ?? 0;
                $row[] = $v;
                $wColTotals[$col] += $v;
            }
            foreach ($this->brokenTypes as $type) {
                $v = $this->brokenSums[$gov->id][$type->id] ?? 0;
                $row[] = $v;
                $bColTotals[$type->id] += $v;
            }

            $rows[] = $row;
        }

        $totalRow = ['الإجمالي'];
        foreach ($workingKeys as $col) {
            $totalRow[] = $wColTotals[$col];
        }
        foreach ($this->brokenTypes as $type) {
            $totalRow[] = $bColTotals[$type->id];
        }
        $rows[] = $totalRow;

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet    = $event->sheet->getDelegate();
                $colCount = 1 + count($this->workingCols) + $this->brokenTypes->count();
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

                $sheet->getStyle("A{$lastRow}:{$lastCol}{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EDE3C2']],
                ]);

                $sheet->getStyle("B2:{$lastCol}{$lastRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getStyle("A1:{$lastCol}{$lastRow}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E0E0E0');

                $sheet->freezePane('B2');
                $sheet->getRowDimension(1)->setRowHeight(30);
            },
        ];
    }
}
