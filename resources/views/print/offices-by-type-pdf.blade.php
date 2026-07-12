<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير المقرات حسب المحافظة والنوع والوصف</title>
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
            padding: 5px 3px; border: 1px solid #b8962e; text-align: center; vertical-align: middle;
        }
        .rt th.grp-loc { background-color: #a85; }
        .rt td { border: 1px solid #ddd; padding: 5px 3px; font-size: 9pt; text-align: center; vertical-align: middle; }
        .rt td.gov { text-align: right; font-weight: bold; color: #222; background-color: #faf6ea; }
        .rt td.total-col { font-weight: bold; background-color: #faf6ea; }
        .rt tbody tr:nth-child(even) td { background-color: #fafafa; }
        .rt tbody tr:nth-child(even) td.gov, .rt tbody tr:nth-child(even) td.total-col { background-color: #f5efdc; }
        .rt tfoot td { font-weight: bold; background-color: #ede3c2; border: 1px solid #c9a847; padding: 5px 3px; }

        .page-footer { margin-top: 10px; padding-top: 4px; border-top: 1px solid #e4e4e4; text-align: center; font-size: 8pt; color: #aaa; }
    </style>
</head>
<body>

    @php
        $hasTypes      = $types->isNotEmpty();
        $hasLocations  = $locations->isNotEmpty();
        $typeColTotals = array_fill_keys($types->pluck('id')->all(), 0);
        $locColTotals  = array_fill_keys($locations->pluck('id')->all(), 0);
        $totalCols     = 1 + $types->count() + ($hasTypes ? 1 : 0) + $locations->count() + ($hasLocations ? 1 : 0);
    @endphp

    <table class="header-table">
        <tr>
            @if($logoBase64)<td style="width:44px;"><img class="logo-img" src="{{ $logoBase64 }}" alt=""></td>@endif
            <td>
                <div class="app-title">{{ __('home.app_name') }}</div>
                <div class="app-subtitle">تقرير المقرات حسب المحافظة والنوع والوصف</div>
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
                @if($hasTypes)<th colspan="{{ $types->count() + 1 }}">نوع المقر</th>@endif
                @if($hasLocations)<th colspan="{{ $locations->count() + 1 }}" class="grp-loc">وصف الموقع</th>@endif
            </tr>
            <tr>
                @foreach($types as $type)<th>{{ $type->name }}</th>@endforeach
                @if($hasTypes)<th>الإجمالي</th>@endif
                @foreach($locations as $loc)<th class="grp-loc">{{ $loc->name }}</th>@endforeach
                @if($hasLocations)<th class="grp-loc">الإجمالي</th>@endif
            </tr>
        </thead>
        <tbody>
            @forelse($governorates as $gov)
                <tr>
                    <td class="gov">{{ $gov->name }}</td>
                    @php $typeRowTotal = 0; $locRowTotal = 0; @endphp
                    @foreach($types as $type)
                        @php $v = $typeCounts[$gov->id][$type->id] ?? 0; $typeColTotals[$type->id] += $v; $typeRowTotal += $v; @endphp
                        <td>{{ $v }}</td>
                    @endforeach
                    @if($hasTypes)<td class="total-col">{{ $typeRowTotal }}</td>@endif
                    @foreach($locations as $loc)
                        @php $v = $locationCounts[$gov->id][$loc->id] ?? 0; $locColTotals[$loc->id] += $v; $locRowTotal += $v; @endphp
                        <td>{{ $v }}</td>
                    @endforeach
                    @if($hasLocations)<td class="total-col">{{ $locRowTotal }}</td>@endif
                </tr>
            @empty
                <tr><td colspan="{{ $totalCols }}" style="text-align:center; color:#999; padding:30px;">لا توجد بيانات</td></tr>
            @endforelse
        </tbody>
        @if($governorates->isNotEmpty())
        <tfoot>
            <tr>
                <td style="text-align:right;">الإجمالي</td>
                @foreach($types as $type)<td>{{ $typeColTotals[$type->id] }}</td>@endforeach
                @if($hasTypes)<td>{{ array_sum($typeColTotals) }}</td>@endif
                @foreach($locations as $loc)<td>{{ $locColTotals[$loc->id] }}</td>@endforeach
                @if($hasLocations)<td>{{ array_sum($locColTotals) }}</td>@endif
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="page-footer">
        نظام مقرات التوثيق والشهر العقاري — طُبع بتاريخ {{ now()->format('Y-m-d H:i') }}
    </div>

</body>
</html>
