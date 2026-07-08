<?php

namespace App\Exports;

use App\Reports\OfficeColumns;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class OfficesExport implements FromCollection, WithHeadings, WithMapping, WithEvents, WithTitle, ShouldAutoSize
{
    /** الأعمدة المُختارة (مرتّبة حسب الكتالوج) — null = تقرير شامل بكل الأعمدة */
    protected array $columns;

    /**
     * @param  Collection  $offices
     * @param  array<string>|null  $columnKeys  مفاتيح الأعمدة المطلوبة (null = الكل)
     */
    public function __construct(protected Collection $offices, ?array $columnKeys = null)
    {
        $this->columns = OfficeColumns::select($columnKeys);
    }

    public function title(): string
    {
        return 'المقرات';
    }

    public function collection(): Collection
    {
        return $this->offices;
    }

    public function headings(): array
    {
        return array_map(fn ($c) => $c['label'], array_values($this->columns));
    }

    public function map($office): array
    {
        return array_map(fn ($c) => $c['value']($office), array_values($this->columns));
    }

    /** عناوين الأقسام => عدد الأعمدة تحتها (بالترتيب، محسوبة من الأعمدة المختارة فقط) */
    protected function groupSpans(): array
    {
        $spans = [];
        foreach ($this->columns as $c) {
            $spans[$c['group']] = ($spans[$c['group']] ?? 0) + 1;
        }

        return $spans;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet    = $event->sheet->getDelegate();
                $colCount = count($this->columns);

                if ($colCount === 0) {
                    return;
                }

                $lastCol = Coordinate::stringFromColumnIndex($colCount);
                $sheet->setRightToLeft(true);

                // صف عناوين الأقسام فوق صف رؤوس الأعمدة
                $sheet->insertNewRowBefore(1, 1);
                $start = 1;
                foreach ($this->groupSpans() as $label => $span) {
                    $from = Coordinate::stringFromColumnIndex($start);
                    $to   = Coordinate::stringFromColumnIndex($start + $span - 1);
                    $sheet->mergeCells("{$from}1:{$to}1");
                    $sheet->setCellValue("{$from}1", $label);
                    $start += $span;
                }

                // تنسيق صف الأقسام (1) — ذهبي
                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C9A847']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // تنسيق صف رؤوس الأعمدة (2)
                $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => '555555']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F0E0']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);

                $lastRow = $sheet->getHighestRow();

                // حدود لكل الخلايا
                $sheet->getStyle("A1:{$lastCol}{$lastRow}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E0E0E0');

                // محاذاة عمودية للبيانات
                $sheet->getStyle("A3:{$lastCol}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                // تظليل متناوب للصفوف
                for ($row = 3; $row <= $lastRow; $row++) {
                    if ($row % 2 === 1) {
                        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FAFAFA');
                    }
                }

                // رابط الخريطة كـ hyperlink فعلي (مش نص خام كما في الداتابيز)
                $mapsColIndex = array_search('google_maps_link', array_keys($this->columns), true);
                if ($mapsColIndex !== false) {
                    $colLetter = Coordinate::stringFromColumnIndex($mapsColIndex + 1);
                    $officesList = $this->offices->values();
                    for ($row = 3; $row <= $lastRow; $row++) {
                        $url = $officesList->get($row - 3)?->google_maps_link;
                        if ($url) {
                            $cell = "{$colLetter}{$row}";
                            $sheet->getCell($cell)->getHyperlink()->setUrl($url);
                            $sheet->getStyle($cell)->applyFromArray([
                                'font' => ['color' => ['rgb' => '1155CC'], 'underline' => Font::UNDERLINE_SINGLE],
                            ]);
                        }
                    }
                }

                // تجميد أول عمود (الاسم) + صفّي الرؤوس + فلتر تلقائي
                $sheet->freezePane('B3');
                $sheet->setAutoFilter("A2:{$lastCol}2");

                $sheet->getRowDimension(1)->setRowHeight(22);
                $sheet->getRowDimension(2)->setRowHeight(28);
            },
        ];
    }
}
