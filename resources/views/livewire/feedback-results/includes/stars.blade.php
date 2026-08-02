{{-- عرض تقييم بالنجوم — $value (1..5) أو null، و$showNumber اختياري --}}
@php
    $val = $value === null ? null : (float) $value;
@endphp

@if($val === null)
    <span class="text-xs text-zinc-400">{{ __('home.fr_not_answered') }}</span>
@else
    <span class="inline-flex items-center gap-1 whitespace-nowrap" title="{{ $val }} / 5">
        <span class="inline-flex">
            @for($i = 1; $i <= 5; $i++)
                <svg class="w-3.5 h-3.5 {{ $i <= round($val) ? 'text-[#c9a847]' : 'text-zinc-300 dark:text-zinc-600' }}"
                     fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.29 3.97a1 1 0 00.95.69h4.18c.97 0 1.37 1.24.59 1.81l-3.38 2.46a1 1 0 00-.36 1.12l1.29 3.97c.3.92-.75 1.69-1.54 1.12l-3.38-2.46a1 1 0 00-1.18 0l-3.38 2.46c-.79.57-1.84-.2-1.54-1.12l1.29-3.97a1 1 0 00-.36-1.12L2.04 9.4c-.78-.57-.38-1.81.59-1.81h4.18a1 1 0 00.95-.69l1.29-3.97z"/>
                </svg>
            @endfor
        </span>
        @if($showNumber ?? true)
            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ rtrim(rtrim(number_format($val, 2), '0'), '.') }}</span>
        @endif
    </span>
@endif
