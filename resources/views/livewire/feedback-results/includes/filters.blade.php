{{-- فلاتر مشتركة لشاشات نتائج رأي المواطن: محافظة / مقر / فترة --}}
@php
    $inp = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]';
    $lbl = 'block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1';
@endphp

<div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label class="{{ $lbl }}">{{ __('home.fr_governorate') }}</label>
            <select wire:model.live="governorate_id" class="{{ $inp }}">
                <option value="">{{ __('home.fr_all_governorates') }}</option>
                @foreach($this->governorateOptions as $gov)
                    <option value="{{ $gov->id }}">{{ $gov->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="{{ $lbl }}">{{ __('home.fr_office') }}</label>
            <select wire:model.live="office_id" class="{{ $inp }}" @disabled($governorate_id === '')>
                <option value="">{{ __('home.fr_all_offices') }}</option>
                @foreach($this->officeOptions as $office)
                    <option value="{{ $office->id }}">{{ $office->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="{{ $lbl }}">{{ __('home.fr_date_from') }}</label>
            <input type="date" wire:model.live="from" class="{{ $inp }}">
        </div>

        <div>
            <label class="{{ $lbl }}">{{ __('home.fr_date_to') }}</label>
            <input type="date" wire:model.live="to" class="{{ $inp }}">
        </div>
    </div>

    {{-- اختصارات فترة جاهزة — تغني عن اختيار تاريخين يدوياً في الحالات الشائعة --}}
    <div class="mt-4 flex flex-wrap items-center gap-2">
        @php $active = $this->activePeriod(); @endphp
        @foreach($this->periodOptions() as $period)
            <button type="button" wire:click="setPeriod('{{ $period }}')"
                    class="px-3 py-1.5 rounded-full text-xs font-medium border transition
                        {{ $active === $period
                            ? 'border-[#c9a847] bg-[#c9a847] text-white'
                            : 'border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
                {{ __('home.fr_period_'.$period) }}
            </button>
        @endforeach
    </div>

    @if($this->hasActiveFilters())
        <div class="mt-4">
            <button type="button" wire:click="resetFilters"
                    class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                {{ __('home.fr_reset_filters') }}
            </button>
        </div>
    @endif
</div>
