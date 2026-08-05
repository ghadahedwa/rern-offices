<?php

namespace App\Exports;

use App\Models\FeedbackRating;
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
 * تقييمات المواطنين — صف لكل تقييم بكل الأعمدة.
 *
 * قيمة هذا الملف أن المحاور الستة والملاحظات (المخفية في صف التفاصيل على الشاشة)
 * تظهر هنا كأعمدة قابلة للفرز والتحليل.
 *
 * ⚠️ الاستعلام يأتي جاهزاً من الشاشة (RatingsQuery) — لا يُعاد بناؤه هنا،
 * وإلا خرج الملف بصفوف تخالف ما يراه المستخدم.
 */
class FeedbackRatingsExport implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithTitle
{
    use FormatsFeedbackSheet;

    public function __construct(
        protected Builder $query,
        protected bool $includePersonal = false,
    ) {}

    public function title(): string
    {
        return __('home.fr_ratings');
    }

    /**
     * الترتيب يأتي مطبَّقاً من المكوّن (نفس ترتيب الشاشة) ولا يُضاف هنا.
     * ⚠️ لا تترك الاستعلام بلا ترتيب: FromQuery يقطّع الصفوف بـ skip/take،
     * وبلا ترتيب ثابت قد يتكرّر صف ويسقط آخر بين القطع.
     */
    public function query()
    {
        return $this->query->with(['office:id,name', 'governorate:id,name']);
    }

    public function headings(): array
    {
        return array_merge(
            [__('home.fr_date'), __('home.fr_governorate'), __('home.fr_office')],
            $this->includePersonal
                ? [__('home.fr_name'), __('home.fr_national_id'), __('home.fr_phone')]
                : [],
            [__('home.fr_wait_time')],
            array_map(fn ($c) => $c[0], array_values(FeedbackRating::CRITERIA)),
            [__('home.fr_criteria_avg'), __('home.fr_overall_rating'), __('home.fr_notes')],
        );
    }

    /** @param  FeedbackRating  $rating */
    public function map($rating): array
    {
        $criteria = [];
        foreach (array_keys(FeedbackRating::CRITERIA) as $field) {
            // المحور الاختياري غير المُجاب يبقى فارغاً — الصفر يبوّظ أي متوسط يُحسب لاحقاً
            $criteria[] = $rating->{$field} !== null ? (int) $rating->{$field} : null;
        }

        return array_merge(
            [
                LocalTime::date($rating->created_at),
                $rating->governorate?->name ?? '—',
                $rating->office?->name ?? __('home.fr_deleted_office'),
            ],
            $this->includePersonal ? [$rating->name, $rating->national_id, $rating->phone] : [],
            [FeedbackRating::WAIT_TIMES[$rating->wait_time] ?? $rating->wait_time],
            $criteria,
            [
                $rating->criteriaAverage(),
                $rating->overall_rating !== null ? (int) $rating->overall_rating : null,
                $rating->notes,
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

                // عمود الملاحظات نص طويل: عرض ثابت مع لفّ السطر بدل تمدّد لا نهائي
                $sheet->getColumnDimension($lastCol)->setAutoSize(false)->setWidth(60);
                $sheet->getStyle($lastCol.'2:'.$lastCol.$sheet->getHighestRow())
                    ->getAlignment()->setWrapText(true);
            },
        ];
    }
}
