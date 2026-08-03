<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير مخصّص للسيارات</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        /* الخط ثابت 10pt في كل الحالات (مقروء وقابل للطباعة على A4) */
        body { font-family: dejavusans, sans-serif; direction: rtl; font-size: 10pt; color: #1a1a1a; }

        /* ── Page Header ── */
        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #c9a847; padding-bottom: 5px; margin-bottom: 8px; }
        .header-table td { vertical-align: middle; padding: 2px; }
        .logo-img { width: 48px; height: 48px; }
        .app-title { font-size: 13pt; font-weight: bold; color: #c9a847; }
        .app-subtitle { font-size: 9pt; color: #666; margin-top: 1px; }
        .meta-cell { text-align: left; font-size: 9pt; color: #666; line-height: 1.6; }

        /* ── Report Table (عمود لكل حقل، يملأ عرض الصفحة) ── */
        .rt { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .rt th, .rt td { word-wrap: break-word; overflow-wrap: break-word; }
        .rt th {
            background-color: #c9a847; color: #fff; font-size: 10pt; font-weight: bold;
            padding: 5px 4px; border: 1px solid #b8962e; text-align: center; vertical-align: middle;
        }
        .rt td {
            border: 1px solid #ddd; padding: 5px 4px; font-size: 10pt;
            text-align: center; vertical-align: middle;
        }
        .rt td.name { font-weight: bold; color: #111; text-align: right; }
        .rt tbody tr:nth-child(even) td { background-color: #fafafa; }

        .page-footer { margin-top: 8px; padding-top: 4px; border-top: 1px solid #e4e4e4; text-align: center; font-size: 8pt; color: #aaa; }
    </style>
</head>
<body>

    @php
        $serialPct = 4;
        $weights = [];
        foreach ($columns as $key => $def) {
            $weights[$key] = $key === 'name' ? 1.8 : 1.0;
        }
        $totalWeight = array_sum($weights) ?: 1;
    @endphp

    {{-- ── Page Header ── --}}
    <table class="header-table">
        <tr>
            @if($logoBase64)<td style="width:44px;"><img class="logo-img" src="{{ $logoBase64 }}" alt=""></td>@endif
            <td>
                <div class="app-title">{{ __('home.app_name') }}</div>
                <div class="app-subtitle">تقرير مخصّص للسيارات المتنقلة</div>
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
                <th width="{{ $serialPct }}%">م</th>
                @foreach($columns as $key => $def)
                    <th width="{{ round($weights[$key] / $totalWeight * (100 - $serialPct), 2) }}%">{{ $def['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($vehicles as $vehicle)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    @foreach($columns as $key => $def)
                        <td class="{{ $key === 'name' ? 'name' : '' }}">{{ $def['value']($vehicle) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($columns) + 1 }}" style="text-align:center; color:#999; padding:30px;">لا توجد سيارات مطابقة لمحددات البحث</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="page-footer">
        نظام مقرات التوثيق والشهر العقاري — طُبع بتاريخ {{ \App\Support\LocalTime::stamp(now()) }}
    </div>

</body>
</html>
