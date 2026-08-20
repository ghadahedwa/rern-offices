{{-- أزرار التصدير المشتركة لشاشات نتائج رأي المواطن.
     الملف يخرج بنفس الفلاتر والبحث والترتيب المعروضة الآن — لا بكل البيانات.
     ⚠️ الإخفاء هنا للواجهة فقط؛ الحارس الفعلي في guardExport/guardPdf. --}}
@php
    $hasPdf      = $this->exportHasPdf();
    $hasPersonal = $this->exportHasPersonalData();
@endphp

@if($this->canExport())
<div class="flex flex-wrap items-center gap-2">
    <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
            class="inline-flex items-center gap-1.5 text-xs px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition disabled:opacity-50 whitespace-nowrap">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
        </svg>
        {{ __('home.fr_export_excel') }}
    </button>

    @if($hasPdf)
        <button type="button" wire:click="exportPdf" wire:loading.attr="disabled" wire:target="exportPdf"
                class="inline-flex items-center gap-1.5 text-xs px-3 py-2 rounded-lg border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 transition disabled:opacity-50 whitespace-nowrap">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            {{ __('home.fr_export_pdf') }}
        </button>
    @endif

    {{-- بيانات المواطن مقفولة افتراضياً: الشاشة داخل النظام، أما الملف فيخرج منه --}}
    @if($hasPersonal)
        <label class="inline-flex items-center gap-1.5 text-xs text-zinc-600 dark:text-zinc-300 cursor-pointer select-none"
               title="{{ __('home.fr_export_personal_hint') }}">
            <input type="checkbox" wire:model.live="exportPersonal"
                   class="rounded border-zinc-300 dark:border-zinc-600 text-[#c9a847] focus:ring-[#c9a847]">
            {{ __('home.fr_export_personal') }}
        </label>
    @endif

    <span wire:loading wire:target="exportExcel,exportPdf" class="text-xs text-zinc-400">
        {{ __('home.fr_export_preparing') }}
    </span>
</div>
@endif
