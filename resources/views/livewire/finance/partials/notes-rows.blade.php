{{-- ملاحظات الشهر في صفٍّ تحت صفّه (المحتوى في notes-panel). يتطلب: $notes و$noteAdd و$key و$cols --}}
@php $monthNotes = $notes->get($key, collect()); $canAddNote = $noteAdd[$key] ?? false; @endphp
@if($monthNotes->isNotEmpty() || $canAddNote)
    <tr wire:key="fin-notes-{{ $key }}" x-show="notesOpen === '{{ $key }}'" style="display:none" class="bg-sky-50/50 dark:bg-sky-900/10">
        <td colspan="{{ $cols ?? 4 }}" class="px-4 py-3">
            @include('livewire.finance.partials.notes-panel')
        </td>
    </tr>
@endif
