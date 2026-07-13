<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    @php
        $d = \Carbon\Carbon::parse($date);
    @endphp
    <title>{{ __('home.meetings_title') }} — {{ $d->format('Y-m-d') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Tahoma, Arial, sans-serif; direction: rtl; color: #1a1a1a; padding: 24px; background: #fff; }

        .header { display: flex; align-items: center; gap: 12px; border-bottom: 2px solid #c9a847; padding-bottom: 10px; margin-bottom: 16px; }
        .header img { width: 52px; height: 52px; object-fit: contain; }
        .app-title { font-size: 18px; font-weight: bold; color: #c9a847; }
        .day-title { font-size: 14px; color: #333; margin-top: 2px; }
        .meta { margin-inline-start: auto; text-align: left; font-size: 11px; color: #777; line-height: 1.7; }

        .meeting { border: 1px solid #e2e2e2; border-radius: 8px; margin-bottom: 12px; overflow: hidden; page-break-inside: avoid; }
        .meeting-head { display: flex; align-items: center; gap: 10px; background: #faf6ea; padding: 8px 12px; border-bottom: 1px solid #eee; }
        .badge { background: #c9a847; color: #fff; font-size: 12px; font-weight: bold; border-radius: 6px; padding: 3px 8px; }
        .m-time { font-weight: bold; color: #222; font-size: 13px; }
        .m-subject { font-weight: bold; color: #222; font-size: 14px; }

        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 16px; padding: 10px 12px; }
        .cell { font-size: 12px; }
        .cell.full { grid-column: 1 / -1; }
        .label { color: #999; font-size: 10px; margin-bottom: 2px; }
        .value { color: #1a1a1a; white-space: pre-wrap; }

        .empty { text-align: center; color: #999; padding: 40px; border: 1px dashed #ccc; border-radius: 8px; }
        .footer { margin-top: 16px; padding-top: 6px; border-top: 1px solid #eee; text-align: center; font-size: 10px; color: #aaa; }

        @media print {
            body { padding: 0; }
            .meeting { border-color: #ccc; }
        }
    </style>
</head>
<body>

    <div class="header">
        <img src="{{ asset('images/logo3.png') }}" alt="">
        <div>
            <div class="app-title">{{ __('home.app_name') }}</div>
            <div class="day-title">{{ __('home.meetings_title') }} — {{ $d->locale('ar')->dayName }} {{ $d->format('Y-m-d') }}</div>
        </div>
        <div class="meta">
            <div>عدد الاجتماعات: {{ $meetings->count() }}</div>
            <div>تاريخ الطباعة: {{ now()->format('Y-m-d') }}</div>
        </div>
    </div>

    @forelse($meetings as $i => $meeting)
        <div class="meeting">
            <div class="meeting-head">
                <span class="badge">{{ $i + 1 }}</span>
                <span class="m-time">{{ $meeting->time ? substr($meeting->time, 0, 5) : '—' }}</span>
                <span class="m-subject">{{ $meeting->subject }}</span>
            </div>
            <div class="grid">
                <div class="cell">
                    <div class="label">{{ __('home.meeting_location') }}</div>
                    <div class="value">{{ $meeting->location ?: '—' }}</div>
                </div>
                <div class="cell">
                    <div class="label">{{ __('home.meeting_concerned_party') }}</div>
                    <div class="value">{{ $meeting->concerned_party ?: '—' }}{{ $meeting->concerned_party_title ? ' — '.$meeting->concerned_party_title : '' }}</div>
                </div>
                <div class="cell full">
                    <div class="label">{{ __('home.meeting_result') }}</div>
                    <div class="value">{{ $meeting->result ?: '—' }}</div>
                </div>
                @if($meeting->notes)
                    <div class="cell full">
                        <div class="label">{{ __('home.meeting_notes') }}</div>
                        <div class="value">{{ $meeting->notes }}</div>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="empty">{{ __('home.no_meetings') }}</div>
    @endforelse

    <div class="footer">{{ __('home.app_name') }}</div>

    <script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
