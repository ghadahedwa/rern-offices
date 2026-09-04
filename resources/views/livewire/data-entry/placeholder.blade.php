{{-- سقالة شاشة مدخلي بيانات لم تُبنَ بعد — بنمط صفحات المشروع لا صفحة بيضاء --}}
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
                          d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $screenTitle }}</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 max-w-md leading-relaxed">{{ $screenNote }}</p>
        </div>
    </div>

</div>
