@php
    $inp = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]';
    $lbl = 'block text-xs text-zinc-500 dark:text-zinc-400 mb-1';
    $pageUrl = route($account->pageRoute());
    $maxFiles = \App\Models\FinanceAttachment::MAX_PER_ORDER;
@endphp

{{-- «ورقة واحدة» (طلب المستخدمة: الصناديق المتداخلة وصفحة المراجعة المنفصلة كانت تُربك):
     كارتٌ واحد — الشهر رأسه، والمبلغ وأمر الدفع متجاوران، والحفظ في تذييله —
     والتأكيد نافذةٌ فوق الفورم لا صفحةٌ تحلّ محلّه. --}}
<div class="max-w-4xl mx-auto p-6 space-y-5">

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

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">

        {{-- رأس الورقة: الشهر — النظام اختاره، فيُقرأ قبل أي حقل --}}
        <div class="flex flex-wrap items-center justify-between gap-2 px-6 py-4 bg-[#c9a847]/10 border-b border-[#c9a847]/30">
            <div>
                <p class="font-bold text-zinc-800 dark:text-zinc-100" style="font-size: 1.3rem">{{ __($record ? 'home.fin_correction_for' : 'home.fin_entry_for', ['month' => $monthLabel]) }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('home.fin_fiscal_year') }} {{ $fiscalLabel }}</p>
            </div>
            @if($record)
                <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">{{ __('home.fin_correction_badge') }}</span>
            @else
                <span class="text-xs font-medium text-[#b8962e] dark:text-[#d8b856]">{{ __('home.fin_next_month_badge') }}</span>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 max-md:divide-y md:divide-x md:divide-x-reverse divide-zinc-100 dark:divide-zinc-800">

            {{-- المبلغ --}}
            <div class="p-6 space-y-2">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.fin_amount_label') }} <span class="text-red-500">*</span></label>
                {{-- الحروف تُحذف أثناء الكتابة والأرقام العربية تصير لاتينية (نمط رقم الموتور — ⚠️ لا x-data على حقل wire:model).
                     ⚠️ **النقطة تبقى**: حذفها يجعل «12.5» 125 صامتة — تبقى فيرفضها الخادم برسالة. --}}
                <input type="text" inputmode="numeric" dir="ltr" autocomplete="off"
                       oninput="this.value = this.value.replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[^0-9.,٬،]/g, '')"
                       wire:model.live.debounce.400ms="amount"
                       style="font-size: 1.6rem" class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-4 py-3 font-bold text-left tracking-wide bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                @if($amountWords)
                    <p class="text-sm text-[#b8962e] dark:text-[#d8b856]">{{ $amountWords }}</p>
                @else
                    <p class="text-xs text-zinc-400">{{ __('home.fin_amount_hint') }}</p>
                @endif
                @error('amount') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>

            {{-- أوامر الدفع --}}
            <div class="p-6 space-y-4">
                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('home.fin_payment_order') }} @unless($noOrder)<span class="text-red-500">*</span>@endunless
                </p>

                {{-- شهرٌ بلا حركة: مبلغه صفر وقد لا يصدر له أمر دفع (قرار المستخدمة) --}}
                @if($amountValue === 0)
                    <label class="flex items-start gap-3 cursor-pointer rounded-lg border border-zinc-200 dark:border-zinc-700 px-3.5 py-3">
                        <input type="checkbox" wire:model.live="noOrder"
                               class="mt-0.5 w-4 h-4 rounded border-zinc-300 dark:border-zinc-600 text-[#c9a847] focus:ring-[#c9a847]" />
                        <span>
                            <span class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.fin_no_order') }}</span>
                            <span class="block text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('home.fin_no_order_hint') }}</span>
                        </span>
                    </label>
                @endif
                @error('noOrder') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror

                @unless($noOrder)
                    @foreach($orders as $i => $order)
                        @php $files = $orderFiles[$i] ?? []; $saved = $kept[$i] ?? []; $count = count($files) + count($saved); @endphp
                        {{-- أحداث الرفع تصعد من الحقل إلى هنا. ⚠️ لا x-data على الحقل نفسه (يكسر wire:model)،
                             والحقل يُخفى ولا يُزال (نمط مرفقات المخازن — إشارة «انتهى الرفع») --}}
                        <div wire:key="fin-order-{{ $i }}-{{ count($orders) }}"
                             class="space-y-3 {{ $i > 0 ? 'pt-4 border-t border-zinc-100 dark:border-zinc-800' : '' }}"
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

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="{{ $lbl }}">{{ __('home.fin_payment_order_number') }}</label>
                                    {{-- أرقام فقط: العربية تصير لاتينية وغير الأرقام يُحذف أثناء الكتابة --}}
                                    <input type="text" inputmode="numeric" dir="ltr" wire:model="orders.{{ $i }}.number" autocomplete="off" class="{{ $inp }} text-left"
                                           oninput="this.value = this.value.replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[^0-9]/g, '')" />
                                    @error("orders.$i.number") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="{{ $lbl }}">{{ __('home.fin_payment_order_date') }}</label>
                                    <input type="date" wire:model="orders.{{ $i }}.issued_on" class="{{ $inp }}" />
                                    @error("orders.$i.issued_on") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            {{-- الصور مصغّرة — ليرى المُدخِل أنه رفع الورقة الصحيحة قبل القفل --}}
                            <div class="flex flex-wrap items-center gap-2">
                                {{-- التصحيح: الصور المحفوظة أولاً — إزالتها تُحذف عند حفظ التصحيح لا الآن --}}
                                @foreach($saved as $j => $id)
                                    @php $att = $keptFiles->get($id); @endphp
                                    @if($att)
                                        <span wire:key="fin-kept-{{ $i }}-{{ $id }}" class="relative w-12 h-12 shrink-0" title="{{ $att->original_name }}">
                                            <img src="{{ $att->url() }}" alt="" class="w-12 h-12 rounded-lg object-cover border border-zinc-200 dark:border-zinc-700">
                                            <span class="absolute bottom-0.5 start-0.5 min-w-4 h-4 px-1 rounded bg-black/60 text-white grid place-items-center leading-none" style="font-size: 0.65rem">{{ $j + 1 }}</span>
                                            <button type="button" wire:click="removeKept({{ $i }}, {{ $id }})"
                                                    class="absolute -top-1.5 -end-1.5 w-5 h-5 rounded-full bg-red-500 hover:bg-red-600 text-white grid place-items-center shadow leading-none transition"
                                                    style="font-size: 0.65rem" title="{{ __('home.wh_attachments_remove') }}">✕</button>
                                        </span>
                                    @endif
                                @endforeach
                                @foreach($files as $j => $file)
                                    <span wire:key="fin-file-{{ $i }}-{{ $j }}-{{ $file->getFilename() }}"
                                          class="relative w-12 h-12 shrink-0" title="{{ $file->getClientOriginalName() }}">
                                        {{-- ⚠️ الفحص باقٍ مع أن المنتقي يقبل الصور وحدها: ملفٌّ غير صورة يصل مباشرةً
                                             (الحالة تمرّ بالمتصفح) فتُسقط معاينتُه الصفحة — والحفظ يرفضه بقواعده --}}
                                        @if($file->isPreviewable())
                                            <img src="{{ $file->temporaryUrl() }}" alt="" class="w-12 h-12 rounded-lg object-cover border border-zinc-200 dark:border-zinc-700">
                                        @else
                                            <span class="w-12 h-12 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-800 block"></span>
                                        @endif
                                        {{-- رقم الصورة --}}
                                        <span class="absolute bottom-0.5 start-0.5 min-w-4 h-4 px-1 rounded bg-black/60 text-white grid place-items-center leading-none" style="font-size: 0.65rem">{{ count($saved) + $j + 1 }}</span>
                                        <button type="button" wire:click="removeFile({{ $i }}, {{ $j }})"
                                                class="absolute -top-1.5 -end-1.5 w-5 h-5 rounded-full bg-red-500 hover:bg-red-600 text-white grid place-items-center shadow leading-none transition"
                                                style="font-size: 0.65rem" title="{{ __('home.wh_attachments_remove') }}">✕</button>
                                    </span>
                                @endforeach

                                <label wire:key="fin-input-{{ $i }}" @if($count >= $maxFiles) style="display:none" @endif
                                       title="{{ __('home.fin_add_image') }}"
                                       class="cursor-pointer w-12 h-12 shrink-0 rounded-lg border-2 border-dashed border-[#c9a847] text-[#b8962e] grid place-items-center hover:bg-[#c9a847]/10 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <input type="file" class="hidden" multiple wire:model="picked.{{ $i }}" accept="image/jpeg,image/png,image/webp" />
                                </label>
                            </div>

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

                    <div class="flex flex-wrap items-center justify-between gap-2">
                        @if(count($orders) < \App\Models\FinancePaymentOrder::MAX_PER_MONTH)
                            <button type="button" wire:click="addOrder"
                                    class="text-xs font-medium text-[#b8962e] hover:text-[#c9a847] transition">
                                + {{ __('home.fin_payment_order_add') }}
                            </button>
                        @endif
                        <p class="text-[11px] text-zinc-400">{{ __('home.fin_images_hint', ['max' => $maxFiles]) }}</p>
                    </div>
                @endunless
            </div>
        </div>

        {{-- سبب التصحيح — إجباري ويُحفظ في السجلّ عند الشهر (قرار العميل) --}}
        @if($record)
            <div class="px-6 py-4 border-t border-zinc-100 dark:border-zinc-800 space-y-1">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.fin_reason_label') }} <span class="text-red-500">*</span></label>
                <textarea wire:model="reason" rows="2" class="{{ $inp }}" placeholder="{{ __('home.fin_reason_placeholder') }}"></textarea>
                @error('reason') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>
        @endif

        {{-- تذييل الورقة --}}
        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 bg-zinc-50 dark:bg-zinc-800/60 border-t border-zinc-200 dark:border-zinc-700">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __($record ? 'home.fin_correction_note' : 'home.fin_entry_lock_note') }}</p>
            <div class="flex items-center gap-3">
                <a href="{{ $pageUrl }}" wire:navigate class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
                    {{ __('home.cancel') }}
                </a>
                {{-- ⚠️ مقفول أثناء الرفع — وإلا جاءه «الصورة مطلوبة» وهو يراها مختارة --}}
                <button type="button" wire:click="review" wire:loading.attr="disabled" wire:target="picked,review"
                        class="bg-[#c9a847] hover:bg-[#b8962e] disabled:opacity-50 text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                    {{ __($record ? 'home.fin_correction_save' : 'home.fin_entry_save') }}
                </button>
            </div>
        </div>
    </div>

    {{-- ── نافذة التأكيد — فوق الفورم، و«رجوع للتعديل» يُغلقها والفورم كما هو ── --}}
    @if($reviewing)
        <div wire:key="fin-confirm" class="fixed inset-0 z-50 bg-black/50 grid place-items-center p-4"
             x-data @keydown.escape.window="$wire.backToForm()">
            <div class="w-full max-w-md max-h-[90vh] overflow-y-auto bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl">
                <div class="px-6 pt-6 pb-4 text-center">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __($record ? 'home.fin_correction_confirm_title' : 'home.fin_confirm_title', ['month' => $monthLabel]) }}</p>
                    @if($record && $record->amount !== $amountValue)
                        {{-- المبلغ قبل التصحيح مشطوباً — ليرى المصحِّح الفرق قبل الحفظ --}}
                        <p class="text-zinc-400 line-through mt-3" dir="ltr" style="font-size: 1.1rem">{{ number_format($record->amount) }}</p>
                    @endif
                    <p class="font-bold text-zinc-800 dark:text-zinc-100 mt-3" dir="ltr" style="font-size: 2.25rem; line-height: 2.75rem">{{ number_format($amountValue) }}</p>
                    <p class="text-sm text-[#b8962e] dark:text-[#d8b856] mt-2">{{ $amountWords }}</p>
                </div>

                <div class="px-6 pb-4 space-y-2 text-sm">
                    @if($noOrder)
                        <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800 px-3 py-2.5 text-zinc-700 dark:text-zinc-200">{{ __('home.fin_no_order') }}</div>
                    @else
                        @foreach($orders as $i => $order)
                            @php
                                $firstKept = $keptFiles->get(($kept[$i] ?? [])[0] ?? 0);
                                $first = ($orderFiles[$i] ?? [])[0] ?? null;
                                $total = count($orderFiles[$i] ?? []) + count($kept[$i] ?? []);
                            @endphp
                            <div class="flex items-center gap-3 rounded-lg bg-zinc-50 dark:bg-zinc-800 p-2">
                                @if($firstKept)
                                    <img src="{{ $firstKept->url() }}" alt="" class="w-12 h-12 rounded object-cover shrink-0">
                                @elseif($first?->isPreviewable())
                                    <img src="{{ $first->temporaryUrl() }}" alt="" class="w-12 h-12 rounded object-cover shrink-0">
                                @endif
                                <div class="min-w-0 text-zinc-800 dark:text-zinc-100">
                                    <p>{{ __('home.fin_payment_order_no', ['number' => $order['number']]) }}</p>
                                    <p class="text-xs text-zinc-500">
                                        <span dir="ltr" class="inline-block">{{ $order['issued_on'] }}</span>
                                        · {{ __('home.fin_files_count', ['count' => $total]) }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    @endif

                    {{-- تنبيهات تستحق نظرة ثانية — لا تمنع الحفظ --}}
                    @foreach($warnings as $warning)
                        <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-3 py-2 text-xs text-red-700 dark:text-red-300">{{ $warning }}</div>
                    @endforeach

                    @if($record)
                        <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800 px-3 py-2.5 text-zinc-700 dark:text-zinc-200">
                            <p class="text-xs text-zinc-500">{{ __('home.fin_reason_label') }}</p>
                            <p class="mt-0.5 whitespace-pre-line">{{ $reason }}</p>
                        </div>
                    @endif

                    <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-3 py-2 text-xs text-amber-800 dark:text-amber-200">
                        {{ __($record ? 'home.fin_correction_warning' : 'home.fin_entry_lock_warning') }}
                    </div>
                </div>

                <div class="flex gap-3 px-6 py-4 bg-zinc-50 dark:bg-zinc-800/60 border-t border-zinc-200 dark:border-zinc-700">
                    <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                            class="flex-1 bg-[#c9a847] hover:bg-[#b8962e] disabled:opacity-50 text-white text-sm font-medium py-2.5 rounded-lg transition">
                        {{ __('home.fin_entry_confirm') }}
                    </button>
                    <button type="button" wire:click="backToForm"
                            class="flex-1 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-sm py-2.5 rounded-lg transition">
                        {{ __('home.fin_entry_back_to_form') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    @endif

    {{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>
</div>
