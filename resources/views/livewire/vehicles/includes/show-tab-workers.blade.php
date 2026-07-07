{{-- السائق --}}
<div>
    <div class="flex items-center gap-3 mb-4">
        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_driver') }}</h3>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.driver_name') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $vehicle->driver_name ?: __('home.no_data') }}</p>
        </div>
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.driver_phone') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100" dir="ltr" style="text-align:right">{{ $vehicle->driver_phone ?: __('home.no_data') }}</p>
        </div>
    </div>
</div>

<div class="border-t border-zinc-100 dark:border-zinc-700"></div>

{{-- الموثق --}}
<div>
    <div class="flex items-center gap-3 mb-4">
        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_notary') }}</h3>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.notary_name') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $vehicle->notary_name ?: __('home.no_data') }}</p>
        </div>
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.notary_phone') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100" dir="ltr" style="text-align:right">{{ $vehicle->notary_phone ?: __('home.no_data') }}</p>
        </div>
    </div>
</div>

<div class="border-t border-zinc-100 dark:border-zinc-700"></div>

{{-- المراجع --}}
<div>
    <div class="flex items-center gap-3 mb-4">
        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_reviewer') }}</h3>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.reviewer_name') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $vehicle->reviewer_name ?: __('home.no_data') }}</p>
        </div>
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.reviewer_phone') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100" dir="ltr" style="text-align:right">{{ $vehicle->reviewer_phone ?: __('home.no_data') }}</p>
        </div>
    </div>
</div>
