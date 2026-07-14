<div class="max-w-7xl mx-auto p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <div class="w-1 h-8 bg-[#c9a847] rounded-full"></div>
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.system_dashboard') }}</h1>
    </div>

    @if(! $isSuperAdmin)
        {{-- Empty state لمن يدخل الفرع بصلاحية offices.settings فقط (بدون إدارة مستخدمين/أدوار) --}}
        <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-12 flex flex-col items-center justify-center text-center gap-3">
            <div class="w-16 h-16 rounded-full bg-[#c9a847]/10 flex items-center justify-center">
                <flux:icon.squares-2x2 class="w-8 h-8 text-[#c9a847]" />
            </div>
            <p class="text-lg font-semibold text-zinc-700 dark:text-zinc-200">{{ __('home.system_dashboard_empty') }}</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 max-w-md">{{ __('home.system_dashboard_empty_hint') }}</p>
        </div>
    @else

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
                <div class="flex items-start justify-between mb-3">
                    <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.kpi_total_users') }}</p>
                    <div class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center">
                        <flux:icon.users variant="outline" class="w-5 h-5 text-emerald-500 dark:text-emerald-400" />
                    </div>
                </div>
                <p class="text-3xl font-bold text-zinc-800 dark:text-zinc-100">{{ number_format($totalUsers) }}</p>
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
                <div class="flex items-start justify-between mb-3">
                    <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.kpi_total_roles') }}</p>
                    <div class="w-9 h-9 rounded-lg bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center">
                        <flux:icon.shield-check variant="outline" class="w-5 h-5 text-violet-500 dark:text-violet-400" />
                    </div>
                </div>
                <p class="text-3xl font-bold text-zinc-800 dark:text-zinc-100">{{ number_format($totalRoles) }}</p>
            </div>
        </div>

        {{-- توزيع المستخدمين حسب الدور --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                    {{ __('home.users_by_role_title') }}
                </h3>
            </div>

            <div class="flex flex-wrap gap-3">
                @foreach($usersByRole as $role)
                <div class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 px-3 py-2">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $role->name }}</span>
                    <span class="inline-flex items-center justify-center min-w-6 h-6 px-1.5 rounded-full bg-[#c9a847]/15 text-xs font-bold text-[#b8962e]">
                        {{ $role->users_count }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- المتصلون الآن — نفس الجزئية المستخدمة في داشبورد المقرات --}}
        @include('livewire.dashboard.online-users')

        {{-- سجل نشاط إدارة النظام --}}
        <div wire:poll.300s class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">

            <div class="flex items-center gap-3 mb-5">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                    {{ __('home.activity_log_title') }}
                </h3>
            </div>

            {{-- Filters --}}
            <div class="flex flex-col sm:flex-row gap-3 mb-4">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('home.search') }}..."
                    class="w-full sm:flex-1 border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]/40"
                >
                <select
                    wire:model.live="filterEvent"
                    class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]/40"
                >
                    <option value="">{{ __('home.activity_filter_all') }}</option>
                    <option value="created">{{ __('home.activity_filter_created') }}</option>
                    <option value="updated">{{ __('home.activity_filter_updated') }}</option>
                    <option value="deleted">{{ __('home.activity_filter_deleted') }}</option>
                    <option value="login">{{ __('home.activity_filter_login') }}</option>
                    <option value="logout">{{ __('home.activity_filter_logout') }}</option>
                </select>
            </div>

            @if($activities->isEmpty())
                <p class="text-sm text-zinc-400 py-8 text-center">{{ __('home.activity_empty') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="text-right py-2 px-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400 whitespace-nowrap">{{ __('home.activity_col_time') }}</th>
                                <th class="text-right py-2 px-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400 whitespace-nowrap">{{ __('home.activity_col_user') }}</th>
                                <th class="text-right py-2 px-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400 whitespace-nowrap">{{ __('home.activity_col_action') }}</th>
                                <th class="text-right py-2 px-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400 whitespace-nowrap">{{ __('home.activity_col_subject') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($activities as $activity)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                                <td class="py-2.5 px-3 whitespace-nowrap">
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ $activity->created_at->diffForHumans() }}</p>
                                    <p class="text-xs text-zinc-300 dark:text-zinc-600">{{ $activity->created_at->format('Y-m-d H:i') }}</p>
                                </td>
                                <td class="py-2.5 px-3 font-medium text-zinc-700 dark:text-zinc-300 whitespace-nowrap text-xs">
                                    {{ optional($activity->causer)->name ?? '—' }}
                                </td>
                                <td class="py-2.5 px-3">
                                    @php
                                        $badgeClass = match($activity->event) {
                                            'created' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                            'updated' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                            'deleted' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                            'login'   => 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
                                            'logout'  => 'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300',
                                            default   => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $badgeClass }}">
                                        {{ $activity->description }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-zinc-600 dark:text-zinc-400">
                                    @if($activity->subject_type === \App\Models\User::class)
                                        @php
                                            $userName = optional($activity->subject)->name ?? data_get($activity->properties, 'attributes.name');
                                        @endphp
                                        @if($activity->subject)
                                            <a href="{{ route('users.edit', $activity->subject_id) }}" wire:navigate
                                               class="text-[#c9a847] hover:underline text-xs">
                                                {{ $userName ?? '#' . $activity->subject_id }}
                                            </a>
                                        @else
                                            <span class="text-xs text-zinc-600 dark:text-zinc-300">{{ $userName ?? '#' . $activity->subject_id }}</span>
                                        @endif
                                    @elseif($activity->subject_type === \Spatie\Permission\Models\Role::class)
                                        @php $roleName = data_get($activity->properties, 'name') ?? optional($activity->subject)->name; @endphp
                                        @if($activity->subject)
                                            <a href="{{ route('roles.edit', $activity->subject_id) }}" wire:navigate
                                               class="text-[#c9a847] hover:underline text-xs">
                                                {{ $roleName ?? '#' . $activity->subject_id }}
                                            </a>
                                        @else
                                            <span class="text-xs text-zinc-600 dark:text-zinc-300">{{ $roleName ?? '#' . $activity->subject_id }}</span>
                                        @endif
                                    @else
                                        <span class="text-xs text-zinc-400">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $activities->links(data: ['scrollTo' => false]) }}
                </div>
            @endif
        </div>

    @endif

</div>
