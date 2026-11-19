<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ __('home.fr_opinions') }}</title>
    @include('print.includes.feedback-styles')
</head>
<body>

@include('print.includes.feedback-header', ['title' => __('home.fr_opinions').' — '.($type !== '' ? __('home.fr_'.$type.'s') : __('home.fr_all_types'))])

@if($total > $maxRows)
    <div class="warn">{{ __('home.fr_export_capped', ['shown' => $maxRows, 'total' => $total]) }}</div>
@endif

<table class="rt">
    <thead>
        <tr>
            <th style="width:8%">{{ __('home.fr_date') }}</th>
            <th style="width:7%">{{ __('home.fr_type') }}</th>
            <th style="width:10%">{{ __('home.fr_governorate') }}</th>
            <th style="width:{{ $personal ? '17%' : '20%' }}">{{ __('home.fr_office') }}</th>
            @if($personal)
                <th style="width:15%">{{ __('home.fr_citizen') }}</th>
            @endif
            <th style="width:{{ $personal ? '43%' : '55%' }}">{{ __('home.fr_opinion_text') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ \App\Support\LocalTime::date($row->created_at) }}</td>
                <td>
                    {{ __('home.fr_type_'.$row->kind) }}
                    @if($row->overall_rating !== null)
                        <div class="muted" style="font-size:7.5pt">{{ (int) $row->overall_rating }}/5</div>
                    @endif
                </td>
                <td class="rt-start">{{ $row->governorate_name ?? '—' }}</td>
                <td class="rt-start">{{ $row->office_name ?? __('home.fr_deleted_office') }}</td>
                @if($personal)
                    <td class="rt-start">
                        {{ $row->name }}
                        <div class="muted" style="font-size:7.5pt">{{ \App\Support\FeedbackResults\OpinionsQuery::isAnonymous($row) ? __('home.fr_anonymous') : implode(' · ', array_filter([$row->national_id, $row->phone])) }}</div>
                    </td>
                @endif
                <td class="rt-start" style="font-size:8pt">
                    {{ filled($row->opinion) ? $row->opinion : '—' }}
                    @if($row->kind === 'suggestion' && ($topics[$row->id] ?? '') !== '')
                        <div class="muted" style="font-size:7.5pt">{{ __('home.fr_topics') }}: {{ $topics[$row->id] }}</div>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="{{ $personal ? 6 : 5 }}">{{ __('home.fr_no_opinions') }}</td></tr>
        @endforelse
    </tbody>
</table>

<div class="page-footer">
    {{ __('home.fr_export_row_count', ['count' => $rows->count(), 'total' => $total]) }}
    — {{ \App\Support\LocalTime::stamp($generatedAt) }}
</div>

</body>
</html>
