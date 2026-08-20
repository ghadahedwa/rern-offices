{{-- خلية التحديد في الصف. .live حتى يظهر شريط الإجراءات فور أول تحديد. --}}
@if($this->canDelete())
<td class="px-3 py-3">
    <input type="checkbox" value="{{ $rowId }}" wire:model.live="selected"
           class="w-4 h-4 rounded border-zinc-300 dark:border-zinc-600 text-[#c9a847] focus:ring-[#c9a847] cursor-pointer">
</td>
@endif
