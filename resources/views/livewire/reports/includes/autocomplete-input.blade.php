{{--
    حقل نصي مع autocomplete من قاعدة البيانات.
    المتغيرات:
      $field       — اسم خاصية النص في الـ component
      $label       — عنوان الحقل
      $suggestions — مجموعة نصوص الاقتراحات (محسوبة في render)
--}}
<div x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false" class="relative">
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ $label }}</label>
    <input wire:model.live.debounce.350ms="{{ $field }}" type="text" autocomplete="off"
           placeholder="{{ __('home.search') }}"
           @focus="open = true" @input="open = true"
           class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />

    @if($suggestions->isNotEmpty())
        <div x-show="open" x-transition.origin.top x-cloak
             class="absolute z-50 mt-1 w-full rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 shadow-xl ring-1 ring-black/5 max-h-52 overflow-y-auto p-1.5 space-y-0.5">
            @foreach($suggestions as $s)
                <button type="button"
                        @click="$wire.set(@js($field), @js($s)); open = false"
                        class="w-full text-right px-2 py-1.5 rounded-md hover:bg-zinc-50 dark:hover:bg-zinc-700/50 cursor-pointer text-sm text-zinc-700 dark:text-zinc-200">
                    {{ $s }}
                </button>
            @endforeach
        </div>
    @endif
</div>
