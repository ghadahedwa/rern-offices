<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير مقر — {{ $office->name }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo3.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Cairo', Arial, sans-serif;
            direction: rtl;
            font-size: 13px;
            color: #1a1a1a;
            background: #fff;
            padding: 24px;
        }

        /* ── Header ── */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 16px;
            border-bottom: 3px solid #c9a847;
            margin-bottom: 20px;
        }
        .page-header .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .page-header img {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }
        .page-header .app-title {
            font-size: 16px;
            font-weight: 700;
            color: #c9a847;
            line-height: 1.4;
        }
        .page-header .app-subtitle {
            font-size: 12px;
            color: #666;
        }
        .page-header .report-meta {
            text-align: left;
            font-size: 11px;
            color: #666;
            line-height: 1.8;
        }

        /* ── Office Title Banner ── */
        .office-banner {
            background: #c9a847;
            color: #fff;
            padding: 10px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .office-banner .office-name {
            font-size: 17px;
            font-weight: 700;
        }
        .office-banner .office-meta {
            font-size: 12px;
            opacity: 0.9;
        }

        /* ── Section ── */
        .section {
            margin-bottom: 18px;
            page-break-inside: avoid;
        }
        .section-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e4e4e4;
        }
        .section-header .bar {
            width: 4px;
            height: 16px;
            background: #c9a847;
            border-radius: 2px;
            flex-shrink: 0;
        }
        .section-header h2 {
            font-size: 13px;
            font-weight: 700;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ── Grid ── */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 20px;
        }
        .grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 8px 20px;
        }
        .field { }
        .field .label {
            font-size: 10px;
            color: #999;
            margin-bottom: 2px;
        }
        .field .value {
            font-size: 13px;
            font-weight: 600;
            color: #111;
        }
        .field .value.empty { color: #bbb; font-weight: 400; }

        /* ── Full-width text ── */
        .text-block {
            margin-bottom: 10px;
        }
        .text-block .label {
            font-size: 10px;
            color: #999;
            margin-bottom: 3px;
        }
        .text-block .value {
            font-size: 12px;
            color: #333;
            line-height: 1.7;
            white-space: pre-wrap;
        }

        /* ── Table ── */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        table thead tr { background: #f5f0e0; }
        table thead th {
            padding: 6px 10px;
            text-align: right;
            font-weight: 700;
            color: #555;
            border: 1px solid #e8e0c8;
        }
        table tbody td {
            padding: 5px 10px;
            border: 1px solid #eee;
            color: #333;
        }
        table tbody tr:nth-child(even) { background: #fafafa; }

        /* ── Print Button ── */
        .print-bar {
            position: fixed;
            top: 0; left: 0; right: 0;
            background: #1a1a1a;
            color: #fff;
            padding: 8px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 999;
            font-size: 13px;
        }
        .print-bar button {
            background: #c9a847;
            color: #fff;
            border: none;
            padding: 6px 18px;
            border-radius: 6px;
            font-family: 'Cairo', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .print-bar button:hover { background: #b8962e; }

        @media print {
            .print-bar { display: none; }
            body { padding: 10px; }
        }

        /* top padding for fixed bar */
        .page-content { padding-top: 48px; }
    </style>
</head>
<body>

    {{-- Print Bar --}}
    <div class="print-bar">
        <span>{{ $office->name }} — تقرير مقر توثيق</span>
        <button onclick="window.print()">🖨 طباعة / حفظ PDF</button>
    </div>

    <div class="page-content">

        {{-- Header --}}
        <div class="page-header">
            <div class="logo-area">
                <img src="{{ asset('images/logo3.png') }}" alt="شعار">
                <div>
                    <div class="app-title">{{ __('home.app_name') }}</div>
                    <div class="app-subtitle">نظام مقرات التوثيق والشهر العقاري</div>
                </div>
            </div>
            <div class="report-meta">
                <div>تاريخ الطباعة: {{ \App\Support\LocalTime::date(now()) }}</div>
                <div>المحافظة: {{ $office->governorate->name ?? '—' }}</div>
                @if($office->officeType)
                <div>النوع: {{ $office->officeType->name }}</div>
                @endif
            </div>
        </div>

        {{-- Office Banner --}}
        <div class="office-banner">
            <div class="office-name">{{ $office->name }}</div>
            @if($office->visited_at)
            <div class="office-meta">تاريخ الزيارة: {{ $office->visited_at->format('Y-m-d') }}</div>
            @endif
        </div>

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
            $brailleLabels      = ['available' => 'متوفر', 'not_available' => 'غير متوفر'];
            $queueLabels        = ['working' => 'يعمل', 'not_working' => 'لا يعمل', 'not_available' => 'غير متوفر'];
            $cameraLabels       = ['available' => 'تعمل', 'not_available' => 'غير متوفرة', 'broken' => 'معطلة'];
            $meterTypeLabels    = ['prepaid' => 'كارت', 'invoice' => 'فاتورة', 'entity_meter' => 'عداد جهة'];
            $meterDebtLabels    = ['yes' => 'يوجد', 'no' => 'لا يوجد'];
        @endphp

        {{-- ── البيانات الأساسية ── --}}
        <div class="section">
            <div class="section-header"><div class="bar"></div><h2>البيانات الأساسية</h2></div>
            <div class="grid-2">
                <div class="field"><div class="label">المحافظة</div><div class="value">{{ $office->governorate->name ?? $dash }}</div></div>
                <div class="field"><div class="label">المستشار المشرف</div><div class="value">{{ $office->governorate->supervising_counselor ?? $dash }}</div></div>
                <div class="field"><div class="label">اسم المقر</div><div class="value">{{ $office->name }}</div></div>
                <div class="field"><div class="label">نوع المقر</div><div class="value">{{ $office->officeType->name ?? $dash }}</div></div>
                <div class="field"><div class="label">وصف الموقع</div><div class="value">{{ $office->locationDescription->name ?? $dash }}</div></div>
                <div class="field"><div class="label">تاريخ الإنشاء</div><div class="value">{{ $office->established_at?->format('Y-m-d') ?? $dash }}</div></div>
                <div class="field"><div class="label">المحكمة الابتدائية</div><div class="value">{{ $office->district_court ?? $dash }}</div></div>
                <div class="field"><div class="label">مساحة المقر</div><div class="value">{{ $office->office_area ? $office->office_area . ' م²' : $dash }}</div></div>
                @if($office->floors_description)
                <div class="field"><div class="label">وصف الطوابق</div><div class="value">{{ $office->floors_description }}</div></div>
                @endif
                <div class="field"><div class="label">الوضع التعاقدي</div><div class="value">{{ $office->contractualStatus->name ?? $dash }}</div></div>
            </div>
        </div>

        {{-- ── أوقات ونظام العمل ── --}}
        <div class="section">
            <div class="section-header"><div class="bar"></div><h2>أوقات ونظام العمل</h2></div>
            <div class="grid-2">
                <div class="field"><div class="label">نظام العمل</div><div class="value">{{ $office->workSystem->name ?? $dash }}</div></div>
                <div class="field"><div class="label">ساعات العمل</div><div class="value">{{ $office->workingHour->name ?? $dash }}</div></div>
                <div class="field"><div class="label">أيام العمل</div><div class="value">{{ $workingDaysLabels[$office->working_days] ?? $dash }}</div></div>
                <div class="field"><div class="label">نوع الاتصال</div><div class="value">{{ $office->connectionType->name ?? $dash }}</div></div>
                <div class="field"><div class="label">تاريخ الميكنة</div><div class="value">{{ $office->mechanization_at?->format('Y-m-d') ?? $dash }}</div></div>
                @if($office->locationDescription?->shows_windows_count)
                <div class="field"><div class="label">عدد الشبابيك</div><div class="value">{{ $office->windows_count ?? $dash }}</div></div>
                @endif
            </div>
        </div>

        {{-- ── الخدمات والتجهيزات ── --}}
        <div class="section">
            <div class="section-header"><div class="bar"></div><h2>الخدمات والتجهيزات</h2></div>
            <div class="grid-3">
                <div class="field"><div class="label">خدمة الميكروفيلم</div><div class="value">{{ $office->MicrofilmOption->name ?? $dash }}</div></div>
                <div class="field"><div class="label">إمكانية ذوي الاحتياجات</div><div class="value">{{ $office->DisabilitieAccess->name ?? $dash }}</div></div>
                <div class="field"><div class="label">السلامة من الحرائق</div><div class="value">{{ $office->FireSafety->name ?? $dash }}</div></div>
                <div class="field"><div class="label">خدمة تصوير المستندات</div><div class="value">{{ $office->DocumentPhotocopyingService->name ?? $dash }}</div></div>
                <div class="field"><div class="label">خدمة البوفيه</div><div class="value">{{ $office->BuffetService->name ?? $dash }}</div></div>
                <div class="field"><div class="label">عقد النظافة</div><div class="value">{{ $office->CleanlinessContract->name ?? $dash }}</div></div>
            </div>
        </div>

        {{-- ── الأجهزة والمعدات ── --}}
        <div class="section">
            <div class="section-header"><div class="bar"></div><h2>الأجهزة والمعدات</h2></div>
            <div class="grid-3">
                <div class="field"><div class="label">لوحة برايل</div><div class="value">{{ $brailleLabels[$office->Braille_sign_device] ?? $dash }}</div></div>
                <div class="field"><div class="label">نظام إدارة الطوابير</div><div class="value">{{ $queueLabels[$office->queue_management_system] ?? $dash }}</div></div>
                <div class="field"><div class="label">كاميرات المراقبة</div><div class="value">{{ $cameraLabels[$office->surveillance_cameras] ?? $dash }}</div></div>
                <div class="field"><div class="label">ماكينات الدفع</div><div class="value">{{ $office->payment_machine_count ?? $dash }}</div></div>
                <div class="field"><div class="label">أجهزة الكمبيوتر</div><div class="value">{{ $office->computers_count ?? $dash }}</div></div>
                <div class="field"><div class="label">الشاشات</div><div class="value">{{ $office->monitors_count ?? $dash }}</div></div>
                <div class="field"><div class="label">الماسحات الضوئية</div><div class="value">{{ $office->scanners_count ?? $dash }}</div></div>
                <div class="field"><div class="label">الطابعات</div><div class="value">{{ $office->printers_count ?? $dash }}</div></div>
                <div class="field"><div class="label">أجهزة البصمة</div><div class="value">{{ $office->fingerprints_count ?? $dash }}</div></div>
                <div class="field"><div class="label">المكيفات</div><div class="value">{{ $office->air_conditioners_count ?? $dash }}</div></div>
                <div class="field"><div class="label">UPS</div><div class="value">{{ $office->ups_count ?? $dash }}</div></div>
            </div>
        </div>

        {{-- ── العدادات ── --}}
        <div class="section">
            <div class="section-header"><div class="bar"></div><h2>العدادات</h2></div>
            <div class="grid-2">
                <div class="field"><div class="label">نوع عداد الكهرباء</div><div class="value">{{ $meterTypeLabels[$office->electricity_meter_type] ?? $dash }}</div></div>
                <div class="field"><div class="label">مديونية الكهرباء</div><div class="value">{{ $meterDebtLabels[$office->electricity_meter_debt] ?? $dash }}</div></div>
                <div class="field"><div class="label">نوع عداد المياه</div><div class="value">{{ $meterTypeLabels[$office->water_meter_type] ?? $dash }}</div></div>
                <div class="field"><div class="label">مديونية المياه</div><div class="value">{{ $meterDebtLabels[$office->water_meter_debt] ?? $dash }}</div></div>
            </div>
        </div>

        {{-- ── الأجهزة المعطلة ── --}}
        @if($office->brokenDevices->isNotEmpty())
        <div class="section">
            <div class="section-header"><div class="bar"></div><h2>الأجهزة المعطلة</h2></div>
            <table>
                <thead>
                    <tr>
                        <th>نوع الجهاز</th>
                        <th style="width:120px">العدد</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($office->brokenDevices as $device)
                    <tr>
                        <td>{{ $device->deviceType->name ?? '—' }}</td>
                        <td>{{ $device->count }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- ── بيانات التقييم ── --}}
        <div class="section">
            <div class="section-header"><div class="bar"></div><h2>بيانات الزيارة والتقييم</h2></div>
            <div class="grid-2">
                <div class="field"><div class="label">تاريخ الزيارة</div><div class="value">{{ $office->visited_at?->format('Y-m-d') ?? $dash }}</div></div>
                <div class="field"><div class="label">الحالة الإنشائية</div><div class="value">{{ $office->structuralCondition->name ?? $dash }}</div></div>
                <div class="field"><div class="label">تقييم النظافة</div><div class="value">{{ \App\Models\Office::CLEANLINESS_RATINGS[$office->cleanliness_rating] ?? $dash }}</div></div>
                <div class="field"><div class="label">تقييم الأرشيف</div><div class="value">{{ \App\Models\Office::ARCHIVE_RATINGS[$office->archive_rating] ?? $dash }}</div></div>
                <div class="field"><div class="label">الالتزام بجدول العمل</div><div class="value">{{ \App\Models\Office::COMMITMENT_RATINGS[$office->work_schedule_commitment] ?? $dash }}</div></div>
                <div class="field"><div class="label">التعامل مع المواطنين</div><div class="value">{{ \App\Models\Office::COMMITMENT_RATINGS[$office->citizen_treatment_commitment] ?? $dash }}</div></div>
            </div>

            @if($office->office_needs || $office->negatives_and_solutions || $office->development_proposals)
            <div style="margin-top:10px; display:grid; grid-template-columns:1fr; gap:8px;">
                @if($office->office_needs)
                <div class="text-block"><div class="label">احتياجات المقر</div><div class="value">{{ $office->office_needs }}</div></div>
                @endif
                @if($office->negatives_and_solutions)
                <div class="text-block"><div class="label">السلبيات والحلول</div><div class="value">{{ $office->negatives_and_solutions }}</div></div>
                @endif
                @if($office->development_proposals)
                <div class="text-block"><div class="label">مقترحات التطوير</div><div class="value">{{ $office->development_proposals }}</div></div>
                @endif
            </div>
            @endif
        </div>

        {{-- ── ملخص الإحصائيات ── --}}
        @if($statGroups->isNotEmpty())
        <div class="section">
            <div class="section-header"><div class="bar"></div><h2>ملخص الإحصائيات (آخر 5 سنوات)</h2></div>
            @foreach($statGroups as $groupName => $rows)
            @if($rows->isNotEmpty())
            <div style="margin-bottom:12px;">
                <div style="font-size:11px; font-weight:700; color:#c9a847; margin-bottom:4px;">{{ $groupName }}</div>
                <table>
                    <thead>
                        <tr>
                            <th>السنة</th>
                            @if($rows->first()?->month) <th>الشهر</th> @endif
                            <th>القيمة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $stat)
                        @php
                            $months = [1=>'يناير',2=>'فبراير',3=>'مارس',4=>'أبريل',5=>'مايو',6=>'يونيو',
                                       7=>'يوليو',8=>'أغسطس',9=>'سبتمبر',10=>'أكتوبر',11=>'نوفمبر',12=>'ديسمبر'];
                        @endphp
                        <tr>
                            <td>{{ $stat->year }}</td>
                            @if($stat->month) <td>{{ $months[$stat->month] ?? $stat->month }}</td> @endif
                            <td>{{ $stat->statType->value_type === 'amount' ? number_format($stat->value, 2) : number_format($stat->value) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
            @endforeach
        </div>
        @endif

        {{-- Footer --}}
        <div style="margin-top:24px; padding-top:10px; border-top:1px solid #e4e4e4; text-align:center; font-size:10px; color:#aaa;">
            نظام مقرات التوثيق والشهر العقاري — طُبع بتاريخ {{ \App\Support\LocalTime::stamp(now()) }}
        </div>

    </div>
</body>
</html>
