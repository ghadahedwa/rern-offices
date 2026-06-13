<?php

namespace App\Exports;

use App\Models\Office;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class OfficesExport implements FromCollection, WithHeadings, WithMapping, WithEvents, WithTitle, ShouldAutoSize
{
    private const DASH = '—';

    /** عناوين الأقسام => عدد الأعمدة تحت كل قسم (بالترتيب) */
    private array $groups = [
        'البيانات الأساسية'   => 14,
        'أوقات وأنظمة العمل'  => 6,
        'الخدمات والتجهيزات'  => 6,
        'الأنظمة التقنية'     => 3,
        'عدد الأجهزة'         => 8,
        'العدادات'            => 4,
        'التقييم والجودة'     => 4,
        'الأجهزة المعطلة'     => 1,
        'ملاحظات المقر'       => 3,
    ];

    private array $workingDaysLabels = [
        'full_week' => 'أسبوع كامل', 'one_day' => 'يوم واحد', 'two_days' => 'يومان',
        'three_days' => 'ثلاثة أيام', 'four_days' => 'أربعة أيام', 'five_days' => 'خمسة أيام',
    ];
    private array $brailleLabels   = ['available' => 'متوفر', 'not_available' => 'غير متوفر'];
    private array $queueLabels     = ['working' => 'يعمل', 'not_working' => 'لا يعمل', 'not_available' => 'غير متوفر'];
    private array $cameraLabels    = ['available' => 'تعمل', 'not_available' => 'غير متوفرة', 'broken' => 'معطلة'];
    private array $meterTypeLabels = ['prepaid' => 'كارت', 'invoice' => 'فاتورة', 'entity_meter' => 'عداد جهة'];
    private array $meterDebtLabels = ['yes' => 'يوجد', 'no' => 'لا يوجد'];

    public function __construct(protected Collection $offices) {}

    public function title(): string
    {
        return 'المقرات';
    }

    public function collection(): Collection
    {
        return $this->offices;
    }

    public function headings(): array
    {
        return [
            // البيانات الأساسية (14)
            'اسم المقر', 'المحافظة', 'المستشار المشرف', 'نوع المقر', 'وصف الموقع', 'تاريخ الإنشاء',
            'المحكمة الابتدائية', 'العنوان', 'المساحة (م²)', 'وصف الطوابق', 'الوضع التعاقدي', 'الحالة الإنشائية',
            'رابط الخريطة', 'آخر زيارة',
            // أوقات وأنظمة العمل (6)
            'نظام العمل', 'ساعات العمل', 'أيام العمل', 'نوع الاتصال', 'تاريخ الميكنة', 'عدد الشبابيك',
            // الخدمات والتجهيزات (6)
            'الميكروفيلم', 'تجهيزات ذوي الهمم', 'الحماية المدنية', 'تصوير المستندات', 'البوفيه', 'عقد النظافة',
            // الأنظمة التقنية (3)
            'لوحة برايل', 'إدارة الطوابير', 'كاميرات المراقبة',
            // عدد الأجهزة (8)
            'كمبيوتر', 'شاشات العرض', 'طابعات', 'ماسحات', 'بصمة', 'ماكينات دفع', 'مكيفات', 'UPS',
            // العدادات (4)
            'عداد الكهرباء', 'مديونية الكهرباء', 'عداد المياه', 'مديونية المياه',
            // التقييم والجودة (4)
            'تقييم النظافة', 'تقييم الأرشيف', 'الالتزام بالمواعيد', 'معاملة المواطنين',
            // الأجهزة المعطلة (1)
            'الأجهزة المعطلة',
            // ملاحظات المقر (3)
            'احتياجات المقر', 'السلبيات والحلول', 'مقترحات التطوير',
        ];
    }

    /** @param Office $office */
    public function map($office): array
    {
        $d = self::DASH;

        $broken = $office->brokenDevices
            ->map(fn ($bd) => ($bd->deviceType->name ?? $d) . ': ' . $bd->count)
            ->implode('، ');

        return [
            // البيانات الأساسية
            $office->name,
            $office->governorate->name ?? $d,
            $office->governorate->supervising_counselor ?? $d,
            $office->officeType->name ?? $d,
            $office->locationDescription->name ?? $d,
            $office->established_at?->format('Y-m-d') ?? $d,
            $office->district_court ?: $d,
            $office->address ?: $d,
            $office->office_area ?? $d,
            $office->floors_description ?: $d,
            $office->contractualStatus->name ?? $d,
            $office->structuralCondition->name ?? $d,
            $office->google_maps_link ?: $d,
            $office->visited_at?->format('Y-m-d') ?? 'لم تُزر',
            // أوقات وأنظمة العمل
            $office->workSystem->name ?? $d,
            $office->workingHour->name ?? $d,
            $this->workingDaysLabels[$office->working_days] ?? $d,
            $office->connectionType->name ?? $d,
            $office->mechanization_at?->format('Y-m-d') ?? $d,
            $office->windows_count ?? $d,
            // الخدمات والتجهيزات
            $office->MicrofilmOption->name ?? $d,
            $office->DisabilitieAccess->name ?? $d,
            $office->FireSafety->name ?? $d,
            $office->DocumentPhotocopyingService->name ?? $d,
            $office->BuffetService->name ?? $d,
            $office->CleanlinessContract->name ?? $d,
            // الأنظمة التقنية
            $this->brailleLabels[$office->Braille_sign_device] ?? $d,
            $this->queueLabels[$office->queue_management_system] ?? $d,
            $this->cameraLabels[$office->surveillance_cameras] ?? $d,
            // عدد الأجهزة
            $office->computers_count ?? 0,
            $office->monitors_count ?? 0,
            $office->printers_count ?? 0,
            $office->scanners_count ?? 0,
            $office->fingerprints_count ?? 0,
            $office->payment_machine_count ?? 0,
            $office->air_conditioners_count ?? 0,
            $office->ups_count ?? 0,
            // العدادات
            $this->meterTypeLabels[$office->electricity_meter_type] ?? $d,
            $this->meterDebtLabels[$office->electricity_meter_debt] ?? $d,
            $this->meterTypeLabels[$office->water_meter_type] ?? $d,
            $this->meterDebtLabels[$office->water_meter_debt] ?? $d,
            // التقييم والجودة
            Office::CLEANLINESS_RATINGS[$office->cleanliness_rating] ?? $d,
            Office::ARCHIVE_RATINGS[$office->archive_rating] ?? $d,
            Office::COMMITMENT_RATINGS[$office->work_schedule_commitment] ?? $d,
            Office::COMMITMENT_RATINGS[$office->citizen_treatment_commitment] ?? $d,
            // الأجهزة المعطلة
            $broken !== '' ? $broken : 'لا يوجد',
            // ملاحظات المقر
            $office->office_needs ?: $d,
            $office->negatives_and_solutions ?: $d,
            $office->development_proposals ?: $d,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet    = $event->sheet->getDelegate();
                $colCount = array_sum($this->groups);
                $lastCol  = Coordinate::stringFromColumnIndex($colCount);
                $sheet->setRightToLeft(true);

                // صف عناوين الأقسام فوق صف رؤوس الأعمدة
                $sheet->insertNewRowBefore(1, 1);
                $start = 1;
                foreach ($this->groups as $label => $span) {
                    $from = Coordinate::stringFromColumnIndex($start);
                    $to   = Coordinate::stringFromColumnIndex($start + $span - 1);
                    $sheet->mergeCells("{$from}1:{$to}1");
                    $sheet->setCellValue("{$from}1", $label);
                    $start += $span;
                }

                // تنسيق صف الأقسام (1) — ذهبي
                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C9A847']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // تنسيق صف رؤوس الأعمدة (2)
                $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => '555555']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F0E0']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);

                $lastRow = $sheet->getHighestRow();

                // حدود لكل الخلايا
                $sheet->getStyle("A1:{$lastCol}{$lastRow}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E0E0E0');

                // محاذاة عمودية للبيانات
                $sheet->getStyle("A3:{$lastCol}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                // تظليل متناوب للصفوف
                for ($row = 3; $row <= $lastRow; $row++) {
                    if ($row % 2 === 1) {
                        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FAFAFA');
                    }
                }

                // تجميد أول عمود (الاسم) + صفّي الرؤوس + فلتر تلقائي
                $sheet->freezePane('B3');
                $sheet->setAutoFilter("A2:{$lastCol}2");

                $sheet->getRowDimension(1)->setRowHeight(22);
                $sheet->getRowDimension(2)->setRowHeight(28);
            },
        ];
    }
}
