{{-- زرّ ملاحظات الشهر بعددها — يفتح صفّها تحته. يتطلب: $notes و$noteAdd و$key --}}
@php $monthNotes = $notes->get($key, collect()); @endphp
@if($monthNotes->isNotEmpty() || ($noteAdd[$key] ?? false))
    <button type="button" @click="notesOpen = notesOpen === '{{ $key }}' ? null : '{{ $key }}'"
            title="{{ __('home.fin_notes') }}"
            class="ms-1.5 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium transition {{ $monthNotes->isNotEmpty() ? 'bg-sky-100 dark:bg-sky-900/30 text-sky-800 dark:text-sky-200 hover:bg-sky-200' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500 hover:bg-zinc-200' }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-9 6l2.5-3H18a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v14z"/>
        </svg>
        @if($monthNotes->isNotEmpty())<span>{{ $monthNotes->count() }}</span>@endif
    </button>
@endif
