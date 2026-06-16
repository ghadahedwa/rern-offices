{{-- KPI Cards — عدد الأعمدة حسب البطاقات الظاهرة (مقرات + محافظات + [مستخدمون: super-admin] + [جديد هذا الشهر: من يملك offices.index]) --}}
@if($isSuperAdmin)
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
@elseif($canViewOfficeStats)
<div class="grid grid-cols-2 md:grid-cols-3 gap-4">
@else
<div class="grid grid-cols-2 gap-4">
@endif

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="flex items-start justify-between mb-3">
            <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.kpi_total_offices') }}</p>
            <div class="w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center">
                <flux:icon.building-office-2 variant="outline" class="w-5 h-5 text-blue-500 dark:text-blue-400" />
            </div>
        </div>
        <p class="text-3xl font-bold text-zinc-800 dark:text-zinc-100">{{ number_format($totalOffices) }}</p>
    </div>

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="flex items-start justify-between mb-3">
            <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.kpi_total_governorates') }}</p>
            <div class="w-9 h-9 rounded-lg bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center">
                <flux:icon.map-pin variant="outline" class="w-5 h-5 text-violet-500 dark:text-violet-400" />
            </div>
        </div>
        <p class="text-3xl font-bold text-zinc-800 dark:text-zinc-100">{{ number_format($totalGovernorates) }}</p>
    </div>

    @if($isSuperAdmin)
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="flex items-start justify-between mb-3">
            <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.kpi_total_users') }}</p>
            <div class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center">
                <flux:icon.users variant="outline" class="w-5 h-5 text-emerald-500 dark:text-emerald-400" />
            </div>
        </div>
        <p class="text-3xl font-bold text-zinc-800 dark:text-zinc-100">{{ number_format($totalUsers) }}</p>
    </div>
    @endif

    @if($canViewOfficeStats)
    <div class="rounded-xl border border-[#c9a847]/40 bg-[#c9a847]/5 dark:bg-[#c9a847]/10 shadow-sm p-5">
        <div class="flex items-start justify-between mb-3">
            <p class="text-xs text-[#b8962e] dark:text-[#c9a847]">{{ __('home.kpi_added_this_month') }}</p>
            <div class="w-9 h-9 rounded-lg bg-[#c9a847]/15 flex items-center justify-center">
                <flux:icon.calendar-days variant="outline" class="w-5 h-5 text-[#b8962e] dark:text-[#c9a847]" />
            </div>
        </div>
        <p class="text-3xl font-bold text-[#b8962e] dark:text-[#c9a847]">{{ number_format($addedThisMonth) }}</p>
    </div>
    @endif

</div>
