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
        @if($canEnter)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[#c9a847]/40 bg-[#c9a847]/[0.06] px-4 py-3">
                <p class="text-sm text-zinc-700 dark:text-zinc-200">
                    {{ __('home.fin_next_month', ['month' => \App\Support\Finance\FinanceCalendar::monthLabel($nextMonth)]) }}
                </p>
                <a href="{{ route('finance.entry', $account->slug()) }}" wire:navigate
                   class="inline-flex items-center gap-2 border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 text-sm font-medium px-4 py-2 rounded-lg transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('home.fin_enter_month') }}
                </a>
            </div>
        @endif

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
                        <th class="px-4 py-3 font-medium sm:w-[22%]">{{ __('home.fin_month') }}</th>
                        <th class="px-4 py-3 font-medium sm:w-[22%]">{{ __('home.fin_amount_label') }}</th>
                        <th class="px-4 py-3 font-medium sm:w-[34%] max-sm:hidden">{{ __('home.fin_payment_orders') }}</th>
                        <th class="px-4 py-3 font-medium sm:w-[22%] max-sm:hidden">{{ __('home.fin_entered_by') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @foreach($rows as $row)
                        @php $record = $row['record']; @endphp
                        <tr wire:key="fin-row-{{ $row['month']->format('Y-m') }}" class="align-top {{ $row['isNext'] && $canEnter ? 'bg-[#c9a847]/[0.05]' : '' }}">
                            <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $row['label'] }}</td>
                            <td class="px-4 py-3">
                                @if($record)
                                    <span class="font-semibold text-zinc-800 dark:text-zinc-100" dir="ltr">{{ number_format($record->amount) }}</span>
                                    {{-- على الموبايل: أوامر الدفع والمُدخِل سطران تحت المبلغ --}}
                                    <div class="sm:hidden mt-1 space-y-0.5 text-xs text-zinc-500">
                                        @foreach($record->paymentOrders as $order)
                                            <p>
                                                {{ __('home.fin_payment_order_line', ['number' => $order->number, 'date' => $order->issued_on->format('Y-m-d')]) }}
                                                @include('livewire.finance.partials.attachment-links', ['order' => $order])
                                            </p>
                                        @endforeach
                                        <p>{{ $record->creator?->name ?? '—' }} · <span dir="ltr" class="inline-block">{{ \App\Support\LocalTime::date($record->created_at) }}</span></p>
                                    </div>
                                @elseif($row['isNext'] && $canEnter)
                                    <span class="text-xs font-medium text-[#b8962e]">{{ __('home.fin_awaiting_entry') }}</span>
                                @elseif(! $row['isDue'])
                                    <span class="text-xs text-zinc-400">{{ __('home.fin_not_due') }}</span>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 max-sm:hidden">
                                @if($record)
                                    <ul class="space-y-1.5">
                                        @foreach($record->paymentOrders as $order)
                                            <li class="text-xs text-zinc-700 dark:text-zinc-200">
                                                {{ __('home.fin_payment_order_line', ['number' => $order->number, 'date' => $order->issued_on->format('Y-m-d')]) }}
                                                @include('livewire.finance.partials.attachment-links', ['order' => $order])
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                            <td class="px-4 py-3 max-sm:hidden text-xs text-zinc-500 dark:text-zinc-400">
                                @if($record)
                                    <p class="text-zinc-700 dark:text-zinc-200">{{ $record->creator?->name ?? '—' }}</p>
                                    <p dir="ltr" class="text-right">{{ \App\Support\LocalTime::stamp($record->created_at) }}</p>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-zinc-50 dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <td class="px-4 py-3 font-semibold text-zinc-700 dark:text-zinc-200">{{ __('home.fin_year_total') }}</td>
                        <td class="px-4 py-3 font-semibold text-zinc-800 dark:text-zinc-100" dir="ltr" style="text-align: right">{{ number_format($total) }}</td>
                        <td class="max-sm:hidden"></td>
                        <td class="max-sm:hidden"></td>
                    </tr>
                </tfoot>
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
        @if($total > 0)
            <p class="text-xs text-zinc-500 dark:text-zinc-400 -mt-3">{{ \App\Support\Finance\AmountInWords::pounds($total) }}</p>
        @endif

    @else
        <div class="rounded-xl border border-dashed border-[#c9a847]/50 bg-[#c9a847]/[0.06] dark:bg-[#c9a847]/[0.08] p-8">
            <div class="flex flex-col items-center text-center gap-3">
                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('home.fin_kind_'.$account->kind) }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 max-w-md leading-relaxed">{{ __('home.fin_placeholder_note') }}</p>
            </div>
        </div>
    @endif

    {{-- أبنية المحاكم: العناصر التي سيُطلب كلٌّ منها في فورم الشهر --}}
    @if($items->isNotEmpty())
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.fin_items_title') }}</h3>
            </div>
            <ol class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm text-zinc-700 dark:text-zinc-200">
                @foreach($items as $item)
                    <li class="flex items-baseline gap-2">
                        <span class="text-xs text-zinc-400 w-5 shrink-0">{{ $loop->iteration }}</span>
                        <span>{{ $item->name }}</span>
                    </li>
                @endforeach
            </ol>
        </div>
    @endif

</div>
