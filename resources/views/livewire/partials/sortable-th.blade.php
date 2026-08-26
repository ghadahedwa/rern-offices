{{--
    رأس عمود قابل للترتيب — يحتاج: $column, $label, و(اختياري) $thClass.
    يعمل مع المكوّنات التي تستخدم App\Livewire\Concerns\WithTableSorting
    (دورة ثلاثية: تصاعدي ← تنازلي ← الترتيب الافتراضي) ومع
    App\Livewire\FeedbackResults\Concerns\WithFeedbackSorting (دورة ثنائية)،
    فكلاهما يوفّر $sortBy و$sortDir ودالة sort().
--}}
<th class="px-3 py-3 font-medium {{ $thClass ?? '' }}">
    <button type="button" wire:click="sort('{{ $column }}')"
            class="inline-flex items-center gap-1 transition hover:text-[#c9a847]
                {{ $sortBy === $column ? 'text-[#c9a847]' : '' }}">
        <span>{{ $label }}</span>
        @if($sortBy === $column)
            <svg class="w-3 h-3 shrink-0 transition-transform {{ $sortDir === 'asc' ? 'rotate-180' : '' }}"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        @else
            <svg class="w-3 h-3 shrink-0 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4M16 15l-4 4-4-4"/>
            </svg>
        @endif
    </button>
</th>
