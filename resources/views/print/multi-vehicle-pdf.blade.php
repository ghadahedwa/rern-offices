<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير مقارنة السيارات</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: dejavusans, sans-serif; direction: rtl; font-size: 9px; color: #1a1a1a; }

        /* ── Page Header ── */
        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #c9a847; padding-bottom: 5px; margin-bottom: 8px; }
        .header-table td { vertical-align: middle; padding: 2px; }
        .logo-img { width: 48px; height: 48px; }
        .app-title { font-size: 13px; font-weight: bold; color: #c9a847; }
        .app-subtitle { font-size: 8px; color: #666; margin-top: 1px; }
        .meta-cell { text-align: left; font-size: 8px; color: #666; line-height: 1.6; }

        /* ── Report Table ── */
        .rt { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .rt td, .rt th { word-wrap: break-word; overflow-wrap: break-word; }
        .rt th {
            background-color: #c9a847; color: #fff; font-size: 9px; font-weight: bold;
            padding: 4px 3px; border: 1px solid #b8962e; text-align: center; vertical-align: middle;
        }
        .rt td { border: 1px solid #ddd; padding: 3px 4px; vertical-align: top; }
        .rt tbody tr:nth-child(even) td { background-color: #fafafa; }

        /* ── Identity cells ── */
        .v-name { font-size: 10px; font-weight: bold; color: #111; }
        .v-plate { font-size: 8px; color: #777; }
        .v-status { font-size: 8px; margin-top: 2px; }
        .v-gov { font-size: 9px; font-weight: bold; color: #222; }

        /* ── Field lines inside section cells ── */
        .f { margin-bottom: 2px; line-height: 1.4; }
        .k { color: #999; font-size: 8px; }
        .v { color: #111; font-weight: bold; font-size: 9px; }
        .broken-item { color: #a33; }
        .none { color: #aaa; }
        .note-v { color: #333; font-size: 8px; }

        .page-footer { margin-top: 8px; padding-top: 4px; border-top: 1px solid #e4e4e4; text-align: center; font-size: 7px; color: #aaa; }
    </style>
</head>
<body>

    @php
        $dash = '—';
        $statusLabels    = \App\Models\Vehicle::STATUSES;
        $availabilityLabels = ['available' => 'متاح', 'not_available' => 'غير متاح'];
        $generatorLabels = ['available' => 'يعمل', 'not_available' => 'غير متوفر', 'broken' => 'معطل'];
        $cameraLabels    = ['available' => 'تعمل', 'not_available' => 'غير متوفرة', 'broken' => 'معطلة'];
        $dayLabels       = \App\Models\VehicleLocation::DAYS;
        $statLabels      = [1 => __('home.vehicle_stat_transactions'), 2 => __('home.vehicle_stat_form_sales'), 3 => __('home.vehicle_stat_folder_sales')];

        $lastYearStat = function ($vehicle, int $typeId) {
            $rows = $vehicle->statistics->where('stat_type_id', $typeId);
            if ($rows->isEmpty()) return null;
            $year = $rows->max('year');
            return number_format((float) $rows->where('year', $year)->sum('value'), 0) . ' (' . $year . ')';
        };
    @endphp

    {{-- ── Page Header ── --}}
    <table class="header-table">
        <tr>
            @if($logoBase64)<td style="width:44px;"><img class="logo-img" src="{{ $logoBase64 }}" alt=""></td>@endif
            <td>
                <div class="app-title">{{ __('home.app_name') }}</div>
                <div class="app-subtitle">تقرير مقارنة السيارات المتنقلة</div>
            </td>
            <td class="meta-cell">
                <div>تاريخ الطباعة: {{ \App\Support\LocalTime::date(now()) }}</div>
                <div>عدد السيارات: {{ $vehicles->count() }}</div>
            </td>
        </tr>
    </table>

    <table class="rt">
        <thead>
            <tr>
                <th width="10%">السيارة</th>
                <th width="8%">المحافظة</th>
                <th width="16%">البيانات الأساسية</th>
                <th width="14%">أيام التمركز وبيانات إضافية</th>
                <th width="14%">العاملون</th>
                <th width="14%">التجهيزات</th>
                <th width="8%">الأجهزة المعطلة</th>
                <th width="10%">الإحصائيات</th>
                <th width="6%">ملاحظات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($vehicles as $vehicle)
            <tr>
                {{-- السيارة --}}
                <td>
                    <div class="v-name">{{ $vehicle->name }}</div>
                    @if($vehicle->license_plate)<div class="v-plate">{{ $vehicle->license_plate }}</div>@endif
                    <div class="v-status">{{ $statusLabels[$vehicle->status] ?? $dash }}</div>
                </td>

                {{-- المحافظة --}}
                <td>
                    <div class="v-gov">{{ $vehicle->governorate->name ?? $dash }}</div>
                </td>

                {{-- البيانات الأساسية --}}
                <td>
                    <div class="f"><span class="k">النوع:</span> <span class="v">{{ $vehicle->type->name ?? $dash }}</span></div>
                    <div class="f"><span class="k">نظام/أوقات العمل:</span> <span class="v">{{ $vehicle->workSystem->name ?? $dash }} — {{ $vehicle->workingHour->name ?? $dash }}</span></div>
                    <div class="f"><span class="k">الماركة:</span> <span class="v">{{ $vehicle->brand->name ?? $dash }}</span></div>
                    <div class="f"><span class="k">الشاسية:</span> <span class="v">{{ $vehicle->chassis_number ?: $dash }}</span></div>
                    <div class="f"><span class="k">سنة الصنع:</span> <span class="v">{{ $vehicle->manufacture_year ?? $dash }}</span></div>
                    <div class="f"><span class="k">تاريخ التشغيل:</span> <span class="v">{{ $vehicle->operated_at?->format('Y-m-d') ?? $dash }}</span></div>
                    <div class="f"><span class="k">انتهاء الترخيص:</span> <span class="v">{{ $vehicle->license_expiry_date?->format('Y-m-d') ?? $dash }}</span></div>
                </td>

                {{-- أيام التمركز وبيانات إضافية --}}
                <td>
                    @forelse($vehicle->locations->groupBy('address') as $address => $rows)
                        <div class="f"><span class="k">{{ $address }}:</span> <span class="v">{{ $rows->pluck('day')->map(fn($d) => $dayLabels[$d] ?? $d)->implode('، ') }}</span></div>
                    @empty
                        <div class="f"><span class="none">{{ $dash }}</span></div>
                    @endforelse
                    @if($vehicle->overnight_address)<div class="f"><span class="k">عنوان المبيت:</span> <span class="v">{{ $vehicle->overnight_address }}</span></div>@endif
                    @if($vehicle->storage_room_location)<div class="f"><span class="k">غرفة الحفظ:</span> <span class="v">{{ $vehicle->storage_room_location }}</span></div>@endif
                </td>

                {{-- العاملون --}}
                <td>
                    <div class="f"><span class="k">السائق:</span> <span class="v">{{ trim(($vehicle->driver_name ?? '') . ' ' . ($vehicle->driver_phone ? '— ' . $vehicle->driver_phone : '')) ?: $dash }}</span></div>
                    <div class="f"><span class="k">الموثق:</span> <span class="v">{{ trim(($vehicle->notary_name ?? '') . ' ' . ($vehicle->notary_phone ? '— ' . $vehicle->notary_phone : '')) ?: $dash }}</span></div>
                    <div class="f"><span class="k">المراجع:</span> <span class="v">{{ trim(($vehicle->reviewer_name ?? '') . ' ' . ($vehicle->reviewer_phone ? '— ' . $vehicle->reviewer_phone : '')) ?: $dash }}</span></div>
                </td>

                {{-- التجهيزات --}}
                <td>
                    <div class="f"><span class="k">شنطة تنقلات:</span> <span class="v">{{ $availabilityLabels[$vehicle->mobility_bag] ?? $dash }}</span></div>
                    <div class="f"><span class="k">الأجهزة التي تعمل:</span> <span class="v">{{ \App\Reports\VehicleColumns::all()['working_equipment']['value']($vehicle) }}</span></div>
                    <div class="f"><span class="k">المولد:</span> <span class="v">{{ $generatorLabels[$vehicle->generator_status] ?? $dash }}</span></div>
                    <div class="f"><span class="k">الكاميرات:</span> <span class="v">{{ $cameraLabels[$vehicle->surveillance_cameras] ?? $dash }}</span></div>
                </td>

                {{-- الأجهزة المعطلة --}}
                <td>
                    @forelse($vehicle->brokenDevices as $bd)
                        <div class="f"><span class="broken-item">{{ $bd->deviceType->name ?? $dash }}: {{ $bd->count }}</span></div>
                    @empty
                        <span class="none">لا يوجد</span>
                    @endforelse
                </td>

                {{-- الإحصائيات --}}
                <td>
                    <div class="f"><span class="k">متوسط يومي:</span> <span class="v">{{ $vehicle->avg_daily_transactions ?? $dash }}</span></div>
                    <div class="f"><span class="k">{{ $statLabels[1] }}:</span> <span class="v">{{ $lastYearStat($vehicle, 1) ?? $dash }}</span></div>
                    <div class="f"><span class="k">{{ $statLabels[2] }}:</span> <span class="v">{{ $lastYearStat($vehicle, 2) ?? $dash }}</span></div>
                    <div class="f"><span class="k">{{ $statLabels[3] }}:</span> <span class="v">{{ $lastYearStat($vehicle, 3) ?? $dash }}</span></div>
                </td>

                {{-- ملاحظات --}}
                <td>
                    @if($vehicle->notes)<span class="note-v">{{ $vehicle->notes }}</span>@else<span class="none">{{ $dash }}</span>@endif
                </td>
            </tr>
            @empty
            <tr><td colspan="9" style="text-align:center; color:#999; padding:30px;">لا توجد سيارات مطابقة لمحددات البحث</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="page-footer">
        نظام مقرات التوثيق والشهر العقاري — طُبع بتاريخ {{ \App\Support\LocalTime::stamp(now()) }}
    </div>

</body>
</html>
