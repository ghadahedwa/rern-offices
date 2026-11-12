{{-- محتوى ملاحظات الشهر: كل ملاحظة بكاتبها وتاريخها، والتعديل والحذف بحُرّاس الموديل (كاتبها والشهر
     مفتوح · التصحيح بعد قفله)، وخانة الإضافة لمن يملكها. يقرؤه صفّ الجدول (notes-rows) ولوحة صفحة الإجمالي.
     يتطلب: $notes و$noteAdd و$key — و$notesTitle اختياري --}}
@php $monthNotes = $notes->get($key, collect()); $canAddNote = $noteAdd[$key] ?? false; @endphp
@isset($notesTitle)
    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">{{ $notesTitle }}</p>
@endisset
<div class="space-y-2.5">
    @foreach($monthNotes as $note)
        <div wire:key="fin-note-{{ $note->id }}" class="rounded-lg bg-white dark:bg-zinc-900 border border-sky-100 dark:border-sky-900/40 px-3 py-2">
            @if($editingNoteId === $note->id)
                <textarea wire:model="editingNoteBody" rows="2" class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100"></textarea>
                @error('editingNoteBody') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                <div class="flex items-center gap-3 mt-2">
                    <button type="button" wire:click="saveNote" class="text-xs font-medium bg-[#c9a847] hover:bg-[#b8962e] text-white px-3 py-1 rounded-lg transition">{{ __('home.save') }}</button>
                    <button type="button" wire:click="cancelEditNote" class="text-xs text-zinc-500 hover:text-zinc-700">{{ __('home.cancel') }}</button>
                </div>
            @else
                <p class="text-sm text-zinc-800 dark:text-zinc-100 whitespace-pre-line">{{ $note->body }}</p>
                <div class="flex flex-wrap items-center justify-between gap-2 mt-1">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ $note->author?->name ?? '—' }}
                        · <span dir="ltr" class="inline-block">{{ \App\Support\LocalTime::date($note->created_at) }}</span>
                        {{ \App\Support\LocalTime::time($note->created_at) }}
                        @if($note->updated_at->gt($note->created_at))
                            · <span class="text-zinc-400">{{ __('home.fin_note_edited') }}</span>
                        @endif
                    </p>
                    <div class="flex items-center gap-1.5">
                        @if($note->canBeEditedBy(auth()->user()))
                            <button type="button" wire:click="startEditNote({{ $note->id }})" class="rounded-full bg-blue-50 dark:bg-blue-900/30 px-2 py-0.5 text-xs text-blue-700 dark:text-blue-300 hover:bg-blue-100 transition">{{ __('home.fin_note_edit') }}</button>
                        @endif
                        @if($note->canBeDeletedBy(auth()->user()))
                            <button type="button" wire:click="confirmDeleteNote({{ $note->id }})" class="rounded-full bg-red-50 dark:bg-red-900/30 px-2 py-0.5 text-xs text-red-600 dark:text-red-300 hover:bg-red-100 transition">{{ __('home.delete') }}</button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endforeach

    @if($canAddNote)
        <div class="flex items-start gap-2">
            <textarea wire:model="noteDrafts.{{ $key }}" rows="1" placeholder="{{ __('home.fin_note_placeholder') }}"
                      class="flex-1 border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]"></textarea>
            <button type="button" wire:click="addNote('{{ $key }}')" wire:loading.attr="disabled" wire:target="addNote"
                    class="shrink-0 border border-[#c9a847] text-[#b8962e] hover:bg-[#c9a847]/10 text-sm font-medium px-3 py-2 rounded-lg transition">{{ __('home.fin_note_add') }}</button>
        </div>
        @error("noteDrafts.$key") <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
    @endif
</div>
