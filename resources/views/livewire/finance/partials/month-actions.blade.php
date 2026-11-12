{{-- تصحيح الشهر وحذفه — حبّات نصية (أزرق للتعديل · أحمر للحذف). الحذف لآخر شهرٍ مُدخَل وحده،
     والحارسان (الصلاحية · آخر شهر) في الإجراء لا هنا. يتطلب: $record و$account و$lastId --}}
@if($canCorrect || ($canDelete && $record->id === $lastId))
    <div class="flex flex-wrap items-center gap-1.5 mt-2">
        @if($canCorrect)
            <a href="{{ $correctUrl($record) }}" wire:navigate
               class="inline-flex items-center rounded-full bg-blue-50 dark:bg-blue-900/30 px-2.5 py-1 text-xs font-medium text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition">
                {{ __('home.fin_correct') }}
            </a>
        @endif
        @if($canDelete && $record->id === $lastId)
            <button type="button" wire:click="confirmDelete({{ $record->id }})"
                    class="inline-flex items-center rounded-full bg-red-50 dark:bg-red-900/30 px-2.5 py-1 text-xs font-medium text-red-600 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/50 transition">
                {{ __('home.delete') }}
            </button>
        @endif
    </div>
@endif
