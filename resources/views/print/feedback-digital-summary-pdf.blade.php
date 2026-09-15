<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ __('home.fr_export_digital_summary_title') }}</title>
    @include('print.includes.feedback-styles')
</head>
<body>

@include('print.includes.feedback-header', ['title' => __('home.fr_export_digital_summary_title')])

{{-- المؤشرات --}}
<table class="kpi-table">
    <tr>
        <td class="kpi">
            <div class="kpi-value">{{ $headline['total'] }}</div>
            <div class="kpi-label">{{ __('home.fr_dg_kpi_total') }}</div>
        </td>
        <td class="kpi">
            <div class="kpi-value">{{ $headline['booked_percent'] !== null ? $headline['booked_percent'].'%' : '—' }}</div>
            <div class="kpi-label">{{ __('home.fr_dg_kpi_booked') }} ({{ __('home.fr_dg_count_of_base', ['count' => $headline['booked'], 'base' => $headline['total']]) }})</div>
        </td>
        <td class="kpi">
            <div class="kpi-value">{{ $headline['score_avg'] ?? '—' }}</div>
            <div class="kpi-label">{{ __('home.fr_dg_kpi_score') }} ({{ __('home.fr_dg_of_ten') }})</div>
        </td>
        <td class="kpi">
            <div class="kpi-value">{{ $headline['on_time_percent'] !== null ? $headline['on_time_percent'].'%' : '—' }}</div>
            <div class="kpi-label">{{ __('home.fr_dg_kpi_on_time') }} ({{ __('home.fr_dg_count_of_base', ['count' => $headline['on_time'], 'base' => $headline['on_time_base']]) }})</div>
        </td>
    </tr>
</table>

