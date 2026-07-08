<?php

namespace App\Exports;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * تقرير تراخيص السيارات: المحافظة · السيارة · رقم اللوحة · تاريخ انتهاء الترخيص · المتبقّي (أيام) · الحالة.
 */
class VehicleLicensesExport implements FromArray, WithHeadings, WithEvents, WithTitle, ShouldAutoSize
{
    private const DASH = '—';

    /**
     * @param  Collection  $rows      سيارات (governorate, name, license_plate, license_expiry_date)
     * @param  int          $soonDays  عتبة "تنتهي قريباً" بالأيام
     */
    public function __construct(protected Collection $rows, protected int $soonDays = 30) {}

    public function title(): string
    {
        return 'تراخيص السيارات';
    }

    public function headings(): array
    {
        return ['المحافظة', 'السيارة', 'رقم اللوحة', 'تاريخ انتهاء الترخيص', 'المتبقّي (أيام)', 'الحالة'];
    }

    public function array(): array
    {
        $out = [];
        foreach ($this->rows as $v) {
            [$remaining, $status] = self::licenseInfo($v->license_expiry_date, $this->soonDays);

            $out[] = [
                $v->governorate->name ?? self::DASH,
                $v->name,
                $v->license_plate ?: self::DASH,
                $v->license_expiry_date?->format('Y-m-d') ?? self::DASH,
                $remaining,
                $status,
            ];
        }

        return $out;
    }

    /**
     * @return array{0:string,1:string}  [المتبقّي (نص), حالة الترخيص]
     */
    public static function licenseInfo(?CarbonInterface $expiry, int $soonDays = 30): array
    {
        if ($expiry === null) {
            return [self::DASH, 'غير مسجّل'];
        }

        $days = (int) now()->startOfDay()->diffInDays($expiry->copy()->startOfDay(), false);

        if ($days < 0) {
            return ['منذ ' . abs($days) . ' يوم', 'منتهية'];
        }

        if ($days <= $soonDays) {
            return [$days . ' يوم', 'تنتهي قريباً'];
        }

        return [$days . ' يوم', 'سارية'];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = 'F';
                $lastRow = $sheet->getHighestRow();
                $sheet->setRightToLeft(true);

                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C9A847']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);

                if ($lastRow >= 2) {
                    $sheet->getStyle("A2:B{$lastRow}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '555555']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F0E0']],
                    ]);
                    $sheet->getStyle("C2:{$lastCol}{$lastRow}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                }

                $sheet->getStyle("A1:{$lastCol}{$lastRow}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E0E0E0');

                $sheet->freezePane('A2');
                $sheet->getRowDimension(1)->setRowHeight(26);
            },
        ];
    }
}
