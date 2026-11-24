@php
    $lbl = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1';
    $inp = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]';
@endphp

<div class="max-w-4xl mx-auto p-6 space-y-6">

    <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.freeze_page') }}</h1>

    <p class="text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed">{{ __('home.freeze_page_hint') }}</p>

    {{-- الحالة والمفتاح --}}
    <div class="rounded-xl border bg-white dark:bg-zinc-900 shadow-sm p-5 space-y-5
                {{ $active ? 'border-amber-300 dark:border-amber-700' : 'border-zinc-200 dark:border-zinc-700' }}">

        @if($active)
            <div class="flex items-start gap-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 px-4 py-3">
                <span class="mt-1 w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
                <div class="text-sm">
                    <p class="font-semibold text-amber-800 dark:text-amber-300">{{ __('home.freeze_state_on') }}</p>
                    @if($since)
                        <p class="text-amber-700 dark:text-amber-400 mt-0.5">
                            {{ __('home.freeze_since') }}
                            <span dir="ltr" class="inline-block">{{ \App\Support\LocalTime::stamp($since->updated_at) }}</span>
                            @if($since->name) — {{ $since->name }} @endif
                        </p>
                    @endif
                    <p class="text-amber-700 dark:text-amber-400 mt-0.5">{{ __('home.freeze_contact') }}: <strong>{{ \App\Support\SystemFreeze::contact() }}</strong></p>
                </div>
            </div>

            <button type="button" wire:click="deactivate"
                    class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-5 py-2.5 rounded-lg transition">
                {{ __('home.freeze_deactivate') }}
            </button>
        @else
            <div class="flex items-center gap-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-4 py-3">
                <span class="w-2.5 h-2.5 rounded-full bg-green-500 shrink-0"></span>
                <p class="text-sm font-semibold text-green-800 dark:text-green-300">{{ __('home.freeze_state_off') }}</p>
            </div>

            <div class="max-w-md">
                <label class="{{ $lbl }}" for="freeze-contact">{{ __('home.freeze_contact') }}</label>
                <input id="freeze-contact" type="text" wire:model="contact" class="{{ $inp }}">
                <p class="text-xs text-zinc-400 mt-1">{{ __('home.freeze_contact_hint') }}</p>
                @error('contact') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="button" wire:click="confirmActivate"
                    class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-5 py-2.5 rounded-lg transition">
                {{ __('home.freeze_activate') }}
            </button>
        @endif
    </div>

    {{-- السجلّ: التفعيل والفكّ والمحاولات المحجوبة --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.freeze_log') }}</h3>
        </div>
        <p class="text-xs text-zinc-400 mb-4">{{ __('home.freeze_log_hint') }}</p>

        @if($events->isEmpty())
            <p class="text-sm text-zinc-400 text-center py-6">{{ __('home.freeze_log_empty') }}</p>
        @else
            <table class="w-full text-sm sm:table-fixed">
                <thead>
                    <tr class="text-xs text-zinc-500 border-b border-zinc-200 dark:border-zinc-700">
                        <th class="text-start py-2 px-2 w-[24%]">{{ __('home.freeze_col_time') }}</th>
                        <th class="text-start py-2 px-2 w-[30%]">{{ __('home.freeze_col_user') }}</th>
                        <th class="text-start py-2 px-2 w-[28%]">{{ __('home.freeze_col_event') }}</th>
                        <th class="text-start py-2 px-2 w-[18%] max-sm:hidden">IP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($events as $event)
                        @php $blocked = str_starts_with((string) $event->event, 'blocked_'); @endphp
                        <tr class="border-b border-zinc-100 dark:border-zinc-800 align-top">
                            <td class="py-2 px-2 text-zinc-600 dark:text-zinc-300">
                                <span dir="ltr" class="inline-block">{{ \App\Support\LocalTime::stamp($event->created_at) }}</span>
                            </td>
                            <td class="py-2 px-2 text-zinc-800 dark:text-zinc-100">
                                {{ $event->causer?->name ?? '—' }}
                                @if($event->causer?->username)
                                    <span class="block text-xs text-zinc-400" dir="ltr">{{ $event->causer->username }}</span>
                                @endif
                                <span class="sm:hidden block text-xs text-zinc-400" dir="ltr">{{ $event->properties['ip'] ?? '' }}</span>
                            </td>
                            <td class="py-2 px-2">
                                <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium
                                    {{ $blocked ? 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' }}">
                                    {{ __('home.freeze_event_'.$event->event) }}
                                </span>
                            </td>
                            <td class="py-2 px-2 text-zinc-500 max-sm:hidden sm:truncate" dir="ltr" title="{{ $event->properties['user_agent'] ?? '' }}">
                                {{ $event->properties['ip'] ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- نافذة تأكيد التجميد --}}
    @if($confirming)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" wire:click.self="$set('confirming', false)">
            <div class="w-full max-w-md rounded-2xl shadow-2xl overflow-hidden bg-white dark:bg-zinc-900 border-2 border-amber-500">
                <div class="px-5 py-3.5 bg-amber-500">
                    <h3 class="text-sm font-semibold text-white">{{ __('home.freeze_confirm_title') }}</h3>
                </div>
                <div class="px-5 py-6 space-y-4">
                    <p class="text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed">{{ __('home.freeze_confirm_body') }}</p>
                    <p class="text-sm text-zinc-500">{{ __('home.freeze_contact') }}: <strong class="text-zinc-800 dark:text-zinc-100">{{ $contact }}</strong></p>
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('confirming', false)"
                                class="px-4 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            {{ __('home.cancel') }}
                        </button>
                        <button type="button" wire:click="activate"
                                class="px-4 py-2 text-sm rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-medium">
                            {{ __('home.freeze_confirm_yes') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
