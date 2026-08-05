<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ __('home.fr_export_dashboard_title') }}</title>
    @include('print.includes.feedback-styles')
</head>
<body>

@include('print.includes.feedback-header', ['title' => __('home.fr_export_dashboard_title')])

{{-- المؤشرات --}}
<table class="kpi-table">
    <tr>
        <td class="kpi">
            <div class="kpi-value">{{ $kpis['ratings'] }}</div>
            <div class="kpi-label">{{ __('home.fr_total_ratings') }}</div>
        </td>
        <td class="kpi">
            <div class="kpi-value">{{ $kpis['avg_overall'] ?? '—' }}</div>
            <div class="kpi-label">{{ __('home.fr_avg_overall') }} ({{ __('home.fr_of_five') }})</div>
        </td>
        <td class="kpi">
            <div class="kpi-value">{{ $kpis['suggestions'] }}</div>
            <div class="kpi-label">{{ __('home.fr_total_suggestions') }}</div>
        </td>
        <td class="kpi">
            <div class="kpi-value">{{ $kpis['rated_offices'] }}</div>
            <div class="kpi-label">{{ __('home.fr_rated_offices') }}</div>
        </td>
    </tr>
</table>

{{-- متوسط المحاور — عدد المجيبين عمود مستقل لأن المحور السادس اختياري --}}
<div class="sec">
    <div class="sec-title">{{ __('home.fr_criteria_averages') }}</div>
    <table class="rt">
        <thead>
            <tr>
                <th style="width:46%">{{ __('home.fr_export_criterion') }}</th>
                <th style="width:12%">{{ __('home.fr_export_avg_of_five') }}</th>
                <th style="width:12%">{{ __('home.fr_answered_count') }}</th>
                <th style="width:30%">{{ __('home.fr_export_level') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($criteria as $c)
                <tr>
                    <td class="rt-start">
                        {{ $c['label'] }}
                        @if($c['optional'])<span class="muted"> ({{ __('home.fr_export_optional') }})</span>@endif
                    </td>
                    <td class="strong">{{ $c['avg'] ?? '—' }}</td>
                    <td>{{ $c['count'] }}</td>
                    <td>@include('print.includes.feedback-bar', ['percent' => ($c['avg'] ?? 0) * 20, 'width' => 45])</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="sec-note">{{ __('home.fr_export_optional_note') }}</div>
</div>

{{-- توزيع مدة الانتظار --}}
<div class="sec">
    <div class="sec-title">{{ __('home.fr_wait_distribution') }}</div>
    <table class="rt">
        <thead>
            <tr>
                <th style="width:40%">{{ __('home.fr_wait_time') }}</th>
                <th style="width:14%">{{ __('home.fr_export_opinions_count') }}</th>
                <th style="width:12%">{{ __('home.fr_export_percent') }}</th>
                <th style="width:34%">{{ __('home.fr_export_share') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($waits as $w)
                <tr>
                    <td class="rt-start">{{ $w['label'] }}</td>
                    <td class="strong">{{ $w['count'] }}</td>
                    <td>{{ $w['percent'] }}%</td>
                    <td>@include('print.includes.feedback-bar', ['percent' => $w['percent'], 'width' => 45])</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- الاتجاه الشهري — جدول لا مخطط: mpdf لا يشغّل JS، والجدول أدق في المطبوع --}}
<div class="sec">
    <div class="sec-title">{{ __('home.fr_monthly_trend') }}</div>
    @if(count($trend))
        <table class="rt">
            <thead>
                <tr>
                    <th style="width:22%">{{ __('home.fr_export_month') }}</th>
                    <th style="width:22%">{{ __('home.fr_export_opinions_count') }}</th>
                    <th style="width:22%">{{ __('home.fr_export_avg_of_five') }}</th>
                    <th style="width:34%">{{ __('home.fr_export_level') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($trend as $m)
                    <tr>
                        <td>{{ $m['label'] }}</td>
                        <td>{{ $m['count'] }}</td>
                        <td class="strong">{{ $m['avg'] }}</td>
                        <td>@include('print.includes.feedback-bar', ['percent' => $m['avg'] * 20, 'width' => 45])</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="sec-note">{{ __('home.fr_no_data') }}</div>
    @endif
</div>

{{-- ترتيب المحافظات --}}
<div class="sec">
    <div class="sec-title">{{ __('home.fr_governorates_ranking') }}</div>
    <div class="sec-note">{{ __('home.fr_export_sample_note', ['min' => $minSample]) }}</div>
    @if($govRanking->isNotEmpty())
        <table class="rt">
            <thead>
                <tr>
                    <th style="width:44%">{{ __('home.fr_governorate') }}</th>
                    <th style="width:18%">{{ __('home.fr_export_opinions_count') }}</th>
                    <th style="width:18%">{{ __('home.fr_export_avg_of_five') }}</th>
                    <th style="width:20%">{{ __('home.fr_export_sample_col', ['min' => $minSample]) }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($govRanking as $g)
                    <tr>
                        <td class="rt-start">{{ $g['governorate'] }}</td>
                        <td>{{ $g['count'] }}</td>
                        <td class="strong">{{ $g['avg'] }}</td>
                        <td class="{{ $g['enough'] ? '' : 'muted' }}">
                            {{ $g['enough'] ? __('home.fr_export_sample_enough') : __('home.fr_export_sample_short') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="sec-note">{{ __('home.fr_no_data') }}</div>
    @endif
</div>

{{-- ترتيب المقرات — كل المقرات المقيَّمة، وما دون حد العينة معلَّم خارج الترتيب --}}
<div class="sec">
    <div class="sec-title">{{ __('home.fr_offices_ranking') }}</div>
    <div class="sec-note">{{ __('home.fr_export_sample_note', ['min' => $minSample]) }}</div>
    @if($offices->isNotEmpty())
        <table class="rt">
            <thead>
                <tr>
                    <th style="width:38%">{{ __('home.fr_office') }}</th>
                    <th style="width:20%">{{ __('home.fr_governorate') }}</th>
                    <th style="width:14%">{{ __('home.fr_export_opinions_count') }}</th>
                    <th style="width:14%">{{ __('home.fr_export_avg_of_five') }}</th>
                    <th style="width:14%">{{ __('home.fr_export_sample_col', ['min' => $minSample]) }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($offices as $o)
                    <tr>
                        <td class="rt-start">{{ $o['office'] }}</td>
                        <td class="rt-start">{{ $o['governorate'] }}</td>
                        <td>{{ $o['count'] }}</td>
                        <td class="strong">{{ $o['avg'] }}</td>
                        <td class="{{ $o['enough'] ? '' : 'muted' }}">
                            {{ $o['enough'] ? __('home.fr_export_sample_enough') : __('home.fr_export_sample_short') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="sec-note">{{ __('home.fr_no_data') }}</div>
    @endif
</div>

{{-- أولويات المقترحات --}}
<div class="sec">
    <div class="sec-title">{{ __('home.fr_top_topics') }}</div>
    @if($priority['topics']->isNotEmpty())
        <table class="rt">
            <thead>
                <tr>
                    <th style="width:52%">{{ __('home.fr_export_topic') }}</th>
                    <th style="width:26%">{{ __('home.fr_export_domain') }}</th>
                    <th style="width:22%">{{ __('home.fr_export_suggestions_count') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($priority['topics'] as $t)
                    <tr>
                        <td class="rt-start">{{ $t['name'] }}</td>
                        <td class="rt-start">{{ $t['domain'] }}</td>
                        <td class="strong">{{ $t['count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="sec-note">{{ __('home.fr_no_data') }}</div>
    @endif
</div>

@if($priority['domains']->isNotEmpty())
    <div class="sec">
        <div class="sec-title">{{ __('home.fr_domains_distribution') }}</div>
        <table class="rt">
            <thead>
                <tr>
                    <th style="width:60%">{{ __('home.fr_export_domain') }}</th>
                    <th style="width:40%">{{ __('home.fr_export_suggestions_count') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($priority['domains'] as $d)
                    <tr>
                        <td class="rt-start">{{ $d['name'] }}</td>
                        <td class="strong">{{ $d['count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- نصوص حرة — أغنى ما في البيانات، وتُعرض بلا أي بيانات شخصية --}}
@if($freeTexts['notes']->isNotEmpty() || $freeTexts['others']->isNotEmpty())
    <div class="sec">
        <div class="sec-title">{{ __('home.fr_free_texts') }}</div>
        @foreach($freeTexts['notes'] as $note)
            <div class="quote">
                {{ $note->notes }}
                <div class="src">{{ $note->office?->name ?? __('home.fr_deleted_office') }} — {{ \App\Support\LocalTime::date($note->created_at) }}</div>
            </div>
        @endforeach
        @foreach($freeTexts['others'] as $other)
            <div class="quote">
                {{ $other->other_suggestion }}
                <div class="src">{{ $other->office?->name ?? __('home.fr_deleted_office') }} — {{ \App\Support\LocalTime::date($other->created_at) }}</div>
            </div>
        @endforeach
    </div>
@endif

{{-- ملخص المحاولات المرفوضة --}}
@if($rejected->isNotEmpty())
    <div class="sec">
        <div class="sec-title">{{ __('home.fr_rejected_summary') }}</div>
        <table class="rt">
            <thead>
                <tr>
                    <th style="width:60%">{{ __('home.fr_reason') }}</th>
                    <th style="width:40%">{{ __('home.fr_export_attempts_count') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rejected as $reason => $count)
                    <tr>
                        <td class="rt-start">{{ __('home.fr_reason_'.$reason) }}</td>
                        <td class="strong">{{ $count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="page-footer">{{ __('home.app_name') }} — {{ \App\Support\LocalTime::stamp($generatedAt) }}</div>

</body>
</html>
