<?php

namespace App\Exports;

use App\Reports\VehicleStatisticsBreakdown;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/** ورقة البيانات الطويلة — صفٌّ لكل رقمٍ مسجَّل: محافظة · سيارة · مؤشر · سنة · شهر · قيمة */
class VehicleStatisticsBreakdownDataSheet implements FromArray, WithHeadings, WithEvents, WithTitle, ShouldAutoSize, WithStrictNullComparison
{
    public function __construct(protected VehicleStatisticsBreakdown $report) {}

    public function title(): string
    {
        return __('home.vbd_sheet_data');
    }

    public function headings(): array
    {
        return [
            __('home.vbd_level_governorate'), __('home.vbd_level_vehicle'), __('home.vbd_metric'),
            __('home.vbd_col_year'), __('home.vbd_col_month'), __('home.vbd_col_value'),
        ];
    }

    public function array(): array
    {
        return $this->report->dataRows();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $sheet->setRightToLeft(true);

                $sheet->getStyle('A1:F1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C9A847']],
                ]);
                $sheet->getStyle("F2:F{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->setAutoFilter("A1:F{$lastRow}");
                $sheet->freezePane('A2');
            },
        ];
    }
}
