<div class="space-y-8"
     x-data
     @keydown.escape.window="$wire.showForm = false; $wire.showDelete = false">

    @php
        $inp = 'border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]';
    @endphp

    {{-- Stat Type Sections --}}
    @foreach($statTypes as $type)
    @php
        $rows     = $existing[$type->id];
        $isAmount = $type->value_type === 'amount';
    @endphp

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5 space-y-4">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ $type->name }}</h3>
                <span class="text-xs font-normal text-zinc-400">· {{ $type->period === 'yearly' ? 'سنوية' : 'شهرية' }}</span>
            </div>

            <button type="button" wire:click="openAdd({{ $type->id }})"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-[#c9a847] hover:bg-[#b8962e] text-white transition cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                {{ $type->period === 'yearly' ? 'إضافة سنة' : 'إضافة شهر' }}
            </button>
        </div>

        {{-- Filters --}}
        <div class="flex items-center justify-end gap-2">
            @if($type->period === 'monthly')
            <select wire:model.live="filterMonth.{{ $type->id }}" class="{{ $inp }} max-w-[140px]">
                <option value="">كل الشهور</option>
                @foreach($months as $num => $name)
                    <option value="{{ $num }}">{{ $name }}</option>
                @endforeach
            </select>
            @endif
            <select wire:model.live="filterYear.{{ $type->id }}" class="{{ $inp }} max-w-[140px]">
                <option value="">كل السنوات</option>
                @foreach($years as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
        </div>

        {{-- Table --}}
        @if($rows->total() > 0)
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <table class="w-full text-sm text-right">
                <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">السنة</th>
                        @if($type->period === 'monthly')<th class="px-4 py-2.5 font-medium">الشهر</th>@endif
                        <th class="px-4 py-2.5 font-medium">{{ $isAmount ? 'المبلغ (ج)' : 'العدد' }}</th>
                        <th class="px-4 py-2.5 font-medium w-24 text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @foreach($rows as $stat)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                        <td class="px-4 py-2.5 text-zinc-700 dark:text-zinc-300">{{ $stat->year }}</td>
                        @if($type->period === 'monthly')
                        <td class="px-4 py-2.5 text-zinc-700 dark:text-zinc-300">{{ $months[$stat->month] ?? $stat->month }}</td>
                        @endif
                        <td class="px-4 py-2.5 font-medium text-zinc-800 dark:text-zinc-100">
                            {{ $isAmount ? number_format($stat->value, 2) : number_format($stat->value) }}
                        </td>
                        <td class="px-4 py-2.5">
                            <div class="flex items-center justify-center gap-1">
                                <button type="button" wire:click="openEdit({{ $stat->id }})"
                                        class="p-1.5 text-zinc-400 hover:text-[#b8962e] hover:bg-[#c9a847]/10 rounded-lg transition cursor-pointer" title="تعديل">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button type="button" wire:click="askDelete({{ $stat->id }})"
                                        class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition cursor-pointer" title="حذف">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if($rows->hasPages())
            <div class="px-4 py-3 border-t border-zinc-100 dark:border-zinc-700">
                {{ $rows->links() }}
            </div>
            @endif
        </div>
        @else
        <p class="text-sm text-zinc-400 text-center py-6 rounded-lg border border-dashed border-zinc-200 dark:border-zinc-700">
            لا توجد بيانات مسجلة
        </p>
        @endif

    </div>
    @endforeach

    {{-- ── Add/Edit Modal ── --}}
    <div x-show="$wire.showForm"
         x-transition.opacity
         @click.self="$wire.showForm = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
         style="display:none">
        <div x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border-2 border-[#c9a847]">
            <div class="flex items-center justify-between px-5 py-3.5 bg-[#c9a847]">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-white">
                        {{ $editingId ? 'تعديل' : 'إضافة' }} — {{ $formName }}
                    </h3>
                </div>
                <button type="button" @click="$wire.showForm = false"
                        class="w-6 h-6 rounded-full flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 transition text-base leading-none">×</button>
            </div>
            <div class="bg-white dark:bg-zinc-900 px-5 py-6">
                <form wire:submit="save" class="space-y-5">
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">السنة <span class="text-red-500">*</span></label>
                        <select wire:model="formYear" class="{{ $inp }}">
                            <option value="">— السنة —</option>
                            @foreach($years as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                        @error('formYear') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    </div>

                    @if($formPeriod === 'monthly')
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">الشهر <span class="text-red-500">*</span></label>
                        <select wire:model="formMonth" class="{{ $inp }}">
                            <option value="">— الشهر —</option>
                            @foreach($months as $num => $name)
                                <option value="{{ $num }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('formMonth') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    </div>
                    @endif

                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ $formValueType === 'amount' ? 'المبلغ (ج)' : 'العدد' }} <span class="text-red-500">*</span>
                        </label>
                        <input type="number" wire:model="formValue" min="0"
                               step="{{ $formValueType === 'amount' ? '0.01' : '1' }}"
                               placeholder="{{ $formValueType === 'amount' ? '0.00' : '0' }}"
                               class="{{ $inp }}" />
                        @error('formValue') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex gap-3 pt-1">
                        <button type="submit"
                                class="flex-1 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium py-2.5 rounded-lg transition">
                            حفظ
                        </button>
                        <button type="button" @click="$wire.showForm = false"
                                class="flex-1 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 text-sm font-medium py-2.5 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                            إلغاء
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Delete Confirmation Modal ── --}}
    <div x-show="$wire.showDelete"
         x-transition.opacity
         @click.self="$wire.showDelete = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
         style="display:none">
        <div x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border-2 border-red-500">
            <div class="flex items-center justify-between px-5 py-3.5 bg-red-500">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-white">تأكيد الحذف</h3>
                </div>
                <button type="button" @click="$wire.showDelete = false"
                        class="w-6 h-6 rounded-full flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 transition text-base leading-none">×</button>
            </div>
            <div class="bg-white dark:bg-zinc-900 px-5 py-6 space-y-5">
                <p class="text-sm text-zinc-600 dark:text-zinc-300 text-center">
                    هل أنت متأكد من حذف هذا السجل؟<br>
                    <span class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $deletingLabel }}</span>
                </p>
                <div class="flex gap-3">
                    <button type="button" wire:click="deleteRow"
                            class="flex-1 bg-red-500 hover:bg-red-600 text-white text-sm font-medium py-2.5 rounded-lg transition">
                        حذف
                    </button>
                    <button type="button" @click="$wire.showDelete = false"
                            class="flex-1 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 text-sm font-medium py-2.5 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        إلغاء
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
