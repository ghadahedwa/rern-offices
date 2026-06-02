<div class="max-w-4xl mx-auto p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
            </svg>
        </div>
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.report_multi_title') }}</h1>
                <span class="px-2.5 py-0.5 text-xs font-medium rounded-full bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400">{{ __('home.report_coming_soon') }}</span>
            </div>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('home.report_multi_desc') }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 p-16 text-center space-y-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-zinc-200 dark:text-zinc-700 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v16.5c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9zm3.75 11.625a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
        </svg>
        <p class="text-base font-medium text-zinc-400 dark:text-zinc-500">هذا التقرير قيد التطوير</p>
        <p class="text-sm text-zinc-400 dark:text-zinc-500">سيتيح تصدير قائمة مقرات بناءً على محددات البحث</p>
    </div>

</div>
