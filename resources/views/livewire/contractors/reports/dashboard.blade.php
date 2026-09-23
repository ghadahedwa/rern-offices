@php
    $lbl = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1';

    $cards = [
        ['home.ct_dash_in_service',   $headline['in_service'],   'text-[#b8962e]'],
        ['home.ct_dash_offices',      $headline['offices'],      'text-zinc-800 dark:text-zinc-100'],
        ['home.ct_dash_governorates', $headline['governorates'], 'text-zinc-800 dark:text-zinc-100'],
        ['home.ct_dash_archived',     $headline['archived'],     'text-zinc-400 dark:text-zinc-500'],
    ];
@endphp

<div class="max-w-7xl mx-auto p-6 space-y-6">

    {{-- الرأس --}}
    <div class="flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-[#c9a847]/10 flex items-center justify-center shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#c9a847]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
            </svg>
        </div>
        <div>
            <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.ct_dash_title') }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('home.ct_dash_hint') }}</p>
        </div>
    </div>

    @include('livewire.contractors.reports.includes.dashboard-filters')

    {{-- الأعداد --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($cards as [$key, $value, $tone])
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
                <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">{{ __($key) }}</p>
                <p class="text-3xl font-semibold {{ $tone }}">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    {{--
        التوزيع على الصفات **بطاقات لا مخططاً** (طلب المستخدمة 2026-09-23): الصفات
        خمسٌ معدودة، والمخطط يحتاج قراءةَ محورٍ ليقول ما يقوله الرقم مباشرة —
        بخلاف المحافظات، فعددها يجعل المقارنة البصرية هي المعلومة.
    --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.ct_dash_by_profession') }}</h3>
        </div>

        @if($byProfession === [])
            <p class="text-sm text-zinc-400 dark:text-zinc-500 text-center py-8">{{ __('home.ct_dash_no_data') }}</p>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                @foreach($byProfession as $row)
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 p-4 text-center">
                        <p class="text-2xl font-semibold text-[#b8962e]">{{ $row['value'] }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 truncate" title="{{ $row['label'] }}">{{ $row['label'] }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- حضور الفترة --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5 space-y-4">
        <div class="flex items-center gap-3">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.ct_dash_attendance') }}</h3>
        </div>

        @if($breakdown)
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ $this->periodLabel() }} — {{ __('home.ct_rep_breakdown', [
                    'total'    => $breakdown['total'],
                    'weekend'  => $breakdown['weekend'],
                    'holidays' => $breakdown['holidays'],
                    'working'  => $breakdown['working'],
                ]) }}
            </p>
        @endif

        {{--
            ⚠️ **الأرقام الكبيرة ليست عناوين هنا**: «أيام العمل» في التقارير أيامُ
               **عاملٍ واحد** (٢٦)، وعلى اللوحة **مجموع أيام كل العاملين** (٢٩٣٣٦) —
               الكلمة واحدة والوحدة مختلفة، فيقرؤها المستخدم لبساً. فالعنوان هنا
               **الغياب والإجازات** (أرقامٌ صغيرة تُقرأ كما هي)، والمجموع تحتها
               **مقاماً يشرح نفسه** لا بطاقةً مفردة.
        --}}
        <div class="flex flex-wrap gap-6">
            @forelse($statuses as $status)
                <div>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ $status->name }}</p>
                    <p class="text-2xl font-semibold" style="color: {{ $status->color }}">
                        {{ $attendance['exceptions'][$status->id] ?? 0 }}
                        <span class="text-xs font-normal text-zinc-400">{{ __('home.ct_dash_day_unit') }}</span>
                    </p>
                </div>
            @empty
                <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('home.ct_dash_no_exceptions') }}</p>
            @endforelse
        </div>

        @include('livewire.contractors.reports.includes.unrecorded-note', ['count' => $unrecorded])

        {{-- المجموع مقاماً: «من أصل … لـ… عاملاً» يشرح الوحدة بلا حاشية --}}
        <p class="text-xs text-zinc-500 dark:text-zinc-400 border-t border-zinc-100 dark:border-zinc-800 pt-3">
            {{ __('home.ct_dash_person_days', [
                'total'   => $attendance['working'],
                'workers' => $headline['in_service'],
                'present' => \App\Support\Contractors\AttendanceReport::attended($attendance),
            ]) }}
        </p>
    </div>

    {{-- مخطط: التوزيع على المحافظات --}}
    @include('livewire.contractors.reports.includes.bar-chart', [
        'title'  => __('home.ct_dash_by_governorate'),
        'rows'   => $byGovernorate,
        'name'   => 'gov',
        'height' => count($byGovernorate) > 12 ? 420 : 300,
    ])

    {{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>
</div>
