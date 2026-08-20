{{-- عمود التحديد في رأس الجدول: يحدد/يلغي صفوف الصفحة الحالية دفعةً واحدة.
     wire:key يتغيّر مع الحالة ليُعاد إنشاء العنصر فيعمل x-init ويضبط الحالة الوسيطة.
     ⚠️ عمود التحديد يظهر لمن يملك الحذف وحده — والعمود يختفي فيقلّ عدد أعمدة
        الصف واحداً، فأي colspan في نفس الجدول يُحسب بـ$this->canDelete(). --}}
@if($this->canDelete())
@php
    $pageAll     = $this->pageFullySelected($pageIds);
    $pagePartial = $this->hasPartialPageSelection($pageIds);
@endphp
<th class="px-3 py-3 font-medium w-[4%]">
    <input type="checkbox"
           wire:key="bulk-page-{{ $pageAll ? 'all' : ($pagePartial ? 'some' : 'none') }}"
           wire:click="togglePage({{ json_encode($pageIds) }})"
           @checked($pageAll)
           x-data x-init="$el.indeterminate = @js($pagePartial)"
           class="w-4 h-4 rounded border-zinc-300 dark:border-zinc-600 text-[#c9a847] focus:ring-[#c9a847] cursor-pointer">
</th>
@endif
