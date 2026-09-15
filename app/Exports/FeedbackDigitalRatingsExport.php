<?php

namespace App\Exports;

use App\Models\FeedbackDigitalRating;
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
 * آراء المنصات الرقمية — صف لكل رأي وعمود لكل سؤال.
 *
 * رأس العمود **برقم السؤال ثم اسمه** («٢٠٢ — منصة الحجز») فيطابق الورقة ودليل الترميز.
 * ⚠️ **خلية السؤال الذي لم يُسأل فارغة** لا «—» ولا «لا»: الفراغ في هذا الملف معناه
 * «لم يُسأل» (مَن جاء بلا حجز لم يُسأل عن مشاكل الحجز)، وعلى مَن يحلّله ألّا يعدّه صفراً.
 *
 * ⚠️ الاستعلام يأتي جاهزاً ومرتَّباً من الشاشة (DigitalRatingsQuery) — لا يُعاد بناؤه هنا.
 */
class FeedbackDigitalRatingsExport implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithTitle
{
    use FormatsFeedbackSheet;

    public function __construct(
        protected Builder $query,
        protected bool $includePersonal = false,
    ) {}

    public function title(): string
    {
        return mb_substr(__('home.fr_digital'), 0, 31);
    }

    public function query()
    {
        return $this->query->with(['office:id,name', 'governorate:id,name', 'choices']);
    }

    public function headings(): array
    {
        $questions = [];
        foreach (array_keys(FeedbackDigitalRating::QUESTIONS) as $key) {
            $questions[] = ltrim($key, 'q').' — '.__('home.fr_dg_'.$key);
        }

        return array_merge(
            [__('home.fr_date'), __('home.fr_governorate'), __('home.fr_office'), __('home.fr_identity')],
            $this->includePersonal ? [__('home.fr_name'), __('home.fr_national_id'), __('home.fr_phone')] : [],
            $questions,
        );
    }

    /** @param  FeedbackDigitalRating  $row */
    public function map($row): array
    {
        $answers = [];
        foreach (FeedbackDigitalRating::QUESTIONS as $key => [$type]) {
            // الدرجة رقماً قابلاً للحساب في Excel، والباقي بمسمّاه
            $answers[] = $type === 'scale' ? $row->{$key} : $row->answerLabel($key);
        }

        return array_merge(
            [
                LocalTime::date($row->created_at),
                $row->governorate?->name ?? '—',
                $row->office?->name ?? __('home.fr_deleted_office'),
                __('home.fr_identity_'.($row->isAnonymous() ? 'anonymous' : 'identified')),
            ],
            $this->includePersonal ? [$row->name, $row->national_id, $row->phone] : [],
            $answers,
        );
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = Coordinate::stringFromColumnIndex(count($this->headings()));
                $this->styleSheet($sheet, $lastCol);
            },
        ];
    }
}
