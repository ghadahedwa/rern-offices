{{--
    اختيارٌ واحد من خيارات قليلة ظاهرةٍ كلها (بدل منسدلة تُخفيها) — بنمط منتقي مستوى الدور: إطارٌ يصير ذهبياً حين يُختار.
    - $model     خاصية المكوّن (live: لون المختار يُقرأ من حالة السيرفر)
    - $options   value => label
    - $selected  القيمة الحالية
    - $label     العنوان
    - $disabled  اختياري: مقفول (يبقى ظاهراً باهتاً)
    - $hint      اختياري: سطرٌ يشرح الخيارات تحتها (فتبقى أسماؤها قصيرةً على سطرٍ واحد)
--}}
@php $disabled = $disabled ?? false; @endphp
<div>
    <span class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ $label }}</span>
    <div class="grid grid-cols-2 gap-2 {{ $disabled ? 'opacity-50' : '' }}">
        @foreach($options as $value => $text)
            @php $on = (string) $selected === (string) $value; @endphp
            <label wire:key="{{ $model }}-{{ $value }}"
                   class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm transition
                    {{ $disabled ? 'cursor-not-allowed' : 'cursor-pointer' }}
                    {{ $on
                        ? 'border-[#c9a847] bg-[#c9a847]/[0.07] dark:bg-[#c9a847]/10 text-[#8a6f1f] dark:text-[#d8b856] font-semibold shadow-sm'
                        : 'border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-200 ' . ($disabled ? '' : 'hover:border-[#c9a847]/60 hover:bg-zinc-50 dark:hover:bg-zinc-800/60') }}">
                <input type="radio" wire:model.live="{{ $model }}" value="{{ $value }}" @disabled($disabled)
                       class="w-3.5 h-3.5 accent-[#c9a847] shrink-0">
                <span class="leading-snug">{{ $text }}</span>
            </label>
        @endforeach
    </div>
    @if($hint ?? null)
        <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">{{ $hint }}</p>
    @endif
</div>
