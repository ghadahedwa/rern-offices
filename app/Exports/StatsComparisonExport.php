<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * مقارنة الإحصائيات بين المحافظات: محافظة × (لكل مجموعة: سنة 1 · سنة 2 · نسبة التغيير).
 * رأس بصفّين: اسم المجموعة (مدموج فوق 3 أعمدة) ثم السنتان + نسبة التغيير.
 */
class StatsComparisonExport implements FromArray, WithEvents, WithTitle, ShouldAutoSize
{
    /**
     * @param  Collection  $governorates  صفوف (id, name)
     * @param  array        $groups       group_key => التسمية (مرتّبة)
     * @param  array        $data         data[gov_id][group_key][year] = مجموع
     */
    public function __construct(
        protected Collection $governorates,
        protected array $groups,
        protected array $data,
        protected int $year1,
        protected int $year2,
    ) {}

    public function title(): string
    {
        return 'مقارنة الإحصائيات';
    }

    public function array(): array
    {
        $keys = array_keys($this->groups);

        // صف الرأس العلوي: المحافظة + اسم كل مجموعة (يُدمج لاحقاً فوق 3 أعمدة)
        $row1 = ['المحافظة'];
        foreach ($this->groups as $label) {
            $row1[] = $label;
            $row1[] = '';
            $row1[] = '';
        }

        // صف الرأس السفلي: سنة1 · سنة2 · نسبة التغيير لكل مجموعة
        $row2 = [''];
        foreach ($keys as $k) {
            $row2[] = (string) $this->year1;
            $row2[] = (string) $this->year2;
            $row2[] = 'نسبة التغيير %';
        }

        $rows = [$row1, $row2];

        $tot1 = array_fill_keys($keys, 0);
        $tot2 = array_fill_keys($keys, 0);

        foreach ($this->governorates as $gov) {
            $r = [$gov->name];
            foreach ($keys as $k) {
                $v1 = $this->data[$gov->id][$k][$this->year1] ?? 0;
                $v2 = $this->data[$gov->id][$k][$this->year2] ?? 0;
                $tot1[$k] += $v1;
                $tot2[$k] += $v2;
                $r[] = $v1;
                $r[] = $v2;
                $r[] = $this->changeStr($v1, $v2);
            }
            $rows[] = $r;
        }

        // صف الإجمالي
        $totalRow = ['الإجمالي'];
        foreach ($keys as $k) {
            $totalRow[] = $tot1[$k];
            $totalRow[] = $tot2[$k];
            $totalRow[] = $this->changeStr($tot1[$k], $tot2[$k]);
        }
        $rows[] = $totalRow;

        return $rows;
    }

    /** نسبة التغيير كنص بإشارة، أو "—" إذا السنة الأولى صفر */
    private function changeStr(int $a, int $b): string
    {
        if ($a <= 0) {
            return '—';
        }

        $c = round((($b - $a) / $a) * 100, 1);

        return ($c > 0 ? '+' : '') . $c . '%';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet      = $event->sheet->getDelegate();
                $groupCount = count($this->groups);
                $colCount   = 1 + 3 * $groupCount;
                $lastCol    = Coordinate::stringFromColumnIndex($colCount);
                $lastRow    = $sheet->getHighestRow();
                $sheet->setRightToLeft(true);

                // دمج خلية المحافظة عبر صفّي الرأس
                $sheet->mergeCells('A1:A2');

                // دمج اسم كل مجموعة فوق أعمدتها الثلاثة في الصف الأول
                for ($i = 0; $i < $groupCount; $i++) {
                    $start = Coordinate::stringFromColumnIndex(2 + 3 * $i);
                    $end   = Coordinate::stringFromColumnIndex(4 + 3 * $i);
                    $sheet->mergeCells("{$start}1:{$end}1");
                }

                // صفّا الرأس — ذهبي
                $sheet->getStyle("A1:{$lastCol}2")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C9A847']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);

                // عمود المحافظة (بيانات + إجمالي)
                $sheet->getStyle("A3:A{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '555555']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F0E0']],
                ]);

                // صف الإجمالي الأخير
                $sheet->getStyle("A{$lastRow}:{$lastCol}{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EDE3C2']],
                ]);

                $sheet->getStyle("B3:{$lastCol}{$lastRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getStyle("A1:{$lastCol}{$lastRow}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E0E0E0');

                $sheet->freezePane('B3');
                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getRowDimension(2)->setRowHeight(22);
            },
        ];
    }
}
