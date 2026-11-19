<?php

namespace App\Exports;

use App\Support\FeedbackResults\OpinionsQuery;
use App\Support\LocalTime;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * آراء المواطنين — صف لكل تقييم أو مقترح. الصفوف تأتي جاهزة من الشاشة
 * (OpinionsQuery بفلاترها وترتيبها) ولا يُعاد بناؤها هنا.
 */
class FeedbackOpinionsExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings, WithStrictNullComparison, WithTitle
{
    use FormatsFeedbackSheet;

    public function __construct(
        protected Collection $rows,
        protected bool $includePersonal = false,
    ) {}

    public function title(): string
    {
        return __('home.fr_opinions');
    }

    public function headings(): array
    {
        return array_merge(
            [__('home.fr_date'), __('home.fr_type'), __('home.fr_governorate'), __('home.fr_office'), __('home.fr_identity')],
            $this->includePersonal ? [__('home.fr_name'), __('home.fr_national_id'), __('home.fr_phone')] : [],
            [__('home.fr_overall_rating'), __('home.fr_topics'), __('home.fr_opinion_text')],
        );
    }

    public function array(): array
    {
        $topics = OpinionsQuery::topicsFor($this->rows);

        return $this->rows->map(fn ($row) => array_merge(
            [
                LocalTime::date($row->created_at),
                __('home.fr_type_'.$row->kind),
                $row->governorate_name ?? '—',
                $row->office_name ?? __('home.fr_deleted_office'),
                __('home.fr_identity_'.(OpinionsQuery::isAnonymous($row) ? 'anonymous' : 'identified')),
            ],
            $this->includePersonal ? [$row->name, $row->national_id, $row->phone] : [],
            [
                $row->overall_rating !== null ? (int) $row->overall_rating : null,
                $row->kind === 'suggestion' ? ($topics[$row->id] ?? null) : null,
                $row->opinion,
            ],
        ))->all();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = Coordinate::stringFromColumnIndex(count($this->headings()));
                $lastRow = $sheet->getHighestRow();

                $this->styleSheet($sheet, $lastCol);

                // العناوين والرأي نصّان طويلان: عرض ثابت ولفّ بدل تمدّد بلا حدّ
                $topicsCol = Coordinate::stringFromColumnIndex(count($this->headings()) - 1);
                foreach ([$topicsCol => 40, $lastCol => 60] as $col => $width) {
                    $sheet->getColumnDimension($col)->setAutoSize(false)->setWidth($width);
                    $sheet->getStyle($col.'2:'.$col.$lastRow)->getAlignment()->setWrapText(true);
                }
            },
        ];
    }
}
