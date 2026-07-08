<?php

namespace App\Exports;

use App\Models\VehicleLocation;
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
 * جدول التمركز الأسبوعي: صفوف = سيارات (اسم + محافظة)، أعمدة = أيام الأسبوع، الخلية = عنوان التمركز.
 */
class VehicleCoverageExport implements FromArray, WithHeadings, WithEvents, WithTitle, ShouldAutoSize
{
    private const DASH = '—';

    /**
     * @param  Collection  $rows  كل عنصر: {name, governorate_name, days: [day => address|null]}
     */
    public function __construct(protected Collection $rows) {}

    public function title(): string
    {
        return 'التغطية الجغرافية';
    }

    public function headings(): array
    {
        return array_merge(
            ['المحافظة', 'السيارة'],
            array_values(VehicleLocation::DAYS),
        );
    }

    public function array(): array
    {
        $dayKeys = array_keys(VehicleLocation::DAYS);
        $out     = [];

        foreach ($this->rows as $row) {
            $line = [$row['governorate_name'], $row['name']];
            foreach ($dayKeys as $day) {
                $line[] = $row['days'][$day] ?? self::DASH;
            }
            $out[] = $line;
        }

        return $out;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet    = $event->sheet->getDelegate();
                $colCount = 2 + count(VehicleLocation::DAYS);
                $lastCol  = Coordinate::stringFromColumnIndex($colCount);
                $lastRow  = $sheet->getHighestRow();
                $sheet->setRightToLeft(true);

                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C9A847']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);

                // عمودا المحافظة + السيارة
                $sheet->getStyle("A2:B{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '555555']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F0E0']],
                ]);

                if ($lastRow >= 2) {
                    $sheet->getStyle("C2:{$lastCol}{$lastRow}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
                }

                $sheet->getStyle("A1:{$lastCol}{$lastRow}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E0E0E0');

                $sheet->freezePane('C2');
                $sheet->getRowDimension(1)->setRowHeight(26);
            },
        ];
    }
}
