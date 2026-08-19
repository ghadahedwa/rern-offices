{{-- منتقي مستوى الرؤية الهرمي للدور --}}
<div class="flex flex-col gap-2">
    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
        {{ __('home.role_level') }} <span class="text-red-500">*</span>
    </label>
    <p class="text-xs text-zinc-400 dark:text-zinc-500 -mt-1">{{ __('home.role_level_hint') }}</p>

    {{-- الثلاثة جنب بعض: درجات على سلّم واحد، فصفٌّ واحد يقرؤها كسلّم لا كقائمة اختيارات.
         ورقم الدرجة شارة على الجانب — يقول إن ٣ أعلى من ٢ بلا أن يُكتب ذلك. --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mt-1">
        @foreach([1 => 'role_level_1', 2 => 'role_level_2', 3 => 'role_level_3'] as $value => $key)
            @php $selected = (int) $level === $value; @endphp
            <label class="group relative flex items-start gap-2.5 rounded-lg border px-3 py-2.5 cursor-pointer transition
                {{ $selected
                    ? 'border-[#c9a847] bg-[#c9a847]/[0.07] dark:bg-[#c9a847]/10 shadow-sm'
                    : 'border-zinc-300 dark:border-zinc-600 hover:border-[#c9a847]/60 hover:bg-zinc-50 dark:hover:bg-zinc-800/60' }}">

                <input type="radio" wire:model.live="level" value="{{ $value }}"
                       class="mt-0.5 w-3.5 h-3.5 accent-[#c9a847] shrink-0" />

                <div class="flex flex-col min-w-0 flex-1">
                    <span class="text-[13px] font-semibold leading-snug
                        {{ $selected ? 'text-[#8a6f1f] dark:text-[#d8b856]' : 'text-zinc-800 dark:text-zinc-100' }}">
                        {{ __('home.'.$key.'_name') }}
                    </span>
                    <span class="text-[11px] text-zinc-500 dark:text-zinc-400 leading-snug mt-0.5">
                        {{ __('home.'.$key.'_desc') }}
                    </span>
                </div>

                {{-- رقم الدرجة --}}
                <span class="shrink-0 w-5 h-5 rounded-full grid place-items-center text-[10px] font-bold transition
                    {{ $selected
                        ? 'bg-[#c9a847] text-white'
                        : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-400 dark:text-zinc-500 group-hover:bg-[#c9a847]/20 group-hover:text-[#b8962e]' }}">
                    {{ ['١', '٢', '٣'][$value - 1] }}
                </span>
            </label>
        @endforeach
    </div>
    @error('level') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
</div>
