<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير مقارنة المقرات</title>
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
        .o-name { font-size: 10px; font-weight: bold; color: #111; }
        .o-addr { font-size: 8px; color: #777; }
        .o-visit { font-size: 8px; color: #555; margin-top: 2px; }
        .o-gov { font-size: 9px; font-weight: bold; color: #222; }

        /* ── Field lines inside section cells ── */
        .f { margin-bottom: 2px; line-height: 1.4; }
        .k { color: #999; font-size: 8px; }
        .v { color: #111; font-weight: bold; font-size: 9px; }
        a.maplink { color: #1a5fb4; text-decoration: underline; }
        .broken-item { color: #a33; }
        .none { color: #aaa; }
        .note-k { color: #999; font-size: 8px; font-weight: bold; }
        .note-v { color: #333; font-size: 8px; }

        .page-footer { margin-top: 8px; padding-top: 4px; border-top: 1px solid #e4e4e4; text-align: center; font-size: 7px; color: #aaa; }
    </style>
</head>
<body>

    @php
        $dash = '—';
        $workingDaysLabels = [
            'full_week' => 'أسبوع كامل', 'one_day' => 'يوم واحد', 'two_days' => 'يومان',
            'three_days' => 'ثلاثة أيام', 'four_days' => 'أربعة أيام', 'five_days' => 'خمسة أيام',
        ];
        $brailleLabels   = ['available' => 'متوفر', 'not_available' => 'غير متوفر'];
        $queueLabels     = ['working' => 'يعمل', 'not_working' => 'لا يعمل', 'not_available' => 'غير متوفر'];
        $cameraLabels    = ['available' => 'تعمل', 'not_available' => 'غير متوفرة', 'broken' => 'معطلة'];
        $meterTypeLabels = [
            'prepaid'      => __('home.meter_type_prepaid'),
            'invoice'      => __('home.meter_type_invoice'),
            'entity_meter' => __('home.meter_type_entity'),
        ];
        $meterDebtLabels = ['yes' => 'يوجد', 'no' => 'لا يوجد'];

        $pair = function ($a, $b) use ($dash) {
            $a = ($a !== null && $a !== '') ? $a : null;
            $b = ($b !== null && $b !== '') ? $b : null;
            if ($a && $b) return $a . ' — ' . $b;
            return $a ?? $b ?? $dash;
        };
    @endphp

    {{-- ── Page Header ── --}}
    <table class="header-table">
        <tr>
            @if($logoBase64)<td style="width:44px;"><img class="logo-img" src="{{ $logoBase64 }}" alt=""></td>@endif
            <td>
                <div class="app-title">قطاع الشهر العقاري</div>
                <div class="app-subtitle">تقرير مقارنة المقرات</div>
            </td>
            <td class="meta-cell">
                <div>تاريخ الطباعة: {{ now()->format('Y-m-d') }}</div>
                <div>عدد المقرات: {{ $offices->count() }}</div>
            </td>
        </tr>
    </table>

    <table class="rt">
        <thead>
            <tr>
                <th width="9%">المقر</th>
                <th width="7%">المحافظة</th>
                <th width="15%">البيانات الأساسية</th>
                <th width="11%">أوقات وأنظمة العمل</th>
                <th width="13%">الخدمات والتجهيزات</th>
                <th width="7%">الأنظمة التقنية</th>
                <th width="7%">عدد الأجهزة</th>
                <th width="11%">العدادات</th>
                <th width="6%">التقييم والجودة</th>
                <th width="5%">الأجهزة المعطلة</th>
                <th width="9%">ملاحظات المقر</th>
            </tr>
        </thead>
        <tbody>
            @forelse($offices as $office)
            <tr>
                {{-- المقر --}}
                <td>
                    <div class="o-name">{{ $office->name }}</div>
                    @if($office->address)<div class="o-addr">({{ $office->address }})</div>@endif
                    <div class="o-visit">آخر زيارة: {{ $office->visited_at?->format('Y-m-d') ?? 'لم تُزر' }}</div>
                </td>

                {{-- المحافظة --}}
                <td>
                    @php $counselor = $office->governorate->supervising_counselor ?? null; @endphp
                    <div class="o-gov">{{ $office->governorate->name ?? $dash }}@if($counselor) - المستشار المشرف ({{ $counselor }})@endif</div>
                </td>

                {{-- البيانات الأساسية --}}
                <td>
                    <div class="f"><span class="k">النوع والموقع:</span> <span class="v">{{ $pair($office->officeType->name ?? null, $office->locationDescription->name ?? null) }}</span></div>
                    <div class="f"><span class="k">تاريخ الإنشاء:</span> <span class="v">{{ $office->established_at?->format('Y-m-d') ?? $dash }}</span></div>
                    <div class="f"><span class="k">المحكمة:</span> <span class="v">{{ $office->district_court ?: $dash }}</span></div>
                    <div class="f"><span class="k">المساحة والطوابق:</span> <span class="v">{{ $pair($office->office_area ? $office->office_area.' م²' : null, $office->floors_description) }}</span></div>
                    <div class="f"><span class="k">التعاقدي والإنشائية:</span> <span class="v">{{ $pair($office->contractualStatus->name ?? null, $office->structuralCondition->name ?? null) }}</span></div>
                    <div class="f"><span class="k">رابط الخريطة:</span> @if($office->google_maps_link)<a class="maplink" href="{{ $office->google_maps_link }}">رابط الخريطة</a>@else<span class="v">{{ $dash }}</span>@endif</div>
                </td>

                {{-- أوقات وأنظمة العمل --}}
                <td>
                    <div class="f"><span class="k">نظام العمل:</span> <span class="v">{{ $office->workSystem->name ?? $dash }}</span></div>
                    <div class="f"><span class="k">ساعات العمل:</span> <span class="v">{{ $office->workingHour->name ?? $dash }}</span></div>
                    <div class="f"><span class="k">أيام العمل:</span> <span class="v">{{ $workingDaysLabels[$office->working_days] ?? $dash }}</span></div>
                    <div class="f"><span class="k">نوع الاتصال:</span> <span class="v">{{ $office->connectionType->name ?? $dash }}</span></div>
                    <div class="f"><span class="k">تاريخ الميكنة:</span> <span class="v">{{ $office->mechanization_at?->format('Y-m-d') ?? $dash }}</span></div>
                    @if($office->locationDescription?->shows_windows_count)
                    <div class="f"><span class="k">عدد الشبابيك:</span> <span class="v">{{ $office->windows_count ?? $dash }}</span></div>
                    @endif
                </td>

                {{-- الخدمات والتجهيزات --}}
                <td>
                    <div class="f"><span class="k">الميكروفيلم:</span> <span class="v">{{ $office->MicrofilmOption->name ?? $dash }}</span></div>
                    <div class="f"><span class="k">ذوي الهمم:</span> <span class="v">{{ $office->DisabilitieAccess->name ?? $dash }}</span></div>
                    <div class="f"><span class="k">الحماية المدنية:</span> <span class="v">{{ $office->FireSafety->name ?? $dash }}</span></div>
                    <div class="f"><span class="k">تصوير المستندات:</span> <span class="v">{{ $office->DocumentPhotocopyingService->name ?? $dash }}</span></div>
                    <div class="f"><span class="k">البوفيه:</span> <span class="v">{{ $office->BuffetService->name ?? $dash }}</span></div>
                    <div class="f"><span class="k">عقد النظافة:</span> <span class="v">{{ $office->CleanlinessContract->name ?? $dash }}</span></div>
                </td>

                {{-- الأنظمة التقنية --}}
                <td>
                    <div class="f"><span class="k">برايل:</span> <span class="v">{{ $brailleLabels[$office->Braille_sign_device] ?? $dash }}</span></div>
                    <div class="f"><span class="k">الطوابير:</span> <span class="v">{{ $queueLabels[$office->queue_management_system] ?? $dash }}</span></div>
                    <div class="f"><span class="k">الكاميرات:</span> <span class="v">{{ $cameraLabels[$office->surveillance_cameras] ?? $dash }}</span></div>
                </td>

                {{-- عدد الأجهزة --}}
                <td>
                    <div class="f"><span class="k">كمبيوتر:</span> <span class="v">{{ $office->computers_count ?? 0 }}</span></div>
                    <div class="f"><span class="k">شاشات العرض:</span> <span class="v">{{ $office->monitors_count ?? 0 }}</span></div>
                    <div class="f"><span class="k">طابعات:</span> <span class="v">{{ $office->printers_count ?? 0 }}</span></div>
                    <div class="f"><span class="k">ماسحات:</span> <span class="v">{{ $office->scanners_count ?? 0 }}</span></div>
                    <div class="f"><span class="k">بصمة:</span> <span class="v">{{ $office->fingerprints_count ?? 0 }}</span></div>
                    <div class="f"><span class="k">ماكينات دفع:</span> <span class="v">{{ $office->payment_machine_count ?? 0 }}</span></div>
                    <div class="f"><span class="k">مكيفات:</span> <span class="v">{{ $office->air_conditioners_count ?? 0 }}</span></div>
                    <div class="f"><span class="k">UPS:</span> <span class="v">{{ $office->ups_count ?? 0 }}</span></div>
                </td>

                {{-- العدادات --}}
                <td>
                    <div class="f"><span class="k">عداد الكهرباء:</span> <span class="v">{{ $meterTypeLabels[$office->electricity_meter_type] ?? $dash }}</span></div>
                    <div class="f"><span class="k">مديونية عداد الكهرباء:</span> <span class="v">{{ $meterDebtLabels[$office->electricity_meter_debt] ?? $dash }}</span></div>
                    <div class="f"><span class="k">عداد المياه:</span> <span class="v">{{ $meterTypeLabels[$office->water_meter_type] ?? $dash }}</span></div>
                    <div class="f"><span class="k">مديونية عداد المياه:</span> <span class="v">{{ $meterDebtLabels[$office->water_meter_debt] ?? $dash }}</span></div>
                </td>

                {{-- التقييم والجودة --}}
                <td>
                    <div class="f"><span class="k">النظافة:</span> <span class="v">{{ \App\Models\Office::CLEANLINESS_RATINGS[$office->cleanliness_rating] ?? $dash }}</span></div>
                    <div class="f"><span class="k">الأرشيف:</span> <span class="v">{{ \App\Models\Office::ARCHIVE_RATINGS[$office->archive_rating] ?? $dash }}</span></div>
                    <div class="f"><span class="k">المواعيد:</span> <span class="v">{{ \App\Models\Office::COMMITMENT_RATINGS[$office->work_schedule_commitment] ?? $dash }}</span></div>
                    <div class="f"><span class="k">المواطنين:</span> <span class="v">{{ \App\Models\Office::COMMITMENT_RATINGS[$office->citizen_treatment_commitment] ?? $dash }}</span></div>
                </td>

                {{-- الأجهزة المعطلة --}}
                <td>
                    @forelse($office->brokenDevices as $bd)
                        <div class="f"><span class="broken-item">{{ $bd->deviceType->name ?? $dash }}: {{ $bd->count }}</span></div>
                    @empty
                        <span class="none">لا يوجد</span>
                    @endforelse
                </td>

                {{-- ملاحظات المقر --}}
                <td>
                    @if($office->office_needs)<div class="f"><span class="note-k">احتياجات:</span> <span class="note-v">{{ $office->office_needs }}</span></div>@endif
                    @if($office->negatives_and_solutions)<div class="f"><span class="note-k">سلبيات وحلول:</span> <span class="note-v">{{ $office->negatives_and_solutions }}</span></div>@endif
                    @if($office->development_proposals)<div class="f"><span class="note-k">مقترحات:</span> <span class="note-v">{{ $office->development_proposals }}</span></div>@endif
                    @if(! $office->office_needs && ! $office->negatives_and_solutions && ! $office->development_proposals)<span class="none">{{ $dash }}</span>@endif
                </td>
            </tr>
            @empty
            <tr><td colspan="11" style="text-align:center; color:#999; padding:30px;">لا توجد مقرات مطابقة لمحددات البحث</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="page-footer">
        نظام مقرات التوثيق والشهر العقاري — طُبع بتاريخ {{ now()->format('Y-m-d H:i') }}
    </div>

</body>
</html>
