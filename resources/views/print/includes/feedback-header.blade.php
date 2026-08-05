{{-- ترويسة تقارير رأي المواطن: الشعار + العنوان + تاريخ الطباعة + سطر الفلتر --}}
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
            <div>{{ __('home.fr_export_generated_at') }}: {{ \App\Support\LocalTime::stamp($generatedAt) }}</div>
        </td>
    </tr>
</table>

<div class="filter-bar">
    @foreach($filters->describe() as [$label, $value])
        <b>{{ $label }}:</b> {{ $value }}@if(! $loop->last) &nbsp;·&nbsp; @endif
    @endforeach
</div>
