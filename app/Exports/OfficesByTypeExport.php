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
 * المقرات حسب المحافظة: مجموعة أعمدة النوع + مجموعة أعمدة وصف الموقع + إجمالي مقرات المحافظة.
 */
class OfficesByTypeExport implements FromArray, WithHeadings, WithEvents, WithTitle, ShouldAutoSize
{
    /**
     * @param  Collection  $governorates    صفوف (id, name)
     * @param  Collection  $types           أعمدة النوع (id, name)
     * @param  Collection  $locations       أعمدة وصف الموقع (id, name)
     * @param  array        $typeCounts     [gov_id][type_id] = count
     * @param  array        $locationCounts [gov_id][location_id] = count
     */
    public function __construct(
        protected Collection $governorates,
        protected Collection $types,
        protected Collection $locations,
        protected array $typeCounts,
        protected array $locationCounts,
    ) {}

    public function title(): string
    {
        return 'المقرات حسب المحافظة';
    }

    public function headings(): array
    {
        return array_merge(
            ['المحافظة'],
            $this->types->pluck('name')->all(),
            $this->types->isNotEmpty() ? ['إجمالي نوع المقر'] : [],
            $this->locations->pluck('name')->map(fn ($n) => $n . ' (موقع)')->all(),
            $this->locations->isNotEmpty() ? ['إجمالي وصف المقر'] : [],
        );
    }

    public function array(): array
    {
        $rows          = [];
        $hasTypes      = $this->types->isNotEmpty();
        $hasLocations  = $this->locations->isNotEmpty();
        $typeColTotals = array_fill_keys($this->types->pluck('id')->all(), 0);
        $locColTotals  = array_fill_keys($this->locations->pluck('id')->all(), 0);

        foreach ($this->governorates as $gov) {
            $row          = [$gov->name];
            $typeRowTotal = 0;
            $locRowTotal  = 0;

            foreach ($this->types as $type) {
                $v = $this->typeCounts[$gov->id][$type->id] ?? 0;
                $row[] = $v;
                $typeColTotals[$type->id] += $v;
                $typeRowTotal += $v;
            }
            if ($hasTypes) {
                $row[] = $typeRowTotal;
            }
            foreach ($this->locations as $loc) {
                $v = $this->locationCounts[$gov->id][$loc->id] ?? 0;
                $row[] = $v;
                $locColTotals[$loc->id] += $v;
                $locRowTotal += $v;
            }
            if ($hasLocations) {
                $row[] = $locRowTotal;
            }

            $rows[] = $row;
        }

        // صف الإجمالي
        $totalRow = ['الإجمالي'];
        foreach ($this->types as $type) {
            $totalRow[] = $typeColTotals[$type->id];
        }
        if ($hasTypes) {
            $totalRow[] = array_sum($typeColTotals);
        }
        foreach ($this->locations as $loc) {
            $totalRow[] = $locColTotals[$loc->id];
        }
        if ($hasLocations) {
            $totalRow[] = array_sum($locColTotals);
        }
        $rows[] = $totalRow;

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet        = $event->sheet->getDelegate();
                $hasTypes     = $this->types->isNotEmpty();
                $hasLocations = $this->locations->isNotEmpty();
                $colCount     = 1 + $this->types->count() + ($hasTypes ? 1 : 0)
                                  + $this->locations->count() + ($hasLocations ? 1 : 0);
                $lastCol      = Coordinate::stringFromColumnIndex($colCount);
                $lastRow      = $sheet->getHighestRow();
                $sheet->setRightToLeft(true);

                // مواضع عمودَي الإجمالي (إجمالي النوع، إجمالي الموقع)
                $totalColIndexes = [];
                if ($hasTypes) {
                    $totalColIndexes[] = 1 + $this->types->count() + 1;
                }
                if ($hasLocations) {
                    $totalColIndexes[] = $colCount;
                }

                // رؤوس الأعمدة — ذهبي
                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C9A847']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);

                // عمود المحافظة + عمود الإجمالي
                $sheet->getStyle("A2:A{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '555555']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F0E0']],
                ]);
                foreach ($totalColIndexes as $idx) {
                    $totalCol = Coordinate::stringFromColumnIndex($idx);
                    $sheet->getStyle("{$totalCol}2:{$totalCol}{$lastRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F0E0']],
                    ]);
                }

                // صف الإجمالي الأخير
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
