<?php

namespace App\Reports;

use App\Livewire\Reports\VehicleDeviceCount;
use App\Models\Governorate;
use App\Models\Vehicle;
use App\Models\VehicleBrokenDevice;
use App\Models\VehicleDeviceType;

/**
 * باني المصفوفة لبيان تجهيزات السيارات العددي — مشترك بين المكوّن وكنترولر الـ PDF.
 * نفس نمط DeviceCountMatrix بالحرف (لكن للسيارات: 5 أعمدة تجهيزات عاملة + المعطلة من vehicle_device_types).
 */
class VehicleDeviceCountMatrix
{
    /**
     * @param  array  $f  الفلاتر: governorateIds, workingDevices (مفاتيح), brokenTypeIds, showWorking, showBroken
     * @return array{governorates:\Illuminate\Support\Collection, workingCols:array, brokenTypes:\Illuminate\Support\Collection, sums:array, brokenSums:array}
     */
    public static function build(?array $allowedGovIds, array $f): array
    {
        $govIds      = $f['governorateIds'] ?? [];
        $workingSel  = $f['workingDevices'] ?? [];
        $brokenSel   = $f['brokenTypeIds'] ?? [];
        $showWorking = $f['showWorking'] ?? true;
        $showBroken  = $f['showBroken'] ?? true;

        // الأعمدة العاملة المعروضة (المختارة أو الكل) — بترتيب التعريف — فقط لو مفعّل عرضها
        $workingCols = ! $showWorking
            ? []
            : ($workingSel
                ? array_filter(VehicleDeviceCount::DEVICES, fn ($k) => in_array($k, $workingSel, true), ARRAY_FILTER_USE_KEY)
                : VehicleDeviceCount::DEVICES);

        // أنواع المعطلة المعروضة (المختارة أو الكل) — فقط لو مفعّل عرضها
        $brokenTypes = $showBroken
            ? VehicleDeviceType::query()
                ->when($brokenSel, fn ($q) => $q->whereIn('id', $brokenSel))
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        $governorates = Governorate::query()
            ->when($allowedGovIds, fn ($q) => $q->whereIn('id', $allowedGovIds))
            ->when($govIds, fn ($q) => $q->whereIn('id', $govIds))
            ->orderBy('order')->orderBy('id')
            ->get(['id', 'name']);

        // مجاميع التجهيزات العاملة (الأعمدة المختارة فقط)
        $sums = [];
        if (! empty($workingCols)) {
            $selects = ['governorate_id'];
            foreach (array_keys($workingCols) as $col) {
                $selects[] = "COALESCE(SUM({$col}),0) as {$col}";
            }

            $deviceRows = Vehicle::query()
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
            $brokenRows = VehicleBrokenDevice::query()
                ->join('vehicles', 'vehicles.id', '=', 'vehicle_broken_devices.vehicle_id')
                ->when($allowedGovIds, fn ($q) => $q->whereIn('vehicles.governorate_id', $allowedGovIds))
                ->when($govIds, fn ($q) => $q->whereIn('vehicles.governorate_id', $govIds))
                ->when($brokenSel, fn ($q) => $q->whereIn('vehicle_broken_devices.device_type_id', $brokenSel))
                ->selectRaw('vehicles.governorate_id as gid, vehicle_broken_devices.device_type_id as tid, COALESCE(SUM(vehicle_broken_devices.count),0) as cnt')
                ->groupBy('vehicles.governorate_id', 'vehicle_broken_devices.device_type_id')
                ->get();

            foreach ($brokenRows as $b) {
                $brokenSums[$b->gid][$b->tid] = (int) $b->cnt;
            }
        }

        return compact('governorates', 'workingCols', 'brokenTypes', 'sums', 'brokenSums');
    }
}
