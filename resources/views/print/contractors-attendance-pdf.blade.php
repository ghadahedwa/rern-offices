<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    @include('print.includes.contractors-styles')
</head>
<body>

@php
    /*
     * ⚠️ **مجموع نِسَب `<th>` = ١٠٠٪ بالضبط** في كل تفريعة — الناقص يوزّعه mpdf
     *    عشوائياً فتنكسر الرؤوس. وعدد أعمدة الحالات متغيّر، فآخر عمودٍ يأخذ
     *    الباقي بالطرح لا بالقسمة (القسمة تترك كسراً).
     */
    $statusCount = max($statuses->count(), 1);

    $fixed = match ($level) {
        'office'     => [22, 22, 11, 10, 12],   // العامل · المقر · العمل · حضر · غير مراجَع
        'contractor' => [26, 18, 12, 11, 13],   // المقر · المدة · العمل · حضر · غير مراجَع
        default      => [24, 12, 12, 11, 13],   // المحافظة · العاملون · العمل · حضر · غير مراجَع
    };

    $remaining  = 100 - array_sum($fixed);
    $statusWide = floor($remaining / $statusCount);
    $lastWide   = $remaining - ($statusWide * ($statusCount - 1));
@endphp

{{-- الترويسة --}}
<table class="header-table">
    <tr>
        @if($logoBase64)
            <td style="width:44px;"><img class="logo-img" src="{{ $logoBase64 }}" alt=""></td>
        @endif
        <td>
            <div class="app-title">{{ __('home.app_name') }}</div>
            <div class="app-subtitle">{{ $title }}</div>
        </td>
        <td class="meta-cell">
            <div>{{ __('home.ct_rep_generated_at') }}: {{ \App\Support\LocalTime::stamp($generatedAt) }}</div>
        </td>
    </tr>
</table>

{{-- سطر الفلتر المطبَّق --}}
<div class="filter-bar">
    @foreach($query->describe() as [$label, $value])
        <b>{{ $label }}:</b> {{ $value }}@if(! $loop->last) &nbsp;·&nbsp; @endif
    @endforeach
</div>

{{-- تفكيك أيام العمل --}}
<div class="breakdown">
    <b>{{ __('home.ct_rep_breakdown', [
        'total'    => $breakdown['total'],
        'weekend'  => $breakdown['weekend'],
        'holidays' => $breakdown['holidays'],
        'working'  => $breakdown['working'],
    ]) }}</b>
    @if(!empty($holidays))
        <div class="holidays">{{ __('home.ct_rep_holidays_in_range') }}: {{ implode(' · ', array_unique(array_values($holidays))) }}</div>
    @endif
</div>

@if($capped)
    <div class="warn">{{ __('home.ct_rep_pdf_capped', ['count' => $maxRows]) }}</div>
@endif

@php
    $hasRows = $level === 'governorates' ? count($groups) > 0 : count($rows) > 0;
@endphp

@if(! $hasRows)
    <div class="empty">{{ __('home.ct_rep_empty') }}</div>
@else

{{-- بطاقة العامل — تقرير العامل وحده --}}
@if($level === 'contractor' && $subject)
    <div class="sec">
        <div class="sec-title">{{ $subject->name }}@if($subject->profession) — {{ $subject->profession->name }}@endif @if($subject->phone) · {{ $subject->phone }}@endif</div>
    </div>
@endif

