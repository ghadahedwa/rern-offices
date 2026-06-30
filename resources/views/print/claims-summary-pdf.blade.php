<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ملخص المديونية</title>
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
        .rt th { background-color: #c9a847; color: #fff; font-size: 9pt; font-weight: bold; padding: 6px 4px; border: 1px solid #b8962e; text-align: center; }
        .rt td { border: 1px solid #ddd; padding: 5px 4px; font-size: 9pt; text-align: center; }
        .rt td.gov { text-align: right; font-weight: bold; color: #222; background-color: #faf6ea; }
        .rt tbody tr:nth-child(even) td { background-color: #fafafa; }
        .rt tbody tr:nth-child(even) td.gov { background-color: #f5efdc; }
        .rt .neg { color: #c0392b; font-weight: bold; }
        .rt .col-cancelled { color: #b8860b; }
        .rt .col-collected { color: #1e7e4f; }
        .rt tfoot td { font-weight: bold; background-color: #ede3c2; border: 1px solid #c9a847; padding: 6px 4px; }

        .page-footer { margin-top: 12px; padding-top: 4px; border-top: 1px solid #e4e4e4; text-align: center; font-size: 8pt; color: #aaa; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            @if($logoBase64)<td style="width:44px;"><img class="logo-img" src="{{ $logoBase64 }}" alt=""></td>@endif
            <td>
                <div class="app-title">قطاع الشهر العقاري</div>
                <div class="app-subtitle">ملخص مديونية المحافظات</div>
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
                <th>المطالبات (ج)</th>
                <th>الملغاة (ج)</th>
                <th>المحصل (ج)</th>
                <th>المديونية (ج)</th>
                <th>نسبة التحصيل</th>
            </tr>
        </thead>
        <tbody>
            @forelse($summary['rows'] as $row)
            <tr>
                <td class="gov">{{ $row['name'] }}</td>
                <td>{{ number_format($row['demands'], 2) }}</td>
                <td class="col-cancelled">{{ number_format($row['cancelled'], 2) }}</td>
                <td class="col-collected">{{ number_format($row['collected'], 2) }}</td>
                <td class="{{ $row['debt'] < 0 ? 'neg' : '' }}">{{ number_format($row['debt'], 2) }}</td>
                <td>{{ $row['rate'] !== null ? $row['rate'] . '%' : '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="padding:25px; color:#999;">لا توجد بيانات</td></tr>
            @endforelse
        </tbody>
        @if(count($summary['rows']))
        <tfoot>
            <tr>
                <td style="text-align:right;">الإجمالي</td>
                <td>{{ number_format($summary['totalDemands'], 2) }}</td>
                <td>{{ number_format($summary['totalCancelled'], 2) }}</td>
                <td>{{ number_format($summary['totalCollected'], 2) }}</td>
                <td class="{{ $summary['totalDebt'] < 0 ? 'neg' : '' }}">{{ number_format($summary['totalDebt'], 2) }}</td>
                <td>{{ $summary['rate'] !== null ? $summary['rate'] . '%' : '—' }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="page-footer">
        نظام مقرات التوثيق والشهر العقاري — طُبع بتاريخ {{ now()->format('Y-m-d H:i') }}
    </div>

</body>
</html>
