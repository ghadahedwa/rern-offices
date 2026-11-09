{{-- صفحة حساب مالي — جدول شهور السنة المالية للحسابين ذوي المبلغ الواحد،
     والحسابان الآخران يُفتحان للإدخال في مرحلتيهما --}}
<div class="max-w-4xl mx-auto p-6 space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $account->label() }}</h1>

        @if($account->acceptsEntry())
            <div class="flex items-center gap-3">
                <label class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('home.fin_fiscal_year') }}</label>
                <select wire:model.live="year"
                        class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                    @foreach($years as $y)
                        <option value="{{ $y }}">{{ \App\Support\Finance\FinanceCalendar::fiscalYearLabel($y) }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>

    @if($account->acceptsEntry())
        {{-- ── الملخّص: إجمالي السنة + الشهر التالي ── --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sm:col-span-2 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('home.fin_year_total_label') }} {{ \App\Support\Finance\FinanceCalendar::fiscalYearLabel($year) }}
                    — {{ __('home.fin_year_range', ['from' => $yearFrom, 'to' => $yearTo]) }}
                </p>
                @if($partial)
                    <p class="text-xs text-zinc-400 mt-0.5">{{ __('home.fin_year_partial', ['count' => $monthCount, 'from' => $yearFrom]) }}</p>
                @endif
                <p class="font-bold text-zinc-800 dark:text-zinc-100 mt-1" dir="ltr" style="text-align: right; font-size: 2rem; line-height: 2.5rem">{{ number_format($total) }}</p>
                @if($total > 0)
                    <p class="text-xs text-[#b8962e] dark:text-[#d8b856] mt-1">{{ \App\Support\Finance\AmountInWords::pounds($total) }}</p>
                @endif
                <div class="mt-4 flex items-center gap-3 text-xs text-zinc-500 dark:text-zinc-400">
                    {{-- في صفحة RTL يمتلئ الشريط من اليمين تلقائياً --}}
                    <div class="flex-1 h-1.5 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                        <div class="h-full bg-[#c9a847] rounded-full" style="width: {{ $monthCount ? round($entered / $monthCount * 100) : 0 }}%"></div>
                    </div>
                    <span class="shrink-0">{{ __('home.fin_entered_of', ['entered' => $entered, 'count' => $monthCount]) }}</span>
                </div>
            </div>

            <div class="rounded-xl border border-[#c9a847]/50 bg-[#c9a847]/10 p-5 flex flex-col justify-between gap-3">
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('home.fin_next_month_badge') }}</p>
                    <p class="font-bold text-zinc-800 dark:text-zinc-100 mt-1" style="font-size: 1.3rem">{{ \App\Support\Finance\FinanceCalendar::monthLabel($nextMonth) }}</p>
                    @unless($nextDue)
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ __('home.fin_opens_on_first') }}</p>
                    @endunless
                </div>
                @if($canEnter)
                    <a href="{{ route('finance.entry', $account->slug()) }}" wire:navigate
                       class="inline-flex items-center justify-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium py-2 rounded-lg transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ __('home.fin_enter_month') }}
                    </a>
                @endif
            </div>
        </div>

        {{-- عارض صور أوامر الدفع — يفتح فوق الصفحة، ويتنقّل بين صور الأمر الواحد --}}
        <div x-data="{
                files: [], index: 0,
                get file() { return this.files[this.index] ?? null },
                openViewer(files, index) { this.files = files; this.index = index },
                closeViewer() { this.files = []; this.index = 0 },
                step(by) { if (this.files.length > 1) this.index = (this.index + by + this.files.length) % this.files.length },
             }"
             @keydown.escape.window="closeViewer()"
             @keydown.right.window="file && step(-1)"
             @keydown.left.window="file && step(1)">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
            <table class="w-full text-sm text-right sm:table-fixed">
                <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs">
                    <tr>
                        <th class="px-4 py-3 font-medium sm:w-[24%]">{{ __('home.fin_month') }}</th>
                        <th class="px-4 py-3 font-medium sm:w-[20%]">{{ $account->isItemized() ? __('home.fin_total_label_unit') : __('home.fin_amount_label') }}</th>
                        <th class="px-4 py-3 font-medium sm:w-[32%] max-sm:hidden">{{ __('home.fin_payment_order') }}</th>
                        <th class="px-4 py-3 font-medium sm:w-[24%] max-sm:hidden">{{ __('home.fin_entered_by') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700" x-data="{ open: null, itemsOpen: null }">
                    @foreach($rows as $row)
                        @php
                            $record = $row['record'];
                            $key    = $row['month']->format('Y-m');
                            $events = $history->get($key, collect());
                        @endphp
                        @if($record)
                            <tr wire:key="fin-row-{{ $row['month']->format('Y-m') }}">
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 font-semibold text-zinc-800 dark:text-zinc-100">
                                        {{ $row['label'] }}
                                        {{-- مقفول: حُفظ فلا يُعدَّل إلا بالتصحيح --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <title>{{ __('home.fin_locked') }}</title>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                                        </svg>
                                    </span>
                                    @if($record->items->isNotEmpty())
                                        {{-- أبنية المحاكم: العناصر تُفتح تحت الشهر --}}
                                        <button type="button" @click="itemsOpen = itemsOpen === '{{ $key }}' ? null : '{{ $key }}'"
                                                class="ms-1.5 inline-flex items-center gap-1 rounded-full bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition">
                                            {{ __('home.fin_items_toggle') }}
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 transition" :class="itemsOpen === '{{ $key }}' && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                    @endif
                                    @include('livewire.finance.partials.history-badge')
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-bold text-zinc-800 dark:text-zinc-100" dir="ltr" style="font-size: 1.05rem">{{ number_format($record->amount) }}</span>
                                    {{-- على الموبايل: أمر الدفع والمُدخِل تحت المبلغ --}}
                                    <div class="sm:hidden mt-2 space-y-2">
                                        @include('livewire.finance.partials.payment-orders-cell', ['record' => $record])
                                        <p class="text-xs text-zinc-500">{{ $record->creator?->name ?? '—' }} · <span dir="ltr" class="inline-block">{{ \App\Support\LocalTime::date($record->created_at) }}</span></p>
                                        @include('livewire.finance.partials.month-actions')
                                    </div>
                                </td>
                                <td class="px-4 py-3 max-sm:hidden">
                                    @include('livewire.finance.partials.payment-orders-cell', ['record' => $record])
                                </td>
                                <td class="px-4 py-3 max-sm:hidden text-xs">
                                    <p class="text-zinc-800 dark:text-zinc-100">{{ $record->creator?->name ?? '—' }}</p>
                                    {{-- التاريخ في عزلته — وسط السطر العربي يُقلب --}}
                                    <p class="text-zinc-500 dark:text-zinc-400 mt-0.5">
                                        <span dir="ltr" class="inline-block">{{ \App\Support\LocalTime::date($record->created_at) }}</span>
                                        · {{ \App\Support\LocalTime::time($record->created_at) }}
                                    </p>
                                    @include('livewire.finance.partials.month-actions')
                                </td>
                            </tr>
                            @if($record->items->isNotEmpty())
                                {{-- العناصر في عمودين: ١–٤ ثم ٥–٨ (grid-flow-col)، والصفر باهت --}}
                                <tr wire:key="fin-items-{{ $key }}" x-show="itemsOpen === '{{ $key }}'" style="display:none" class="bg-zinc-50/70 dark:bg-zinc-800/40">
                                    <td colspan="4" class="px-4 py-3">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 sm:grid-flow-col gap-x-8 gap-y-1.5 text-xs"
                                             style="grid-template-rows: repeat({{ (int) ceil($record->items->count() / 2) }}, auto)">
                                            @foreach($record->items as $item)
                                                <div class="flex items-baseline justify-between gap-3 border-b border-dashed border-zinc-200 dark:border-zinc-700 pb-1">
                                                    <span class="text-zinc-600 dark:text-zinc-300">{{ $loop->iteration }} · {{ $item->item->name }}</span>
                                                    <span dir="ltr" class="shrink-0 {{ $item->amount === 0 ? 'text-zinc-400' : 'font-semibold text-zinc-800 dark:text-zinc-100' }}">{{ number_format($item->amount) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endif
                            @include('livewire.finance.partials.history-rows')
                        @else
                            {{-- الشهر التالي — بدأ ولم يُدخَل --}}
                            <tr wire:key="fin-row-{{ $row['month']->format('Y-m') }}" class="bg-[#c9a847]/10">
                                <td class="px-4 py-3 font-semibold text-zinc-800 dark:text-zinc-100">
                                    {{ $row['label'] }}
                                    {{-- شهرٌ حُذف وينتظر إعادة إدخاله — سجلّه يبقى ظاهراً --}}
                                    @include('livewire.finance.partials.history-badge')
                                </td>
                                <td class="px-4 py-3" colspan="3">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-xs font-medium text-[#b8962e] dark:text-[#d8b856]">{{ __('home.fin_awaiting_entry') }}</span>
                                        @if($canEnter)
                                            <a href="{{ route('finance.entry', $account->slug()) }}" wire:navigate
                                               class="inline-flex items-center gap-1 border border-[#c9a847] text-[#b8962e] hover:bg-[#c9a847]/10 text-xs font-medium px-3 py-1.5 rounded-lg transition">
                                                + {{ __('home.fin_enter_short') }}
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @include('livewire.finance.partials.history-rows')
                        @endif
                    @endforeach

                    @if($later)
                        <tr wire:key="fin-row-later" class="bg-zinc-50/60 dark:bg-zinc-800/30">
                            <td class="px-4 py-3 text-xs text-zinc-400" colspan="4">{{ __('home.fin_later_months', $later) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div x-show="file" x-transition.opacity style="display:none; z-index:9999;"
             class="fixed inset-0 flex flex-col bg-black/90">
            <div class="flex items-center gap-3 h-14 shrink-0 px-4">
                <a :href="file?.download"
                   class="flex items-center gap-2 px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-sm font-medium transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                    </svg>
                    {{ __('home.fin_download') }}
                </a>
                <p class="flex-1 min-w-0 truncate text-sm text-white/80" x-text="file?.title"></p>
                <p x-show="files.length > 1" class="shrink-0 text-sm text-white/70" dir="ltr" x-text="(index + 1) + ' / ' + files.length"></p>
            </div>

            <div class="flex-1 min-h-0 flex items-center justify-center gap-3 px-4 pb-4" @click.self="closeViewer()">
                <button type="button" x-show="files.length > 1" @click="step(-1)"
                        class="shrink-0 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white grid place-items-center transition"
                        title="{{ __('home.fin_viewer_prev') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>

                {{-- الإغلاق على الصورة نفسها، أعلاها في المنتصف (طلب المستخدمة) --}}
                <div class="relative min-w-0 flex items-center justify-center"
                     :class="file?.pdf ? 'w-full h-full max-w-[1000px]' : 'max-w-full'">
                    <template x-if="file && ! file.pdf">
                        <img :src="file.src" :alt="file.name" class="rounded-lg shadow-2xl object-contain"
                             style="max-width: 100%; max-height: calc(100vh - 90px);">
                    </template>
                    <template x-if="file && file.pdf">
                        <iframe :src="file.src" class="w-full h-full rounded-lg bg-white"></iframe>
                    </template>

                    <button type="button" @click="closeViewer()"
                            class="absolute top-3 left-1/2 -translate-x-1/2 flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-red-500/90 hover:bg-red-600 text-white text-sm font-semibold shadow-lg transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        {{ __('home.close') }}
                    </button>
                </div>

                <button type="button" x-show="files.length > 1" @click="step(1)"
                        class="shrink-0 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white grid place-items-center transition"
                        title="{{ __('home.fin_viewer_next') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>
            </div>
        </div>
        </div>

    @else
        <div class="rounded-xl border border-dashed border-[#c9a847]/50 bg-[#c9a847]/[0.06] dark:bg-[#c9a847]/[0.08] p-8">
            <div class="flex flex-col items-center text-center gap-3">
                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('home.fin_kind_'.$account->kind) }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 max-w-md leading-relaxed">{{ __('home.fin_placeholder_note') }}</p>
            </div>
        </div>
    @endif

    @include('livewire.partials.delete-modal', ['deletingReasonModel' => 'deleteReason'])

</div>
