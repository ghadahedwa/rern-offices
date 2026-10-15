<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير إحصائيات السيارات</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: dejavusans, sans-serif; direction: rtl; font-size: 9pt; color: #1a1a1a; }

        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #c9a847; padding-bottom: 5px; margin-bottom: 10px; }
        .header-table td { vertical-align: middle; padding: 2px; }
        .logo-img { width: 48px; height: 48px; }
        .app-title { font-size: 13pt; font-weight: bold; color: #c9a847; }
        .app-subtitle { font-size: 9pt; color: #666; margin-top: 1px; }
        .meta-cell { text-align: left; font-size: 9pt; color: #666; line-height: 1.6; }
        .note { font-size: 8.5pt; color: #8a6d00; background-color: #fff8e1; border: 1px solid #f0d98a; padding: 5px 7px; margin-bottom: 8px; }

        .rt { width: 100%; border-collapse: collapse; }
        .rt th {
            background-color: #c9a847; color: #fff; font-size: 8.5pt; font-weight: bold;
            padding: 5px 3px; border: 1px solid #b8962e; text-align: center; vertical-align: middle;
        }
        .rt td { border: 1px solid #ddd; padding: 4px 3px; font-size: 9pt; text-align: center; vertical-align: middle; }
        .rt td.name { text-align: right; }
        .rt td.gov-row { text-align: right; font-weight: bold; background-color: #f4f4f5; color: #444; }
        .rt td.sub { font-weight: bold; background-color: #f5efdc; }
        .rt tfoot td { font-weight: bold; background-color: #ede3c2; border: 1px solid #c9a847; padding: 5px 3px; }

        .footnote { margin-top: 6px; font-size: 8pt; color: #888; }
        .page-footer { margin-top: 10px; padding-top: 4px; border-top: 1px solid #e4e4e4; text-align: center; font-size: 8pt; color: #aaa; }
    </style>
</head>
<body>

    @php
        $groups  = $report->groups();
        $grand   = $report->grandTotals();
        $y       = $report->year();
        $partial = $report->isPartial();
        $fmt     = fn ($v) => $v === null ? '—' : number_format($v);
        $cols    = $partial ? 6 : 7;
        // مجموع النِّسَب ١٠٠٪ في الحالتين — الناقص يوزّعه mpdf عشوائياً
        $w = $partial
            ? ['name' => 33, 'avg' => 10, 'trips' => 17, 'tx' => 20, 'prev' => 0]
            : ['name' => 28, 'avg' => 10, 'trips' => 12, 'tx' => 15, 'prev' => 15];
    @endphp

    <table class="header-table">
        <tr>
            @if($logoBase64)<td style="width:44px;"><img class="logo-img" src="{{ $logoBase64 }}" alt=""></td>@endif
            <td>
                <div class="app-title">{{ __('home.app_name') }}</div>
                <div class="app-subtitle">تقرير إحصائيات السيارات المتنقلة — سنة {{ $y }}{{ $partial ? ' (حتى الآن)' : '' }}</div>
            </td>
            <td class="meta-cell">
                <div>تاريخ الطباعة: {{ \App\Support\LocalTime::date(now()) }}</div>
            </td>
        </tr>
    </table>

    @if($partial)
        <div class="note">{{ __('home.vstat_report_partial_note', ['year' => $y]) }}</div>
    @endif

    <table class="rt">
        <thead>
            <tr>
                <th style="width:{{ $w['name'] }}%">السيارة</th>
                <th style="width:{{ $w['avg'] }}%">متوسط المعاملات اليومي</th>
                <th style="width:{{ $w['avg'] }}%">متوسط الدعم اليومي</th>
                <th style="width:{{ $w['avg'] }}%">متوسط الانتقال اليومي</th>
                <th style="width:{{ $w['trips'] }}%">الانتقالات {{ $y }}</th>
                <th style="width:{{ $w['tx'] }}%">معاملات التوثيق {{ $y }}{{ $partial ? ' (حتى الآن)' : '' }}</th>
                @unless($partial)<th style="width:{{ $w['prev'] }}%">معاملات التوثيق {{ $y - 1 }}</th>@endunless
            </tr>
        </thead>
        <tbody>
            @forelse($groups as $group)
                <tr><td class="gov-row" colspan="{{ $cols }}">{{ $group['governorate']->name ?? '—' }}</td></tr>
                @foreach($group['vehicles'] as $row)
                    @php $v = $row['vehicle']; @endphp
                    <tr>
                        <td class="name">{{ $v->name }}</td>
                        <td>{{ $fmt($v->avg_daily_transactions) }}</td>
                        <td>{{ $fmt($v->avg_daily_support) }}</td>
                        <td>{{ $fmt($v->avg_daily_trips) }}</td>
                        <td>{{ $fmt($row['trips']) }}</td>
                        <td style="font-weight:bold;">{{ $fmt($row['tx']) }}</td>
                        @unless($partial)<td style="color:#666;">{{ $fmt($row['txPrev']) }}</td>@endunless
                    </tr>
                @endforeach
                <tr>
                    <td class="sub name">إجمالي {{ $group['governorate']->name ?? '' }}</td>
                    <td class="sub"></td><td class="sub"></td><td class="sub"></td>
                    <td class="sub">{{ $fmt($group['totals']['trips']) }}</td>
                    <td class="sub">{{ $fmt($group['totals']['tx']) }}</td>
                    @unless($partial)<td class="sub">{{ $fmt($group['totals']['txPrev']) }}</td>@endunless
                </tr>
            @empty
                <tr><td colspan="{{ $cols }}" style="color:#999; padding:30px;">لا توجد سيارات</td></tr>
            @endforelse
        </tbody>
        @if($groups->isNotEmpty())
        <tfoot>
            <tr>
                <td style="text-align:right;">الإجمالي العام</td>
                <td></td><td></td><td></td>
                <td>{{ $fmt($grand['trips']) }}</td>
                <td>{{ $fmt($grand['tx']) }}</td>
                @unless($partial)<td>{{ $fmt($grand['txPrev']) }}</td>@endunless
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footnote">{{ __('home.vstat_report_footnote') }}</div>

    <div class="page-footer">
        نظام مقرات التوثيق والشهر العقاري — طُبع بتاريخ {{ \App\Support\LocalTime::stamp(now()) }}
    </div>

</body>
</html>
