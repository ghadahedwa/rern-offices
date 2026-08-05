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

/**
 * الورقة الثانية من ملف المقترحات: صف لكل (مقترح × عنوان).
 * هذا هو الشكل الوحيد الذي يصلح لجدول محوري — الخلية متعددة القيم لا تُحلَّل.
 */
class FeedbackSuggestionTopicsSheet implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithTitle
{
    use FormatsFeedbackSheet;

    public function __construct(protected Builder $query) {}

    public function title(): string
    {
        return __('home.fr_export_sheet_topics');
    }

    /** الترتيب يأتي مطبَّقاً من المكوّن — راجع التعليق في FeedbackRatingsExport. */
    public function query()
    {
        return $this->query->with(['office:id,name', 'governorate:id,name', 'topics.domain']);
    }

    public function headings(): array
    {
        return [
            __('home.fr_date'),
            __('home.fr_governorate'),
            __('home.fr_office'),
            __('home.fr_export_domain'),
            __('home.fr_export_topic'),
        ];
    }

    /**
     * صف واحد يتحوّل لعدة صفوف (عنوان لكل صف) — WithMapping يقبل مصفوفة صفوف.
     * المقترح بلا عناوين (اقتراح حر فقط) لا يظهر هنا؛ مكانه الورقة الأولى.
     *
     * @param  FeedbackSuggestion  $suggestion
     */
    public function map($suggestion): array
    {
        $base = [
            LocalTime::date($suggestion->created_at),
            $suggestion->governorate?->name ?? '—',
            $suggestion->office?->name ?? __('home.fr_deleted_office'),
        ];

        return $suggestion->topics
            ->map(fn ($topic) => array_merge($base, [$topic->domain?->name ?? '—', $topic->name]))
            ->all();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->styleSheet($event->sheet->getDelegate(), 'E');
            },
        ];
    }
}