<table class="rt">
    <thead>
        <tr>
            @if($level === 'governorates')
                <th style="width:{{ $fixed[0] }}%">{{ __('home.ct_rep_governorate') }}</th>
                <th style="width:{{ $fixed[1] }}%">{{ __('home.ct_rep_col_contractors') }}</th>
            @elseif($level === 'office')
                <th style="width:{{ $fixed[0] }}%">{{ __('home.ct_rep_contractor') }}</th>
                <th style="width:{{ $fixed[1] }}%">{{ __('home.ct_rep_office_col') }}</th>
            @else
                <th style="width:{{ $fixed[0] }}%">{{ __('home.ct_rep_office_col') }}</th>
                <th style="width:{{ $fixed[1] }}%">{{ __('home.ct_rep_assignment_period') }}</th>
            @endif

            <th style="width:{{ $fixed[2] }}%">{{ __('home.ct_rep_col_working') }}</th>
            <th style="width:{{ $fixed[3] }}%">{{ __('home.ct_rep_col_present') }}</th>
            <th style="width:{{ $fixed[4] }}%">{{ __('home.ct_rep_col_unreviewed') }}</th>

            @foreach($statuses as $status)
                <th style="width:{{ $loop->last ? $lastWide : $statusWide }}%">{{ $status->name }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @if($level === 'governorates')
            @foreach($groups as $group)
                <tr>
                    <td class="rt-start">{{ $group['governorate_name'] ?? '—' }}</td>
                    <td>{{ $group['contractors'] }}</td>
                    <td>{{ $group['working'] }}</td>
                    <td>{{ $group['present'] }}</td>
                    <td>{{ $group['unreviewed'] }}</td>
                    @foreach($statuses as $status)
                        @php $value = $group['exceptions'][$status->id] ?? 0; @endphp
                        <td class="{{ $value ? '' : 'muted' }}">{{ $value }}</td>
                    @endforeach
                </tr>
            @endforeach
        @else
            @foreach($rows as $row)
                <tr>
                    @if($level === 'office')
                        <td class="rt-start">
                            {{ $row['contractor_name'] }}
                            @if($row['profession'])<div class="sub">{{ $row['profession'] }}</div>@endif
                        </td>
                        <td class="rt-start">{{ $row['office_name'] }}</td>
                    @else
                        <td class="rt-start">{{ $row['office_name'] }}</td>
                        <td class="sub">{{ $row['started_on'] }} — {{ $row['ended_on'] ?? __('home.ct_rep_open_assignment') }}</td>
                    @endif

                    <td>{{ $row['working'] }}</td>
                    <td>{{ $row['present'] }}</td>
                    <td>{{ $row['unreviewed'] }}</td>
                    @foreach($statuses as $status)
                        @php $value = $row['exceptions'][$status->id] ?? 0; @endphp
                        <td class="{{ $value ? '' : 'muted' }}">{{ $value }}</td>
                    @endforeach
                </tr>
            @endforeach
        @endif
    </tbody>
    <tfoot>
        <tr>
            <td class="rt-start">{{ __('home.ct_rep_total') }}</td>
            <td>{{ $level === 'governorates' ? $contractors : '' }}</td>
            <td>{{ $totals['working'] }}</td>
            <td>{{ $totals['present'] }}</td>
            <td>{{ $totals['unreviewed'] }}</td>
            @foreach($statuses as $status)
                <td>{{ $totals['exceptions'][$status->id] ?? 0 }}</td>
            @endforeach
        </tr>
    </tfoot>
</table>

<div class="equation">{{ __('home.ct_rep_equation') }}</div>

{{-- تفصيل التواريخ — ما يميّز تقرير العامل: «غاب يوم ٢» لا «غاب ٢» --}}
@if($level === 'contractor')
    <div class="sec">
        <div class="sec-title">{{ __('home.ct_rep_days_detail') }}</div>
        @if(!empty($exceptions))
            <div class="days">
                @foreach($exceptions as $day)
                    <span class="day">{{ $day['date'] }} — {{ $day['status'] }}</span>
                @endforeach
            </div>
        @else
            <div class="days muted">{{ __('home.ct_rep_no_exceptions') }}</div>
        @endif
    </div>
@endif

@endif

<div class="page-footer">
    {{ __('home.app_name') }} — {{ \App\Support\LocalTime::stamp($generatedAt) }}
</div>

</body>
</html>
