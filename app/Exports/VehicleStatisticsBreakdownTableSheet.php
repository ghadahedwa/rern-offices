<?php

namespace App\Exports;

use App\Reports\VehicleStatisticsBreakdown;
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
 * ورقة الجدول — خلايا أرقام خالصة: ما لم يُسجَّل خليةٌ فارغة، والصفر صفر.
 * ⚠️ `WithStrictNullComparison`: بدونه يُكتب الصفر خانةً فارغة (`0 == null`).
 */
class VehicleStatisticsBreakdownTableSheet implements FromArray, WithHeadings, WithEvents, WithTitle, ShouldAutoSize, WithStrictNullComparison
{
    private array $totalRows = [];

    public function __construct(protected VehicleStatisticsBreakdown $report) {}

    public function title(): string
    {
        // اسم الورقة: ٣١ حرفاً بلا الرموز الممنوعة في Excel
        $title = $this->report->isVertical() ? $this->report->subjectLabel() : $this->report->metricLabel();

        return mb_substr(str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', $title), 0, 31) ?: 'Sheet1';
    }

    private function byVehicle(): bool
    {
        return ! $this->report->isVertical() && $this->report->level() === 'vehicle';
    }

    public function headings(): array
    {
        // الرأسي (سيارة/محافظة واحدة): الفترة ثم مؤشرات العائلة — كالشاشة
        if ($this->report->isVertical()) {
            return [
                $this->report->period() === 'monthly' ? __('home.vbd_col_month') : __('home.vbd_col_year'),
                ...array_map(fn ($m) => VehicleStatisticsBreakdown::seriesLabel($m), $this->report->series()),
            ];
        }

        $lead = $this->byVehicle()
            ? [__('home.vbd_level_governorate'), __('home.vbd_level_vehicle')]
            : [__('home.vbd_level_governorate'), __('home.vbd_col_vehicles'), __('home.vbd_col_reporting')];

        $periods = [...array_values($this->report->columns()), __('home.vbd_col_total')];
        $sub     = $this->report->splitSeries();

        // إجمالي الانتقالات: ثلاثة أعمدة لكل فترة في صفّ رأسٍ واحد («سبتمبر — عادية») — يبقى صالحاً للفلترة
        $periods = $sub
            ? array_merge(...array_map(fn ($p) => array_map(fn ($m) => $p . ' — ' . VehicleStatisticsBreakdown::subLabel($m), $sub), $periods))
            : $periods;

        return [...$lead, ...$periods];
    }

    public function array(): array
    {
        if ($this->report->isVertical()) {
            $vt   = $this->report->vertical();
            $rows = array_values(array_map(fn ($r) => [$r['label'], ...array_values($r['values'])], $vt['rows']));
            $rows[] = [__('home.vbd_col_total'), ...array_values($vt['total'])];
            $this->totalRows[] = count($rows) + 1;

            return $rows;
        }

        $cols = array_keys($this->report->columns());
        $rows = [];
        $sub  = $this->report->splitSeries();
        $line = fn (array $row) => $sub
            ? [
                ...array_merge(...array_map(fn ($c) => array_map(fn ($m) => $row['split'][$m]['cells'][$c] ?? null, $sub), $cols)),
                ...array_map(fn ($m) => $row['split'][$m]['total'] ?? null, $sub),
            ]
            : [...array_map(fn ($c) => $row['cells'][$c], $cols), $row['total']];

        foreach ($this->report->groups() as $group) {
            $gov = $group['governorate']->name ?? '';
            $t   = $group['total'];

            if (! $this->byVehicle()) {
                $rows[] = [$gov, $t['vehicles'], $t['reporting'], ...$line($t)];
                continue;
            }

            foreach ($group['vehicleRows'] as $r) {
                $rows[] = [$gov, $r['vehicle']->name, ...$line($r)];
            }
            $rows[] = [__('home.vstat_gov_total', ['name' => $gov]), __('home.vbd_reporting_of', ['n' => $t['reporting'], 'm' => $t['vehicles']]), ...$line($t)];
            $this->totalRows[] = count($rows) + 1;
        }

        $g = $this->report->grandTotal();
        $rows[] = $this->byVehicle()
            ? [__('home.vbd_grand_total'), __('home.vbd_reporting_of', ['n' => $g['reporting'], 'm' => $g['vehicles']]), ...$line($g)]
            : [__('home.vbd_grand_total'), $g['vehicles'], $g['reporting'], ...$line($g)];
        $this->totalRows[] = count($rows) + 1;

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet    = $event->sheet->getDelegate();
                $lastCol  = Coordinate::stringFromColumnIndex(count($this->headings()));
                $lastRow  = $sheet->getHighestRow();
                $firstNum = $this->byVehicle() ? 'C' : 'B';   // الرأسي والمحافظة: الأرقام من B
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

                $sheet->getStyle("{$firstNum}2:{$lastCol}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("{$firstNum}2:{$lastCol}{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("A1:{$lastCol}{$lastRow}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E0E0E0');

                $sheet->freezePane($firstNum . '2');
                $sheet->getRowDimension(1)->setRowHeight(30);
            },
        ];
    }
}

