<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.de_operators') }}</h1>
        <div class="flex flex-wrap items-center gap-2">
        @can('data-entry.create')
            <a href="{{ route('data-entry.operators.import') }}" wire:navigate
               class="inline-flex items-center gap-2 border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 text-sm font-medium px-4 py-2 rounded-lg transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L8 8m4-4l4 4M4 20h16"/>
                </svg>
                {{ __('home.de_import_button') }}
            </a>
            <a href="{{ route('data-entry.operators.create') }}" wire:navigate
               class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('home.de_operator_add') }}
            </a>
        @endcan
        </div>
    </div>

    {{-- شريط الفلاتر الموحّد: الخمسة في صفٍّ واحد على الشاشة العريضة --}}
    <x-filter-bar :active="$this->hasActiveFilters()" :per-page-options="$this->perPageOptions()" :columns="5">
        <x-filter-input :label="__('home.search')" wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('home.de_operator_search_placeholder') }}" />

        <x-filter-select :label="__('home.de_operator_governorate')" wire:model.live="governorate">
            <option value="">{{ __('home.de_operator_all_governorates') }}</option>
            @foreach($governorates as $gov)
                <option value="{{ $gov->id }}">{{ $gov->name }}</option>
            @endforeach
        </x-filter-select>

        <x-filter-select :label="__('home.de_operator_office_type')" wire:model.live="officeType">
            <option value="">{{ __('home.de_operator_all_office_types') }}</option>
            @foreach($officeTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        </x-filter-select>

        <x-filter-select :label="__('home.de_operator_office')" wire:model.live="office">
            <option value="">{{ __('home.de_operator_all_offices') }}</option>
            @foreach($offices as $off)
                <option value="{{ $off->id }}" title="{{ $off->name }}">{{ $off->short_name }}</option>
            @endforeach
        </x-filter-select>

        <x-filter-select :label="__('home.de_operator_status')" wire:model.live="status">
            <option value="in_service">{{ __('home.de_operator_status_active') }}</option>
            <option value="ended">{{ __('home.de_operator_status_ended') }}</option>
            <option value="all">{{ __('home.de_operator_status_all') }}</option>
        </x-filter-select>
    </x-filter-bar>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="w-full text-sm text-right table-fixed min-w-140">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 font-medium w-[4%] hidden 2xl:table-cell">#</th>
                    @include('livewire.partials.sortable-th', ['column' => 'name', 'label' => __('home.de_operator_name'), 'thClass' => 'w-[30%]'])
                    <th class="px-4 py-3 font-medium w-[32%]">{{ __('home.de_operator_current_office') }}</th>
                    <th class="px-4 py-3 font-medium w-[12%] hidden xl:table-cell">{{ __('home.de_operator_governorate') }}</th>
                    <th class="px-4 py-3 font-medium w-[22%]">{{ __('home.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @php
                    // حبّات ناعمة بلا حدود: أربعة إطارات في خليةٍ واحدة تصنع ضجيجاً بصرياً،
                    // والخلفية الملوّنة تكفي لتمييز الفعل مع بقاء النصّ مقروءاً.
                    // ⚠️ الحبّة الرمادية درجتان أغمق من خلفية الصفّ (البيضاء والمؤرشَفة معاً)
                    //    — بدرجةٍ واحدة كانت تذوب في الخلفية فلا تبدو زرّاً.
                    $pillBase    = 'inline-flex items-center text-xs font-medium px-3 py-1.5 rounded-full transition';
                    $pillNeutral = $pillBase.' bg-zinc-200 text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-600';
                    // ⚠️ الأزرق بوزن الرمادي (100 لا 50): حبّةٌ أفتح بجواره تبدو معطَّلة لا مختلفة
                    $pillBlue    = $pillBase.' bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:hover:bg-blue-900/60';
                    $pillGold    = $pillBase.' bg-[#c9a847]/15 text-[#b8962e] hover:bg-[#c9a847]/25 dark:text-[#d4b65e]';
                    $pillDanger  = $pillBase.' bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30';
                @endphp
                @forelse($operators as $operator)
                    @php($assignment = $operator->currentAssignment)
                    {{-- الصفّ المؤرشَف بخلفيةٍ باهتة: في فلتر «الكل» يختلط المنتهية خدمته بالعامل --}}
                    <tr class="transition {{ $assignment ? 'hover:bg-zinc-50 dark:hover:bg-zinc-800' : 'bg-zinc-50 hover:bg-zinc-100 dark:bg-zinc-800/40 dark:hover:bg-zinc-800' }}">
                        <td class="px-4 py-3 text-zinc-500 hidden 2xl:table-cell">{{ $operators->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-zinc-800 dark:text-zinc-100 truncate" title="{{ $operator->name }}">
                                {{ $operator->name }}
                                @unless($assignment)
                                    <span class="inline-flex items-center ms-2 text-[11px] font-medium px-2 py-0.5 rounded-full bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300">
                                        {{ __('home.de_operator_status_ended') }}
                                    </span>
                                @endunless
                            </div>
                            {{-- الهاتف سطرٌ ثانٍ تحت الاسم: بيانا تعريفٍ لشخصٍ واحد، وعمودٌ مستقل كان يضيّق ما بعده --}}
                            <div class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ $operator->phone ?: '—' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-zinc-600 dark:text-zinc-300 truncate"
                                 title="{{ $assignment?->office?->name ?? __('home.de_operator_no_office') }}">
                                {{ $assignment?->office?->name ?? __('home.de_operator_no_office') }}
                            </div>
                            {{-- تاريخ الالتحاق تحت المقر: هو تاريخ بدء التسكين فيه، لا بيانَ تعريفٍ بالشخص --}}
                            @if($assignment)
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 truncate">
                                    {{ __('home.de_operator_started_on') }}: {{ $assignment->started_on?->toDateString() ?? '—' }}
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-500 truncate hidden xl:table-cell">{{ $assignment?->office?->governorate?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                                @can('data-entry.edit')
                                    <a href="{{ route('data-entry.operators.edit', $operator) }}" wire:navigate class="{{ $pillBlue }}">
                                        {{ __('home.edit') }}
                                    </a>
                                    @if($assignment)
                                        <button wire:click="askTransfer({{ $operator->id }})" class="{{ $pillGold }}">
                                            {{ __('home.de_operator_transfer') }}
                                        </button>
                                        <button wire:click="askEnd({{ $operator->id }})" class="{{ $pillNeutral }}">
                                            {{ __('home.de_operator_end') }}
                                        </button>
                                    @else
                                        {{-- الصفّ المؤرشَف: طريقه الوحيد للعودة (وتصحيح إنهاءٍ بالخطأ) --}}
                                        <button wire:click="askReassign({{ $operator->id }})" class="{{ $pillGold }}">
                                            {{ __('home.de_operator_reassign') }}
                                        </button>
                                    @endif
                                @endcan
                                @can('data-entry.delete')
                                    <button wire:click="askDelete({{ $operator->id }})" class="{{ $pillDanger }}">
                                        {{ __('home.delete') }}
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        {{-- «لا مدخلين بعد» غير «الفلتر لم يطابق»: لكلٍّ نصُّه وزرُّه، والشرطة وحدها لا تقول أيّهما --}}
                        <td colspan="5" class="px-4 py-12 text-center">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $hasAnyOperator ? __('home.de_operators_no_match') : __('home.de_operators_empty') }}
                            </p>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">
                                {{ $hasAnyOperator ? __('home.de_operators_no_match_hint') : __('home.de_operators_empty_hint') }}
                            </p>
                            <div class="mt-4">
                                @if($hasAnyOperator)
                                    <button type="button" wire:click="resetFilters"
                                            class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                                        {{ __('home.reset_filters') }}
                                    </button>
                                @else
                                    @can('data-entry.create')
                                        <a href="{{ route('data-entry.operators.create') }}" wire:navigate
                                           class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-[#c9a847] text-[#b8962e] hover:bg-[#c9a847]/10 transition">
                                            {{ __('home.de_operator_add') }}
                                        </a>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $operators->links() }}</div>

    {{-- مودال النقل --}}
    <div x-show="$wire.showTransfer" x-transition.opacity @click.self="$wire.showTransfer = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" style="display:none">
        <div class="w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border-2 border-[#c9a847]">
            <div class="flex items-center justify-between px-5 py-3.5 bg-[#c9a847]">
                <h3 class="text-sm font-semibold text-white">{{ __('home.de_operator_transfer_title') }}</h3>
                <button type="button" @click="$wire.showTransfer = false"
                        class="w-6 h-6 rounded-full flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 transition text-base leading-none">×</button>
            </div>
            <div class="bg-white dark:bg-zinc-900 px-5 py-6 space-y-4">
                <p class="text-sm text-zinc-600 dark:text-zinc-300">
                    <span class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $transferOperatorName }}</span>
                </p>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.de_operator_governorate') }}</label>
                    <select wire:model.live="transferGovernorate"
                            class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                        <option value="">{{ __('home.de_operator_all_governorates') }}</option>
                        @foreach($governorates as $gov)
                            <option value="{{ $gov->id }}">{{ $gov->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.de_operator_office') }}</label>
                    <select wire:model="transferOffice"
                            class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                        <option value="">—</option>
                        @foreach($transferOffices as $off)
                            <option value="{{ $off->id }}" title="{{ $off->name }}">{{ $off->short_name }}</option>
                        @endforeach
                    </select>
                    @error('transferOffice') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.de_operator_transfer_date') }}</label>
                    <input type="date" wire:model="transferDate"
                           class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('home.de_operator_transfer_hint') }}</p>
                    @error('transferDate') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-3 pt-1">
                    <button type="button" wire:click="transfer"
                            class="flex-1 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium py-2.5 rounded-lg transition">
                        {{ __('home.de_operator_transfer') }}
                    </button>
                    <button type="button" @click="$wire.showTransfer = false"
                            class="flex-1 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 text-sm font-medium py-2.5 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        {{ __('home.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- مودال إنهاء الخدمة --}}
    <div x-show="$wire.showEnd" x-transition.opacity @click.self="$wire.showEnd = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" style="display:none">
        <div class="w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border-2 border-zinc-400">
            <div class="flex items-center justify-between px-5 py-3.5 bg-zinc-600">
                <h3 class="text-sm font-semibold text-white">{{ __('home.de_operator_end_title') }}</h3>
                <button type="button" @click="$wire.showEnd = false"
                        class="w-6 h-6 rounded-full flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 transition text-base leading-none">×</button>
            </div>
            <div class="bg-white dark:bg-zinc-900 px-5 py-6 space-y-4">
                <p class="text-sm text-zinc-600 dark:text-zinc-300">
                    <span class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $endOperatorName }}</span>
                </p>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.de_operator_end_date') }}</label>
                    <input type="date" wire:model="endDate"
                           class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('home.de_operator_end_hint') }}</p>
                    @error('endDate') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-3 pt-1">
                    <button type="button" wire:click="endService"
                            class="flex-1 bg-zinc-600 hover:bg-zinc-700 text-white text-sm font-medium py-2.5 rounded-lg transition">
                        {{ __('home.de_operator_end') }}
                    </button>
                    <button type="button" @click="$wire.showEnd = false"
                            class="flex-1 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 text-sm font-medium py-2.5 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        {{ __('home.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- مودال إعادة التسكين --}}
    <div x-show="$wire.showReassign" x-transition.opacity @click.self="$wire.showReassign = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" style="display:none">
        <div class="w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border-2 border-[#c9a847]">
            <div class="flex items-center justify-between px-5 py-3.5 bg-[#c9a847]">
                <h3 class="text-sm font-semibold text-white">{{ __('home.de_operator_reassign_title') }}</h3>
                <button type="button" @click="$wire.showReassign = false"
                        class="w-6 h-6 rounded-full flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 transition text-base leading-none">×</button>
            </div>
            <div class="bg-white dark:bg-zinc-900 px-5 py-6 space-y-4">
                <p class="text-sm text-zinc-600 dark:text-zinc-300">
                    <span class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $reassignOperatorName }}</span>
                </p>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.de_operator_governorate') }}</label>
                    <select wire:model.live="reassignGovernorate"
                            class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                        <option value="">{{ __('home.de_operator_all_governorates') }}</option>
                        @foreach($governorates as $gov)
                            <option value="{{ $gov->id }}">{{ $gov->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.de_operator_office') }}</label>
                    <select wire:model="reassignOffice"
                            class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                        <option value="">—</option>
                        @foreach($reassignOffices as $off)
                            <option value="{{ $off->id }}" title="{{ $off->name }}">{{ $off->short_name }}</option>
                        @endforeach
                    </select>
                    @error('reassignOffice') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.de_operator_reassign_date') }}</label>
                    <input type="date" wire:model="reassignDate"
                           class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('home.de_operator_reassign_hint') }}</p>
                    @error('reassignDate') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-3 pt-1">
                    <button type="button" wire:click="reassign"
                            class="flex-1 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium py-2.5 rounded-lg transition">
                        {{ __('home.de_operator_reassign') }}
                    </button>
                    <button type="button" @click="$wire.showReassign = false"
                            class="flex-1 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 text-sm font-medium py-2.5 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        {{ __('home.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @include('livewire.partials.delete-modal')

</div>
