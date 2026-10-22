<?php

namespace App\Exports;

use App\Reports\VehicleStatisticsBreakdown;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * إحصائيات السيارات التفصيلية — ورقتان من `VehicleStatisticsBreakdown` نفسه الذي تقرأ منه الشاشة:
 *   ١) الجدول كما في الشاشة.
 *   ٢) البيانات طويلةً (صفٌّ لكل رقمٍ مسجَّل) — الشكل الصالح لجدول محوري، فيصنع المستخدم أي تقسيمة أخرى.
 */
class VehicleStatisticsBreakdownExport implements WithMultipleSheets
{
    public function __construct(protected VehicleStatisticsBreakdown $report) {}

    public function sheets(): array
    {
        return [
            new VehicleStatisticsBreakdownTableSheet($this->report),
            new VehicleStatisticsBreakdownDataSheet($this->report),
        ];
    }
}
