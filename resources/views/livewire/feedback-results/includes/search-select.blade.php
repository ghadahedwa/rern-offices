{{--
    منسدلة باختيار واحد وبحثٍ داخلها — بشكل منسدلات «تقرير بحث متقدم»: تُفتح فتجد سطر الكتابة فوق الخيارات.
    ⚠️ البحث **في السيرفر** (خاصية $search بـArabicText) لا في المتصفح — قاعدة البحث العربي في المشروع،
       وتكرارها في JS يجعل ما يجده المستخدم هنا مخالفاً لبقية الشاشات.

      $label · $model (خاصية القيمة) · $search (خاصية البحث) · $options (id · name)
      $all (نص «الكل») · $disabled · $disabledText · $placeholder · $lbl · $inp (فئات القالب المستدعي)
--}}
@php
    $current      = (string) data_get($this, $model);
    $selectedName = $current !== '' ? $options->firstWhere('id', (int) $current)?->name : null;
    $disabled     = $disabled ?? false;
@endphp

<div x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false"
     class="relative" :class="open && 'z-50'" wire:key="search-select-{{ $model }}">
    <label class="{{ $lbl }}">{{ $label }}</label>

    <button type="button" @disabled($disabled)
            @click="open = ! open; if (open) $nextTick(() => $refs.q.focus())"
            class="{{ $inp }} flex items-center justify-between gap-2 text-right disabled:opacity-50 disabled:cursor-not-allowed">
        <span class="truncate {{ $selectedName ? '' : 'text-zinc-500 dark:text-zinc-400' }}" title="{{ $selectedName }}">
            {{ $disabled ? ($disabledText ?? $all) : ($selectedName ?? $all) }}
        </span>
        <svg class="w-4 h-4 text-zinc-400 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
        </svg>
    </button>

    {{-- style بدل x-cloak (نمط multi-picker): لا قاعدة [x-cloak] في بناء الواجهة الإدارية --}}
    <div x-show="open" x-transition.origin.top style="display:none"
         class="absolute z-50 mt-1 w-full min-w-56 rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 shadow-xl ring-1 ring-black/5">
        <div class="p-2 border-b border-zinc-100 dark:border-zinc-700">
            <input x-ref="q" type="text" wire:model.live.debounce.250ms="{{ $search }}" placeholder="{{ $placeholder }}" autocomplete="off"
                   class="w-full border border-zinc-300 dark:border-zinc-600 rounded-md px-2.5 py-1.5 text-sm bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-1 focus:ring-[#c9a847]" />
        </div>
        <ul class="max-h-64 overflow-y-auto py-1 text-sm">
            <li>
                <button type="button" wire:click="$set('{{ $model }}', '')" @click="open = false"
                        class="w-full text-right px-3 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-700 {{ $current === '' ? 'text-[#b8962e] font-medium' : 'text-zinc-500 dark:text-zinc-400' }}">
                    {{ $all }}
                </button>
            </li>
            @forelse($options as $option)
                <li wire:key="search-select-{{ $model }}-{{ $option->id }}">
                    <button type="button" wire:click="$set('{{ $model }}', '{{ $option->id }}')" @click="open = false"
                            class="w-full text-right px-3 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-700 {{ (string) $option->id === $current ? 'bg-[#c9a847]/10 text-[#b8962e] font-medium' : 'text-zinc-800 dark:text-zinc-100' }}">
                        {{ $option->name }}
                    </button>
                </li>
            @empty
                <li class="px-3 py-2 text-xs text-zinc-400">{{ __('home.fr_no_matches') }}</li>
            @endforelse
        </ul>
    </div>
</div>
