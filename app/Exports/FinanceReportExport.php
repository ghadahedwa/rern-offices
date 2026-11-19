<?php

namespace App\Exports;

use App\Support\Finance\FinanceReport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * تقرير الشئون المالية — ورقتان من `FinanceReport` نفسه الذي تقرأ منه الشاشة:
 *   ١) الجدول كما في الشاشة (والعناصر الثمانية تحته).
 *   ٢) البيانات طويلةً: صفٌّ لكل (وحدة × حساب × شهر) — الشكل الصالح لجدول محوري.
 */
class FinanceReportExport implements WithMultipleSheets
{
    public function __construct(protected FinanceReport $report) {}

    public function sheets(): array
    {
        return [
            new FinanceReportTableSheet($this->report),
            new FinanceReportDataSheet($this->report),
        ];
    }
}
