<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير مقر — {{ $office->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: dejavusans, sans-serif;
            direction: rtl;
            font-size: 11px;
            color: #1a1a1a;
            background: #fff;
        }

        /* ── Header ── */
        .header-table { width: 100%; border-collapse: collapse; border-bottom: 3px solid #c9a847; padding-bottom: 10px; margin-bottom: 14px; }
        .header-table td { vertical-align: middle; padding: 4px; }
        .logo-img { width: 50px; height: 50px; }
        .app-title { font-size: 14px; font-weight: bold; color: #c9a847; }
        .app-subtitle { font-size: 10px; color: #666; margin-top: 2px; }
        .meta-cell { text-align: left; font-size: 10px; color: #666; line-height: 1.8; }

        /* ── Office Banner ── */
        .office-banner { background-color: #c9a847; color: #fff; padding: 7px 12px; margin-bottom: 14px; width: 100%; }
        .office-banner table { width: 100%; border-collapse: collapse; }
        .office-banner td { padding: 0; vertical-align: middle; color: #fff; }
        .office-name { font-size: 14px; font-weight: bold; }
        .office-meta-text { font-size: 10px; text-align: left; }

        /* ── Section ── */
        .section { margin-bottom: 14px; }
        .section-hdr { width: 100%; border-collapse: collapse; margin-bottom: 6px; border-bottom: 1px solid #e4e4e4; padding-bottom: 3px; }
        .section-hdr td { padding: 0; vertical-align: middle; }
        .bar-cell { width: 5px; background-color: #c9a847; }
        .section-title { font-size: 11px; font-weight: bold; color: #555; padding-right: 7px; padding-left: 4px; }

        /* ── Field Grid ── */
        .grid-table { width: 100%; border-collapse: collapse; }
        .grid-table td { width: 50%; vertical-align: top; padding: 3px 5px 5px 5px; }
        .grid-table.cols3 td { width: 33.33%; }

        .lbl { font-size: 9px; color: #999; margin-bottom: 2px; }
        .val { font-size: 11px; font-weight: bold; color: #111; }

        /* ── Long text ── */
        .txt-block { margin-bottom: 7px; }
        .txt-block .lbl { font-size: 9px; color: #999; margin-bottom: 2px; }
        .txt-block .val { font-size: 10px; font-weight: normal; color: #333; line-height: 1.6; }

        /* ── Data Table ── */
        .data-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        .data-table thead tr { background-color: #f5f0e0; }
        .data-table thead th { padding: 5px 8px; text-align: right; font-weight: bold; color: #555; border: 1px solid #e0d8c0; }
        .data-table tbody td { padding: 4px 8px; border: 1px solid #eee; color: #333; }
        .data-table tbody tr:nth-child(even) { background-color: #fafafa; }

        /* ── Stats ── */
        .stat-group-title { font-size: 10px; font-weight: bold; color: #c9a847; margin-bottom: 4px; margin-top: 8px; }

        /* ── Footer ── */
        .page-footer { margin-top: 18px; padding-top: 7px; border-top: 1px solid #e4e4e4; text-align: center; font-size: 9px; color: #aaa; }
    </style>
</head>
<body>

    @php
        $dash = '—';
        $workingDaysLabels = [
            'full_week'  => 'أسبوع كامل',
            'one_day'    => 'يوم واحد',
            'two_days'   => 'يومان',
            'three_days' => 'ثلاثة أيام',
            'four_days'  => 'أربعة أيام',
            'five_days'  => 'خمسة أيام',
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
        $months = [1=>'يناير',2=>'فبراير',3=>'مارس',4=>'أبريل',5=>'مايو',6=>'يونيو',
                   7=>'يوليو',8=>'أغسطس',9=>'سبتمبر',10=>'أكتوبر',11=>'نوفمبر',12=>'ديسمبر'];
    @endphp

    {{-- ── Page Header ── --}}
    <table class="header-table">
        <tr>
            @if($logoBase64)
            <td style="width:54px;"><img class="logo-img" src="{{ $logoBase64 }}" alt=""></td>
            @endif
            <td>
                <div class="app-title">قطاع الشهر العقاري</div>
                <div class="app-subtitle">نظام مقرات التوثيق والشهر العقاري</div>
            </td>
            <td class="meta-cell">
                <div>تاريخ الطباعة: {{ now()->format('Y-m-d') }}</div>
                <div>المحافظة: {{ $office->governorate->name ?? $dash }}</div>
                @if($office->officeType)<div>النوع: {{ $office->officeType->name }}</div>@endif
            </td>
        </tr>
    </table>

    {{-- ── Office Banner ── --}}
    <div class="office-banner">
        <table>
            <tr>
                <td><div class="office-name">{{ $office->name }}</div></td>
                @if($office->visited_at)
                <td><div class="office-meta-text">تاريخ الزيارة: {{ $office->visited_at->format('Y-m-d') }}</div></td>
                @endif
            </tr>
        </table>
    </div>

    {{-- ── البيانات الأساسية ── --}}
    <div class="section">
        <table class="section-hdr"><tr>
            <td class="bar-cell">&nbsp;</td>
            <td><span class="section-title">البيانات الأساسية</span></td>
        </tr></table>
        <table class="grid-table">
            <tr>
                <td><div class="lbl">المحافظة</div><div class="val">{{ $office->governorate->name ?? $dash }}</div></td>
                <td><div class="lbl">المستشار المشرف</div><div class="val">{{ $office->governorate->supervising_counselor ?? $dash }}</div></td>
            </tr>
            <tr>
                <td><div class="lbl">اسم المقر</div><div class="val">{{ $office->name }}</div></td>
                <td><div class="lbl">نوع المقر</div><div class="val">{{ $office->officeType->name ?? $dash }}</div></td>
            </tr>
            <tr>
                <td><div class="lbl">رقم موبيل رئيس المقر</div><div class="val">{{ $office->head_mobile ?: $dash }}</div></td>
                <td></td>
            </tr>
            <tr>
                <td><div class="lbl">وصف الموقع</div><div class="val">{{ $office->locationDescription->name ?? $dash }}</div></td>
                <td><div class="lbl">تاريخ الإنشاء</div><div class="val">{{ $office->established_at?->format('Y-m-d') ?? $dash }}</div></td>
            </tr>
            <tr>
                <td><div class="lbl">المحكمة الابتدائية</div><div class="val">{{ $office->district_court ?? $dash }}</div></td>
                <td><div class="lbl">مساحة المقر</div><div class="val">{{ $office->office_area ? $office->office_area . ' م2' : $dash }}</div></td>
            </tr>
            <tr>
                <td><div class="lbl">الوضع التعاقدي</div><div class="val">{{ $office->contractualStatus->name ?? $dash }}</div></td>
                <td>
                    @if($office->floors_description)
                    <div class="lbl">وصف الطوابق</div><div class="val">{{ $office->floors_description }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- ── أوقات ونظام العمل ── --}}
    <div class="section">
        <table class="section-hdr"><tr>
            <td class="bar-cell">&nbsp;</td>
            <td><span class="section-title">أوقات ونظام العمل</span></td>
        </tr></table>
        <table class="grid-table">
            <tr>
                <td><div class="lbl">نظام العمل</div><div class="val">{{ $office->workSystem->name ?? $dash }}</div></td>
                <td><div class="lbl">ساعات العمل</div><div class="val">{{ $office->workingHour->name ?? $dash }}</div></td>
            </tr>
            <tr>
                <td><div class="lbl">أيام العمل</div><div class="val">{{ $workingDaysLabels[$office->working_days] ?? $dash }}</div></td>
                <td><div class="lbl">نوع الاتصال</div><div class="val">{{ $office->connectionType->name ?? $dash }}</div></td>
            </tr>
            <tr>
                <td><div class="lbl">تاريخ الميكنة</div><div class="val">{{ $office->mechanization_at?->format('Y-m-d') ?? $dash }}</div></td>
                <td>
                    @if($office->locationDescription?->shows_windows_count)
                    <div class="lbl">عدد الشبابيك</div><div class="val">{{ $office->windows_count ?? $dash }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- ── الخدمات والتجهيزات ── --}}
    <div class="section">
        <table class="section-hdr"><tr>
            <td class="bar-cell">&nbsp;</td>
            <td><span class="section-title">الخدمات والتجهيزات</span></td>
        </tr></table>
        <table class="grid-table cols3">
            <tr>
                <td><div class="lbl">خدمة الميكروفيلم</div><div class="val">{{ $office->MicrofilmOption->name ?? $dash }}</div></td>
                <td><div class="lbl">ذوو الاحتياجات الخاصة</div><div class="val">{{ $office->DisabilitieAccess->name ?? $dash }}</div></td>
                <td><div class="lbl">السلامة من الحرائق</div><div class="val">{{ $office->FireSafety->name ?? $dash }}</div></td>
            </tr>
            <tr>
                <td><div class="lbl">تصوير المستندات</div><div class="val">{{ $office->DocumentPhotocopyingService->name ?? $dash }}</div></td>
                <td><div class="lbl">خدمة البوفيه</div><div class="val">{{ $office->BuffetService->name ?? $dash }}</div></td>
                <td><div class="lbl">عقد النظافة</div><div class="val">{{ $office->CleanlinessContract->name ?? $dash }}</div></td>
            </tr>
        </table>
    </div>

    {{-- ── الأجهزة والمعدات ── --}}
    <div class="section">
        <table class="section-hdr"><tr>
            <td class="bar-cell">&nbsp;</td>
            <td><span class="section-title">الأجهزة والمعدات</span></td>
        </tr></table>
        <table class="grid-table cols3">
            <tr>
                <td><div class="lbl">لوحة برايل</div><div class="val">{{ $brailleLabels[$office->Braille_sign_device] ?? $dash }}</div></td>
                <td><div class="lbl">إدارة الطوابير</div><div class="val">{{ $queueLabels[$office->queue_management_system] ?? $dash }}</div></td>
                <td><div class="lbl">كاميرات المراقبة</div><div class="val">{{ $cameraLabels[$office->surveillance_cameras] ?? $dash }}</div></td>
            </tr>
            <tr>
                <td><div class="lbl">ماكينات الدفع</div><div class="val">{{ $office->payment_machine_count ?? $dash }}</div></td>
                <td><div class="lbl">أجهزة كمبيوتر</div><div class="val">{{ $office->computers_count ?? $dash }}</div></td>
                <td><div class="lbl">شاشات العرض</div><div class="val">{{ $office->monitors_count ?? $dash }}</div></td>
            </tr>
            <tr>
                <td><div class="lbl">ماسحات ضوئية</div><div class="val">{{ $office->scanners_count ?? $dash }}</div></td>
                <td><div class="lbl">طابعات</div><div class="val">{{ $office->printers_count ?? $dash }}</div></td>
                <td><div class="lbl">أجهزة بصمة</div><div class="val">{{ $office->fingerprints_count ?? $dash }}</div></td>
            </tr>
            <tr>
                <td><div class="lbl">مكيفات</div><div class="val">{{ $office->air_conditioners_count ?? $dash }}</div></td>
                <td><div class="lbl">UPS</div><div class="val">{{ $office->ups_count ?? $dash }}</div></td>
                <td></td>
            </tr>
        </table>
    </div>

    {{-- ── العدادات ── --}}
    <div class="section">
        <table class="section-hdr"><tr>
            <td class="bar-cell">&nbsp;</td>
            <td><span class="section-title">العدادات</span></td>
        </tr></table>
        <table class="grid-table">
            <tr>
                <td><div class="lbl">نوع عداد الكهرباء</div><div class="val">{{ $meterTypeLabels[$office->electricity_meter_type] ?? $dash }}</div></td>
                <td><div class="lbl">مديونية الكهرباء</div><div class="val">{{ $meterDebtLabels[$office->electricity_meter_debt] ?? $dash }}</div></td>
            </tr>
            <tr>
                <td><div class="lbl">نوع عداد المياه</div><div class="val">{{ $meterTypeLabels[$office->water_meter_type] ?? $dash }}</div></td>
                <td><div class="lbl">مديونية المياه</div><div class="val">{{ $meterDebtLabels[$office->water_meter_debt] ?? $dash }}</div></td>
            </tr>
        </table>
    </div>

    {{-- ── الأجهزة المعطلة ── --}}
    @if($office->brokenDevices->isNotEmpty())
    <div class="section">
        <table class="section-hdr"><tr>
            <td class="bar-cell">&nbsp;</td>
            <td><span class="section-title">الأجهزة المعطلة</span></td>
        </tr></table>
        <table class="data-table">
            <thead><tr>
                <th>نوع الجهاز</th>
                <th style="width:120px">العدد</th>
            </tr></thead>
            <tbody>
                @foreach($office->brokenDevices as $device)
                <tr>
                    <td>{{ $device->deviceType->name ?? $dash }}</td>
                    <td>{{ $device->count }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ── بيانات الزيارة والتقييم ── --}}
    <div class="section">
        <table class="section-hdr"><tr>
            <td class="bar-cell">&nbsp;</td>
            <td><span class="section-title">بيانات الزيارة والتقييم</span></td>
        </tr></table>
        <table class="grid-table">
            <tr>
                <td><div class="lbl">تاريخ الزيارة</div><div class="val">{{ $office->visited_at?->format('Y-m-d') ?? $dash }}</div></td>
                <td><div class="lbl">الحالة الإنشائية</div><div class="val">{{ $office->structuralCondition->name ?? $dash }}</div></td>
            </tr>
            <tr>
                <td><div class="lbl">تقييم النظافة</div><div class="val">{{ \App\Models\Office::CLEANLINESS_RATINGS[$office->cleanliness_rating] ?? $dash }}</div></td>
                <td><div class="lbl">تقييم الأرشيف</div><div class="val">{{ \App\Models\Office::ARCHIVE_RATINGS[$office->archive_rating] ?? $dash }}</div></td>
            </tr>
            <tr>
                <td><div class="lbl">الالتزام بجدول العمل</div><div class="val">{{ \App\Models\Office::COMMITMENT_RATINGS[$office->work_schedule_commitment] ?? $dash }}</div></td>
                <td><div class="lbl">التعامل مع المواطنين</div><div class="val">{{ \App\Models\Office::COMMITMENT_RATINGS[$office->citizen_treatment_commitment] ?? $dash }}</div></td>
            </tr>
        </table>

        @if($office->office_needs || $office->negatives_and_solutions || $office->development_proposals)
        <div style="margin-top:8px;">
            @if($office->office_needs)
            <div class="txt-block"><div class="lbl">احتياجات المقر</div><div class="val">{{ $office->office_needs }}</div></div>
            @endif
            @if($office->negatives_and_solutions)
            <div class="txt-block"><div class="lbl">السلبيات والحلول</div><div class="val">{{ $office->negatives_and_solutions }}</div></div>
            @endif
            @if($office->development_proposals)
            <div class="txt-block"><div class="lbl">مقترحات التطوير</div><div class="val">{{ $office->development_proposals }}</div></div>
            @endif
        </div>
        @endif
    </div>

    {{-- ── ملخص الإحصائيات ── --}}
    @if($statGroups->isNotEmpty())
    <div class="section">
        <table class="section-hdr"><tr>
            <td class="bar-cell">&nbsp;</td>
            <td><span class="section-title">ملخص الإحصائيات (آخر 5 سنوات)</span></td>
        </tr></table>
        @foreach($statGroups as $groupName => $rows)
        @if($rows->isNotEmpty())
        <div class="stat-group-title">{{ $groupName }}</div>
        <table class="data-table" style="margin-bottom:6px;">
            <thead><tr>
                <th>السنة</th>
                @if($rows->first()?->month)<th>الشهر</th>@endif
                <th>القيمة</th>
            </tr></thead>
            <tbody>
                @foreach($rows as $stat)
                <tr>
                    <td>{{ $stat->year }}</td>
                    @if($stat->month)<td>{{ $months[$stat->month] ?? $stat->month }}</td>@endif
                    <td>{{ $stat->statType->value_type === 'amount' ? number_format($stat->value, 2) : number_format($stat->value) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
        @endforeach
    </div>
    @endif

    {{-- ── Footer ── --}}
    <div class="page-footer">
        نظام مقرات التوثيق والشهر العقاري — طُبع بتاريخ {{ now()->format('Y-m-d H:i') }}
    </div>

</body>
</html>
