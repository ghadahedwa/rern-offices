{{-- شريط الإجراءات الجماعية — يظهر فقط عند وجود تحديد (نمط البريد الإلكتروني).
     يتوقّع: $pageIds (معرّفات الصفحة كنصوص) و$total (إجمالي المطابق للفلتر). --}}
@php
    $bulkCount = $this->selectedCount();
    $inTrash   = $this->usesSoftDeletes() && $this->viewingTrash();
    $btn       = 'inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-md border transition';
@endphp

@if($bulkCount > 0)
    <div class="rounded-xl border border-[#c9a847] bg-[#c9a847]/10 dark:bg-[#c9a847]/15 px-4 py-3 space-y-2">
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                {{ __('home.fr_bulk_selected', ['count' => $bulkCount]) }}
            </span>

            @if($inTrash)
                {{-- الاسترجاع غير مدمّر (وقابل للتراجع بحذفٍ آخر) فينفَّذ بلا تأكيد --}}
                <button type="button" wire:click="restoreSelected"
                        class="{{ $btn }} border-emerald-300 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20">
                    {{ __('home.fr_bulk_restore') }}
                </button>
                <button type="button" wire:click="askBulkForceDelete"
                        class="{{ $btn }} border-red-300 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                    {{ __('home.fr_bulk_force_delete') }}
                </button>
            @else
                {{-- التأكيد في المودال المشترك (نفس مودال حذف المقرات)، ونصّه يتغيّر بحسب قابلية التراجع --}}
                <button type="button" wire:click="askBulkDelete"
                        class="{{ $btn }} border-red-300 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    {{ $this->usesSoftDeletes() ? __('home.fr_bulk_delete') : __('home.fr_bulk_force_delete') }}
                </button>
            @endif

            <button type="button" wire:click="clearSelection"
                    class="{{ $btn }} border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                {{ __('home.fr_bulk_clear') }}
            </button>
        </div>

        {{-- ترقية التحديد من الصفحة إلى كل نتائج الفلتر — الطريق الوحيد لحذف ما يتجاوز صفحة --}}
        @if($selectAllMatching)
            <p class="text-xs text-zinc-600 dark:text-zinc-300">
                {{ __('home.fr_bulk_all_matching_note', ['total' => $total]) }}
            </p>
        @elseif($this->pageFullySelected($pageIds) && $total > count($pageIds))
            <p class="text-xs text-zinc-600 dark:text-zinc-300">
                {{ __('home.fr_bulk_page_selected', ['count' => count($pageIds)]) }}
                <button type="button" wire:click="markAllMatching"
                        class="underline font-medium text-[#b8962e] dark:text-[#c9a847]">
                    {{ __('home.fr_bulk_select_all_matching', ['total' => $total]) }}
                </button>
            </p>
        @endif
    </div>
@endif
