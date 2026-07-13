@php
    $inp = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]';
    $lbl = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1';
@endphp

<div class="max-w-4xl mx-auto p-6 space-y-6" data-form-recovery>

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('meetings.index') }}" wire:navigate
           class="w-8 h-8 rounded-lg border border-zinc-300 dark:border-zinc-600 flex items-center justify-center text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">
            {{ $isEditing ? __('home.edit_meeting') : __('home.add_meeting') }}
        </h1>
    </div>

    {{-- Preview banner (الحفظ غير مفعّل) --}}
    <div class="rounded-lg border border-amber-300 dark:border-amber-700/50 bg-amber-50 dark:bg-amber-900/20 px-4 py-3 text-sm text-amber-800 dark:text-amber-300 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ __('home.meeting_preview_banner') }}
    </div>

    {{-- Form --}}
    <form wire:submit="save" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-6 space-y-5">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="{{ $lbl }}">{{ __('home.meeting_date') }} <span class="text-red-500">*</span></label>
                <input type="date" wire:model="date" class="{{ $inp }}" />
                @error('date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.meeting_time') }} <span class="text-red-500">*</span></label>
                <input type="time" wire:model="time" class="{{ $inp }}" />
                @error('time') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="{{ $lbl }}">{{ __('home.meeting_subject') }} <span class="text-red-500">*</span></label>
            <input type="text" wire:model="subject" class="{{ $inp }}" />
            @error('subject') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="{{ $lbl }}">{{ __('home.meeting_location') }}</label>
            <input type="text" wire:model="location" class="{{ $inp }}" />
            @error('location') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- المعنيون بالاجتماع — كذا شخص، كل واحد باسمه وصفته (repeater) --}}
        <div>
            <label class="{{ $lbl }}">{{ __('home.meeting_attendees') }}</label>
            <div x-data="{ attendees: [{ name: '', title: '' }] }" class="space-y-2">
                <template x-for="(a, i) in attendees" :key="i">
                    <div class="flex items-center gap-2">
                        <input type="text" x-model="a.name" placeholder="{{ __('home.attendee_name') }}"
                               class="flex-1 border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                        <input type="text" x-model="a.title" placeholder="{{ __('home.attendee_title') }}"
                               class="w-40 border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                        <button type="button" @click="attendees.splice(i, 1)" x-show="attendees.length > 1"
                                class="shrink-0 w-9 h-9 flex items-center justify-center rounded-lg border border-red-200 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition">✕</button>
                    </div>
                </template>
                <button type="button" @click="attendees.push({ name: '', title: '' })"
                        class="inline-flex items-center gap-1.5 text-sm text-[#c9a847] hover:text-[#b8962e] font-medium mt-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('home.add_attendee') }}
                </button>
            </div>
        </div>

        <div>
            <label class="{{ $lbl }}">{{ __('home.meeting_result') }}</label>
            <textarea wire:model="result" rows="3" class="{{ $inp }}"></textarea>
            @error('result') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="{{ $lbl }}">{{ __('home.meeting_notes') }}</label>
            <textarea wire:model="notes" rows="3" class="{{ $inp }}"></textarea>
            @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                {{ __('home.save') }}
            </button>
            <a href="{{ route('meetings.index') }}" wire:navigate
               class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
                {{ __('home.cancel') }}
            </a>
        </div>
    </form>

    {{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>

</div>
