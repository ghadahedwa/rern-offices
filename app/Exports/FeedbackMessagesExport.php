<?php

namespace App\Exports;

use App\Models\FeedbackMessage;
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
 * رسائل نوعٍ بعينه — صف لكل رسالة. الأعمدة من تعريف النوع: عمود الهوية حين تكون
 * اختيارية، وعمود الموظف حين يطلبه النوع.
 *
 * ⚠️ الاستعلام يأتي جاهزاً مرتَّباً من الشاشة (MessagesQuery) — لا يُعاد بناؤه هنا.
 */
class FeedbackMessagesExport implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithTitle
{
    use FormatsFeedbackSheet;

    private readonly array $config;

    public function __construct(
        protected Builder $query,
        protected string $type,
        protected bool $includePersonal = false,
    ) {
        $this->config = FeedbackMessage::config($type);
    }

    public function title(): string
    {
        return mb_substr(__('home.fr_msg_'.$this->type), 0, 31);
    }

    public function query()
    {
        return $this->query->with(['office:id,name', 'governorate:id,name']);
    }

    private function hasIdentityColumn(): bool
    {
        return $this->config['identity'] !== 'required';
    }

    public function headings(): array
    {
        return array_merge(
            [__('home.fr_date'), __('home.fr_governorate'), __('home.fr_office')],
            $this->hasIdentityColumn() ? [__('home.fr_identity')] : [],
            $this->includePersonal ? [__('home.fr_name'), __('home.fr_national_id'), __('home.fr_phone')] : [],
            $this->config['employee'] ? [__('home.fr_msg_employee')] : [],
            [__('home.fr_msg_body')],
        );
    }

    /** @param  FeedbackMessage  $msg */
    public function map($msg): array
    {
        return array_merge(
            [
                LocalTime::date($msg->created_at),
                $msg->governorate?->name ?? '—',
                $msg->office?->name ?? ($msg->office_id ? __('home.fr_deleted_office') : '—'),
            ],
            $this->hasIdentityColumn()
                ? [__('home.fr_identity_'.($msg->isAnonymous() ? 'anonymous' : 'identified'))]
                : [],
            $this->includePersonal ? [$msg->name, $msg->national_id, $msg->phone] : [],
            $this->config['employee'] ? [$msg->employee_name] : [],
            [$msg->body],
        );
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = Coordinate::stringFromColumnIndex(count($this->headings()));

                $this->styleSheet($sheet, $lastCol);

                // نص الرسالة طويل: عرض ثابت مع لفّ السطر
                $sheet->getColumnDimension($lastCol)->setAutoSize(false)->setWidth(70);
                $sheet->getStyle($lastCol.'2:'.$lastCol.$sheet->getHighestRow())
                    ->getAlignment()->setWrapText(true);
            },
        ];
    }
}
