<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>كشف حساب محافظة</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: dejavusans, sans-serif; direction: rtl; font-size: 9pt; color: #1a1a1a; }

        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #c9a847; padding-bottom: 5px; margin-bottom: 10px; }
        .header-table td { vertical-align: middle; padding: 2px; }
        .logo-img { width: 48px; height: 48px; }
        .app-title { font-size: 13pt; font-weight: bold; color: #c9a847; }
        .app-subtitle { font-size: 9pt; color: #666; margin-top: 1px; }
        .meta-cell { text-align: left; font-size: 9pt; color: #666; line-height: 1.6; }

        .info { margin-bottom: 8px; font-size: 9.5pt; color: #333; }
        .info b { color: #111; }

        .rt { width: 100%; border-collapse: collapse; }
        .rt th { background-color: #c9a847; color: #fff; font-size: 9pt; font-weight: bold; padding: 6px 4px; border: 1px solid #b8962e; text-align: center; }
        .rt td { border: 1px solid #ddd; padding: 5px 4px; font-size: 9pt; text-align: center; }
        .rt td.txt { text-align: right; }
        .rt tbody tr:nth-child(even) td { background-color: #fafafa; }
        .rt .opening td { background-color: #f5efdc; font-weight: bold; }
        .rt .neg { color: #c0392b; font-weight: bold; }
        .rt .col-collected { color: #1e7e4f; }
        .rt .col-cancelled { color: #b8860b; }
        .rt tfoot td { font-weight: bold; background-color: #ede3c2; border: 1px solid #c9a847; padding: 6px 4px; }

        .page-footer { margin-top: 12px; padding-top: 4px; border-top: 1px solid #e4e4e4; text-align: center; font-size: 8pt; color: #aaa; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            @if($logoBase64)<td style="width:44px;"><img class="logo-img" src="{{ $logoBase64 }}" alt=""></td>@endif
            <td>
                <div class="app-title">{{ __('home.app_name') }}</div>
                <div class="app-subtitle">كشف حساب مطالبات محافظة</div>
            </td>
            <td class="meta-cell">
                <div>تاريخ الطباعة: {{ now()->format('Y-m-d') }}</div>
            </td>
        </tr>
    </table>

    <div class="info">
        <b>المحافظة:</b> {{ $gov->name }} &nbsp;|&nbsp;
        <b>الفترة:</b> من {{ $fromLabel }} إلى {{ $toLabel }}
    </div>

    <table class="rt">
        <thead>
            <tr>
                <th>السنة</th>
                <th>الشهر</th>
                <th>المطالبات (ج)</th>
                <th>الملغاة (ج)</th>
                <th>صافي المطالبات (ج)</th>
                <th>المحصل (ج)</th>
                <th>المديونية (ج)</th>
            </tr>
        </thead>
        <tbody>
            <tr class="opening">
                <td class="txt" colspan="6">مديونية ما قبل الفترة</td>
                <td class="{{ $statement['opening'] < 0 ? 'neg' : '' }}">{{ number_format($statement['opening'], 2) }}</td>
            </tr>
            @forelse($statement['rows'] as $row)
            <tr>
                <td>{{ $row['year'] }}</td>
                <td>{{ $months[$row['month']] ?? $row['month'] }}</td>
                <td>{{ $row['demand'] ? number_format($row['demand'], 2) : '—' }}</td>
                <td class="col-cancelled">{{ $row['cancelled'] ? number_format($row['cancelled'], 2) : '—' }}</td>
                <td>{{ number_format($row['net'], 2) }}</td>
                <td class="col-collected">{{ $row['collected'] ? number_format($row['collected'], 2) : '—' }}</td>
                <td class="{{ $row['balance'] < 0 ? 'neg' : '' }}">{{ number_format($row['balance'], 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="padding:25px; color:#999;">لا توجد حركات خلال الفترة</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="txt">الإجمالي</td>
                <td>{{ number_format($statement['totalDemand'], 2) }}</td>
                <td>{{ number_format($statement['totalCancelled'], 2) }}</td>
                <td>{{ number_format($statement['totalNet'], 2) }}</td>
                <td>{{ number_format($statement['totalCollected'], 2) }}</td>
                <td class="{{ $statement['closing'] < 0 ? 'neg' : '' }}">{{ number_format($statement['closing'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="info" style="margin-top:10px;">
        <b>صافي مديونية الفترة:</b> {{ number_format($statement['periodNet'], 2) }} جنيه
        &nbsp;|&nbsp;
        <b>إجمالي المديونية:</b> {{ number_format($statement['closing'], 2) }} جنيه
    </div>

    <div class="page-footer">
        نظام مقرات التوثيق والشهر العقاري — طُبع بتاريخ {{ now()->format('Y-m-d H:i') }}
    </div>

</body>
</html>
