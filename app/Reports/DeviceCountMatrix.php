<?php

namespace App\Reports;

use App\Livewire\Reports\DeviceCount;
use App\Models\DeviceType;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\OfficeBrokenDevice;

/**
 * باني المصفوفة لبيان الأجهزة العددي — مشترك بين المكوّن وكنترولر الـ PDF.
 *
 * الأعمدة ديناميكية:
 *   - الأجهزة الشغالة: الأعمدة الثمانية المختارة (أو الكل) — مجموع لكل محافظة.
 *   - الأجهزة المعطلة: أنواع `device_types` المختارة (أو الكل) — مجموع count لكل نوع لكل محافظة.
 */
class DeviceCountMatrix
{
    /**
     * @param  array  $f  الفلاتر: governorateIds, workingDevices (مفاتيح), brokenTypeIds
     * @return array{governorates:\Illuminate\Support\Collection, workingCols:array, brokenTypes:\Illuminate\Support\Collection, sums:array, brokenSums:array}
     */
    public static function build(?array $allowedGovIds, array $f): array
    {
        $govIds      = $f['governorateIds'] ?? [];
        $workingSel  = $f['workingDevices'] ?? [];
        $brokenSel   = $f['brokenTypeIds'] ?? [];
        $showWorking = $f['showWorking'] ?? true;
        $showBroken  = $f['showBroken'] ?? true;

        // الأعمدة الشغالة المعروضة (المختارة أو الكل) — بترتيب التعريف — فقط لو مفعّل عرضها
        $workingCols = ! $showWorking
            ? []
            : ($workingSel
                ? array_filter(DeviceCount::DEVICES, fn ($k) => in_array($k, $workingSel, true), ARRAY_FILTER_USE_KEY)
                : DeviceCount::DEVICES);

        // أنواع المعطلة المعروضة (المختارة أو الكل) — فقط لو مفعّل عرضها
        $brokenTypes = $showBroken
            ? DeviceType::query()
                ->when($brokenSel, fn ($q) => $q->whereIn('id', $brokenSel))
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        $governorates = Governorate::query()
            ->when($allowedGovIds, fn ($q) => $q->whereIn('id', $allowedGovIds))
            ->when($govIds, fn ($q) => $q->whereIn('id', $govIds))
            ->orderBy('order')->orderBy('id')
            ->get(['id', 'name']);

        // مجاميع الأجهزة الشغالة (الأعمدة المختارة فقط)
        $sums = [];
        if (! empty($workingCols)) {
            $selects = ['governorate_id'];
            foreach (array_keys($workingCols) as $col) {
                $selects[] = "COALESCE(SUM({$col}),0) as {$col}";
            }

            $deviceRows = Office::query()
                ->when($allowedGovIds, fn ($q) => $q->whereIn('governorate_id', $allowedGovIds))
                ->when($govIds, fn ($q) => $q->whereIn('governorate_id', $govIds))
                ->selectRaw(implode(', ', $selects))
                ->groupBy('governorate_id')
                ->get();

            foreach ($deviceRows as $row) {
                foreach (array_keys($workingCols) as $col) {
                    $sums[$row->governorate_id][$col] = (int) $row->$col;
                }
            }
        }

        // مجاميع المعطلة لكل (محافظة، نوع)
        $brokenSums = [];
        if ($brokenTypes->isNotEmpty()) {
            $brokenRows = OfficeBrokenDevice::query()
                ->join('offices', 'offices.id', '=', 'office_broken_devices.office_id')
                ->when($allowedGovIds, fn ($q) => $q->whereIn('offices.governorate_id', $allowedGovIds))
                ->when($govIds, fn ($q) => $q->whereIn('offices.governorate_id', $govIds))
                ->when($brokenSel, fn ($q) => $q->whereIn('office_broken_devices.device_type_id', $brokenSel))
                ->selectRaw('offices.governorate_id as gid, office_broken_devices.device_type_id as tid, COALESCE(SUM(office_broken_devices.count),0) as cnt')
                ->groupBy('offices.governorate_id', 'office_broken_devices.device_type_id')
                ->get();

            foreach ($brokenRows as $b) {
                $brokenSums[$b->gid][$b->tid] = (int) $b->cnt;
            }
        }

        return compact('governorates', 'workingCols', 'brokenTypes', 'sums', 'brokenSums');
    }
}
