<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ __('home.fr_msg_'.$type) }}</title>
    @include('print.includes.feedback-styles')
</head>
<body>

@include('print.includes.feedback-header', ['title' => __('home.fr_msg_'.$type)])

@if($trashed)
    <div class="warn">{{ __('home.fr_export_trashed_note') }}</div>
@endif

@if($total > $maxRows)
    <div class="warn">{{ __('home.fr_export_capped', ['shown' => $maxRows, 'total' => $total]) }}</div>
@endif

@php
    // ⚠️ مجموع نِسَب الرؤوس = ١٠٠٪ في كل تفريعة — الناقص يوزّعه mpdf عشوائياً
    $hasEmployee = (bool) $config['employee'];
    $bodyWidth   = 100 - 8 - 12 - 18 - ($personal ? 14 : 0) - ($hasEmployee ? 14 : 0);
    $colspan     = 4 + ($personal ? 1 : 0) + ($hasEmployee ? 1 : 0);
@endphp

<table class="rt">
    <thead>
        <tr>
            <th style="width:8%">{{ __('home.fr_date') }}</th>
            <th style="width:12%">{{ __('home.fr_governorate') }}</th>
            <th style="width:18%">{{ __('home.fr_office') }}</th>
            @if($personal)
                <th style="width:14%">{{ __('home.fr_citizen') }}</th>
            @endif
            @if($hasEmployee)
                <th style="width:14%">{{ __('home.fr_msg_employee') }}</th>
            @endif
            <th style="width:{{ $bodyWidth }}%">{{ __('home.fr_msg_body') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($messages as $msg)
            <tr>
                <td>{{ \App\Support\LocalTime::date($msg->created_at) }}</td>
                <td class="rt-start">{{ $msg->governorate?->name ?? '—' }}</td>
                <td class="rt-start">{{ $msg->office?->name ?? ($msg->office_id ? __('home.fr_deleted_office') : '—') }}</td>
                @if($personal)
                    <td class="rt-start">
                        {{ $msg->name }}
                        <div class="muted" style="font-size:7.5pt">{{ $msg->isAnonymous() ? __('home.fr_anonymous') : implode(' · ', array_filter([$msg->national_id, $msg->phone])) }}</div>
                    </td>
                @endif
                @if($hasEmployee)
                    <td class="rt-start">{{ $msg->employee_name ?: '—' }}</td>
                @endif
                <td class="rt-start" style="font-size:8pt">{{ $msg->body }}</td>
            </tr>
        @empty
            <tr><td colspan="{{ $colspan }}">{{ __('home.fr_no_messages') }}</td></tr>
        @endforelse
    </tbody>
</table>

<div class="page-footer">
    {{ __('home.fr_export_row_count', ['count' => $messages->count(), 'total' => $total]) }}
    — {{ \App\Support\LocalTime::stamp($generatedAt) }}
</div>

</body>
</html>
