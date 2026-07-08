<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير التغطية الجغرافية للسيارات</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: dejavusans, sans-serif; direction: rtl; font-size: 8.5pt; color: #1a1a1a; }

        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #c9a847; padding-bottom: 5px; margin-bottom: 10px; }
        .header-table td { vertical-align: middle; padding: 2px; }
        .logo-img { width: 48px; height: 48px; }
        .app-title { font-size: 13pt; font-weight: bold; color: #c9a847; }
        .app-subtitle { font-size: 9pt; color: #666; margin-top: 1px; }
        .meta-cell { text-align: left; font-size: 9pt; color: #666; line-height: 1.6; }

        .rt { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .rt th, .rt td { word-wrap: break-word; overflow-wrap: break-word; }
        .rt th {
            background-color: #c9a847; color: #fff; font-size: 8.5pt; font-weight: bold;
            padding: 4px 3px; border: 1px solid #b8962e; text-align: center; vertical-align: middle;
        }
        .rt td { border: 1px solid #ddd; padding: 4px 3px; font-size: 8pt; text-align: center; vertical-align: top; }
        .rt td.gov { text-align: right; font-weight: bold; color: #222; background-color: #faf6ea; }
        .rt td.vname { text-align: right; font-weight: bold; color: #111; background-color: #fcfaf3; }
        .rt tbody tr:nth-child(even) td { background-color: #fafafa; }
        .rt tbody tr:nth-child(even) td.gov { background-color: #f5efdc; }
        .rt .none { color: #bbb; }

        .page-footer { margin-top: 10px; padding-top: 4px; border-top: 1px solid #e4e4e4; text-align: center; font-size: 8pt; color: #aaa; }
    </style>
</head>
<body>

    @php $dayKeys = array_keys($days); @endphp

    <table class="header-table">
        <tr>
            @if($logoBase64)<td style="width:44px;"><img class="logo-img" src="{{ $logoBase64 }}" alt=""></td>@endif
            <td>
                <div class="app-title">قطاع الشهر العقاري</div>
                <div class="app-subtitle">تقرير التغطية الجغرافية — جدول التمركز الأسبوعي</div>
            </td>
            <td class="meta-cell">
                <div>تاريخ الطباعة: {{ now()->format('Y-m-d') }}</div>
                <div>عدد السيارات: {{ $rows->count() }}</div>
            </td>
        </tr>
    </table>

    <table class="rt">
        <thead>
            <tr>
                <th width="11%">المحافظة</th>
                <th width="12%">السيارة</th>
                @foreach($days as $key => $label)
                    <th width="{{ round(77 / count($days), 2) }}%">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td class="gov">{{ $row['governorate_name'] }}</td>
                    <td class="vname">{{ $row['name'] }}</td>
                    @foreach($dayKeys as $day)
                        @php $addr = $row['days'][$day] ?? null; @endphp
                        <td class="{{ $addr ? '' : 'none' }}">{{ $addr ?? '—' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ 2 + count($days) }}" style="text-align:center; color:#999; padding:30px;">لا توجد بيانات</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="page-footer">
        نظام مقرات التوثيق والشهر العقاري — طُبع بتاريخ {{ now()->format('Y-m-d H:i') }}
    </div>

</body>
</html>
