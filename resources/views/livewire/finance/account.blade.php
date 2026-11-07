{{-- صفحة حساب مالي — المرحلة (أ): الصفحة وصلاحيتها قائمتان، وجدول الشهور يأتي مع بدء الإدخال --}}
<div class="p-6 max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $account->label() }}</h1>
    </div>

    <div class="rounded-xl border border-dashed border-[#c9a847]/50 bg-[#c9a847]/[0.06] dark:bg-[#c9a847]/[0.08] p-8">
        <div class="flex flex-col items-center text-center gap-3">
            <div class="w-12 h-12 rounded-full bg-[#c9a847]/15 grid place-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-[#b8962e]" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('home.fin_kind_'.$account->kind) }}</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 max-w-md leading-relaxed">{{ __('home.fin_placeholder_note') }}</p>
        </div>
    </div>

    {{-- أبنية المحاكم: العناصر التي سيُطلب كلٌّ منها في فورم الشهر — يراها صاحب الحساب قبل أن يُدخل --}}
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
