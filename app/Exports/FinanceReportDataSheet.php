<?php

namespace App\Exports;

use App\Support\Finance\FinanceReport;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/** ورقة البيانات الطويلة — صفٌّ لكل (وحدة × حساب × شهر) مسجَّل: الشكل الصالح لجدول محوري */
class FinanceReportDataSheet implements FromArray, WithHeadings, WithEvents, WithTitle, ShouldAutoSize, WithStrictNullComparison
{
    public function __construct(protected FinanceReport $report) {}

    public function title(): string
    {
        return __('home.fin_rep_sheet_data');
    }

    public function headings(): array
    {
        return [
            __('home.fin_unit'), __('home.fin_rep_account'), __('home.fin_month'),
            __('home.fin_rep_amount_col'), __('home.fin_income'), __('home.fin_rep_balance'), __('home.fin_rep_orders'),
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
                $lastRow = max($sheet->getHighestRow(), 1);
                $sheet->setRightToLeft(true);

                $sheet->getStyle('A1:G1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C9A847']],
                ]);
                $sheet->getStyle("D2:F{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->setAutoFilter("A1:G{$lastRow}");
                $sheet->freezePane('A2');
            },
        ];
    }
}
