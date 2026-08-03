<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    @php
        $d = \Carbon\Carbon::parse($date);
        $ord = ['الأول', 'الثاني', 'الثالث', 'الرابع', 'الخامس', 'السادس', 'السابع', 'الثامن', 'التاسع', 'العاشر'];
        $chunks = $meetings->chunk(3);
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: dejavusans, sans-serif; direction: rtl; font-size: 10pt; color: #1a1a1a; }

        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #c9a847; padding-bottom: 4px; margin-bottom: 8px; }
        .header-table td { vertical-align: middle; padding: 2px; }
        .logo-img { width: 42px; height: 42px; }
        .app-title { font-size: 13pt; font-weight: bold; color: #c9a847; }
        .app-subtitle { font-size: 10pt; color: #555; margin-top: 1px; }
        .meta-cell { text-align: left; font-size: 9pt; color: #666; line-height: 1.6; }

        .agenda { width: 100%; border-collapse: collapse; }
        .agenda th, .agenda td { border: 1px solid #333; padding: 7px; font-size: 10pt; vertical-align: top; }
        .agenda th { background-color: #c9a847; color: #fff; text-align: center; font-weight: bold; }
        .agenda td.lbl { background-color: #faf6ea; font-weight: bold; text-align: center; color: #222; vertical-align: middle; height: 36mm; }
        .agenda td.day { background-color: #f5efdc; font-weight: bold; text-align: center; vertical-align: middle; font-size: 11pt; }
        .agenda td .muted { color: #666; font-size: 9pt; }
        {{-- المعنيون: نقطة لكل شخص + إزاحة معلّقة (نفس مبدأ تقرير اليوم) --}}
        .agenda td .person { padding-right: 11px; text-indent: -11px; margin-bottom: 3px; }

        .empty { text-align: center; color: #888; padding: 30px; border: 1px solid #ccc; margin-top: 10px; }
    </style>
</head>
<body>

    @if($meetings->isEmpty())
        <table class="header-table">
            <tr>
                @if($logoBase64)<td style="width:48px;"><img class="logo-img" src="{{ $logoBase64 }}" alt=""></td>@endif
                <td><div class="app-title">{{ __('home.app_name') }}</div><div class="app-subtitle">{{ __('home.meetings_title') }}</div></td>
            </tr>
        </table>
        <div class="empty">{{ __('home.no_meetings') }} — {{ $d->locale('ar')->dayName }} {{ $d->format('Y/m/d') }}</div>
    @else
        @foreach($chunks as $page => $chunk)
            @php $cols = $chunk->values(); @endphp
            <div @if(! $loop->first) style="page-break-before: always;" @endif>

                <table class="header-table">
                    <tr>
                        @if($logoBase64)<td style="width:48px;"><img class="logo-img" src="{{ $logoBase64 }}" alt=""></td>@endif
                        <td>
                            <div class="app-title">{{ __('home.app_name') }}</div>
                            <div class="app-subtitle">{{ __('home.meetings_title') }} — {{ $d->locale('ar')->dayName }} {{ $d->format('Y/m/d') }}</div>
                        </td>
                        <td class="meta-cell">
                            <div>إجمالي الاجتماعات: {{ $meetings->count() }}</div>
                            <div>صفحة {{ $page + 1 }} من {{ $chunks->count() }}</div>
                        </td>
                    </tr>
                </table>

                @php
                    $count = $cols->count();
                    $mw = round(70 / max($count, 1), 2); // عرض عمود الاجتماع حسب العدد (يملأ العرض)
                @endphp
                <table class="agenda">
                    <thead>
                        <tr>
                            <th style="width:10%;">{{ __('home.meeting_day') }}</th>
                            <th style="width:8%;"></th>
                            @foreach($cols as $k => $m)
                                @php $abs = $page * 3 + $k; @endphp
                                <th style="width:{{ $mw }}%;">{{ 'الاجتماع ' . ($ord[$abs] ?? ($abs + 1)) }}</th>
                            @endforeach
                            <th style="width:12%;">{{ __('home.meeting_notes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="day" rowspan="4">{{ $d->locale('ar')->dayName }}<br>{{ $d->format('Y/m/d') }}</td>
                            <td class="lbl">{{ __('home.meeting_subject') }}</td>
                            @foreach($cols as $m)<td>{{ $m->subject }}</td>@endforeach
                            <td rowspan="4">
                                @foreach($cols as $k => $m)
                                    @php $abs = $page * 3 + $k; @endphp
                                    @if($m->notes)<div>{{ 'الاجتماع ' . ($ord[$abs] ?? ($abs + 1)) }}: {{ $m->notes }}</div>@endif
                                @endforeach
                            </td>
                        </tr>
                        <tr>
                            <td class="lbl">{{ __('home.meeting_location') }}</td>
                            @foreach($cols as $m)
                                <td>{{ $m->location }}@if($m->time)<br><span class="muted">الساعة {{ \App\Support\LocalTime::clock($m->time) }}</span>@endif</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="lbl">{{ __('home.meeting_attendees') }}</td>
                            @foreach($cols as $m)
                                <td>@foreach($m->attendees as $att)<div class="person">• {{ $att->title ? $att->title . ' / ' . $att->name : $att->name }}</div>@endforeach</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="lbl">{{ __('home.meeting_result') }}</td>
                            @foreach($cols as $m)<td>{{ $m->result }}</td>@endforeach
                        </tr>
                    </tbody>
                </table>

            </div>
        @endforeach
    @endif

</body>
</html>
