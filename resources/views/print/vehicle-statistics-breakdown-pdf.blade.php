<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ __('home.report_vehicle_breakdown_title') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: dejavusans, sans-serif; direction: rtl; font-size: 9pt; color: #1a1a1a; }

        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #c9a847; padding-bottom: 5px; margin-bottom: 10px; }
        .header-table td { vertical-align: middle; padding: 2px; }
        .logo-img { width: 48px; height: 48px; }
        .app-title { font-size: 13pt; font-weight: bold; color: #c9a847; }
        .app-subtitle { font-size: 9pt; color: #666; margin-top: 1px; }
        .meta-cell { text-align: left; font-size: 9pt; color: #666; line-height: 1.6; }
        .note { font-size: 9pt; color: #8a6d00; background-color: #fff8e1; border: 1px solid #f0d98a; padding: 8px; }

        .rt { width: 100%; border-collapse: collapse; }
        .rt th {
            background-color: #c9a847; color: #fff; font-size: 8pt; font-weight: bold;
            padding: 5px 2px; border: 1px solid #b8962e; text-align: center; vertical-align: middle; white-space: nowrap;
        }
        .rt td { border: 1px solid #ddd; padding: 4px 2px; font-size: 8.5pt; text-align: center; vertical-align: middle; }
        .rt td.name { text-align: right; }
        .rt td.gov-row { text-align: right; font-weight: bold; background-color: #f4f4f5; color: #444; }
        .rt td.sub { font-weight: bold; background-color: #f5efdc; }
        .rt td.tot { font-weight: bold; }
        .rt tfoot td { font-weight: bold; background-color: #ede3c2; border: 1px solid #c9a847; padding: 5px 2px; }
        .muted { color: #777; font-weight: normal; font-size: 7.5pt; }

        .footnote { margin-top: 6px; font-size: 8pt; color: #888; }
        .page-footer { margin-top: 10px; padding-top: 4px; border-top: 1px solid #e4e4e4; text-align: center; font-size: 8pt; color: #aaa; }
    </style>
</head>
<body>

    @php
        $columns   = $report->columns();
        $byVehicle = $report->level() === 'vehicle';
        $fmt       = fn ($v) => $v === null ? '—' : number_format($v);
        $span      = count($columns) + ($byVehicle ? 2 : 4);
    @endphp

    <table class="header-table">
        <tr>
            @if($logoBase64)<td style="width:44px;"><img class="logo-img" src="{{ $logoBase64 }}" alt=""></td>@endif
            <td>
                <div class="app-title">{{ __('home.app_name') }}</div>
                <div class="app-subtitle">{{ __('home.report_vehicle_breakdown_title') }} — @if($report->isVertical()){{ $report->subjectLabel() }} — @endif{{ $report->metricLabel() }} — {{ $report->isUnavailable() ? __('home.vbd_period_year', ['year' => $report->year()]) : $report->periodLabel() }}</div>
            </td>
            <td class="meta-cell">
                <div>{{ \App\Support\LocalTime::date(now()) }}</div>
            </td>
        </tr>
    </table>

    @if($report->isUnavailable())
        <div class="note">{{ __('home.vbd_tx_monthly_unavailable') }}</div>
    @elseif($report->isVertical())
    @php $vt = $report->vertical(); $series = $report->series(); $w = round(70 / count($series), 2); @endphp
    <table class="rt">
        <thead>
            <tr>
                <th style="width:30%; text-align:right;">{{ $report->period() === 'monthly' ? __('home.vbd_col_month') : __('home.vbd_col_year') }}</th>
                @foreach($series as $m)
                <th style="width:{{ $w }}%;{{ $m === 'trips_all' ? ' background-color:#b8962e;' : '' }}">{{ \App\Reports\VehicleStatisticsBreakdown::seriesLabel($m) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($vt['rows'] as $row)
            <tr>
                <td class="name">{{ $row['label'] }}</td>
                @foreach($series as $m)<td{!! $m === 'trips_all' ? ' class="tot"' : '' !!}>{{ $fmt($row['values'][$m]) }}</td>@endforeach
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td style="text-align:right;">{{ __('home.vbd_col_total') }}@if($vt['vehicles'] > 1) <span class="muted">({{ __('home.vbd_reporting_of', ['n' => $vt['reporting'], 'm' => $vt['vehicles']]) }})</span>@endif</td>
                @foreach($series as $m)<td>{{ $fmt($vt['total'][$m]) }}</td>@endforeach
            </tr>
        </tfoot>
    </table>

    <div class="footnote">{{ __('home.vbd_footnote') }}</div>
    @else
    @php
        $groups = $report->groups();
        $grand  = $report->grandTotal();
        // إجمالي الانتقالات: تحت كل فترة ثلاثة أعمدة (عادية · فورية · إجمالي)
        $sub    = $report->splitSeries();
        $per    = max(count($sub), 1);
        $vals   = fn ($row, $key) => $sub ? array_map(fn ($m) => $row['split'][$m]['cells'][$key] ?? null, $sub) : [$row['cells'][$key] ?? null];
        $tots   = fn ($row) => $sub ? array_map(fn ($m) => $row['split'][$m]['total'] ?? null, $sub) : [$row['total']];
        $bold   = fn ($i) => $sub && $i === $per - 1 ? ' style="font-weight:bold;"' : '';
        $span   = (count($columns) + 1) * $per + ($byVehicle ? 1 : 3);
    @endphp
    <table class="rt">
        <thead>
            <tr>
                <th @if($sub) rowspan="2" @endif style="text-align:right;">{{ $byVehicle ? __('home.vbd_level_vehicle') : __('home.vbd_level_governorate') }}</th>
                @unless($byVehicle)
                <th @if($sub) rowspan="2" @endif>{{ __('home.vbd_col_vehicles') }}</th>
                <th @if($sub) rowspan="2" @endif>{{ __('home.vbd_col_reporting') }}</th>
                @endunless
                @foreach($columns as $label)<th colspan="{{ $per }}">{{ $label }}</th>@endforeach
                <th colspan="{{ $per }}" style="background-color:#b8962e;">{{ __('home.vbd_col_total') }}</th>
            </tr>
            @if($sub)
            <tr>
                @foreach(array_keys($columns + ['total' => '']) as $key)
                    @foreach($sub as $m)<th style="font-weight:normal;{{ $key === 'total' ? ' background-color:#b8962e;' : '' }}">{{ \App\Reports\VehicleStatisticsBreakdown::subLabel($m) }}</th>@endforeach
                @endforeach
            </tr>
            @endif
        </thead>
        <tbody>
            @forelse($groups as $group)
                @php $t = $group['total']; $govName = $group['governorate']->name ?? '—'; @endphp
                @if($byVehicle)
                    <tr><td class="gov-row" colspan="{{ $span }}">{{ $govName }}</td></tr>
                    @foreach($group['vehicleRows'] as $row)
                        <tr>
                            <td class="name">{{ $row['vehicle']->name }}</td>
                            @foreach($columns as $key => $label)@foreach($vals($row, $key) as $i => $val)<td{!! $bold($i) !!}>{{ $fmt($val) }}</td>@endforeach @endforeach
                            @foreach($tots($row) as $val)<td class="tot">{{ $fmt($val) }}</td>@endforeach
                        </tr>
                    @endforeach
                    <tr>
                        <td class="sub name">{{ __('home.vstat_gov_total', ['name' => $govName]) }} <span class="muted">({{ __('home.vbd_reporting_of', ['n' => $t['reporting'], 'm' => $t['vehicles']]) }})</span></td>
                        @foreach($columns as $key => $label)@foreach($vals($t, $key) as $val)<td class="sub">{{ $fmt($val) }}</td>@endforeach @endforeach
                        @foreach($tots($t) as $val)<td class="sub">{{ $fmt($val) }}</td>@endforeach
                    </tr>
                @else
                    <tr>
                        <td class="name">{{ $govName }}</td>
                        <td>{{ $t['vehicles'] }}</td>
                        <td style="{{ $t['reporting'] < $t['vehicles'] ? 'color:#b45309;font-weight:bold;' : '' }}">{{ $t['reporting'] }}</td>
                        @foreach($columns as $key => $label)@foreach($vals($t, $key) as $i => $val)<td{!! $bold($i) !!}>{{ $fmt($val) }}</td>@endforeach @endforeach
                        @foreach($tots($t) as $val)<td class="tot">{{ $fmt($val) }}</td>@endforeach
                    </tr>
                @endif
            @empty
                <tr><td colspan="{{ $span }}" style="color:#999; padding:30px;">{{ __('home.no_vehicles') }}</td></tr>
            @endforelse
        </tbody>
        @if($groups->isNotEmpty())
        <tfoot>
            <tr>
                <td style="text-align:right;">{{ __('home.vbd_grand_total') }}@if($byVehicle) <span class="muted">({{ __('home.vbd_reporting_of', ['n' => $grand['reporting'], 'm' => $grand['vehicles']]) }})</span>@endif</td>
                @unless($byVehicle)
                <td>{{ $grand['vehicles'] }}</td>
                <td>{{ $grand['reporting'] }}</td>
                @endunless
                @foreach($columns as $key => $label)@foreach($vals($grand, $key) as $val)<td>{{ $fmt($val) }}</td>@endforeach @endforeach
                @foreach($tots($grand) as $val)<td>{{ $fmt($val) }}</td>@endforeach
            </tr>
        </tfoot>
        @endif
    </table>

<div class="footnote">{{ __('home.vbd_footnote') }}</div>
    @endif

    <div class="page-footer">
        {{ __('home.app_name') }} — {{ \App\Support\LocalTime::stamp(now()) }}
    </div>

</body>
</html>
