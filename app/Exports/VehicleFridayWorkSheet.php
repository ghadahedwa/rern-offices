<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * ورقة من ملف «العمل أيام الجمع» — بتنسيق تقرير الحالة التشغيلية نفسه.
 * ⚠️ `WithStrictNullComparison`: بدونه يُكتب الصفر خانةً فارغة (`0 == null`).
 */
class VehicleFridayWorkSheet implements FromArray, WithHeadings, WithEvents, WithTitle, ShouldAutoSize, WithStrictNullComparison
{
    public function __construct(
        protected string $title,
        protected array $headings,
        protected array $rows,
        protected bool $totalRow,
    ) {}

    public function title(): string
    {
        return $this->title;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = Coordinate::stringFromColumnIndex(count($this->headings));
                $lastRow = $sheet->getHighestRow();
                $sheet->setRightToLeft(true);

                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C9A847']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);

                if ($this->totalRow && $lastRow > 1) {
                    $sheet->getStyle("A{$lastRow}:{$lastCol}{$lastRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EDE3C2']],
                    ]);
                    $sheet->getStyle("B2:{$lastCol}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $sheet->getStyle("A1:{$lastCol}{$lastRow}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E0E0E0');

                $sheet->freezePane('A2');
                $sheet->getRowDimension(1)->setRowHeight(26);
            },
        ];
    }
}
