<?php

namespace App\Exports;

use App\Models\FeedbackSuggestion;
use App\Support\LocalTime;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/** الورقة الأولى من ملف المقترحات: صف لكل مقترح. */
class FeedbackSuggestionsSheet implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithTitle
{
    use FormatsFeedbackSheet;

    public function __construct(
        protected Builder $query,
        protected bool $includePersonal = false,
    ) {}

    public function title(): string
    {
        return __('home.fr_suggestions');
    }

    /** الترتيب يأتي مطبَّقاً من المكوّن — راجع التعليق في FeedbackRatingsExport. */
    public function query()
    {
        return $this->query->with(['office:id,name', 'governorate:id,name', 'topics.domain']);
    }

    public function headings(): array
    {
        return array_merge(
            [__('home.fr_date'), __('home.fr_governorate'), __('home.fr_office')],
            $this->includePersonal
                ? [__('home.fr_name'), __('home.fr_national_id'), __('home.fr_phone')]
                : [],
            [__('home.fr_topics_count'), __('home.fr_topics'), __('home.fr_other_suggestion')],
        );
    }

    /** @param  FeedbackSuggestion  $suggestion */
    public function map($suggestion): array
    {
        return array_merge(
            [
                LocalTime::date($suggestion->created_at),
                $suggestion->governorate?->name ?? '—',
                $suggestion->office?->name ?? __('home.fr_deleted_office'),
            ],
            $this->includePersonal
                ? [$suggestion->name, $suggestion->national_id, $suggestion->phone]
                : [],
            [
                $suggestion->topics->count(),
                $suggestion->topics->pluck('name')->implode('، '),
                $suggestion->other_suggestion,
            ],
        );
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet    = $event->sheet->getDelegate();
                $colCount = count($this->headings());
                $lastCol  = Coordinate::stringFromColumnIndex($colCount);

                $this->styleSheet($sheet, $lastCol);

                // العناوين المجمّعة والاقتراح الحر نصوص طويلة
                foreach ([Coordinate::stringFromColumnIndex($colCount - 1), $lastCol] as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(false)->setWidth(50);
                    $sheet->getStyle($col.'2:'.$col.$sheet->getHighestRow())
                        ->getAlignment()->setWrapText(true);
                }
            },
        ];
    }
}
