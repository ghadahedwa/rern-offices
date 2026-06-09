{{-- Activity Log — صف مستقل كامل العرض --}}
<div>
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
                <option value="viewed">{{ __('home.activity_filter_viewed') }}</option>
                <option value="login">{{ __('home.activity_filter_login') }}</option>
                <option value="logout">{{ __('home.activity_filter_logout') }}</option>
            </select>

            @if($isSuperAdmin)
            {{-- حذف السجلات القديمة --}}
            <div class="flex gap-2 sm:ms-auto">
                <select
                    wire:model.live="deletePeriod"
                    class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-red-400/40"
                >
                    <option value="">{{ __('home.activity_delete_label') }}</option>
                    <option value="3days">{{ __('home.activity_period_3days') }}</option>
                    <option value="1week">{{ __('home.activity_period_1week') }}</option>
                    <option value="2weeks">{{ __('home.activity_period_2weeks') }}</option>
                    <option value="3weeks">{{ __('home.activity_period_3weeks') }}</option>
                    <option value="1month">{{ __('home.activity_period_1month') }}</option>
                </select>
                <button
                    wire:click="deleteOldActivities"
                    wire:confirm="{{ __('home.activity_delete_confirm') }}"
                    @disabled(!$deletePeriod)
                    class="inline-flex items-center gap-1.5 text-sm px-3 py-2 rounded-lg border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition disabled:opacity-40 disabled:cursor-not-allowed"
                >
                    <flux:icon.trash variant="outline" class="w-4 h-4" />
                    {{ __('home.activity_delete_btn') }}
                </button>
            </div>
            @endif
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
                                        'viewed'  => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
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
                                @if($activity->subject_type === \App\Models\Office::class && $activity->subject)
                                    @php
                                        $officeUrl = $canView
                                            ? route('offices.show', $activity->subject_id)
                                            : ($canEdit ? route('offices.edit', $activity->subject_id) : null);
                                    @endphp
                                    @if($officeUrl)
                                        <a href="{{ $officeUrl }}" wire:navigate
                                           class="text-[#c9a847] hover:underline text-xs">
                                            {{ $activity->subject->name ?? '#' . $activity->subject_id }}
                                        </a>
                                    @else
                                        <span class="text-xs text-zinc-600 dark:text-zinc-300">{{ $activity->subject->name ?? '#' . $activity->subject_id }}</span>
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

</div>
