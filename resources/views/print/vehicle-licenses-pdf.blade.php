<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير تراخيص السيارات</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: dejavusans, sans-serif; direction: rtl; font-size: 9pt; color: #1a1a1a; }

        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #c9a847; padding-bottom: 5px; margin-bottom: 10px; }
        .header-table td { vertical-align: middle; padding: 2px; }
        .logo-img { width: 48px; height: 48px; }
        .app-title { font-size: 13pt; font-weight: bold; color: #c9a847; }
        .app-subtitle { font-size: 9pt; color: #666; margin-top: 1px; }
        .meta-cell { text-align: left; font-size: 9pt; color: #666; line-height: 1.6; }

        .rt { width: 100%; border-collapse: collapse; }
        .rt th, .rt td { word-wrap: break-word; overflow-wrap: break-word; }
        .rt th {
            background-color: #c9a847; color: #fff; font-size: 9pt; font-weight: bold;
            padding: 5px 4px; border: 1px solid #b8962e; text-align: center; vertical-align: middle;
        }
        .rt td { border: 1px solid #ddd; padding: 5px 4px; font-size: 9pt; text-align: center; vertical-align: middle; }
        .rt td.right { text-align: right; }
        .rt td.vname { text-align: right; font-weight: bold; color: #111; }
        .rt tbody tr:nth-child(even) td { background-color: #fafafa; }
        .st { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 8pt; font-weight: bold; }
        .st-expired { background-color: #fde2e2; color: #a33; }
        .st-soon { background-color: #fbeecb; color: #a76a00; }
        .st-valid { background-color: #dcf3e3; color: #1c7a3f; }
        .st-none { background-color: #eee; color: #888; }

        .page-footer { margin-top: 10px; padding-top: 4px; border-top: 1px solid #e4e4e4; text-align: center; font-size: 8pt; color: #aaa; }
    </style>
</head>
<body>

    @php
        $statusClass = ['منتهية' => 'st-expired', 'تنتهي قريباً' => 'st-soon', 'سارية' => 'st-valid', 'غير مسجّل' => 'st-none'];
    @endphp

    <table class="header-table">
        <tr>
            @if($logoBase64)<td style="width:44px;"><img class="logo-img" src="{{ $logoBase64 }}" alt=""></td>@endif
            <td>
                <div class="app-title">{{ __('home.app_name') }}</div>
                <div class="app-subtitle">تقرير تراخيص السيارات المتنقلة</div>
            </td>
            <td class="meta-cell">
                <div>تاريخ الطباعة: {{ now()->format('Y-m-d') }}</div>
                <div>عدد السيارات: {{ $rows->count() }}</div>
            </td>
        </tr>
    </table>

    <table class="rt">
        <thead>
            <tr>
                <th width="5%">م</th>
                <th width="18%">المحافظة</th>
                <th width="22%">السيارة</th>
                <th width="15%">رقم اللوحة</th>
                <th width="16%">انتهاء الترخيص</th>
                <th width="12%">المتبقّي</th>
                <th width="12%">الحالة</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $v)
                @php [$remaining, $status] = \App\Exports\VehicleLicensesExport::licenseInfo($v->license_expiry_date, $soonDays); @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="right">{{ $v->governorate->name ?? '—' }}</td>
                    <td class="vname">{{ $v->name }}</td>
                    <td>{{ $v->license_plate ?: '—' }}</td>
                    <td>{{ $v->license_expiry_date?->format('Y-m-d') ?? '—' }}</td>
                    <td>{{ $remaining }}</td>
                    <td><span class="st {{ $statusClass[$status] ?? '' }}">{{ $status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center; color:#999; padding:30px;">لا توجد بيانات</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="page-footer">
        نظام مقرات التوثيق والشهر العقاري — طُبع بتاريخ {{ now()->format('Y-m-d H:i') }}
    </div>

</body>
</html>
