<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ __('home.fr_suggestions') }}</title>
    @include('print.includes.feedback-styles')
</head>
<body>

@include('print.includes.feedback-header', ['title' => __('home.fr_suggestions')])

@if($trashed)
    <div class="warn">{{ __('home.fr_export_trashed_note') }}</div>
@endif

@if($total > $maxRows)
    <div class="warn">{{ __('home.fr_export_capped', ['shown' => $maxRows, 'total' => $total]) }}</div>
@endif

<table class="rt">
    <thead>
        <tr>
            <th style="width:8%">{{ __('home.fr_date') }}</th>
            <th style="width:12%">{{ __('home.fr_governorate') }}</th>
            <th style="width:18%">{{ __('home.fr_office') }}</th>
            @if($personal)
                <th style="width:14%">{{ __('home.fr_citizen') }}</th>
            @endif
            <th style="width:{{ $personal ? '26%' : '34%' }}">{{ __('home.fr_topics') }}</th>
            <th style="width:{{ $personal ? '22%' : '28%' }}">{{ __('home.fr_other_suggestion') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($suggestions as $suggestion)
            <tr>
                <td>{{ \App\Support\LocalTime::date($suggestion->created_at) }}</td>
                <td class="rt-start">{{ $suggestion->governorate?->name ?? '—' }}</td>
                <td class="rt-start">{{ $suggestion->office?->name ?? __('home.fr_deleted_office') }}</td>
                @if($personal)
                    <td class="rt-start">
                        {{ $suggestion->name }}
                        <div class="muted" style="font-size:7.5pt">{{ $suggestion->national_id }} · {{ $suggestion->phone }}</div>
                    </td>
                @endif
                <td class="rt-start" style="font-size:8pt">
                    {{ $suggestion->topics->pluck('name')->implode('، ') ?: '—' }}
                </td>
                <td class="rt-start" style="font-size:8pt">{{ $suggestion->other_suggestion }}</td>
            </tr>
        @empty
            <tr><td colspan="{{ $personal ? 6 : 5 }}">{{ __('home.fr_no_suggestions') }}</td></tr>
        @endforelse
    </tbody>
</table>

<div class="page-footer">
    {{ __('home.fr_export_row_count', ['count' => $suggestions->count(), 'total' => $total]) }}
    — {{ \App\Support\LocalTime::stamp($generatedAt) }}
</div>

</body>
</html>
