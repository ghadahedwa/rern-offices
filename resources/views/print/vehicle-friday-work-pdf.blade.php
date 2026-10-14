<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير السيارات العاملة أيام الجمع</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: dejavusans, sans-serif; direction: rtl; font-size: 9pt; color: #1a1a1a; }

        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #c9a847; padding-bottom: 5px; margin-bottom: 10px; }
        .header-table td { vertical-align: middle; padding: 2px; }
        .logo-img { width: 48px; height: 48px; }
        .app-title { font-size: 13pt; font-weight: bold; color: #c9a847; }
        .app-subtitle { font-size: 9pt; color: #666; margin-top: 1px; }
        .meta-cell { text-align: left; font-size: 9pt; color: #666; line-height: 1.6; }

        .section { font-size: 10.5pt; font-weight: bold; color: #b8962e; margin: 12px 0 5px; }
        .note { font-size: 8.5pt; color: #8a6d00; background-color: #fff8e1; border: 1px solid #f0d98a; padding: 5px 7px; margin-bottom: 8px; }

        .rt { width: 100%; border-collapse: collapse; }
        .rt th {
            background-color: #c9a847; color: #fff; font-size: 9pt; font-weight: bold;
            padding: 5px 4px; border: 1px solid #b8962e; text-align: center; vertical-align: middle;
        }
        .rt th.total-col { background-color: #b8962e; }
        .rt td { border: 1px solid #ddd; padding: 5px 4px; font-size: 9.5pt; text-align: center; vertical-align: middle; }
        .rt td.gov { text-align: right; font-weight: bold; color: #222; background-color: #faf6ea; }
        .rt td.name { text-align: right; }
        .rt td.total-cell { font-weight: bold; background-color: #faf6ea; }
        .rt tfoot td { font-weight: bold; background-color: #ede3c2; border: 1px solid #c9a847; padding: 5px 4px; }

        .page-footer { margin-top: 10px; padding-top: 4px; border-top: 1px solid #e4e4e4; text-align: center; font-size: 8pt; color: #aaa; }
    </style>
</head>
<body>

    @php
        $keys      = array_keys($labels);
        $colTotals = array_fill_keys($keys, 0);
        foreach ($governorates as $gov) {
            foreach ($keys as $k) { $colTotals[$k] += $counts[$gov->id][$k] ?? 0; }
        }
        // ⚠️ اللون inline لا class — قاعدة تخطيط الصفوف أعلى أولوية فتطغى عليه
        $color = ['works' => '#2a7', 'not_works' => '#a33', 'unset' => '#888'];
    @endphp

    <table class="header-table">
        <tr>
            @if($logoBase64)<td style="width:44px;"><img class="logo-img" src="{{ $logoBase64 }}" alt=""></td>@endif
            <td>
                <div class="app-title">{{ __('home.app_name') }}</div>
                <div class="app-subtitle">تقرير السيارات العاملة أيام الجمع</div>
            </td>
            <td class="meta-cell">
                <div>تاريخ الطباعة: {{ \App\Support\LocalTime::date(now()) }}</div>
                @if($friday !== '')<div>العمل أيام الجمع: {{ $labels[$friday] }}</div>@endif
            </td>
        </tr>
    </table>

    @if($colTotals['unset'] > 0)
        <div class="note">{{ __('home.report_friday_unset_note', ['count' => $colTotals['unset']]) }}</div>
    @endif

    <div class="section">الملخص حسب المحافظة</div>
    <table class="rt">
        <thead>
            <tr>
                <th style="width:32%">المحافظة</th>
                @foreach($labels as $label)<th style="width:17%">{{ $label }}</th>@endforeach
                <th class="total-col" style="width:17%">الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @forelse($governorates as $gov)
                <tr>
                    <td class="gov">{{ $gov->name }}</td>
                    @foreach($keys as $k)
                        @php $v = $counts[$gov->id][$k] ?? 0; @endphp
                        <td style="{{ $v > 0 ? 'color:' . $color[$k] . '; font-weight:bold;' : 'color:#bbb;' }}">{{ $v }}</td>
                    @endforeach
                    <td class="total-cell">{{ array_sum($counts[$gov->id] ?? []) }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ 2 + count($keys) }}" style="color:#999; padding:30px;">لا توجد بيانات</td></tr>
            @endforelse
        </tbody>
        @if($governorates->isNotEmpty())
        <tfoot>
            <tr>
                <td style="text-align:right;">الإجمالي</td>
                @foreach($keys as $k)<td>{{ $colTotals[$k] }}</td>@endforeach
                <td>{{ array_sum($colTotals) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="section">قائمة السيارات ({{ $vehicles->count() }})</div>
    <table class="rt">
        <thead>
            <tr>
                <th style="width:6%">م</th>
                <th style="width:24%">المحافظة</th>
                <th style="width:34%">اسم السيارة</th>
                <th style="width:18%">نوع السيارة</th>
                <th style="width:18%">العمل أيام الجمع</th>
            </tr>
        </thead>
        <tbody>
            @forelse($vehicles as $i => $vehicle)
                @php $k = \App\Reports\VehicleFridayWork::keyOf($vehicle->works_on_friday); @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="name">{{ $vehicle->governorate->name ?? '—' }}</td>
                    <td class="name">{{ $vehicle->name }}</td>
                    <td>{{ $vehicle->type->name ?? '—' }}</td>
                    <td style="color:{{ $color[$k] }}; font-weight:bold;">{{ $labels[$k] }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="color:#999; padding:20px;">لا توجد سيارات</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="page-footer">
        نظام مقرات التوثيق والشهر العقاري — طُبع بتاريخ {{ \App\Support\LocalTime::stamp(now()) }}
    </div>

</body>
</html>
