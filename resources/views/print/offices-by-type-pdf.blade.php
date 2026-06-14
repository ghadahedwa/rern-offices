<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير المقرات حسب المحافظة والنوع</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: dejavusans, sans-serif; direction: rtl; font-size: 10pt; color: #1a1a1a; }

        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #c9a847; padding-bottom: 5px; margin-bottom: 10px; }
        .header-table td { vertical-align: middle; padding: 2px; }
        .logo-img { width: 48px; height: 48px; }
        .app-title { font-size: 13pt; font-weight: bold; color: #c9a847; }
        .app-subtitle { font-size: 9pt; color: #666; margin-top: 1px; }
        .meta-cell { text-align: left; font-size: 9pt; color: #666; line-height: 1.6; }

        .rt { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .rt th, .rt td { word-wrap: break-word; overflow-wrap: break-word; }
        .rt th {
            background-color: #c9a847; color: #fff; font-size: 10pt; font-weight: bold;
            padding: 6px 4px; border: 1px solid #b8962e; text-align: center; vertical-align: middle;
        }
        .rt td { border: 1px solid #ddd; padding: 6px 4px; font-size: 10pt; text-align: center; vertical-align: middle; }
        .rt td.gov { text-align: right; font-weight: bold; color: #222; background-color: #faf6ea; }
        .rt tbody tr:nth-child(even) td { background-color: #fafafa; }
        .rt tbody tr:nth-child(even) td.gov { background-color: #f5efdc; }
        .rt .total-col { font-weight: bold; background-color: #faf6ea; }
        .rt tfoot td { font-weight: bold; background-color: #ede3c2; border: 1px solid #c9a847; padding: 6px 4px; }

        .page-footer { margin-top: 10px; padding-top: 4px; border-top: 1px solid #e4e4e4; text-align: center; font-size: 8pt; color: #aaa; }
    </style>
</head>
<body>

    @php
        $grandTotal = 0;
        $colTotals  = [];
        foreach ($types as $t) { $colTotals[$t->id] = 0; }
        // عرض عمود المحافظة أوسع، الباقي بالتساوي
        $colCount  = $types->count() + 1; // أنواع + إجمالي
        $govWidth  = 18;
        $cellWidth = $colCount > 0 ? round((100 - $govWidth) / $colCount, 2) : 0;
    @endphp

    <table class="header-table">
        <tr>
            @if($logoBase64)<td style="width:44px;"><img class="logo-img" src="{{ $logoBase64 }}" alt=""></td>@endif
            <td>
                <div class="app-title">قطاع الشهر العقاري</div>
                <div class="app-subtitle">تقرير المقرات حسب المحافظة والنوع</div>
            </td>
            <td class="meta-cell">
                <div>تاريخ الطباعة: {{ now()->format('Y-m-d') }}</div>
            </td>
        </tr>
    </table>

    <table class="rt">
        <thead>
            <tr>
                <th width="{{ $govWidth }}%">المحافظة</th>
                @foreach($types as $type)
                    <th width="{{ $cellWidth }}%">{{ $type->name }}</th>
                @endforeach
                <th width="{{ $cellWidth }}%">الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @forelse($governorates as $gov)
                @php $rowTotal = 0; @endphp
                <tr>
                    <td class="gov">{{ $gov->name }}</td>
                    @foreach($types as $type)
                        @php $cnt = $map[$gov->id][$type->id] ?? 0; $rowTotal += $cnt; $colTotals[$type->id] += $cnt; @endphp
                        <td>{{ $cnt }}</td>
                    @endforeach
                    <td class="total-col">{{ $rowTotal }}</td>
                </tr>
                @php $grandTotal += $rowTotal; @endphp
            @empty
                <tr><td colspan="{{ $types->count() + 2 }}" style="text-align:center; color:#999; padding:30px;">لا توجد بيانات</td></tr>
            @endforelse
        </tbody>
        @if($governorates->isNotEmpty())
        <tfoot>
            <tr>
                <td style="text-align:right;">الإجمالي</td>
                @foreach($types as $type)
                    <td>{{ $colTotals[$type->id] }}</td>
                @endforeach
                <td>{{ $grandTotal }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="page-footer">
        نظام مقرات التوثيق والشهر العقاري — طُبع بتاريخ {{ now()->format('Y-m-d H:i') }}
    </div>

</body>
</html>
