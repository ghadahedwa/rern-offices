<?php

namespace App\Exports;

use App\Reports\VehicleStatistics;
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
 * إحصائيات السيارات — الأرقام من `VehicleStatistics` نفسه الذي تقرأ منه الشاشة.
 * خلايا أرقام خالصة (لا «—»): ما لم يُسجَّل خليةٌ فارغة، فيبقى الملف صالحاً للحساب.
 * ⚠️ `WithStrictNullComparison`: بدونه يُكتب الصفر خانةً فارغة (`0 == null`).
 */
class VehicleStatisticsExport implements FromArray, WithHeadings, WithEvents, WithTitle, ShouldAutoSize, WithStrictNullComparison
{
    /** أرقام صفوف الإجمالي (للتنسيق) */
    private array $totalRows = [];

    public function __construct(protected VehicleStatistics $report) {}

    public function title(): string
    {
        return (string) $this->report->year();
    }

    public function headings(): array
    {
        $y = $this->report->year();

        $headings = [
            'المحافظة', 'السيارة',
            'متوسط المعاملات اليومي', 'متوسط الدعم اليومي', 'متوسط الانتقال اليومي',
            __('home.vstat_col_trips', ['year' => $y]),
            __('home.vstat_col_instant_trips', ['year' => $y]),
            "معاملات التوثيق {$y}" . ($this->report->isPartial() ? ' (حتى الآن)' : ''),
        ];

        if (! $this->report->isPartial()) {
            $headings[] = 'معاملات التوثيق ' . ($y - 1);
        }

        return $headings;
    }

    public function array(): array
    {
        $withPrev = ! $this->report->isPartial();
        $rows     = [];

        $line = function (array $values, ?array $prev) use ($withPrev) {
            return $withPrev ? [...$values, $prev[0]] : $values;
        };

        foreach ($this->report->groups() as $group) {
            $gov = $group['governorate']->name ?? '';

            foreach ($group['vehicles'] as $r) {
                $v = $r['vehicle'];
                $rows[] = $line([
                    $gov, $v->name,
                    $v->avg_daily_transactions, $v->avg_daily_support, $v->avg_daily_trips,
                    $r['trips'], $r['instant'], $r['tx'],
                ], [$r['txPrev']]);
            }

            $t = $group['totals'];
            $rows[] = $line(["إجمالي {$gov}", null, null, null, null, $t['trips'], $t['instant'], $t['tx']], [$t['txPrev']]);
            $this->totalRows[] = count($rows) + 1;   // +1 لصفّ الرؤوس
        }

        $g = $this->report->grandTotals();
        $rows[] = $line(['الإجمالي العام', null, null, null, null, $g['trips'], $g['instant'], $g['tx']], [$g['txPrev']]);
        $this->totalRows[] = count($rows) + 1;

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = Coordinate::stringFromColumnIndex(count($this->headings()));
                $lastRow = $sheet->getHighestRow();
                $sheet->setRightToLeft(true);

                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C9A847']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);

                foreach ($this->totalRows as $r) {
                    $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EDE3C2']],
                    ]);
                }

                $sheet->getStyle("C2:{$lastCol}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C2:{$lastCol}{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("A1:{$lastCol}{$lastRow}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E0E0E0');

                $sheet->freezePane('C2');
                $sheet->getRowDimension(1)->setRowHeight(30);
            },
        ];
    }
}
