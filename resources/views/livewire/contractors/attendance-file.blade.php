@php
    $select = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]';
    $label  = 'block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1';
    $gridUrl = route('contractors.attendance', array_filter(['gov' => $governorate, 'month' => $month]));
@endphp

<div class="p-6 max-w-4xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ $gridUrl }}" wire:navigate title="{{ __('home.ct_attendance') }}"
               class="w-8 h-8 rounded-lg border border-zinc-300 dark:border-zinc-600 flex items-center justify-center text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.ct_att_file_title') }}</h1>
        </div>
    </div>

    <p class="text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed">{{ __('home.ct_att_file_hint') }}</p>

    {{-- ١. المحافظة والشهر --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 [&>*]:min-w-0">
            <div>
                <label class="{{ $label }}">{{ __('home.ct_worker_governorate') }}</label>
                <select wire:model.live="governorate" class="{{ $select }}">
                    <option value="">—</option>
                    @foreach($governorates as $gov)
                        <option value="{{ $gov->id }}">{{ $gov->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="{{ $label }}">{{ __('home.ct_att_month') }}</label>
                <div class="flex items-center gap-1.5 min-w-0">
                    <button type="button" wire:click="shiftMonth(-1)" title="{{ __('home.ct_att_prev_month') }}"
                            class="shrink-0 w-9 h-9 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800">›</button>
                    <input type="month" wire:model.live="month" class="{{ $select }} min-w-0 flex-1" />
                    <button type="button" wire:click="shiftMonth(1)" title="{{ __('home.ct_att_next_month') }}"
                            class="shrink-0 w-9 h-9 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800">‹</button>
                </div>
            </div>
        </div>
    </div>

    @if(! $fileLabel)
        <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 p-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('home.ct_att_file_pick_governorate') }}
        </div>
    @else
        {{-- ٢. التنزيل والرفع --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5 space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">{{ $fileLabel }}</h3>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="button" wire:click="downloadMonthFile" wire:loading.attr="disabled" wire:target="downloadMonthFile"
                        class="inline-flex items-center gap-2 border border-[#c9a847] text-[#b8962e] hover:bg-[#c9a847]/10 text-sm font-medium px-4 py-2 rounded-lg transition disabled:opacity-40">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/>
                    </svg>
                    {{ __('home.ct_att_file_download', ['label' => $fileLabel]) }}
                </button>

                <label class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-4 py-2 rounded-lg transition cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L8 8m4-4l4 4M4 20h16"/>
                    </svg>
                    {{ __('home.ct_att_file_upload') }}
                    {{-- ⚠️ المفتاح يتغيّر مع كل رفع: حقل الملف نفسه لا يُطلق change لملفٍّ بالاسم نفسه مرتين --}}
                    <input type="file" wire:model="monthFile" wire:key="month-file-{{ $filePreview ? 'filled' : 'empty' }}" accept=".xlsx" class="hidden" />
                </label>

                <span wire:loading wire:target="monthFile" class="text-xs text-zinc-500">{{ __('home.ct_att_file_reading') }}</span>
            </div>
            @error('monthFile') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror

            @if($savedResult)
                <div class="rounded-lg border border-emerald-200 dark:border-emerald-900 bg-emerald-50 dark:bg-emerald-900/20 px-4 py-3 flex flex-wrap items-center justify-between gap-3">
                    <span class="text-sm text-emerald-800 dark:text-emerald-300">{{ __('home.ct_att_saved', $savedResult) }}</span>
                    <a href="{{ $gridUrl }}" wire:navigate class="text-sm font-medium text-emerald-700 dark:text-emerald-300 underline underline-offset-4">
                        {{ __('home.ct_att_file_open_grid') }}
                    </a>
                </div>
            @endif

            @if($filePreview)
                @if($filePreview['error'])
                    <div class="rounded-lg border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                        {{ __('home.ct_att_file_'.$filePreview['error'], ['label' => $fileLabel]) }}
                    </div>
                @else
                    @php($summary = $filePreview['summary'])
                    {{-- ٣. المعاينة قبل الحفظ --}}
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 p-4 space-y-3">
                        <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                            {{ __('home.ct_att_file_summary', ['label' => $filePreview['label'], 'workers' => $summary['workers'], 'offices' => $summary['offices']]) }}
                        </p>
                        <div class="flex flex-wrap items-center gap-2 text-xs font-medium">
                            <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ __('home.ct_att_file_created', ['count' => $summary['created']]) }}</span>
                            <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">{{ __('home.ct_att_file_updated', ['count' => $summary['updated']]) }}</span>
                            {{-- «سيُحذف» صريحٌ لا مطويّ: الخلية الراجعة فارغة تحذف استثناءً مسجَّلاً --}}
                            <span class="px-3 py-1 rounded-full {{ $summary['deleted'] ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : 'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300' }}">{{ __('home.ct_att_file_deleted', ['count' => $summary['deleted']]) }}</span>
                            @if($filePreview['ignored'])
                                <span class="px-3 py-1 rounded-full bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">{{ __('home.ct_att_file_ignored', ['count' => $filePreview['ignored']]) }}</span>
                            @endif
                        </div>

                        @if($filePreview['errors'])
                            <div class="space-y-1.5">
                                <p class="text-xs font-semibold text-red-600 dark:text-red-400">{{ __('home.ct_att_file_errors', ['count' => count($filePreview['errors'])]) }}</p>
                                <div class="max-h-64 overflow-y-auto rounded-md border border-red-100 dark:border-red-900/50 divide-y divide-red-50 dark:divide-red-900/30 bg-white dark:bg-zinc-900">
                                    @foreach($filePreview['errors'] as $error)
                                        <div class="flex flex-wrap gap-x-3 gap-y-0.5 px-3 py-1.5 text-xs">
                                            <span class="shrink-0 text-zinc-400">{{ __('home.ct_att_file_line') }} {{ $error['line'] }}</span>
                                            <span class="shrink-0 font-medium text-zinc-700 dark:text-zinc-200">{{ $error['name'] }}</span>
                                            <span class="text-red-600 dark:text-red-400">{{ __('home.ct_att_file_err_'.$error['message'], ['cells' => $error['cells'] ?? '']) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="flex gap-2 pt-1">
                            @if($summary['workers'] > 0)
                                <button type="button" wire:click="importMonthFile" wire:loading.attr="disabled" wire:target="importMonthFile"
                                        class="text-sm font-medium px-5 py-2 rounded-lg bg-[#c9a847] hover:bg-[#b8962e] text-white transition disabled:opacity-40">
                                    {{ __('home.ct_att_file_save') }}
                                </button>
                            @else
                                <span class="text-sm text-zinc-500 self-center">{{ __('home.ct_att_file_nothing') }}</span>
                            @endif
                            <button type="button" wire:click="cancelMonthFile"
                                    class="text-sm px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-white dark:hover:bg-zinc-800 transition">
                                {{ __('home.ct_att_file_cancel') }}
                            </button>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    @endif

    {{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>
</div>
