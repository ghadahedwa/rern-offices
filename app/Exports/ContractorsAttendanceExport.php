<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * ملفُّ تقارير الحضور الثلاثة — **واحدٌ لا ثلاثة**: الصفوف تصل مبنيّةً من
 * `AttendanceReport`، والملف لا يحسب رقماً بنفسه فلا يخالف الشاشة.
 *
 * ⚠️ **رأس العمود يشرح نفسه بلا حاشية**: «عدد العاملين» لا «العدد»، و«أيام العمل»
 *    و«حضر» و«غير مراجَع» صريحة — المعدود يختلف من تقريرٍ لآخر.
 * ⚠️ **صفّا الفترة وتفكيك أيام العمل فوق الجدول** — رقمٌ بلا مداه لا يُراجَع.
 */
class ContractorsAttendanceExport implements FromArray, ShouldAutoSize, WithEvents, WithStrictNullComparison, WithTitle
{
    /**
     * عدد الصفوف التمهيدية فوق رأس الجدول: العنوان · الفترة · التفكيك.
     *
     * ⚠️ **لا صفَّ فاصلاً فارغاً**: الكاتب يُسقط الصفّ الفارغ فيزيح رأس الجدول عمّا
     *    يحسبه التنسيق، فتُصبغ صفوف البيانات بلون الرأس. والفصل بارتفاع الصفّ.
     */
    private const LEAD_ROWS = 3;

    /**
     * @param  array<int|string,array<string,mixed>>  $rows  صفوف مبنيّة (مجمَّعة أو مفرَدة)
     * @param  Collection  $statuses  أعمدة الحالات بترتيب جدول الحالات
     * @param  string  $subjectKey  مفتاح العمود الأول في الصفّ (governorate_name · office_name · contractor_name)
     */
    public function __construct(
        protected array $rows,
        protected Collection $statuses,
        protected string $subjectLabel,
        protected string $subjectKey,
        protected bool $withContractorCount,
        protected string $title,
        protected string $period,
        protected array $breakdown = [],
        protected ?string $secondLabel = null,
        protected ?string $secondKey = null,
    ) {}

    public function title(): string
    {
        return mb_substr($this->title, 0, 31);
    }

    public function array(): array
    {
        $out = [
            [$this->title],
            [$this->period],
            [$this->breakdownLine()],
            $this->headings(),
        ];

        foreach ($this->rows as $row) {
            $line = [$this->subjectOf($row)];

            if ($this->secondKey !== null) {
                $line[] = $row[$this->secondKey] ?? '—';
            }

            if ($this->withContractorCount) {
                $line[] = $row['contractors'] ?? 1;
            }

            $line[] = $row['working'];
            $line[] = $row['present'];
            $line[] = $row['unreviewed'];

            foreach ($this->statuses as $status) {
                $line[] = $row['exceptions'][$status->id] ?? 0;
            }

            $out[] = $line;
        }

        $out[] = $this->totalsLine();

        return $out;
    }

    public function headings(): array
    {
        $head = [$this->subjectLabel];

        if ($this->secondLabel !== null) {
            $head[] = $this->secondLabel;
        }

        if ($this->withContractorCount) {
            $head[] = __('home.ct_rep_col_contractors');
        }

        $head[] = __('home.ct_rep_col_working');
        $head[] = __('home.ct_rep_col_present');
        $head[] = __('home.ct_rep_col_unreviewed');

        foreach ($this->statuses as $status) {
            $head[] = $status->name;
        }

        return $head;
    }

    private function subjectOf(array $row): string
    {
        return (string) ($row[$this->subjectKey] ?? $row['key'] ?? '—');
    }

    private function breakdownLine(): string
    {
        if ($this->breakdown === []) {
            return '';
        }

        return __('home.ct_rep_breakdown', [
            'total'    => $this->breakdown['total'],
            'weekend'  => $this->breakdown['weekend'],
            'holidays' => $this->breakdown['holidays'],
            'working'  => $this->breakdown['working'],
        ]);
    }

    /** صفّ الإجمالي — يُحسب من الصفوف نفسها فلا يفترق عن الجدول. */
    private function totalsLine(): array
    {
        $line = [__('home.ct_rep_total')];

        if ($this->secondKey !== null) {
            $line[] = '';
        }

        if ($this->withContractorCount) {
            // العامل الواحد في محافظتين يُعدّ مرة في كلٍّ منهما ومرة في الإجمالي.
            $ids = [];

            foreach ($this->rows as $row) {
                foreach ($row['contractor_ids'] ?? [] as $id) {
                    $ids[$id] = true;
                }
            }

            $line[] = count($ids);
        }

        foreach (['working', 'present', 'unreviewed'] as $key) {
            $line[] = array_sum(array_column($this->rows, $key));
        }

        foreach ($this->statuses as $status) {
            $line[] = array_sum(array_map(fn ($row) => $row['exceptions'][$status->id] ?? 0, $this->rows));
        }

        return $line;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet    = $event->sheet->getDelegate();
                $colCount = count($this->headings());
                $lastCol  = Coordinate::stringFromColumnIndex($colCount);
                $lastRow  = $sheet->getHighestRow();
                $headRow  = self::LEAD_ROWS + 1;

                $sheet->setRightToLeft(true);

                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->mergeCells("A3:{$lastCol}3");

                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("A2:A3")->applyFromArray([
                    'font'      => ['size' => 10, 'color' => ['rgb' => '666666']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle("A{$headRow}:{$lastCol}{$headRow}")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C9A847']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);

                if ($lastRow > $headRow) {
                    $firstData = $headRow + 1;

                    $sheet->getStyle("A{$firstData}:{$lastCol}{$lastRow}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                    $sheet->getStyle("A{$firstData}:A{$lastRow}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setWrapText(true);

                    // صفّ الإجمالي — آخر صفّ دائماً.
                    $sheet->getStyle("A{$lastRow}:{$lastCol}{$lastRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F0E0']],
                    ]);

                    $sheet->getStyle("A{$headRow}:{$lastCol}{$lastRow}")->getBorders()
                        ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E0E0E0');
                }

                $sheet->getRowDimension($headRow)->setRowHeight(26);
                $sheet->freezePane('A'.($headRow + 1));
            },
        ];
    }
}
