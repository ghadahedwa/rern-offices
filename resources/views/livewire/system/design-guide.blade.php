<div class="space-y-4">

    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.design_guide') }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ __('home.design_guide_hint') }}</p>
        </div>
        <a href="{{ route('design-guide.file') }}" target="_blank" rel="noopener"
           class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-[#c9a847] text-[#c9a847] text-sm font-medium hover:bg-[#c9a847]/10 transition">
            {{ __('home.design_guide_open') }}
            <flux:icon.arrow-top-right-on-square class="size-4" />
        </a>
    </div>

    {{-- الإطار بطول الدليل كله فيتمرّر مع الصفحة (سكرول واحد لا سكرول داخل سكرول).
         الدليل من الدومين نفسه فيُقرأ ارتفاعه، ويُعاد قياسه مع أي تغيّر داخله (فتح منسدلة · الوضع الغامق). --}}
    <iframe src="{{ route('design-guide.file') }}" title="{{ __('home.design_guide') }}" scrolling="no"
            x-data="{
                bound: false,
                attach() {
                    const doc = $el.contentDocument;
                    if (this.bound || ! doc || doc.readyState !== 'complete' || ! doc.body?.childElementCount) return;
                    this.bound = true;
                    const fit = () => { $el.style.height = doc.documentElement.scrollHeight + 'px'; };
                    fit();
                    new ResizeObserver(fit).observe(doc.body);
                    new MutationObserver(fit).observe(doc.body, { subtree: true, childList: true, attributes: true });
                },
            }"
            x-init="attach()"
            x-on:load="attach()"
            class="w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white shadow-sm overflow-hidden"
            style="height: 80vh"></iframe>
</div>
