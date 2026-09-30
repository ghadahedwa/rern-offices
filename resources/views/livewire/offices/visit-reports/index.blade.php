{{-- بعرض الشاشة وفلاترٍ في سطرٍ واحد بلا بطاقة — كشاشة المقرات، فيبدأ الجدول من الموضع نفسه --}}
<div class="p-6 space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $title }}</h1>
    </div>

    @php $filterCls = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]'; @endphp
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
        <input wire:model.live.debounce.300ms="search" type="text"
               placeholder="{{ __('home.search') }}"
               class="{{ $filterCls }}" />
        <select wire:model.live="governorate" class="{{ $filterCls }}">
            <option value="">{{ __('home.all_governorates') }}</option>
            @foreach($governorates as $gov)
                <option value="{{ $gov->id }}">{{ $gov->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="visit" class="{{ $filterCls }}">
            <option value="">— {{ __('home.vr_visit_filter') }} —</option>
            @foreach(array_keys(\App\Livewire\Offices\VisitReportsIndex::VISIT_FILTERS) as $key)
                <option value="{{ $key }}">{{ __('home.vr_filter_'.$key) }}</option>
            @endforeach
        </select>
        {{-- عدد الصفوف في خانة السطر الرابعة لا في سطرٍ تحته — فلا ينزل الجدول --}}
        <div class="flex items-center gap-2">
            <select wire:model.live="perPage" title="{{ __('home.per_page') }}" class="{{ str_replace('w-full', 'w-auto', $filterCls) }}">
                @foreach($this->perPageOptions() as $option)
                    <option value="{{ $option }}">{{ $option }} {{ __('home.rows_unit') }}</option>
                @endforeach
            </select>
            @if($this->hasActiveFilters())
                <button type="button" wire:click="resetFilters"
                        class="shrink-0 text-xs px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                    {{ __('home.reset_filters') }}
                </button>
            @endif
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm overflow-x-auto">
        <table class="w-full min-w-140 table-fixed text-sm text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs">
                <tr>
                    <th class="px-3 py-3 font-medium w-[6%]">#</th>
                    @include('livewire.partials.sortable-th', ['column' => 'name', 'label' => __('home.office_name'), 'thClass' => 'w-[41%]'])
                    @include('livewire.partials.sortable-th', ['column' => 'governorate', 'label' => __('home.governorate'), 'thClass' => 'w-[20%]'])
                    @include('livewire.partials.sortable-th', ['column' => 'visited', 'label' => __('home.visited_at'), 'thClass' => 'w-[16%]'])
                    <th class="px-3 py-3 font-medium w-[17%]"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse($offices as $office)
                    <tr wire:key="vr-{{ $office->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <td class="px-3 py-3 text-zinc-500">{{ $offices->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-3">
                            <p class="font-medium text-zinc-800 dark:text-zinc-100 truncate" title="{{ $office->name }}">{{ $office->name }}</p>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500 truncate">{{ $office->officeType->name ?? '—' }}</p>
                        </td>
                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300 truncate" title="{{ $office->governorate->name ?? '' }}">
                            {{ $office->governorate->name ?? '—' }}
                        </td>
                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300">
                            @if($office->{$visitedField})
                                {{ $office->{$visitedField}->format('Y-m-d') }}
                            @else
                                <span class="text-xs text-zinc-400">{{ __('home.vr_not_visited') }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-3">
                            <a href="{{ route('offices.visit-report', [$office->id, $type]) }}" wire:navigate
                               class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 transition">
                                {{ __('home.vr_open') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-10 text-center text-sm text-zinc-400">
                            {{ $this->hasActiveFilters() ? __('home.vr_no_match') : __('home.vr_no_offices') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $offices->links() }}
</div>
