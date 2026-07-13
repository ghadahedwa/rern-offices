<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    @php
        $d = \Carbon\Carbon::parse($date);
        $ord = ['الأول', 'الثاني', 'الثالث', 'الرابع', 'الخامس', 'السادس', 'السابع', 'الثامن', 'التاسع', 'العاشر'];
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: dejavusans, sans-serif; direction: rtl; font-size: 10pt; color: #1a1a1a; }

        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #c9a847; padding-bottom: 4px; margin-bottom: 10px; }
        .header-table td { vertical-align: middle; padding: 2px; }
        .logo-img { width: 46px; height: 46px; }
        .app-title { font-size: 14pt; font-weight: bold; color: #c9a847; }
        .app-subtitle { font-size: 10pt; color: #555; margin-top: 1px; }
        .meta-cell { text-align: left; font-size: 9pt; color: #666; line-height: 1.6; }

        .agenda { width: 100%; border-collapse: collapse; }
        .agenda th, .agenda td { border: 1px solid #333; padding: 6px; font-size: 9.5pt; vertical-align: top; }
        .agenda th { background-color: #c9a847; color: #fff; text-align: center; font-weight: bold; }
        .agenda td.lbl { background-color: #faf6ea; font-weight: bold; text-align: center; color: #222; }
        .agenda td.day { background-color: #f5efdc; font-weight: bold; text-align: center; }
        .agenda td .muted { color: #666; font-size: 8.5pt; }

        .empty { text-align: center; color: #888; padding: 30px; border: 1px solid #ccc; margin-top: 10px; }
        .footer { margin-top: 10px; padding-top: 4px; border-top: 1px solid #e4e4e4; text-align: center; font-size: 8pt; color: #aaa; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            @if($logoBase64)<td style="width:52px;"><img class="logo-img" src="{{ $logoBase64 }}" alt=""></td>@endif
            <td>
                <div class="app-title">{{ __('home.app_name') }}</div>
                <div class="app-subtitle">{{ __('home.meetings_title') }}</div>
            </td>
            <td class="meta-cell">
                <div>عدد الاجتماعات: {{ $meetings->count() }}</div>
                <div>تاريخ الطباعة: {{ now()->format('Y-m-d') }}</div>
            </td>
        </tr>
    </table>

    @if($meetings->isEmpty())
        <div class="empty">{{ __('home.no_meetings') }} — {{ $d->locale('ar')->dayName }} {{ $d->format('Y/m/d') }}</div>
    @else
        <table class="agenda">
            <thead>
                <tr>
                    <th style="width:90px;">{{ __('home.meeting_day') }}</th>
                    <th style="width:70px;"></th>
                    @foreach($meetings as $i => $m)
                        <th>{{ 'الاجتماع ' . ($ord[$i] ?? ($i + 1)) }}</th>
                    @endforeach
                    <th style="width:130px;">{{ __('home.meeting_notes') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="day" rowspan="4">{{ $d->locale('ar')->dayName }}<br>{{ $d->format('Y/m/d') }}</td>
                    <td class="lbl">{{ __('home.meeting_subject') }}</td>
                    @foreach($meetings as $m)<td>{{ $m->subject }}</td>@endforeach
                    <td rowspan="4">
                        @foreach($meetings as $i => $m)
                            @if($m->notes)<div>{{ 'الاجتماع ' . ($ord[$i] ?? ($i + 1)) }}: {{ $m->notes }}</div>@endif
                        @endforeach
                    </td>
                </tr>
                <tr>
                    <td class="lbl">{{ __('home.meeting_location') }}</td>
                    @foreach($meetings as $m)
                        <td>{{ $m->location ?: '—' }}@if($m->time)<br><span class="muted">الساعة {{ substr($m->time, 0, 5) }}</span>@endif</td>
                    @endforeach
                </tr>
                <tr>
                    <td class="lbl">{{ __('home.meeting_attendees') }}</td>
                    @foreach($meetings as $m)
                        <td>@forelse($m->attendees as $att)<div>{{ $att->title ? $att->title . ' / ' . $att->name : $att->name }}</div>@empty—@endforelse</td>
                    @endforeach
                </tr>
                <tr>
                    <td class="lbl">{{ __('home.meeting_result') }}</td>
                    @foreach($meetings as $m)<td>{{ $m->result ?: '—' }}</td>@endforeach
                </tr>
            </tbody>
        </table>
    @endif

    <div class="footer">{{ __('home.app_name') }}</div>

</body>
</html>
