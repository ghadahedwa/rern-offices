<div class="p-6 max-w-4xl mx-auto space-y-6" data-form-recovery>

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4 min-w-0">
            <a href="{{ route('offices.visit-reports', $type) }}" wire:navigate
               class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $title }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5 truncate" title="{{ $office->name }}">
                    {{ $office->name }}
                    &mdash; {{ $office->governorate->name ?? '—' }}
                    @if($office->officeType) &mdash; {{ $office->officeType->name }} @endif
                </p>
            </div>
        </div>

        @if($canViewOffice)
        <a href="{{ route('offices.show', $office->id) }}" wire:navigate
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 text-sm font-medium transition shrink-0">
            {{ __('home.vr_view_office') }}
        </a>
        @endif
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6 space-y-5">
        @php
            $inp = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847] disabled:bg-zinc-50 dark:disabled:bg-zinc-800/60 disabled:cursor-default';
            $lbl = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1';
            $err = 'text-red-500 text-xs mt-1';
        @endphp

        @unless($canEditReport)
            <p class="text-xs text-zinc-500 dark:text-zinc-400 rounded-lg bg-zinc-50 dark:bg-zinc-800 px-3 py-2">{{ __('home.vr_read_only') }}</p>
        @endunless

        {{-- صاحب العرض وحده يرى الخانات نفسها مقفولة — والحفظ محروس في المكوّن --}}
        <fieldset @disabled(! $canEditReport)>
            @include('livewire.offices.visit-reports.form-fields')
        </fieldset>
    </div>

    @if($canEditReport)
    <div class="flex justify-end">
        <button wire:click="save" type="button" wire:loading.attr="disabled" wire:target="save"
                class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-5 py-2.5 rounded-lg transition disabled:opacity-60">
            {{ __('home.vr_save') }}
        </button>
    </div>

    {{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>
    @endif

</div>
