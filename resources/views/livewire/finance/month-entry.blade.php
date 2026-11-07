@php
    $inp = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]';
    $lbl = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1';
    $pageUrl = route($account->pageRoute());
@endphp

<div class="max-w-4xl mx-auto p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ $pageUrl }}" wire:navigate
               class="w-8 h-8 rounded-lg border border-zinc-300 dark:border-zinc-600 grid place-items-center text-zinc-500 hover:text-[#c9a847] hover:border-[#c9a847] transition"
               title="{{ __('home.back') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $account->label() }}</h1>
                @if($monthLabel)
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('home.fin_entry_month', ['month' => $monthLabel]) }}</p>
                @endif
            </div>
        </div>
    </div>

    @if(! $monthLabel)
        {{-- الشهر التالي لم يبدأ بعد --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-8 text-center">
            <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('home.fin_entry_not_due', ['month' => $nextLabel]) }}</p>
        </div>

    @elseif(! $reviewing)
        {{-- ── الخطوة الأولى: الفورم ── --}}
        <div wire:key="fin-step-form" class="space-y-6">

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.fin_amount') }}</h3>
                </div>
                <label class="{{ $lbl }}">{{ __('home.fin_amount_label') }} <span class="text-red-500">*</span></label>
                <input type="text" inputmode="numeric" dir="ltr" autocomplete="off"
                       wire:model.live.debounce.400ms="amount"
                       class="{{ $inp }} max-w-xs text-left font-semibold tracking-wide" />
                @if($amountWords)
                    <p class="mt-2 text-sm text-[#b8962e] dark:text-[#d8b856]">{{ $amountWords }}</p>
                @endif
                <p class="mt-1 text-xs text-zinc-400">{{ __('home.fin_amount_hint') }}</p>
                @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5 space-y-5">
                <div class="flex items-center gap-3">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.fin_payment_orders') }}</h3>
                </div>

                @foreach($orders as $i => $order)
                    @php $files = $orderFiles[$i] ?? []; $count = count($files); @endphp
                    <div wire:key="fin-order-{{ $i }}-{{ count($orders) }}"
                         class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('home.fin_payment_order_n', ['n' => $i + 1]) }}</p>
                            @if(count($orders) > 1)
                                <button type="button" wire:click="removeOrder({{ $i }})"
                                        class="text-xs text-red-500 hover:text-red-600 font-medium transition">
                                    {{ __('home.fin_payment_order_remove') }}
                                </button>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="{{ $lbl }}">{{ __('home.fin_payment_order_number') }} <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="orders.{{ $i }}.number" autocomplete="off" class="{{ $inp }}" />
                                @error("orders.$i.number") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="{{ $lbl }}">{{ __('home.fin_payment_order_date') }} <span class="text-red-500">*</span></label>
                                <input type="date" wire:model="orders.{{ $i }}.issued_on" class="{{ $inp }}" />
                                @error("orders.$i.issued_on") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- صور أمر الدفع — أحداث الرفع تصعد من الحقل إلى هنا.
                             ⚠️ لا x-data على الحقل نفسه (يكسر wire:model)، والقائمة والحقل
                             يُخفيان ولا يُزالان (نمط مرفقات المخازن — إشارة «انتهى الرفع») --}}
                        <div class="flex flex-col gap-2"
                             x-data="{ uploading: false, progress: 0, failed: false }"
                             x-on:livewire-upload-start="uploading = true; progress = 0; failed = false"
                             x-on:livewire-upload-progress="progress = $event.detail.progress"
                             x-on:livewire-upload-finish="uploading = false"
                             x-on:livewire-upload-error="uploading = false; failed = true"
                             x-on:livewire-upload-cancel="uploading = false">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('home.fin_payment_order_files') }} <span class="text-red-500">*</span>
                                <span class="text-xs font-normal text-zinc-400">({{ __('home.wh_attachments_count', ['count' => $count, 'max' => \App\Models\FinanceAttachment::MAX_PER_ORDER]) }})</span>
                            </label>

                            <ul wire:key="fin-files-{{ $i }}" @if(! $count) style="display:none" @endif
                                class="divide-y divide-zinc-100 dark:divide-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                                @foreach($files as $j => $file)
                                    <li wire:key="fin-file-{{ $i }}-{{ $j }}-{{ $file->getFilename() }}" class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                                        <span class="truncate text-zinc-700 dark:text-zinc-200" title="{{ $file->getClientOriginalName() }}">{{ $file->getClientOriginalName() }}</span>
                                        <button type="button" wire:click="removeFile({{ $i }}, {{ $j }})"
                                                class="shrink-0 text-xs text-red-500 hover:text-red-600 font-medium transition">
                                            {{ __('home.wh_attachments_remove') }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>

                            <label wire:key="fin-input-{{ $i }}" @if($count >= \App\Models\FinanceAttachment::MAX_PER_ORDER) style="display:none" @endif
                                   class="self-start cursor-pointer inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#c9a847]/10 text-[#b8962e] text-sm font-medium hover:bg-[#c9a847]/20 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ $count ? __('home.wh_attachments_add_more') : __('home.wh_attachments_choose') }}
                                <input type="file" class="hidden" multiple wire:model="picked.{{ $i }}" accept="image/jpeg,image/png,.jpg,.jpeg,.png,.pdf" />
                            </label>

                            <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.wh_attachments_hint', ['max' => \App\Models\FinanceAttachment::MAX_PER_ORDER]) }}</p>
                            <div x-show="uploading" style="display:none" class="flex items-center gap-3">
                                <div class="flex-1 h-2 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                                    <div class="h-full bg-[#c9a847] transition-all duration-200" :style="`width: ${progress}%`"></div>
                                </div>
                                <span class="shrink-0 text-xs text-zinc-500 dark:text-zinc-400">{{ __('home.uploading') }} <span x-text="progress + '٪'" dir="ltr"></span></span>
                            </div>
                            <p x-show="failed" style="display:none" class="text-red-500 text-xs">{{ __('home.wh_attachments_upload_failed') }}</p>
                            @error("orderFiles.$i") <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                            @error("orderFiles.$i.*") <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endforeach

                @if(count($orders) < \App\Models\FinancePaymentOrder::MAX_PER_MONTH)
                    <button type="button" wire:click="addOrder"
                            class="inline-flex items-center gap-2 text-sm font-medium text-[#b8962e] hover:text-[#c9a847] transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ __('home.fin_payment_order_add') }}
                    </button>
                @endif
            </div>

            <div class="flex items-center gap-3">
                {{-- ⚠️ مقفول أثناء الرفع — وإلا جاءه «الصورة مطلوبة» وهو يراها مختارة --}}
                <button type="button" wire:click="review" wire:loading.attr="disabled" wire:target="picked,review"
                        class="bg-[#c9a847] hover:bg-[#b8962e] disabled:opacity-50 text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                    {{ __('home.fin_entry_review') }}
                </button>
                <a href="{{ $pageUrl }}" wire:navigate class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
                    {{ __('home.cancel') }}
                </a>
            </div>
        </div>

    @else
        {{-- ── الخطوة الثانية: التأكيد ── --}}
        <div wire:key="fin-step-review" class="space-y-6">
            <div class="rounded-xl border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 px-4 py-3">
                <p class="text-sm font-medium text-amber-800 dark:text-amber-200">{{ __('home.fin_entry_lock_warning') }}</p>
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5 space-y-5">
                <div>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.fin_month') }}</p>
                    <p class="text-sm text-zinc-800 dark:text-zinc-100">{{ $monthLabel }}</p>
                </div>
                <div>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.fin_amount_label') }}</p>
                    <p class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100" dir="ltr" style="text-align: right">{{ number_format($amountValue) }}</p>
                    <p class="text-sm text-[#b8962e] dark:text-[#d8b856] mt-1">{{ $amountWords }}</p>
                </div>
                <div>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">{{ __('home.fin_payment_orders') }}</p>
                    <ul class="space-y-1.5">
                        @foreach($orders as $i => $order)
                            <li class="text-sm text-zinc-800 dark:text-zinc-100">
                                {{ __('home.fin_payment_order_line', ['number' => $order['number'], 'date' => $order['issued_on']]) }}
                                <span class="text-xs text-zinc-400">— {{ __('home.fin_files_count', ['count' => count($orderFiles[$i] ?? [])]) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                        class="bg-[#c9a847] hover:bg-[#b8962e] disabled:opacity-50 text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                    {{ __('home.fin_entry_confirm') }}
                </button>
                <button type="button" wire:click="backToForm"
                        class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
                    {{ __('home.fin_entry_back_to_form') }}
                </button>
            </div>
        </div>
    @endif

    {{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>
</div>
