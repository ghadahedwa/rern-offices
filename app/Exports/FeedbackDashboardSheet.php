<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * ورقة جاهزة (عنوان + رؤوس + صفوف) — أقسام لوحة النتائج كلها بنفس الشكل،
 * فلا داعي لكلاس لكل قسم.
 */
class FeedbackDashboardSheet implements FromArray, ShouldAutoSize, WithEvents, WithHeadings, WithTitle
{
    use FormatsFeedbackSheet;

    public function __construct(
        protected string $title,
        protected array $headings,
        protected array $rows,
        protected bool $summary = false,
    ) {}

    public function title(): string
    {
        // Excel يرفض > ٣١ محرفاً في اسم الورقة، ويرفض : \ / ? * [ ]
        return mb_substr(str_replace([':', '\\', '/', '?', '*', '[', ']'], '', $this->title), 0, 31);
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
                $lastCol = Coordinate::stringFromColumnIndex(max(1, count($this->headings)));

                $this->summary
                    ? $this->styleSummarySheet($sheet, $lastCol)
                    : $this->styleSheet($sheet, $lastCol);
            },
        ];
    }
}
