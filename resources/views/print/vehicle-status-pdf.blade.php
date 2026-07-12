<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير الحالة التشغيلية للسيارات</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: dejavusans, sans-serif; direction: rtl; font-size: 9pt; color: #1a1a1a; }

        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #c9a847; padding-bottom: 5px; margin-bottom: 10px; }
        .header-table td { vertical-align: middle; padding: 2px; }
        .logo-img { width: 48px; height: 48px; }
        .app-title { font-size: 13pt; font-weight: bold; color: #c9a847; }
        .app-subtitle { font-size: 9pt; color: #666; margin-top: 1px; }
        .meta-cell { text-align: left; font-size: 9pt; color: #666; line-height: 1.6; }

        .rt { width: 100%; border-collapse: collapse; }
        .rt th, .rt td { word-wrap: break-word; overflow-wrap: break-word; }
        .rt th {
            background-color: #c9a847; color: #fff; font-size: 9pt; font-weight: bold;
            padding: 5px 4px; border: 1px solid #b8962e; text-align: center; vertical-align: middle;
        }
        .rt th.total-col { background-color: #b8962e; }
        .rt td { border: 1px solid #ddd; padding: 5px 4px; font-size: 9.5pt; text-align: center; vertical-align: middle; }
        .rt td.gov { text-align: right; font-weight: bold; color: #222; background-color: #faf6ea; }
        .rt td.total-cell { font-weight: bold; background-color: #faf6ea; }
        .rt .c-working { color: #2a7; font-weight: bold; }
        .rt .c-maintenance { color: #c90; font-weight: bold; }
        .rt .c-stopped { color: #a33; font-weight: bold; }
        .rt tbody tr:nth-child(even) td { background-color: #fafafa; }
        .rt tbody tr:nth-child(even) td.gov,
        .rt tbody tr:nth-child(even) td.total-cell { background-color: #f5efdc; }
        .rt tfoot td { font-weight: bold; background-color: #ede3c2; border: 1px solid #c9a847; padding: 5px 4px; }

        .page-footer { margin-top: 10px; padding-top: 4px; border-top: 1px solid #e4e4e4; text-align: center; font-size: 8pt; color: #aaa; }
    </style>
</head>
<body>

    @php
        $statusKeys = array_keys($statuses);
        $colTotals  = array_fill_keys($statusKeys, 0);
        $statusClass = ['working' => 'c-working', 'maintenance' => 'c-maintenance', 'stopped' => 'c-stopped'];
    @endphp

    <table class="header-table">
        <tr>
            @if($logoBase64)<td style="width:44px;"><img class="logo-img" src="{{ $logoBase64 }}" alt=""></td>@endif
            <td>
                <div class="app-title">{{ __('home.app_name') }}</div>
                <div class="app-subtitle">تقرير الحالة التشغيلية للسيارات المتنقلة</div>
            </td>
            <td class="meta-cell">
                <div>تاريخ الطباعة: {{ now()->format('Y-m-d') }}</div>
            </td>
        </tr>
    </table>

    <table class="rt">
        <thead>
            <tr>
                <th>المحافظة</th>
                @foreach($statuses as $key => $label)
                    <th>{{ $label }}</th>
                @endforeach
                <th class="total-col">الإجمالي</th>
                <th class="total-col">نسبة الجاهزية</th>
            </tr>
        </thead>
        <tbody>
            @forelse($governorates as $gov)
                @php
                    $rowTotal = 0;
                    foreach ($statusKeys as $st) { $rowTotal += ($counts[$gov->id][$st] ?? 0); }
                    $working = $counts[$gov->id]['working'] ?? 0;
                    $readiness = $rowTotal > 0 ? round($working / $rowTotal * 100) . '%' : '—';
                @endphp
                <tr>
                    <td class="gov">{{ $gov->name }}</td>
                    @foreach($statusKeys as $st)
                        @php $v = $counts[$gov->id][$st] ?? 0; $colTotals[$st] += $v; @endphp
                        <td class="{{ $v > 0 ? ($statusClass[$st] ?? '') : '' }}">{{ $v }}</td>
                    @endforeach
                    <td class="total-cell">{{ $rowTotal }}</td>
                    <td class="total-cell">{{ $readiness }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ 3 + count($statuses) }}" style="text-align:center; color:#999; padding:30px;">لا توجد بيانات</td></tr>
            @endforelse
        </tbody>
        @if($governorates->isNotEmpty())
        @php
            $grandTotal = array_sum($colTotals);
            $grandReadiness = $grandTotal > 0 ? round(($colTotals['working'] ?? 0) / $grandTotal * 100) . '%' : '—';
        @endphp
        <tfoot>
            <tr>
                <td style="text-align:right;">الإجمالي</td>
                @foreach($statusKeys as $st)
                    <td>{{ $colTotals[$st] }}</td>
                @endforeach
                <td>{{ $grandTotal }}</td>
                <td>{{ $grandReadiness }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="page-footer">
        نظام مقرات التوثيق والشهر العقاري — طُبع بتاريخ {{ now()->format('Y-m-d H:i') }}
    </div>

</body>
</html>
