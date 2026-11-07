@php
    $inp = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]';
    $lbl = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1';
    $pageUrl = route($account->pageRoute());
    $maxFiles = \App\Models\FinanceAttachment::MAX_PER_ORDER;
@endphp

<div class="max-w-4xl mx-auto p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ $pageUrl }}" wire:navigate
           class="w-8 h-8 rounded-lg border border-zinc-300 dark:border-zinc-600 grid place-items-center text-zinc-500 hover:text-[#c9a847] hover:border-[#c9a847] transition"
           title="{{ __('home.back') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $account->label() }}</h1>
    </div>

    @if(! $monthLabel)
        {{-- الشهر التالي لم يبدأ بعد --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-8 text-center">
            <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('home.fin_entry_not_due', ['month' => $nextLabel]) }}</p>
        </div>
    @else

    {{-- الشهر أولاً وبوضوح: هو ما يُدخَل، والنظام اختاره — فيُقرأ قبل أي حقل --}}
    <div class="rounded-xl border border-[#c9a847]/40 bg-[#c9a847]/[0.06] dark:bg-[#c9a847]/[0.08] px-5 py-5 text-center space-y-4">
        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('home.fin_entry_for') }}</p>
            <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-100 mt-1">{{ $monthLabel }}</p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ __('home.fin_fiscal_year') }} {{ $fiscalLabel }}</p>
        </div>

        {{-- الخطوتان --}}
        <div class="flex items-center justify-center gap-3 text-sm">
            @foreach([1 => __('home.fin_step_data'), 2 => __('home.fin_step_review')] as $n => $stepLabel)
                @php $active = ($n === 1 && ! $reviewing) || ($n === 2 && $reviewing); $done = $n === 1 && $reviewing; @endphp
                @if($n === 2)
                    <span class="w-10 h-px bg-[#c9a847]/50"></span>
                @endif
                <span class="flex items-center gap-2 {{ $active ? 'text-[#b8962e] dark:text-[#d8b856] font-semibold' : 'text-zinc-400' }}">
                    <span class="w-6 h-6 rounded-full grid place-items-center text-xs font-bold
                        {{ $active || $done ? 'bg-[#c9a847] text-white' : 'border border-zinc-300 dark:border-zinc-600' }}">
                        {{ $done ? '✓' : $n }}
                    </span>
                    {{ $stepLabel }}
                </span>
            @endforeach
        </div>
    </div>

    @if(! $reviewing)
        {{-- ── الخطوة الأولى: البيانات ── --}}
        <div wire:key="fin-step-form" class="space-y-6">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm divide-y divide-zinc-100 dark:divide-zinc-800">

                {{-- المبلغ --}}
                <div class="p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.fin_amount_label') }} <span class="text-red-500">*</span></h3>
                    </div>
                    <input type="text" inputmode="numeric" dir="ltr" autocomplete="off"
                           wire:model.live.debounce.400ms="amount"
                           class="{{ $inp }} max-w-xs text-left text-lg font-semibold tracking-wide" />
                    @if($amountWords)
                        <p class="mt-2 text-sm text-[#b8962e] dark:text-[#d8b856]">{{ $amountWords }}</p>
                    @endif
                    <p class="mt-1 text-xs text-zinc-400">{{ __('home.fin_amount_hint') }}</p>
                    @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- أوامر الدفع: كل أمر سطرٌ واحد — رقم · تاريخ · صورة --}}
                <div class="p-5 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.fin_payment_orders') }} <span class="text-red-500">*</span></h3>
                    </div>

                    @foreach($orders as $i => $order)
                        @php $files = $orderFiles[$i] ?? []; $count = count($files); @endphp
                        {{-- أحداث الرفع تصعد من الحقل إلى هنا. ⚠️ لا x-data على الحقل نفسه (يكسر wire:model)،
                             والقائمة والحقل يُخفيان ولا يُزالان (نمط مرفقات المخازن — إشارة «انتهى الرفع») --}}
                        <div wire:key="fin-order-{{ $i }}-{{ count($orders) }}"
                             class="rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 p-4 space-y-3"
                             x-data="{ uploading: false, progress: 0, failed: false }"
                             x-on:livewire-upload-start="uploading = true; progress = 0; failed = false"
                             x-on:livewire-upload-progress="progress = $event.detail.progress"
                             x-on:livewire-upload-finish="uploading = false"
                             x-on:livewire-upload-error="uploading = false; failed = true"
                             x-on:livewire-upload-cancel="uploading = false">

                            @if(count($orders) > 1)
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-xs font-semibold text-zinc-500">{{ __('home.fin_payment_order_n', ['n' => $i + 1]) }}</p>
                                    <button type="button" wire:click="removeOrder({{ $i }})"
                                            class="text-xs text-red-500 hover:text-red-600 font-medium transition">
                                        {{ __('home.fin_payment_order_remove') }}
                                    </button>
                                </div>
                            @endif

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-start">
                                <div>
                                    <label class="{{ $lbl }}">{{ __('home.fin_payment_order_number') }}</label>
                                    <input type="text" wire:model="orders.{{ $i }}.number" autocomplete="off" class="{{ $inp }}" />
                                    @error("orders.$i.number") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="{{ $lbl }}">{{ __('home.fin_payment_order_date') }}</label>
                                    <input type="date" wire:model="orders.{{ $i }}.issued_on" class="{{ $inp }}" />
                                    @error("orders.$i.issued_on") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="{{ $lbl }}">
                                        {{ __('home.fin_payment_order_files') }}
                                        <span class="text-xs font-normal text-zinc-400">({{ __('home.wh_attachments_count', ['count' => $count, 'max' => $maxFiles]) }})</span>
                                    </label>
                                    <label wire:key="fin-input-{{ $i }}" @if($count >= $maxFiles) style="display:none" @endif
                                           class="cursor-pointer w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg border border-dashed border-[#c9a847]/60 bg-white dark:bg-zinc-800 text-[#b8962e] text-sm font-medium hover:bg-[#c9a847]/10 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        {{ $count ? __('home.wh_attachments_add_more') : __('home.wh_attachments_choose') }}
                                        <input type="file" class="hidden" multiple wire:model="picked.{{ $i }}" accept="image/jpeg,image/png,.jpg,.jpeg,.png,.pdf" />
                                    </label>
                                </div>
                            </div>

                            <ul wire:key="fin-files-{{ $i }}" @if(! $count) style="display:none" @endif class="flex flex-wrap gap-2">
                                @foreach($files as $j => $file)
                                    <li wire:key="fin-file-{{ $i }}-{{ $j }}-{{ $file->getFilename() }}"
                                        class="inline-flex items-center gap-2 max-w-full ps-3 pe-1.5 py-1 rounded-full bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-xs">
                                        <span class="truncate text-zinc-700 dark:text-zinc-200" title="{{ $file->getClientOriginalName() }}">{{ $file->getClientOriginalName() }}</span>
                                        <button type="button" wire:click="removeFile({{ $i }}, {{ $j }})"
                                                class="shrink-0 w-5 h-5 rounded-full grid place-items-center text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition"
                                                title="{{ __('home.wh_attachments_remove') }}">✕</button>
                                    </li>
                                @endforeach
                            </ul>

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
                    @endforeach

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.wh_attachments_hint', ['max' => $maxFiles]) }}</p>
                        @if(count($orders) < \App\Models\FinancePaymentOrder::MAX_PER_MONTH)
                            <button type="button" wire:click="addOrder"
                                    class="inline-flex items-center gap-1.5 text-sm font-medium text-[#b8962e] hover:text-[#c9a847] transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ __('home.fin_payment_order_add') }}
                            </button>
                        @endif
                    </div>
                </div>
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
        {{-- ── الخطوة الثانية: المراجعة ── --}}
        <div wire:key="fin-step-review" class="space-y-6">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm divide-y divide-zinc-100 dark:divide-zinc-800">
                <div class="p-5">
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.fin_amount_label') }}</p>
                    <p class="text-3xl font-bold text-zinc-800 dark:text-zinc-100" dir="ltr" style="text-align: right">{{ number_format($amountValue) }}</p>
                    <p class="text-sm text-[#b8962e] dark:text-[#d8b856] mt-1">{{ $amountWords }}</p>
                </div>
                <div class="p-5">
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-2">{{ __('home.fin_payment_orders') }}</p>
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

            {{-- تنبيهات تستحق نظرة ثانية — لا تمنع الحفظ --}}
            @if($warnings)
                <div class="rounded-xl border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 space-y-2">
                    <p class="text-sm font-semibold text-red-700 dark:text-red-300">{{ __('home.fin_warn_title') }}</p>
                    <ul class="list-disc ps-5 space-y-1 text-sm text-red-700 dark:text-red-300">
                        @foreach($warnings as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rounded-xl border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 px-4 py-3">
                <p class="text-sm font-medium text-amber-800 dark:text-amber-200">{{ __('home.fin_entry_lock_warning') }}</p>
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

    @endif

    {{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>
</div>