{{-- التوزيعات — جدول لكل سؤال، ومقامه في عنوانه --}}
@foreach(collect($sections)->flatten(1) as $dist)
    <div class="sec">
        <div class="sec-title">
            {{ ltrim($dist['key'], 'q') }} — {{ $dist['title'] }}
            <span class="muted" style="font-size:8pt; font-weight:normal">({{ __('home.fr_dg_base', ['base' => $dist['base']]) }}@if($dist['multi']) · {{ __('home.fr_dg_multi_note') }}@endif)</span>
        </div>
        <table class="rt">
            <thead>
                <tr>
                    <th style="width:40%">{{ __('home.fr_export_answer') }}</th>
                    <th style="width:15%">{{ __('home.fr_export_opinions_count') }}</th>
                    <th style="width:15%">{{ __('home.fr_export_percent') }}</th>
                    <th style="width:30%">{{ __('home.fr_export_share') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dist['rows'] as $row)
                    <tr>
                        <td class="rt-start">{{ $row['label'] }}</td>
                        <td class="strong">{{ $row['count'] }}</td>
                        <td>{{ $row['percent'] !== null ? $row['percent'].'%' : '—' }}</td>
                        <td>@include('print.includes.feedback-bar', ['percent' => min(100, $row['percent'] ?? 0), 'width' => 45])</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endforeach

{{-- درجة المنصة --}}
<div class="sec">
    <div class="sec-title">
        205 — {{ $score['title'] }}: {{ $score['avg'] ?? '—' }} {{ __('home.fr_dg_of_ten') }}
        <span class="muted" style="font-size:8pt; font-weight:normal">({{ __('home.fr_dg_base', ['base' => $score['base']]) }})</span>
    </div>
    <table class="rt">
        <thead>
            <tr>
                <th style="width:20%">{{ __('home.fr_export_score') }}</th>
                <th style="width:20%">{{ __('home.fr_export_opinions_count') }}</th>
                <th style="width:20%">{{ __('home.fr_export_percent') }}</th>
                <th style="width:40%">{{ __('home.fr_export_share') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($score['distribution'] as $d)
                <tr>
                    <td class="strong">{{ $d['score'] }}</td>
                    <td>{{ $d['count'] }}</td>
                    <td>{{ $d['percent'] !== null ? $d['percent'].'%' : '—' }}</td>
                    <td>@include('print.includes.feedback-bar', ['percent' => $d['percent'] ?? 0, 'width' => 55])</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="rt" style="margin-top:6px">
        <thead>
            <tr>
                <th style="width:50%">{{ __('home.fr_dg_by_platform') }}</th>
                <th style="width:25%">{{ __('home.fr_export_opinions_count') }}</th>
                <th style="width:25%">{{ __('home.fr_dg_kpi_score') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($score['platforms'] as $p)
                <tr>
                    <td class="rt-start">{{ $p['label'] }}</td>
                    <td>{{ $p['count'] }}</td>
                    <td class="strong">{{ $p['avg'] ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- الاتجاه الشهري --}}
<div class="sec">
    <div class="sec-title">{{ __('home.fr_dg_trend') }}</div>
    @if(count($trend))
        <table class="rt">
            <thead>
                <tr>
                    <th style="width:16%">{{ __('home.fr_export_month') }}</th>
                    <th style="width:14%">{{ __('home.fr_export_opinions_count') }}</th>
                    <th style="width:14%">{{ __('home.fr_dg_booked_count') }}</th>
                    <th style="width:14%">{{ __('home.fr_dg_kpi_booked') }}</th>
                    <th style="width:28%">{{ __('home.fr_export_share') }}</th>
                    <th style="width:14%">{{ __('home.fr_dg_kpi_score') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($trend as $m)
                    <tr>
                        <td>{{ $m['label'] }}</td>
                        <td>{{ $m['count'] }}</td>
                        <td>{{ $m['booked'] }}</td>
                        <td class="strong">{{ $m['booked_percent'] !== null ? $m['booked_percent'].'%' : '—' }}</td>
                        <td>@include('print.includes.feedback-bar', ['percent' => $m['booked_percent'] ?? 0, 'width' => 40])</td>
                        <td>{{ $m['score_avg'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="muted">{{ __('home.fr_no_data') }}</p>
    @endif
</div>

{{-- مقارنة المقرات بنسبة الحجز --}}
<div class="sec">
    <div class="sec-title">{{ __('home.fr_dg_offices') }}</div>
    @if($offices->count() > $maxRows)
        <div class="warn">{{ __('home.fr_export_capped', ['shown' => $maxRows, 'total' => $offices->count()]) }}</div>
    @endif
    <table class="rt">
        <thead>
            <tr>
                <th style="width:30%">{{ __('home.fr_office') }}</th>
                <th style="width:14%">{{ __('home.fr_governorate') }}</th>
                <th style="width:10%">{{ __('home.fr_export_opinions_count') }}</th>
                <th style="width:12%">{{ __('home.fr_dg_kpi_booked') }}</th>
                <th style="width:10%">{{ __('home.fr_dg_kpi_score') }}</th>
                <th style="width:24%">{{ __('home.fr_export_sample_col', ['min' => $minSample]) }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($offices->take($maxRows) as $o)
                <tr>
                    <td class="rt-start">{{ $o['office'] }}</td>
                    <td>{{ $o['governorate'] }}</td>
                    <td>{{ $o['count'] }}</td>
                    <td class="strong">{{ $o['booked_percent'] !== null ? $o['booked_percent'].'%' : '—' }}</td>
                    <td>{{ $o['score_avg'] ?? '—' }}</td>
                    <td>{{ $o['enough'] ? __('home.fr_export_sample_enough') : __('home.fr_export_sample_short') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- النصوص الحرة — الأحدث (الكامل في Excel) --}}
@foreach($texts as $block)
    <div class="sec">
        <div class="sec-title">
            {{ ltrim($block['key'], 'q') }} — {{ $block['title'] }}
            <span class="muted" style="font-size:8pt; font-weight:normal">({{ __('home.fr_dg_texts_written', ['written' => $block['written'], 'base' => $block['base']]) }})</span>
        </div>
        @forelse($block['items'] as $item)
            <div class="quote">
                {{ $item['text'] }}
                <div class="src">{{ $item['office'] }} — {{ \App\Support\LocalTime::date($item['date']) }}</div>
            </div>
        @empty
            <p class="muted">{{ __('home.fr_no_data') }}</p>
        @endforelse
    </div>
@endforeach

<div class="page-footer">{{ __('home.app_name') }} — {{ \App\Support\LocalTime::stamp($generatedAt) }}</div>

</body>
</html>
