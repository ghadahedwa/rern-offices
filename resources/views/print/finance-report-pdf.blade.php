<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $report->title() }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: khalidart, dejavusans, sans-serif; direction: rtl; font-size: 9pt; color: #1a1a1a; }

        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #c9a847; padding-bottom: 5px; margin-bottom: 8px; }
        .header-table td { vertical-align: middle; padding: 2px; }
        .logo-img { width: 48px; height: 48px; }
        .app-title { font-size: 13pt; font-weight: bold; color: #c9a847; }
        .app-subtitle { font-size: 9pt; color: #666; margin-top: 1px; }
        .meta-cell { text-align: left; font-size: 8.5pt; color: #666; }

        .filter-bar { background-color: #faf6ea; border: 1px solid #ede3c2; padding: 5px 8px; margin-bottom: 8px; font-size: 9pt; color: #555; }
        .filter-bar b { color: #222; }

        .rt { width: 100%; border-collapse: collapse; }
        .rt th { background-color: #c9a847; color: #fff; font-size: 8.5pt; font-weight: bold; padding: 5px 3px; border: 1px solid #b8962e; text-align: center; vertical-align: middle; white-space: nowrap; }
        .rt td { border: 1px solid #ddd; padding: 4px 3px; font-size: 8.5pt; text-align: center; vertical-align: middle; }
        .rt td.rt-start { text-align: right; }
        .rt td.pending { color: #b45309; background-color: #fffbeb; font-size: 7.5pt; }
        .rt td.muted { color: #bbb; }
        .rt tr.total td { background-color: #faf6ea; font-weight: bold; border-top: 2px solid #c9a847; }
        .inc { color: #047857; }
        .exp { color: #b91c1c; }

        .sec { margin-top: 10px; }
        .sec-title { font-size: 10pt; font-weight: bold; color: #b8962e; border-right: 3px solid #c9a847; padding-right: 6px; margin-bottom: 5px; }
        .words td { border-bottom: 1px dashed #e4e4e4; padding: 3px 4px; font-size: 8.5pt; }
        .note { margin-top: 5px; font-size: 7.5pt; color: #888; }
    </style>
</head>
<body>
@php
    $isUnit   = $report->kind === 'unit';
    $accounts = $report->accounts();
    $fmt      = fn ($v) => $v === null ? '—' : number_format($v);
    // ⚠️ مجموع نِسَب <th> = ١٠٠٪ — الناقص يوزّعه mpdf عشوائياً
    [$first, $plain, $tel, $last] = $isUnit ? [16, 13, 11, 12] : [22, 12, 10, 12];
@endphp

<table class="header-table">
    <tr>
        @if($logoBase64)
            <td style="width:44px;"><img class="logo-img" src="{{ $logoBase64 }}" alt=""></td>
        @endif
        <td>
            <div class="app-title">{{ __('home.app_name') }}</div>
            <div class="app-subtitle">{{ __('home.fin_reports') }}</div>
        </td>
        <td class="meta-cell">{{ __('home.ct_rep_generated_at') }}: {{ \App\Support\LocalTime::stamp($generatedAt) }}</td>
    </tr>
</table>

<div class="filter-bar">
    <b>{{ $report->title() }}</b> &nbsp;·&nbsp; <b>{{ __('home.fin_rep_period') }}:</b> {{ $report->periodLabel() }}
</div>

<table class="rt">
    <thead>
        <tr>
            <th rowspan="2" style="width: {{ $first }}%">{{ $isUnit ? __('home.fin_month') : __('home.fin_unit') }}</th>
            @foreach($accounts as $account)
                @if($account->isLedger())
                    <th colspan="3" style="width: {{ $tel * 3 }}%">{{ __('home.fin_tab_'.$account->key) }}</th>
                @else
                    <th rowspan="2" style="width: {{ $plain }}%">{{ __('home.fin_tab_'.$account->key) }}</th>
                @endif
            @endforeach
            <th rowspan="2" style="width: {{ $last }}%">{{ $isUnit ? __('home.fin_rep_orders') : __('home.fin_rep_entered') }}</th>
        </tr>
        <tr>
            <th>{{ __('home.fin_income') }}</th>
            <th>{{ __('home.fin_expense') }}</th>
            <th>{{ $isUnit ? __('home.fin_rep_balance') : __('home.fin_rep_balance_end') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data['rows'] as $row)
            <tr>
                <td class="rt-start">{{ $isUnit ? $row['label'] : $row['unit']->name }}</td>
                @foreach($accounts as $account)
                    @if($account->isLedger())
                        @if($isUnit && ! $row['telecom'])
                            <td colspan="3" class="{{ $row['ended'] ? 'pending' : 'muted' }}">{{ $row['ended'] ? __('home.fin_not_entered') : '—' }}</td>
                        @else
                            <td class="inc">{{ number_format($row['telecom']['income']) }}</td>
                            <td class="exp">{{ number_format($row['telecom']['expense']) }}</td>
                            <td><b>{{ $fmt($row['telecom']['balance']) }}</b></td>
                        @endif
                    @elseif($isUnit && $row['cells'][$account->key] === null)
                        <td class="{{ $row['ended'] ? 'pending' : 'muted' }}">{{ $row['ended'] ? __('home.fin_not_entered') : '—' }}</td>
                    @else
                        <td>{{ $fmt($row['cells'][$account->key]) }}</td>
                    @endif
                @endforeach
                <td>
                    {{ $isUnit
                        ? __('home.fin_rep_orders_count', ['count' => $row['orders']])
                        : __('home.fin_units_of', ['entered' => $row['entered'], 'count' => $row['required']]) }}
                </td>
            </tr>
        @endforeach
        <tr class="total">
            <td class="rt-start">{{ __('home.fin_total_label') }}</td>
            @foreach($accounts as $account)
                @if($account->isLedger())
                    <td class="inc">{{ number_format($data['totals']['telecom']['income']) }}</td>
                    <td class="exp">{{ number_format($data['totals']['telecom']['expense']) }}</td>
                    <td>{{ $fmt($data['totals']['telecom']['balance']) }}</td>
                @else
                    <td>{{ $fmt($data['totals']['cells'][$account->key]) }}</td>
                @endif
            @endforeach
            <td>{{ $isUnit ? __('home.fin_rep_orders_count', ['count' => $data['totals']['orders']]) : '' }}</td>
        </tr>
    </tbody>
</table>
<div class="note">{{ $isUnit ? __('home.fin_rep_unit_note') : __('home.fin_rep_total_note') }}</div>

{{-- إجمالي الفترة بالحروف — لكل حساب، واتصالات رصيدها في آخر الفترة --}}
<div class="sec">
    <div class="sec-title">{{ __('home.fin_rep_in_words') }}</div>
    <table class="words" style="width: 100%; border-collapse: collapse;">
        @foreach($accounts as $account)
            @php $value = $account->isLedger() ? $data['totals']['telecom']['balance'] : $data['totals']['cells'][$account->key]; @endphp
            <tr>
                <td style="width: 22%"><b>{{ __('home.fin_tab_'.$account->key) }}</b>@if($account->isLedger()) ({{ __('home.fin_rep_balance_end') }})@endif</td>
                <td style="width: 15%">{{ $fmt($value) }}</td>
                <td>{{ $value !== null ? \App\Support\Finance\AmountInWords::pounds($value) : '—' }}</td>
            </tr>
        @endforeach
    </table>
</div>

@if($items)
    <div class="sec">
        <div class="sec-title">{{ __('home.fin_rep_items_title') }}</div>
        <table class="rt">
            <thead><tr><th style="width: 8%">#</th><th style="width: 62%">{{ __('home.fin_rep_item') }}</th><th style="width: 30%">{{ __('home.fin_rep_amount') }}</th></tr></thead>
            <tbody>
                @foreach($items as $name => $amount)
                    <tr><td>{{ $loop->iteration }}</td><td class="rt-start">{{ $name }}</td><td>{{ number_format($amount) }}</td></tr>
                @endforeach
                <tr class="total"><td></td><td class="rt-start">{{ __('home.fin_total_label') }}</td><td>{{ number_format(array_sum($items)) }}</td></tr>
            </tbody>
        </table>
    </div>
@endif
</body>
</html>
