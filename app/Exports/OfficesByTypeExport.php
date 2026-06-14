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
 * تصدير المصفوفة المتقاطعة: محافظة × نوع مقر + إجماليات.
 */
class OfficesByTypeExport implements FromArray, WithHeadings, WithEvents, WithTitle, ShouldAutoSize
{
    /**
     * @param  Collection  $governorates  صفوف (id, name)
     * @param  Collection  $types         أعمدة (id, name)
     * @param  array        $map          map[gov_id][type_id] = count
     */
    public function __construct(
        protected Collection $governorates,
        protected Collection $types,
        protected array $map,
    ) {}

    public function title(): string
    {
        return 'المقرات حسب النوع';
    }

    public function headings(): array
    {
        return array_merge(
            ['المحافظة'],
            $this->types->pluck('name')->all(),
            ['الإجمالي'],
        );
    }

    public function array(): array
    {
        $rows       = [];
        $colTotals  = array_fill_keys($this->types->pluck('id')->all(), 0);
        $grandTotal = 0;

        foreach ($this->governorates as $gov) {
            $row      = [$gov->name];
            $rowTotal = 0;
            foreach ($this->types as $type) {
                $cnt = $this->map[$gov->id][$type->id] ?? 0;
                $row[] = $cnt;
                $rowTotal += $cnt;
                $colTotals[$type->id] += $cnt;
            }
            $row[] = $rowTotal;
            $grandTotal += $rowTotal;
            $rows[] = $row;
        }

        // صف الإجمالي
        $totalRow = ['الإجمالي'];
        foreach ($this->types as $type) {
            $totalRow[] = $colTotals[$type->id];
        }
        $totalRow[] = $grandTotal;
        $rows[]     = $totalRow;

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet    = $event->sheet->getDelegate();
                $colCount = $this->types->count() + 2; // المحافظة + الأنواع + الإجمالي
                $lastCol  = Coordinate::stringFromColumnIndex($colCount);
                $lastRow  = $sheet->getHighestRow();
                $sheet->setRightToLeft(true);

                // رؤوس الأعمدة (صف 1) — ذهبي
                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C9A847']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);

                // عمود المحافظة (A) — تمييز
                $sheet->getStyle("A2:A{$lastRow}")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => '555555']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F0E0']],
                ]);

                // صف الإجمالي الأخير — تمييز
                $sheet->getStyle("A{$lastRow}:{$lastCol}{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EDE3C2']],
                ]);

                // عمود الإجمالي الأخير — تمييز
                $sheet->getStyle("{$lastCol}2:{$lastCol}{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F0E0']],
                ]);

                // محاذاة الأرقام للوسط
                $sheet->getStyle("B2:{$lastCol}{$lastRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                // حدود
                $sheet->getStyle("A1:{$lastCol}{$lastRow}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E0E0E0');

                $sheet->freezePane('B2');
                $sheet->getRowDimension(1)->setRowHeight(26);
            },
        ];
    }
}
