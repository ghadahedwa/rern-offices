{{-- سقالة شاشة مراسلات لم تُبنَ بعد — بنمط صفحات المشروع لا صفحة بيضاء --}}
<div class="p-6 max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $screenTitle }}</h1>
    </div>

    <div class="rounded-xl border border-dashed border-[#c9a847]/50 bg-[#c9a847]/[0.06] dark:bg-[#c9a847]/[0.08] p-8">
        <div class="flex flex-col items-center text-center gap-3">
            <div class="w-12 h-12 rounded-full bg-[#c9a847]/15 grid place-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-[#b8962e]" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $screenTitle }}</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 max-w-md leading-relaxed">{{ $screenNote }}</p>
        </div>
    </div>

</div>
