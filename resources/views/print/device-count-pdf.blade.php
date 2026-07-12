<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>بيان الأجهزة العددي</title>
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
            background-color: #c9a847; color: #fff; font-size: 8.5pt; font-weight: bold;
            padding: 4px 3px; border: 1px solid #b8962e; text-align: center; vertical-align: middle;
        }
        .rt th.grp-broken { background-color: #a85; }
        .rt td { border: 1px solid #ddd; padding: 4px 3px; font-size: 9pt; text-align: center; vertical-align: middle; }
        .rt td.gov { text-align: right; font-weight: bold; color: #222; background-color: #faf6ea; }
        .rt tbody tr:nth-child(even) td { background-color: #fafafa; }
        .rt tbody tr:nth-child(even) td.gov { background-color: #f5efdc; }
        .rt .sub { font-weight: bold; background-color: #faf6ea; }
        .rt .broken-cell { color: #a33; }
        .rt tfoot td { font-weight: bold; background-color: #ede3c2; border: 1px solid #c9a847; padding: 4px 3px; }

        .page-footer { margin-top: 10px; padding-top: 4px; border-top: 1px solid #e4e4e4; text-align: center; font-size: 8pt; color: #aaa; }
    </style>
</head>
<body>

    @php
        $workingKeys = array_keys($workingCols);
        $hasWorking  = ! empty($workingCols);
        $hasBroken   = $brokenTypes->isNotEmpty();
        $wColTotals  = array_fill_keys($workingKeys, 0);
        $bColTotals  = array_fill_keys($brokenTypes->pluck('id')->all(), 0);
    @endphp

    <table class="header-table">
        <tr>
            @if($logoBase64)<td style="width:44px;"><img class="logo-img" src="{{ $logoBase64 }}" alt=""></td>@endif
            <td>
                <div class="app-title">{{ __('home.app_name') }}</div>
                <div class="app-subtitle">بيان الأجهزة العددي</div>
            </td>
            <td class="meta-cell">
                <div>تاريخ الطباعة: {{ now()->format('Y-m-d') }}</div>
            </td>
        </tr>
    </table>

    <table class="rt">
        <thead>
            <tr>
                <th rowspan="2">المحافظة</th>
                @if($hasWorking)
                    <th colspan="{{ count($workingCols) }}">الأجهزة التى تعمل</th>
                @endif
                @if($hasBroken)
                    <th colspan="{{ $brokenTypes->count() }}" class="grp-broken">الأجهزة المعطلة</th>
                @endif
            </tr>
            <tr>
                @foreach($workingCols as $col => $label)
                    <th>{{ $label }}</th>
                @endforeach
                @if($hasBroken)
                    @foreach($brokenTypes as $type)
                        <th class="grp-broken">{{ $type->name }}</th>
                    @endforeach
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($governorates as $gov)
                <tr>
                    <td class="gov">{{ $gov->name }}</td>
                    @foreach($workingKeys as $col)
                        @php $v = $sums[$gov->id][$col] ?? 0; $wColTotals[$col] += $v; @endphp
                        <td>{{ $v }}</td>
                    @endforeach
                    @if($hasBroken)
                        @foreach($brokenTypes as $type)
                            @php $v = $brokenSums[$gov->id][$type->id] ?? 0; $bColTotals[$type->id] += $v; @endphp
                            <td class="broken-cell">{{ $v }}</td>
                        @endforeach
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ 1 + count($workingCols) + ($hasBroken ? $brokenTypes->count() : 0) }}" style="text-align:center; color:#999; padding:30px;">لا توجد بيانات</td></tr>
            @endforelse
        </tbody>
        @if($governorates->isNotEmpty())
        <tfoot>
            <tr>
                <td style="text-align:right;">الإجمالي</td>
                @foreach($workingKeys as $col)
                    <td>{{ $wColTotals[$col] }}</td>
                @endforeach
                @if($hasBroken)
                    @foreach($brokenTypes as $type)
                        <td class="broken-cell">{{ $bColTotals[$type->id] }}</td>
                    @endforeach
                @endif
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="page-footer">
        نظام مقرات التوثيق والشهر العقاري — طُبع بتاريخ {{ now()->format('Y-m-d H:i') }}
    </div>

</body>
</html>
