@php
    $defaultStatus = $allStatuses->firstWhere('is_default', true);
    $visibleRows   = $workerId ? array_values(array_filter($rows, fn ($row) => $row['id'] === $workerId)) : $rows;
    $visibleIds    = array_column($visibleRows, 'id');
    $weekdays      = __('home.ct_att_weekdays');

    $config = [
        'rows'        => $rows,
        'fingerprint' => $fingerprint,
        // الأداة الافتراضية أول حالات التعليم بالترتيب — التعليم بـ«حاضر» مَحوٌ لا تعليم
        'brush'       => $statuses->first()?->id ?? 0,
        'statuses'    => $allStatuses->reject(fn ($s) => $s->is_default)
            ->mapWithKeys(fn ($s) => [$s->id => ['name' => $s->name, 'color' => $s->color]]),
        // لون «حاضر» من جدول الحالات — يُرسم صبغةً باهتة لا لوناً مصمتاً (انظر cellStyle)
        'present'     => $defaultStatus?->color ?? '#71717a',
    ];

    $select = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847] disabled:opacity-50 disabled:cursor-not-allowed';
    $label  = 'block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1';
@endphp

<div class="p-6 space-y-6">
    {{--
        ⚠️ المفتاح يتغيّر بالمقر والشهر وبعد كل حفظ فتُبنى حالة الشبكة من جديد من الداتابيز،
           ولا يتغيّر بفلتر العامل ولا بالبحث ولا بالـkeepalive فلا تضيع تغييراتٌ لم تُحفظ.
    --}}
    <div wire:key="attendance-{{ $office }}-{{ $monthValue }}-{{ $version }}"
         x-data="attendanceGrid"
         data-config="{{ json_encode($config) }}"
         class="space-y-6">

        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.ct_attendance') }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1 max-w-3xl">{{ __('home.ct_att_hint') }}</p>
            </div>
            @if($sheet && $rows)
                <button type="button" @click="save()" :disabled="!dirty || saving"
                        class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-5 py-2 rounded-lg transition disabled:opacity-40 disabled:cursor-not-allowed">
                    <span x-show="!saving">{{ __('home.ct_att_save') }}</span>
                    <span x-show="saving" x-cloak>{{ __('home.ct_att_saving') }}</span>
                </button>
            @endif
        </div>

        {{-- الاختيار: المحافظة ← المقر ← الشهر، وبحثٌ عابرٌ للمقارّ --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5 space-y-3">
            {{--
                المحافظة والمقر والشهر في صفّ (المقر أعرضها: أسماؤه تطول)، والبحث في صفٍّ وحده — هو طريقٌ
                بديل للوصول لا فلترٌ رابع.
                ⚠️ min-w-0 على كل خانة: حقل الشهر له عرضٌ أدنى ذاتي، فبلاه يتمدّد فوق الخانة المجاورة.
            --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 [&>*]:min-w-0">
                <div class="lg:col-span-3">
                    <label class="{{ $label }}">{{ __('home.ct_worker_governorate') }}</label>
                    <select wire:model.live="governorate" :disabled="dirty" class="{{ $select }}">
                        <option value="">—</option>
                        @foreach($governorates as $gov)
                            <option value="{{ $gov->id }}">{{ $gov->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-5">
                    <label class="{{ $label }}">{{ __('home.ct_worker_office') }}</label>
                    <select wire:model.live="office" :disabled="dirty" class="{{ $select }}">
                        <option value="">—</option>
                        @foreach($offices as $off)
                            <option value="{{ $off->id }}" title="{{ $off->name }}">{{ $off->short_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-4">
                    <label class="{{ $label }}">{{ __('home.ct_att_month') }}</label>
                    <div class="flex items-center gap-1.5 min-w-0">
                        {{-- RTL: السابق على اليمين --}}
                        <button type="button" wire:click="shiftMonth(-1)" :disabled="dirty" title="{{ __('home.ct_att_prev_month') }}"
                                class="shrink-0 w-9 h-9 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 disabled:opacity-40 disabled:cursor-not-allowed">›</button>
                        <input type="month" wire:model.live="month" :disabled="dirty" class="{{ $select }} min-w-0 flex-1" />
                        <button type="button" wire:click="shiftMonth(1)" :disabled="dirty" title="{{ __('home.ct_att_next_month') }}"
                                class="shrink-0 w-9 h-9 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 disabled:opacity-40 disabled:cursor-not-allowed">‹</button>
                    </div>
                </div>

                <div class="relative sm:col-span-2 lg:col-span-12 lg:max-w-md">
                    <label class="{{ $label }}">{{ __('home.ct_att_search') }}</label>
                    <input type="text" wire:model.live.debounce.300ms="search" :disabled="dirty"
                           placeholder="{{ __('home.ct_att_search_placeholder') }}" class="{{ $select }}" />
                    @if(mb_strlen(trim($search)) >= 2)
                        <div class="absolute z-30 mt-1 w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-lg overflow-hidden">
                            @forelse($searchResults as $result)
                                <button type="button" wire:click="openWorker({{ $result->id }})"
                                        class="block w-full text-right px-3 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                    <span class="block text-sm text-zinc-800 dark:text-zinc-100 truncate">{{ $result->name }}</span>
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400 truncate">
                                        {{ $result->currentAssignment?->office?->name ?? __('home.ct_worker_status_ended') }}
                                    </span>
                                </button>
                            @empty
                                <p class="px-3 py-2 text-xs text-zinc-500">{{ __('home.ct_att_search_none') }}</p>
                            @endforelse
                        </div>
                    @endif
                </div>
            </div>

            <p x-show="dirty" x-cloak class="text-xs text-amber-700 dark:text-amber-400">{{ __('home.ct_att_filters_locked') }}</p>
        </div>

        {{--
            كشف الشهر بالإكسيل — للمحافظة كلها. نفس قواعد الشبكة (AttendanceMonthFile ← AttendanceSheet).
            ⚠️ مقفولٌ ما دام في الشبكة تغييرات: الحفظ من الملف يُعيد بناء الشبكة فيمحوها.
        --}}
        @if($governorate !== '')
            @php($fileLabel = $monthLabel.' — '.($governorates->firstWhere('id', (int) $governorate)?->name ?? ''))
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">{{ __('home.ct_att_file_title') }}</h3>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 max-w-3xl leading-relaxed">{{ __('home.ct_att_file_hint') }}</p>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" wire:click="downloadMonthFile" :disabled="dirty" wire:loading.attr="disabled" wire:target="downloadMonthFile"
                            class="inline-flex items-center gap-2 border border-[#c9a847] text-[#b8962e] hover:bg-[#c9a847]/10 text-sm font-medium px-4 py-2 rounded-lg transition disabled:opacity-40 disabled:cursor-not-allowed">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/>
                        </svg>
                        {{ __('home.ct_att_file_download', ['label' => $fileLabel]) }}
                    </button>

                    <label :class="dirty ? 'opacity-40 pointer-events-none' : ''"
                           class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-4 py-2 rounded-lg transition cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L8 8m4-4l4 4M4 20h16"/>
                        </svg>
                        {{ __('home.ct_att_file_upload') }}
                        <input type="file" wire:model="monthFile" accept=".xlsx" class="hidden" />
                    </label>

                    <span wire:loading wire:target="monthFile" class="text-xs text-zinc-500">{{ __('home.ct_att_file_reading') }}</span>
                </div>
                @error('monthFile') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror

                @if($filePreview)
                    @if($filePreview['error'])
                        <div class="rounded-lg border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                            {{ __('home.ct_att_file_'.$filePreview['error'], ['label' => $fileLabel]) }}
                        </div>
                    @else
                        @php($summary = $filePreview['summary'])
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
                                    <div class="max-h-48 overflow-y-auto rounded-md border border-red-100 dark:border-red-900/50 divide-y divide-red-50 dark:divide-red-900/30 bg-white dark:bg-zinc-900">
                                        @foreach($filePreview['errors'] as $error)
                                            <div class="flex gap-3 px-3 py-1.5 text-xs">
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

        @if(! $sheet)
            <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 p-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                {{ $governorate !== '' && $offices->isEmpty() ? __('home.ct_att_no_offices') : __('home.ct_att_pick_office') }}
            </div>
        @elseif(! $rows)
            <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 p-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('home.ct_att_empty_office') }}
            </div>
        @else
            {{-- شريط الشهر: التفكيك لا الرقم وحده — خطأٌ في المعادلة يُرى بالعين --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-4 flex flex-wrap items-center gap-x-6 gap-y-2">
                <div class="flex items-center gap-3">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ $monthLabel }}</span>
                </div>
                <span class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('home.ct_att_breakdown', ['total' => $breakdown['total'], 'weekend' => $breakdown['weekend'], 'holidays' => $breakdown['holidays']]) }}
                </span>
                <span class="inline-flex items-center text-sm font-semibold px-3 py-1 rounded-full bg-[#c9a847]/15 text-[#b8962e] dark:text-[#d4b65e]">
                    {{ __('home.ct_att_working_days', ['count' => $breakdown['working']]) }}
                </span>
                @if($holidays)
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('home.ct_att_holidays') }}:
                        @foreach(collect($holidays)->groupBy(fn ($name) => $name, preserveKeys: true) as $name => $days)
                            <span class="text-zinc-700 dark:text-zinc-300">{{ $name }}</span>
                            ({{ collect($days)->keys()->map(fn ($d) => (int) substr($d, 8))->implode('، ') }})@if(! $loop->last) · @endif
                        @endforeach
                    </span>
                @endif
            </div>

            {{-- الأدوات: حالة التعليم · فلتر العامل · وصل كشف المقر --}}
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="space-y-1.5">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('home.ct_att_brush') }}</span>
                        @foreach($statuses as $status)
                            <button type="button" @click="brush = {{ $status->id }}"
                                    :class="brush === {{ $status->id }} ? 'ring-2 ring-offset-1 ring-zinc-800 dark:ring-zinc-100 dark:ring-offset-zinc-900' : 'opacity-70 hover:opacity-100'"
                                    class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-full text-white transition"
                                    style="background-color: {{ $status->color }}">
                                {{ $status->name }}
                            </button>
                        @endforeach
                        <button type="button" @click="brush = 0"
                                :class="brush === 0 ? 'ring-2 ring-offset-1 ring-zinc-800 dark:ring-zinc-100 dark:ring-offset-zinc-900' : 'opacity-70 hover:opacity-100'"
                                class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-full transition"
                                style="background-color: {{ $defaultStatus?->color }}33; color: {{ $defaultStatus?->color }}">
                            {{ $defaultStatus?->name }}
                        </button>
                    </div>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 max-w-xl">{{ __('home.ct_att_brush_hint') }}</p>
                </div>

                <div class="flex flex-wrap items-end gap-3">
                    <div class="w-56">
                        <label class="{{ $label }}">{{ __('home.ct_att_worker') }}</label>
                        <select wire:model.live="worker" class="{{ $select }}">
                            <option value="">{{ __('home.ct_att_all_workers') }}</option>
                            @foreach($rows as $row)
                                <option value="{{ $row['id'] }}">{{ $row['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">
                            {!! str_replace(':done', '<span class="font-semibold text-zinc-700 dark:text-zinc-200" x-text="reviewedCount('.e(json_encode($visibleIds)).')"></span>', e(__('home.ct_att_reviewed_count', ['total' => count($visibleIds)]))) !!}
                        </span>
                        <button type="button" @click="toggleReviewAll(@js($visibleIds))" title="{{ __('home.ct_att_review_toggle_hint') }}"
                                class="inline-flex items-center gap-1.5 border border-emerald-600 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-sm font-medium px-3 py-1.5 rounded-lg transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ __('home.ct_att_review_all') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- الشبكة — ⚠️ الاستثناء الوحيد من «الجدول يدخل عرض الشاشة»: ثلاثون عموداً لا تنضغط، فالتمرير داخل البطاقة والاسم ثابت --}}
            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm select-none">
                <table class="text-xs text-center border-collapse w-max min-w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400">
                        <tr>
                            <th class="sticky start-0 z-20 bg-zinc-50 dark:bg-zinc-800 px-3 py-2 text-right font-medium min-w-48 border-b border-e border-zinc-200 dark:border-zinc-700">
                                {{ __('home.ct_worker_name') }}
                            </th>
                            {{-- «وصل الكشف» بجوار الاسم لا في آخر الصفّ: الشهر ثلاثون عموداً، وما بعدها يحتاج تمريراً فيُنسى --}}
                            <th class="px-2 py-2 font-medium border-b border-e border-zinc-200 dark:border-zinc-700 whitespace-nowrap">{{ __('home.ct_att_reviewed') }}</th>
                            @foreach($columns as $col)
                                <th class="w-7 min-w-7 px-0 py-1.5 font-medium border-b border-zinc-200 dark:border-zinc-700
                                           {{ $col['kind'] === 'weekend' ? 'bg-zinc-300/70 dark:bg-zinc-700' : '' }}
                                           {{ $col['kind'] === 'holiday' ? 'bg-[#c9a847]/30' : '' }}"
                                    title="{{ $col['holiday'] ?? ($col['kind'] === 'weekend' ? __('home.ct_att_locked_weekend') : $col['date']) }}">
                                    <div class="text-zinc-700 dark:text-zinc-200">{{ $col['day'] }}</div>
                                    <div class="text-[10px] font-normal">{{ $weekdays[$col['weekday']] }}</div>
                                </th>
                            @endforeach
                            <th class="px-2 py-2 font-medium border-b border-s border-zinc-200 dark:border-zinc-700 whitespace-nowrap">{{ __('home.ct_att_col_working') }}</th>
                            <th class="px-2 py-2 font-medium border-b border-zinc-200 dark:border-zinc-700 whitespace-nowrap" style="color: {{ $defaultStatus?->color }}">{{ $defaultStatus?->name }}</th>
                            @foreach($statuses as $status)
                                <th class="px-2 py-2 font-medium border-b border-zinc-200 dark:border-zinc-700 whitespace-nowrap" style="color: {{ $status->color }}">{{ $status->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($visibleRows as $row)
                            @php($open = array_flip($row['open']))
                            <tr wire:key="att-row-{{ $row['id'] }}" class="group">
                                <td class="sticky start-0 z-10 bg-white dark:bg-zinc-900 group-hover:bg-zinc-50 dark:group-hover:bg-zinc-800 px-3 py-1.5 text-right border-b border-e border-zinc-100 dark:border-zinc-800 max-w-56">
                                    <div class="text-sm text-zinc-800 dark:text-zinc-100 truncate" title="{{ $row['name'] }}">{{ $row['name'] }}</div>
                                    @if($row['profession'])
                                        <div class="text-[11px] text-[#b8962e] dark:text-[#d4b65e] truncate">{{ $row['profession'] }}</div>
                                    @endif
                                </td>
                                <td class="px-2 border-b border-e border-zinc-100 dark:border-zinc-800"
                                    :class="reviewed[{{ $row['id'] }}] ? 'bg-emerald-50 dark:bg-emerald-900/20' : ''">
                                    <input type="checkbox" x-model="reviewed[{{ $row['id'] }}]"
                                           class="w-4 h-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer" />
                                </td>
                                {{-- ⚠️ حدود بيضاء عريضة: بلونٍ قريب من الخلية تذوب الأيام في كتلةٍ رمادية واحدة --}}
                                @foreach($columns as $col)
                                    @if($col['kind'] === 'weekend')
                                        <td class="h-10 border-2 border-white dark:border-zinc-900 bg-zinc-300/70 dark:bg-zinc-700" title="{{ __('home.ct_att_locked_weekend') }}"></td>
                                    @elseif($col['kind'] === 'holiday')
                                        <td class="h-10 border-2 border-white dark:border-zinc-900 bg-[#c9a847]/30" title="{{ $col['holiday'] }}"></td>
                                    @elseif(! isset($open[$col['date']]))
                                        {{-- قبل التحاقه أو بعد نقله: اليوم يخصّ مقراً آخر أو لا يخصّ أحداً --}}
                                        <td class="h-10 border-2 border-white dark:border-zinc-900 bg-zinc-50 dark:bg-zinc-800/40"
                                            style="background-image: repeating-linear-gradient(135deg, transparent 0 4px, rgba(113,113,122,.25) 4px 5px)"
                                            title="{{ __('home.ct_att_locked_outside') }}"></td>
                                    @else
                                        <td class="h-10 border-2 border-white dark:border-zinc-900 cursor-pointer font-bold transition-colors"
                                            :class="mark({{ $row['id'] }}, '{{ $col['date'] }}') ? 'text-white' : 'hover:brightness-90'"
                                            :style="cellStyle({{ $row['id'] }}, '{{ $col['date'] }}')"
                                            @mousedown.prevent="down({{ $row['id'] }}, '{{ $col['date'] }}')"
                                            @mouseenter="enter({{ $row['id'] }}, '{{ $col['date'] }}')"
                                            x-text="label({{ $row['id'] }}, '{{ $col['date'] }}')"
                                            title="{{ $col['date'] }}"></td>
                                    @endif
                                @endforeach
                                <td class="px-2 border-b border-s border-zinc-100 dark:border-zinc-800 text-zinc-500">{{ count($row['open']) }}</td>
                                <td class="px-2 border-b border-zinc-100 dark:border-zinc-800 font-semibold text-zinc-700 dark:text-zinc-200" x-text="present({{ $row['id'] }})"></td>
                                @foreach($statuses as $status)
                                    <td class="px-2 border-b border-zinc-100 dark:border-zinc-800 font-semibold"
                                        :style="count({{ $row['id'] }}, {{ $status->id }}) ? 'color: {{ $status->color }}' : ''"
                                        x-text="count({{ $row['id'] }}, {{ $status->id }})"></td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('home.ct_att_review_hint') }}</p>

            {{-- شريط التغييرات غير المحفوظة — ثابت أسفل الشاشة فلا يُنسى بعد التمرير --}}
            <div x-show="dirty" x-cloak x-transition.opacity
                 class="sticky bottom-4 z-30 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/30 shadow-lg px-5 py-3">
                <span class="text-sm font-medium text-amber-800 dark:text-amber-300">{{ __('home.ct_att_unsaved') }}</span>
                <div class="flex items-center gap-2">
                    <button type="button" @click="discard()" :disabled="saving"
                            class="text-sm px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-white dark:hover:bg-zinc-800 transition disabled:opacity-40">
                        {{ __('home.ct_att_discard') }}
                    </button>
                    <button type="button" @click="save()" :disabled="saving"
                            class="text-sm font-medium px-5 py-2 rounded-lg bg-[#c9a847] hover:bg-[#b8962e] text-white transition disabled:opacity-40">
                        <span x-show="!saving">{{ __('home.ct_att_save') }}</span>
                        <span x-show="saving" x-cloak>{{ __('home.ct_att_saving') }}</span>
                    </button>
                </div>
            </div>
        @endif

        {{-- مودال الخروج بتغييرات غير محفوظة --}}
        <div x-show="showLeave" x-transition.opacity @click.self="showLeave = false"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" style="display:none">
            <div class="w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border-2 border-amber-500">
                <div class="px-5 py-3.5 bg-amber-500">
                    <h3 class="text-sm font-semibold text-white">{{ __('home.ct_att_leave_title') }}</h3>
                </div>
                <div class="bg-white dark:bg-zinc-900 px-5 py-6 space-y-5">
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('home.ct_att_leave_body') }}</p>
                    <div class="flex gap-3">
                        <button type="button" @click="showLeave = false"
                                class="flex-1 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium py-2.5 rounded-lg transition">
                            {{ __('home.ct_att_leave_cancel') }}
                        </button>
                        <button type="button" @click="leave()"
                                class="flex-1 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 text-sm font-medium py-2.5 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                            {{ __('home.ct_att_leave_confirm') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>
</div>
