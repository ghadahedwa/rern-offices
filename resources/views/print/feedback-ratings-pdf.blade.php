<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ __('home.fr_ratings') }}</title>
    @include('print.includes.feedback-styles')
</head>
<body>

@include('print.includes.feedback-header', ['title' => __('home.fr_ratings')])

@if($trashed)
    <div class="warn">{{ __('home.fr_export_trashed_note') }}</div>
@endif

@if($total > $maxRows)
    <div class="warn">{{ __('home.fr_export_capped', ['shown' => $maxRows, 'total' => $total]) }}</div>
@endif

<table class="rt">
    <thead>
        <tr>
            <th style="width:{{ $personal ? '7%' : '8%' }}">{{ __('home.fr_date') }}</th>
            <th style="width:{{ $personal ? '9%' : '11%' }}">{{ __('home.fr_governorate') }}</th>
            <th style="width:{{ $personal ? '13%' : '17%' }}">{{ __('home.fr_office') }}</th>
            @if($personal)
                <th style="width:10%">{{ __('home.fr_citizen') }}</th>
            @endif
            <th style="width:{{ $personal ? '8%' : '9%' }}">{{ __('home.fr_wait_time') }}</th>
            {{-- ⚠️ الاسم المختصر مقيس لا مختار: تجاوزه يكسر الرأس سطرين
                 (راجع FeedbackRating::CRITERIA_SHORT و RatingsPdfLayoutTest) --}}
            @foreach($criteria as $field => [$label, $optional])
                <th style="width:5%">{{ $criteriaShort[$field] ?? $label }}</th>
            @endforeach
            <th style="width:5%">{{ __('home.fr_criteria_avg_short') }}</th>
            <th style="width:5%">{{ __('home.fr_export_overall_short') }}</th>
            {{-- مجموع النِّسَب ١٠٠٪ بالضبط في الحالتين --}}
            <th style="width:{{ $personal ? '13%' : '15%' }}">{{ __('home.fr_notes') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($ratings as $rating)
            <tr>
                <td>{{ \App\Support\LocalTime::date($rating->created_at) }}</td>
                <td class="rt-start">{{ $rating->governorate?->name ?? '—' }}</td>
                <td class="rt-start">{{ $rating->office?->name ?? __('home.fr_deleted_office') }}</td>
                @if($personal)
                    <td class="rt-start">
                        {{ $rating->name }}
                        <div class="muted" style="font-size:7.5pt">{{ $rating->national_id }} · {{ $rating->phone }}</div>
                    </td>
                @endif
                <td>{{ $waitTimes[$rating->wait_time] ?? $rating->wait_time }}</td>
                @foreach($criteria as $field => [$label, $optional])
                    {{-- المحور الاختياري غير المُجاب يبقى شرطة، لا صفراً --}}
                    <td>{{ $rating->{$field} ?? '—' }}</td>
                @endforeach
                <td class="strong">{{ $rating->criteriaAverage() ?? '—' }}</td>
                <td class="strong">{{ $rating->overall_rating ?? '—' }}</td>
                <td class="rt-start" style="font-size:7.5pt">{{ $rating->notes }}</td>
            </tr>
        @empty
            <tr><td colspan="{{ ($personal ? 4 : 3) + count($criteria) + 3 }}">{{ __('home.fr_no_ratings') }}</td></tr>
        @endforelse
    </tbody>
</table>

{{-- بيان الأعمدة: كل رأس مختصر يُفَكّ هنا بعنوانه الكامل. المطبوع بلا tooltip. --}}
<div class="legend">
    <b>{{ __('home.fr_export_legend') }}</b>
    @foreach($criteria as $field => [$label, $optional])
        {{ $criteriaShort[$field] ?? $label }} = {{ $label }}@if($optional) ({{ __('home.fr_export_optional') }})@endif &nbsp;·&nbsp;
    @endforeach
    {{ __('home.fr_criteria_avg_short') }} = {{ __('home.fr_criteria_avg') }} &nbsp;·&nbsp;
    {{ __('home.fr_export_overall_short') }} = {{ __('home.fr_overall_rating') }}
</div>

<div class="page-footer">
    {{ __('home.fr_export_row_count', ['count' => $ratings->count(), 'total' => $total]) }}
    — {{ \App\Support\LocalTime::stamp($generatedAt) }}
</div>

</body>
</html>
