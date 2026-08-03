<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    @php
        $d = \Carbon\Carbon::parse($date);
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

        {{-- الجدول بعرض الصفحة كاملاً وحشو أقل: يفسح للأعمدة الضيقة (الساعة) --}}
        .agenda-wrap { width: 100%; margin: 0 auto; }
        .agenda { width: 100%; border-collapse: collapse; }
        .agenda th, .agenda td { border: 1px solid #333; padding: 5px 3px; font-size: 14pt; font-weight: bold; text-align: center; vertical-align: top; }
        .agenda th { background-color: #c9a847; color: #fff; white-space: nowrap; }
        .agenda td.no { color: #666; background-color: #faf6ea; }
        .agenda td.time { white-space: nowrap; }
        {{-- المعنيون: نقطة لكل شخص + إزاحة معلّقة، فالاسم الذي يلفّ سطرين لا يبدو شخصين --}}
        .agenda td.people { text-align: right; }
        .agenda td.people .person { padding-right: 11px; text-indent: -11px; margin-bottom: 3px; }
        .agenda .muted { color: #666; font-size: 9pt; }

        .empty { text-align: center; color: #888; padding: 30px; border: 1px solid #ccc; margin-top: 10px; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            @if($logoBase64)<td style="width:48px;"><img class="logo-img" src="{{ $logoBase64 }}" alt=""></td>@endif
            <td>
                <div class="app-title">{{ __('home.app_name') }}</div>
                <div class="app-subtitle">{{ __('home.meetings_title') }} — {{ $d->locale('ar')->dayName }} {{ $d->format('Y/m/d') }}</div>
            </td>
            @if($meetings->isNotEmpty())
                <td class="meta-cell">
                    <div>{{ __('home.meetings_total') }}: {{ $meetings->count() }}</div>
                </td>
            @endif
        </tr>
    </table>

    @if($meetings->isEmpty())
        <div class="empty">{{ __('home.no_meetings') }} — {{ $d->locale('ar')->dayName }} {{ $d->format('Y/m/d') }}</div>
    @else
        <div class="agenda-wrap">
            <table class="agenda">
                <thead>
                    <tr>
                        <th style="width:4%;">{{ __('home.meeting_no') }}</th>
                        <th style="width:19%;">{{ __('home.meeting_subject') }}</th>
                        <th style="width:13%;">{{ __('home.meeting_location') }}</th>
                        <th style="width:11%;">{{ __('home.meeting_time') }}</th>
                        <th style="width:19%;">{{ __('home.meeting_attendees') }}</th>
                        <th style="width:17%;">{{ __('home.meeting_result') }}</th>
                        <th style="width:17%;">{{ __('home.meeting_notes') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($meetings as $k => $m)
                        <tr>
                            <td class="no">{{ $k + 1 }}</td>
                            <td>{{ $m->subject }}</td>
                            <td>{{ $m->location }}</td>
                            <td class="time">{{ \App\Support\LocalTime::clock($m->time) }}</td>
                            <td class="people">@foreach($m->attendees as $att)<div class="person">• {{ $att->title ? $att->title . ' / ' . $att->name : $att->name }}</div>@endforeach</td>
                            <td>{{ $m->result }}</td>
                            <td>{{ $m->notes }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</body>
</html>
