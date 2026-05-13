
            {{-- ── Step 4: الإحصائيات ── --}}
            @php
                $months = [
                    1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
                    5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
                    9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
                ];
                $currentYear = date('Y');
                $years = range($currentYear, 2024);
            @endphp

            {{-- ══ القسم 1: معاملات المكتب (سنوية) ══ --}}
            <div class="mb-6">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">إحصائية معاملات المكتب (سنوية)</h3>
                    </div>
                    @if(count($transactionStats) < count($years))
                    <button type="button" wire:click="addTransactionStat"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-[#c9a847] hover:bg-[#b8962e] text-white transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        إضافة سنة
                    </button>
                    @endif
                </div>

                @if(count($transactionStats) > 0)
                @php
                    $selectedTxYears = collect($transactionStats)->pluck('year')->filter()->map(fn($v)=>(int)$v)->toArray();
                @endphp
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div style="max-height:208px;overflow-y:auto;">
                    <table class="w-full text-sm text-right">
                        <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs sticky top-0">
                            <tr>
                                <th class="px-4 py-2.5 font-medium">السنة</th>
                                <th class="px-4 py-2.5 font-medium">عدد المعاملات</th>
                                <th class="px-4 py-2.5 font-medium w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach($transactionStats as $index => $row)
                            @php
                                $thisTxYear = (int)($row['year'] ?? 0);
                                $otherTxYears = array_filter($selectedTxYears, fn($y) => $y !== $thisTxYear);
                            @endphp
                            <tr>
                                <td class="px-4 py-2">
                                    <select wire:model.live="transactionStats.{{ $index }}.year" class="{{ $inp }}">
                                        <option value="">— السنة —</option>
                                        @foreach($years as $y)
                                            @if(!in_array($y, $otherTxYears))
                                            <option value="{{ $y }}">{{ $y }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-2">
                                    <input type="number" wire:model="transactionStats.{{ $index }}.value" min="0" placeholder="0" class="{{ $inp }}" />
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <button type="button" wire:click="removeTransactionStat({{ $index }})"
                                            class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
                @else
                <p class="text-sm text-zinc-400 dark:text-zinc-500 text-center py-4 rounded-xl border border-dashed border-zinc-200 dark:border-zinc-700">لا توجد بيانات مسجلة</p>
                @endif
            </div>

            <div class="border-t border-zinc-100 dark:border-zinc-700 my-2"></div>

            {{-- ══ القسم 2: بيع النماذج (شهرية) ══ --}}
            <div class="mb-6">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">إحصائية بيع النماذج (شهرية)</h3>
                    </div>
                    <button type="button" wire:click="addFormSaleStat"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-[#c9a847] hover:bg-[#b8962e] text-white transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        إضافة شهر
                    </button>
                </div>

                @if(count($formSalesStats) > 0)
                @php
                    $usedFormPairs = collect($formSalesStats)->filter(fn($r)=>!empty($r['year'])&&!empty($r['month']))->map(fn($r)=>$r['year'].'-'.$r['month'])->toArray();
                @endphp
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div style="max-height:650px;overflow-y:auto;">
                    <table class="w-full text-sm text-right">
                        <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs sticky top-0">
                            <tr>
                                <th class="px-4 py-2.5 font-medium">السنة</th>
                                <th class="px-4 py-2.5 font-medium">الشهر</th>
                                <th class="px-4 py-2.5 font-medium">القيمة</th>
                                <th class="px-4 py-2.5 font-medium w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach($formSalesStats as $index => $row)
                            @php
                                $thisFormYear  = $row['year']  ?? '';
                                $thisFormMonth = $row['month'] ?? '';
                                $thisFormPair  = $thisFormYear.'-'.$thisFormMonth;
                                $otherFormPairs = array_filter($usedFormPairs, fn($p) => $p !== $thisFormPair);
                            @endphp
                            <tr>
                                <td class="px-4 py-2">
                                    <select wire:model.live="formSalesStats.{{ $index }}.year" class="{{ $inp }}">
                                        <option value="">— السنة —</option>
                                        @foreach($years as $y)
                                            <option value="{{ $y }}">{{ $y }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-2">
                                    <select wire:model.live="formSalesStats.{{ $index }}.month" class="{{ $inp }}">
                                        <option value="">— الشهر —</option>
                                        @foreach($months as $num => $name)
                                            @if(!in_array($thisFormYear.'-'.$num, $otherFormPairs))
                                            <option value="{{ $num }}">{{ $name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-2">
                                    <input type="number" wire:model="formSalesStats.{{ $index }}.value" min="0" placeholder="0" class="{{ $inp }}" />
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <button type="button" wire:click="removeFormSaleStat({{ $index }})"
                                            class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
                @else
                <p class="text-sm text-zinc-400 dark:text-zinc-500 text-center py-4 rounded-xl border border-dashed border-zinc-200 dark:border-zinc-700">لا توجد بيانات مسجلة</p>
                @endif
            </div>

            <div class="border-t border-zinc-100 dark:border-zinc-700 my-2"></div>

            {{-- ══ القسم 3: بيع الحوافظ (شهرية) ══ --}}
            <div>
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">إحصائية بيع الحوافظ (شهرية)</h3>
                    </div>
                    <button type="button" wire:click="addFolderSaleStat"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-[#c9a847] hover:bg-[#b8962e] text-white transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        إضافة شهر
                    </button>
                </div>

                @if(count($folderSalesStats) > 0)
                @php
                    $usedFolderPairs = collect($folderSalesStats)->filter(fn($r)=>!empty($r['year'])&&!empty($r['month']))->map(fn($r)=>$r['year'].'-'.$r['month'])->toArray();
                @endphp
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div style="max-height:650px;overflow-y:auto;">
                    <table class="w-full text-sm text-right">
                        <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs sticky top-0">
                            <tr>
                                <th class="px-4 py-2.5 font-medium">السنة</th>
                                <th class="px-4 py-2.5 font-medium">الشهر</th>
                                <th class="px-4 py-2.5 font-medium">القيمة</th>
                                <th class="px-4 py-2.5 font-medium w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach($folderSalesStats as $index => $row)
                            @php
                                $thisFolderYear  = $row['year']  ?? '';
                                $thisFolderMonth = $row['month'] ?? '';
                                $thisFolderPair  = $thisFolderYear.'-'.$thisFolderMonth;
                                $otherFolderPairs = array_filter($usedFolderPairs, fn($p) => $p !== $thisFolderPair);
                            @endphp
                            <tr>
                                <td class="px-4 py-2">
                                    <select wire:model.live="folderSalesStats.{{ $index }}.year" class="{{ $inp }}">
                                        <option value="">— السنة —</option>
                                        @foreach($years as $y)
                                            <option value="{{ $y }}">{{ $y }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-2">
                                    <select wire:model.live="folderSalesStats.{{ $index }}.month" class="{{ $inp }}">
                                        <option value="">— الشهر —</option>
                                        @foreach($months as $num => $name)
                                            @if(!in_array($thisFolderYear.'-'.$num, $otherFolderPairs))
                                            <option value="{{ $num }}">{{ $name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-2">
                                    <input type="number" wire:model="folderSalesStats.{{ $index }}.value" min="0" placeholder="0" class="{{ $inp }}" />
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <button type="button" wire:click="removeFolderSaleStat({{ $index }})"
                                            class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
                @else
                <p class="text-sm text-zinc-400 dark:text-zinc-500 text-center py-4 rounded-xl border border-dashed border-zinc-200 dark:border-zinc-700">لا توجد بيانات مسجلة</p>
                @endif
            </div>
