<?php

namespace App\Exports;

use App\Models\FeedbackRejectedAttempt;
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

/**
 * المحاولات المرفوضة — قيمتها تشغيلية (متابعة إساءة الاستخدام) لا تقريرية.
 * ⚠️ الاستعلام يأتي جاهزاً من الشاشة (RejectedAttemptsQuery).
 */
class FeedbackRejectedExport implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithTitle
{
    use FormatsFeedbackSheet;

    public function __construct(
        protected Builder $query,
        protected bool $includePersonal = false,
    ) {}

    public function title(): string
    {
        return __('home.fr_rejected');
    }

    /** الترتيب يأتي مطبَّقاً من المكوّن — راجع التعليق في FeedbackRatingsExport. */
    public function query()
    {
        return $this->query->with('office:id,name,governorate_id');
    }

    public function headings(): array
    {
        return array_merge(
            [__('home.fr_date'), __('home.fr_type'), __('home.fr_reason'), __('home.fr_office')],
            $this->includePersonal ? [__('home.fr_national_id'), __('home.fr_phone')] : [],
            [__('home.fr_ip')],
        );
    }

    /** @param  FeedbackRejectedAttempt  $attempt */
    public function map($attempt): array
    {
        return array_merge(
            [
                LocalTime::date($attempt->created_at),
                __('home.fr_type_'.$attempt->type),
                __('home.fr_reason_'.$attempt->reason),
                $attempt->office?->name ?? __('home.fr_deleted_office'),
            ],
            $this->includePersonal ? [$attempt->national_id, $attempt->phone] : [],
            [$attempt->ip_address],
        );
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->styleSheet(
                    $event->sheet->getDelegate(),
                    Coordinate::stringFromColumnIndex(count($this->headings())),
                );
            },
        ];
    }
}
